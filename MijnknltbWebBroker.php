<?php

include 'Utils.php';
include 'HtmlParser.php';

class MijnknltbWebBroker {

  // curl handle to make all the requests to the mijnklntb website
  private $curl;
  // this string will hold the responses to the curl exec calls
  private $response;
  private const FILENAME_COOKIE = PATH . 'cookies.txt';
  private const KEYWORD_COOKIEWALL = 'CookiePurposes_0_';
  private const KEYWORD_LOGIN = 'form_login';
  private const KEYWORD_PLAYER_PROFILE = 'player-profile';
  private const KEYWORD_REQUEST_VERIFICATION_TOKEN =
  '__RequestVerificationToken';
  private const URL_PLAYER_PROFILE =
  'https://mijnknltb.toernooi.nl/player-profile/';
  private const URL_COOKIEWALL_SAVE =
  'https://mijnknltb.toernooi.nl/cookiewall/Save';
  private const URL_USER = 'https://mijnknltb.toernooi.nl/user';

  // the username and password to access the mijnklntb website
  private $username;
  private $password;

  private $htmlParser;

  function __construct($mijnknltbUser) {
    $this->username = $mijnknltbUser->getLogin();
    $this->password = $mijnknltbUser->getPassword();
    $this->curl = curl_init();
    $this->htmlParser = HtmlParser::getInstance();

    // Let's use cookies
    curl_setopt($this->curl, CURLOPT_COOKIEJAR, self::FILENAME_COOKIE);
    curl_setopt($this->curl, CURLOPT_COOKIEFILE, self::FILENAME_COOKIE);

    // CURLOPT_VERBOSE: TRUE to output verbose information. Writes output to
    // STDERR, or the file specified using CURLOPT_STDERR.
    curl_setopt($this->curl, CURLOPT_VERBOSE, true);

    $verbose = fopen('curl-output.txt', 'w+');
    curl_setopt($this->curl, CURLOPT_STDERR, $verbose);
  }

  function __destruct() {
    curl_close($this->curl);
  }

  function getResponse() {
    return $this->response;
  }

  private function makeHttpRequest($url, $isPostRequest, $payload) {

    $httpHeader = array($this->getCookieHeaderString(self::FILENAME_COOKIE));

    curl_setopt_array($this->curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      //    CURLOPT_CUSTOMREQUEST => "POST",
      // Have to use the "old" non-compliant CURLOPT_POST, due to redirect from
      // POST to GET by mijnknltb.toernooi.nl
      CURLOPT_POST => $isPostRequest,
      CURLOPT_HTTPHEADER => $httpHeader,
    ));

    if (!is_null($payload)) {
      curl_setopt($this->curl, CURLOPT_POSTFIELDS, $payload);
    }

    $this->response = curl_exec($this->curl);
  }

  private function acceptCookieWall($playerProfileId) {
    $url = self::URL_COOKIEWALL_SAVE;
    $payload = array('ReturnUrl' => '/player-profile/' . $playerProfileId);
    $this->makeHttpRequest($url, true, $payload);
  }

  private function fetchPlayerProfile($playerProfileId) {
    $url = self::URL_PLAYER_PROFILE . $playerProfileId;
    $this->makeHttpRequest($url, false, NULL);

    if (strpos($this->response, self::KEYWORD_COOKIEWALL)) {
      // We bumped into the cookie wall, so we'll have to accept it to continue
      printMessage('Facing cookiewall fetching player profile, accepting it');
      //  $response = fetchWebpage(URL_COOKIEWALL, HTTP_METHOD_POST, NULL, $payload);
      $this->acceptCookieWall($playerProfileId);
      //      $this->fetchPlayerProfile($playerProfileId);
    } else {
      printMessage("Did not face cookiewall fetching player profile");
    }
  }

  private function fetchMatches($leagueId, $teamId) {
    $url = 'mijnknltb.toernooi.nl/league/' . $leagueId . '/team/' . $teamId;
    $this->makeHttpRequest($url, false, NULL);
  }

  private function getCookieHeaderString($filename) {
    $cookieString = 'Cookie: ';

    if (file_exists($filename)) {
      $lines = file($filename);

      // iterate over lines
      foreach($lines as $line) {

        // we only care for valid cookie def lines
        if($line[0] != '# ' && substr_count($line, "\t") == 6) {

          // get tokens in an array
          $tokens = explode("\t", $line);

          // trim the tokens
          $tokens = array_map('trim', $tokens);

          // let's convert the expiration to something readable
          $tokens[4] = date('Y-m-d h:i:s', $tokens[4]);

          $extraCookieString = $tokens[5] . '=' . $tokens[6] . "; ";
          $cookieString .= $extraCookieString;
        }
      }

      if (strlen($cookieString) > 9) {
        // Remove the last "; "
        $cookieString = substr($cookieString, 0, -2);
      }
    }

    return $cookieString;
  }

  private function setMatchDetails($matches) {
    printMessage("matches: " . count($matches));
    foreach ($matches as $match) {
      $httpHeader = array($this->getCookieHeaderString(self::FILENAME_COOKIE));
      curl_setopt_array($this->curl, array(
        CURLOPT_URL => $match->getUrl(),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //    CURLOPT_CUSTOMREQUEST => "GET",
        //  Have to use the "old" non-compliant CURLOPT_POST, due to redirect from POST to GET by mijnknltb.toernooi.nl
        CURLOPT_POST => false,
        CURLOPT_HTTPHEADER => $httpHeader,
      ));

      $this->response = curl_exec($this->curl);

      $this->htmlParser->setMatchDetails($this->response, $match);
    }
  }

  function getLeaguesAndTeams($playerProfileIds) {
    $leaguesAndTeams = [];
    $i = 1; $j = count($playerProfileIds);
    foreach ($playerProfileIds as $playerProfileId) {
      printMessage("PlayerProfileId $i of $j:\t" . $playerProfileId); $i++;

      $this->fetchPlayerProfile($playerProfileId);
      $leaguesAndTeams[] = $this->htmlParser->getLeaguesAndTeams($this->response);
    }
    return $leaguesAndTeams;
  }

  function getMatches($leagueId, $teamId, $filters) {

    $matches = [];
    $this->fetchMatches($leagueId, $teamId);

    if (is_null($filters)) {
      // Let's grab all the events of this league and team combo
      printMessage('Grabbing all matches');
      $matches = array_merge($matches, $this->htmlParser->getMatches($this->response, $teamId));
    } else {
      // We have to apply the filter, so let's check if this team is in the team filters
      printMessage('Grabbing filtered matches');
      foreach ($filters as $filter) {
        if (strpos($this->response, $filter)) {
          // Yes, we hit the filter, so let's import these events
          $matches = array_merge($matches, $this->htmlParser->getMatches($this->response, $teamId));
          break;
        }
      }
    }
    // Set the location and other details for all matches
    $this->setMatchDetails($matches);
    return($matches);
  }

} ?>
