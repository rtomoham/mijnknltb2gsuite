<?php

include('TournamentMatch.php');
include('LeagueMatch.php');
include_once('Player.php');

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

    $links = $this->domDoc->getElementsByTagName("a");
    foreach ($links as $item) {
      $href = $item->getAttribute("href");

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
