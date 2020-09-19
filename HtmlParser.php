<?php

require_once('TournamentMatch.php');
require_once('LeagueMatch.php');
require_once('Player.php');
require_once('Tournament.php');
require_once('Draw.php');
require_once('TournamentPlayer.php');

class HtmlParser {

  private static $instance = NULL;
  private $domDoc;

  private function __construct() {
    // Suppress DOM warnings
    libxml_use_internal_errors(true);
    $this->domDoc = new DOMDocument();
  }

  static function getInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new HtmlParser();
    }
    return self::$instance;
  }

  function addMatches($html, $draw, $tournament) {
    $this->domDoc->loadHTML($html);
    libxml_clear_errors();

    $tbodies = $this->domDoc->getElementsByTagName('tbody');
    foreach ($tbodies as $tbody) {
      $trs = $tbody->getElementsByTagName('tr');
      foreach ($trs as $tr) {
        $tdsParsed = 0;
        $tds = $tr->getElementsByTagName('td');
        if (15 <= $tds->count()) {
          $start = $this->getItem($tds, 1);
          $drawTitle = $this->getItem($tds, 2);
          $dateAndTime = explode(' ', $start);
          if (3 <= sizeof($dateAndTime)) {
            $start = $dateAndTime[1] . ' ' . $dateAndTime[2];
          } else {
            $start = $dateAndTime[1] . ' 00:00';
          }
          $format = "d-m-Y H:i";
          $start = DateTime::createFromFormat($format, $start);
          $start = $start->getTimeStamp();

          $summary = $this->getItem($tds, 2);
          $home = [];
          $away = [];
          if (17 == $tds->count()) {
            $home[] = $this->getTournamentPlayer($tds->item(4));
            $home[] = $this->getTournamentPlayer($tds->item(5));
            $away[] = $this->getTournamentPlayer($tds->item(8));
            $away[] = $this->getTournamentPlayer($tds->item(9));
            $score = $this->getItem($tds, 10);
            $duration = $this->getItem($tds, 14);
            $location = $this->getItem($tds, 15);
          } elseif (15 == $tds->count()) {
            $home[] = $this->getTournamentPlayer($tds->item(4));
            $away[] = $this->getTournamentPlayer($tds->item(7));
            $score = $this->getItem($tds, 8);
            $duration = $this->getItem($tds, 12);
            $location = $this->getItem($tds, 13);
          }

          if (is_null($location)) {
            $location = 'TBD';
          }

          $summary = 'toernooiwedstrijd';

          if (0 < strlen($score)) {
            $summary .= ' (uitslag: ' . $score . ')';
          }

          $match = new TournamentMatch(-1, $summary, $tournament->getId(), $start, $home, $away);
          $match->setLocation($location);
          $match->setTitle($draw->getTitle());

          if (0 < strlen($duration)) {
            $hoursAndMinutes = explode(':', $duration);
            $duration = $hoursAndMinutes[0] * 60 + $hoursAndMinutes[1];
            $duration = new DateInterval('PT' . $duration . 'M');
            $match->setDuration($duration);
          } else {
            if (0 == strcmp('00:00', date('H:i', $start))) {
              $summary .= ' (nog geen starttijd)';
              $match->setSummary($summary);
              $match->setDuration(new DateInterval('PT24H'));
            }
          }

          if (0 == strcmp($drawTitle, $draw->getTitle())) {
            $draw->addMatch($match);
          }
        }
      }
    }
  }

  function getItem($domList, $nr) {
    $item = $domList->item($nr);
    $item = $item->nodeValue;
    return $this->cleanUpString($item);
  }

  function getMatches($htmlString, $teamId) {
    /*
    * Pre:  $htmlString contains <a> tags containing league name first, then
    *       matches
    */
    $this->domDoc->loadHTML($this->cleanUpString($htmlString));
    libxml_clear_errors();

    $leagueName = NULL;
    $matches = array(); $players = array();
    $links = $this->domDoc->getElementsByTagName('a');
    foreach ($links as $match) {
      $href = $match->getAttribute('href');
      if (strpos($href, 'draw')) {
        // This is a league match
        if (is_null($leagueName)) {
          $leagueName = trim($match->firstChild->nodeValue);
        }
      } elseif (0 < strpos($href, '/player/')) {
        $playerName = $match->firstChild->nodeValue;
        $div = $match->parentNode->parentNode;
        $spans = $div->getElementsByTagName('span');
        foreach ($spans as $span) {
          $class = $span->getAttribute('class');
          if (0 == strcmp($class, 'tag-duo__title')) {
            switch ($span->parentNode->getAttribute('title')) {
              case 'Enkel': $playerRatingSingles = trim($span->nodeValue);
              break;
              case 'Dubbel':$playerRatingDoubles = trim($span->nodeValue);
              break;
            }
          }
        }
        $players[] =
          new Player($playerName, $playerRatingSingles, $playerRatingDoubles);
      }
    }
    $links = $this->domDoc->getElementsByTagName("a");
    $matchNumber = 0;
    foreach ($links as $match) {
      $class = $match->getAttribute("class");
      if ('team-match__wrapper' == $class) {
        // Found a match, let's get the details
        $url = $match->getAttribute('href');
        $timeElement = $match->getElementsByTagName('time');
        $timeElement = $timeElement[0];
        $dateTime = $timeElement->getAttribute('datetime');
        printBasicMessage("Found match starting at $dateTime");
        $start = strtotime($dateTime);
        $divs = $match->getElementsByTagName('div');
        foreach ($divs as $div) {
          $subclass = $div->getAttribute("class");
          $teamName = $div->nodeValue;
          $teamName = trim($teamName);
          $pos = strpos($subclass, 'is-team-');
          if (false !== $pos) {
            $pos = strpos($subclass, 'is-team-1');
            if (false !== $pos) {
              $team1 = $teamName;
            }
            $pos = strpos($subclass, 'is-team-2');
            if (false !== $pos) {
              $team2 = $teamName;
            }
          }
        }
        $summary = "$team1 vs $team2";
        $identifiers = explode('/', $url);
        $matchId = $identifiers[4];
        $leagueId = $identifiers[2];
        $newMatch = new LeagueMatch($matchId, $summary, $leagueName, $leagueId, $start, $team1, $team2);
        $newMatch->setPlayers($players);
        $newMatch->setMatchNumber($matchNumber++);
        $newMatch->setTeamId($teamId);
        $matches[] = $newMatch;
      }
    }
    return $matches;
  }

  function getLeaguesAndTeams($htmlString) {
    $this->domDoc->loadHTML($htmlString);
    libxml_clear_errors();

    $leaguesAndTeams = array();
    $links = $this->domDoc->getElementsByTagName("a");
    foreach ($links as $item) {
      $href = $item->getAttribute("href");

      if (strpos($href, 'league') and strpos($href, 'team')) {
        $list = explode("/", $href);
        $leaguesAndTeams[] = array($list[2], $list[4]);
      }
    }
    return $leaguesAndTeams;
  }

  function getPlayerProfileId($htmlString, $keywordPlayerProfile) {
    $playerProfileId = NULL;

    $this->domDoc->loadHTML($htmlString);

    libxml_clear_errors();

    $links = $this->domDoc->getElementsByTagName('a');
    foreach ($links as $item) {
      $href = $item->getAttribute('href');

      if (strpos($href, $keywordPlayerProfile)) {
        $playerProfileId = explode("/", $href)[2];
        break;
      }
    }

    return $playerProfileId;
  }

  function getRequestVerificationToken($htmlString, $keywordLogin) {
    $this->domDoc->loadHTML($htmlString);
    libxml_clear_errors();

    $element = $this->domDoc->getElementById($keywordLogin);
    if (is_null($element)) {
      // Did not find the login section, so return false;
      return FALSE;
    } else {
      // Ok, we found the login section, let's get the REQUEST_VERIFICATION_TOKEN
      $element = $element->firstChild;

      return $element->getAttribute("value");
    }
  }

  private function getTournamentPlayer($domElement) {
    $url = $domElement->getAttribute('href');
    $name = $domElement->nodeValue;
    $name = $this->cleanUpString($name);

    return new TournamentPlayer($name, $url);
  }

  function getTournamentPlayerUrl($html, $tournament) {
    $this->domDoc->loadHTML($html);
    libxml_clear_errors();

    $h2s = $this->domDoc->getElementsByTagName('h2');
    foreach ($h2s as $h2) {
      $h2Class = $h2->getAttribute('class');
      if (0 == strcmp('hgroup__heading truncate', $h2Class)) {
        $name = $h2->nodeValue;
        break;
      }
    }

    if (!is_null($name)) {
      $oLists = $this->domDoc->getElementsByTagName('ol');
      foreach ($oLists as $ol) {
        $olClass = $ol->getAttribute('class');
        if (0 == strcmp('match-group', $olClass)) {
          $links = $ol->getElementsByTagName('a');
          foreach ($links as $link) {
            $linkText = $link->nodeValue;
            if (0 == strcmp($linkText, $name)) {
              $url = $link->getAttribute('href');
              if (strpos($url, $tournament->getId())) {
                return $url;
              }
            }
          }
        }
      }
    }
  }

  function getTournament($html) {
    $this->domDoc->loadHTML($html);
    libxml_clear_errors();

    $links = $this->domDoc->getElementsByTagName('a');
    foreach ($links as $link) {
      $href = $link->getAttribute('href');
      $pos = strpos($href, 'sport/tournament?id=');
      if ($pos) {
        $title = $link->getAttribute('title');
        if (0 < strlen($title)) {
          // We found a tournament or league
          if (false === strpos($title, 'Competitie')) {
            // It is not a league, so must be a tournament
            $posEqualSign = strpos($href, '=');
            $id = substr(
              $href,
              $posEqualSign + 1,
              strlen($href) - ($posEqualSign) + 1);
            $tournament = new Tournament($id, $title);
            $tournaments[] = $tournament;

            // Now, let's add the draws for this tournament
            foreach ($links as $link2) {
              $href2 = $link2->getAttribute('href');
              $pos2 = strpos($href2, $tournament->getId() . '&draw=');
              if ($pos2) {
                // Found a draw for this tournament
                $posSecondEqualSign = strpos($href2, '=');
                $posSecondEqualSign =
                  strpos($href2, '=', $posSecondEqualSign + 1);
                $drawId = substr(
                  $posSecondEqualSign + 1,
                  strlen($href2) - ($posSecondEqualSign) + 1);
                $drawTitle = $link2->nodeValue;
                $draw = new Draw($drawId, $drawTitle);
                $tournament->addDraw($draw);

                $oLists = $this->domDoc->getElementsByTagName('ol');
                foreach ($oLists as $oList) {
                  $oListId = $oList->getAttribute('id');
                  $oListPos =
                    strpos($oListId, $tournament->getId() . $draw->getId());
                  if ($oListPos) {
                    $oListItems = $oList->getElementsByTagName('li');
                    foreach ($oListItems as $oListItem) {
                      $oListItemClass = $oListItem->getAttribute('class');
                      if (0 == strcmp($oListItemClass, 'match-group__item')) {
                        $divs = $oListItem->getElementsByTagName('div');
                        $divsParsed = 0;
                        foreach ($divs as $div) {
                          $divClass = $div->getAttribute('class');
                          if (0 == strcmp($divClass, 'match__header-title-main')) {
                            $summary = $div->nodeValue;
                            $summary = $this->cleanUpString($summary);
                            $divsParsed++;
                          } elseif (0 == strcmp($divClass, 'match__body')) {
                            $links = $div->getElementsByTagName('a');
                            $home = [];
                            $away = [];
                            if (3 == $links->length) {
                              $home[] = $this->getTournamentPlayer($links->item(0));
                              $away[] = $this->getTournamentPlayer($links->item(1));
                            } elseif (5 == $links->length) {
                              $home[] = $this->getTournamentPlayer($links->item(0));
                              $home[] = $this->getTournamentPlayer($links->item(1));
                              $away[] = $this->getTournamentPlayer($links->item(2));
                              $away[] = $this->getTournamentPlayer($links->item(3));
                            }
                            $divsParsed++;
                          } elseif (0 == strcmp($divClass, 'match__footer')) {
                            $spans = $div->getElementsByTagName('span');
                            $span = $spans->item(1);
                            $start = $this->cleanUpString($span->nodeValue);
                            if (strpos($start, 'om')) {
                              // Start includes a time, so match has not been played yet
                              $dateAndTime = explode(' ', $start);
                              $start = $dateAndTime[1] . ' ' . $dateAndTime[3];
                              $format = "d-m-Y H:i";
                              $start = DateTime::createFromFormat($format, $start);
                              $start = $start->getTimeStamp();

                              $span = $spans->item(3);
                              $location = $this->cleanUpString($span->nodeValue);
                              $divsParsed++;
                            }
                          }
                          if (2 < $divsParsed) {
                            $match = new TournamentMatch(-1, $summary, $tournament->getId(), $start, $home, $away);
                            $match->setLocation($location);
                            $match->setTitle($draw->getTitle());
                            $draw->addMatch($match);
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    return $tournament;
  }

  function getTournaments($html) {
    $this->domDoc->loadHTML($html);
    libxml_clear_errors();

    $tournaments = [];
    $links = $this->domDoc->getElementsByTagName('a');
    foreach ($links as $link) {
      $href = $link->getAttribute('href');
      $pos = strpos($href, 'sport/tournament?id=');
      if ($pos) {
        $title = $link->getAttribute('title');
        if (0 < strlen($title)) {
          // We found a tournament or league
          if (false === strpos($title, 'Competitie')) {
            // It is not a league, so must be a tournament
            $posEqualSign = strpos($href, '=');
            $id = substr(
              $href,
              $posEqualSign + 1,
              strlen($href) - ($posEqualSign) + 1);
            $tournament = new Tournament($id, $title);
            if (!array_key_exists($id, $tournaments)) {
              $tournaments[$id] = $tournament;
            }

            // Now, let's add the draws for this tournament
            foreach ($links as $link2) {
              $href2 = $link2->getAttribute('href');
              $pos2 = strpos($href2, $tournament->getId() . '&draw=');
              if ($pos2) {
                // Found a draw for this tournament
                $posSecondEqualSign = strpos($href2, '=');
                $posSecondEqualSign =
                  strpos($href2, '=', $posSecondEqualSign + 1);
                $drawId = substr(
                  $href2,
                  $posSecondEqualSign + 1,
                  strlen($href2) - ($posSecondEqualSign) + 1);
                $drawTitle = $link2->nodeValue;
                $draw = new Draw($drawId, $drawTitle);
                $tournament->addDraw($draw);
              }
            }
          }
        }
      }
    }
    return $tournaments;
  }

  private function cleanUpString($htmlString) {
    //Replace the newline and carriage return characters
    //using str_replace.
    // And while I'm at it, remove a large string of spaces that shows up
    // in the LeagueName
    return trim(str_replace(array("\n", "\r", '             '), '', $htmlString));
  }

  function setMatchDetails($htmlString, $match) {
    // First, replace all <br>'s with a semicolon
    $htmlString = str_replace("<br>", '; ', $htmlString);

    $this->domDoc->loadHTML($htmlString);
    libxml_clear_errors();

    $divs = $this->domDoc->getElementsByTagName('div');
    foreach ($divs as $div) {
      $class = $div->getAttribute('class');
      // Let's process an alert, if any exists
      if (0 == strcmp($class, 'alert__body-inner')) {
        $alert = trim($div->nodeValue);
        $match->setAlert($alert);
      // Or the location
      } elseif (0 == strcmp($class, 'h-card')) {
        $location = $div->nodeValue;
        $match->setLocation($this->cleanupString($location));
      // Or the scores (if already played)
      } elseif (0 == strcmp($class, 'score score--large')) {
        $spans = $div->getElementsByTagName('span');
        foreach ($spans as $span) {
          $spanClass = $span->getAttribute('class');
          if (0 == strcmp($spanClass, 'is-team-1')) {
            $match->setScoreHome($span->nodeValue);
          } elseif (0 == strcmp($spanClass, 'is-team-2')) {
            $match->setScoreAway($span->nodeValue);
          }
        }
      }
    }

    $spans = $this->domDoc->getElementsByTagName('span');
    foreach ($spans as $span) {
      $class = $span->getAttribute('class');
      if (0 == strcmp($class, 'nav-link')) {
        $svgs = $span->getElementsByTagName('svg');
        if (!is_null($svgs)) {
          foreach ($svgs as $svg) {
            $class = $svg->getAttribute('class');
            if (0 == strcmp($class, 'icon-court nav-link__prefix')) {
              $surface = $span->nodeValue;
              $surface = $this->cleanupString($surface);
              $surfaces = explode(' ', $surface);
              $surface = '';
              foreach ($surfaces as $surface1) {
                if (0 < strlen($surface1)) {
                  $surface .= $surface1 . ', ';
                }
              }
              if (2 < strlen($surface)) {
                $surface = substr($surface, 0, -2);
              } else {
                $surface = 'onbekend';
              }
              $match->setSurface($surface);
              break;
            }
          }
        }
      }
    }
  }


} ?>
