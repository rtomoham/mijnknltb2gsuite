<?php

require_once('TournamentMatch.php');
require_once('LeagueMatch.php');
require_once('Player.php');
require_once('Tournament.php');
require_once('Draw.php');

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
                        foreach ($divs as $div) {
                          $divClass = $div->getAttribute('class');
                          if (0 == strcmp($divClass, 'match__header-title-main')) {
                            $summary = $div->nodeValue;
                            $summary = $this->cleanUpString($summary);
                          } elseif (0 == strcmp($divClass, 'match__body')) {
                            $links = $div->getElementsByTagName('a');
                            if (3 == $links->length) {
                              $home = $links->item(0);
                              $home = $home->nodeValue;
                              $home = $this->cleanUpString($home);
                              $away = $links->item(1);
                              $away = $away->nodeValue;
                              $away = $this->cleanUpString($away);
                            } elseif (5 == $links->length) {
                              $home1 = $links->item(0);
                              $home1 = $home1->nodeValue;
                              $home2 = $links->item(1);
                              $home2 = $home2->nodeValue;
                              $home = $home1 . ' & ' . $home2;
                              $home = $this->cleanUpString($home);
                              $away1 = $links->item(2);
                              $away1 = $away1->nodeValue;
                              $away2 = $links->item(3);
                              $away2 = $away2->nodeValue;
                              $away = $away1 . ' & ' . $away2;
                              $away = $this->cleanUpString($away);
                            }
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
              $match->setSurface($this->cleanupString($span->nodeValue));
              break;
            }
          }
        }
      }
    }
  }


} ?>
