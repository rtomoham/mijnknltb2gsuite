<?php

include 'Utils.php';

class Mijnknltb {

  // curl handle to make all the requests to the mijnklntb website
  private $curl;
  // this string will hold the responses to the curl exec calls
  private $response;
  private const COOKIE_FILENAME = 'cookies.txt';
  private const KEYWORD_COOKIEWALL = 'CookiePurposes_0_';
  private const KEYWORD_LOGIN = 'form_login';
  private const KEYWORD_PLAYER_PROFILE = 'player-profile';
  private const KEYWORD_REQUEST_VERIFICATION_TOKEN =
    '__RequestVerificationToken';
  private const URL_PLAYER_PROFILE =
    'https://mijnknltb.toernooi.nl/player-profile/';

  // the username and password to access the mijnklntb website
  private $username;
  private $password;

  private $utils;
  private $mijnknltbProfileId;

  function __construct($mijnknltbUser, $mijnknltbProfileId) {
    $this->username = $mijnknltbUser->getLogin();
    $this->password = $mijnknltbUser->getPassword();
    $this->mijnknltbProfileId = $mijnknltbProfileId;
    $this->curl = curl_init();
    $this->utils = new Utils();

    // Let's use cookies
    curl_setopt($this->curl, CURLOPT_COOKIEJAR, self::COOKIE_FILENAME);
    curl_setopt($this->curl, CURLOPT_COOKIEFILE, self::COOKIE_FILENAME);

    // CURLOPT_VERBOSE: TRUE to output verbose information. Writes output to STDERR,
    // or the file specified using CURLOPT_STDERR.
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

  function acceptCookieWall() {

    $payload = array("ReturnUrl" => "/user");
    $httpHeader = array($this->utils->getCookieHeaderString(self::COOKIE_FILENAME));

    curl_setopt_array($this->curl, array(
      CURLOPT_URL => "https://mijnknltb.toernooi.nl/cookiewall/Save",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      //    CURLOPT_CUSTOMREQUEST => "POST",
      //  Have to use the "old" non-compliant CURLOPT_POST, due to redirect from POST to GET by mijnknltb.toernooi.nl
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $payload,
      //    CURLPT_HTTPHEADER => array("Cookie: ASP.NET_SessionId=<SessionId>; st=l=<xxxx>&exp=<ExpirationOrSomething>"),
      CURLOPT_HTTPHEADER => $httpHeader,
      //    CURLOPT_HTTPHEADER => array(StringForCookieWall())
    ));
    $this->response = curl_exec($this->curl);
  }

  function loginToSite($cookieHeaderString) {

    $requestVerificationToken = $this->utils->getRequestVerificationToken(
      $this->response, self::KEYWORD_LOGIN);
    $payload = array(
      self::KEYWORD_REQUEST_VERIFICATION_TOKEN => $requestVerificationToken,
      "Login" => $this->username,
      "Password" => $this->password,
      "ReturnUrl" => "/"
    );

    $httpHeader = array($this->utils->getCookieHeaderString(self::COOKIE_FILENAME));

    curl_setopt_array($this->curl, array(
      CURLOPT_URL => "https://mijnknltb.toernooi.nl/user",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      //    CURLOPT_CUSTOMREQUEST => "POST",
      //  Have to use the "old" non-compliant CURLOPT_POST, due to redirect from POST to GET by mijnknltb.toernooi.nl
      CURLOPT_POST => true,
      //    CURLOPT_POSTFIELDS => array('__RequestVerificationToken' => '<VerificationToken','Login' => '<Username>','Password' => '<password>','ReturnUrl' => '/'),
      CURLOPT_POSTFIELDS => $payload,
      //    CURLOPT_HTTPHEADER => array("Cookie: ASP.NET_SessionId=<SessionId>; st=<SessionExpirationOrSomething>; __RequestVerificationToken=<VerificationToken>; .ASPX_TOURNAMENT_WEBSITE=<SomeIdentifier>" ),
      CURLOPT_HTTPHEADER => $httpHeader,
    ));

    $this->response = curl_exec($this->curl);
  }

  private function fetchPlayerProfile() {

    $url = self::URL_PLAYER_PROFILE . $this->mijnknltbProfileId;
    $httpHeader = array($this->utils->getCookieHeaderString(self::COOKIE_FILENAME));

    curl_setopt_array($this->curl, array(
      CURLOPT_URL => $url,
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
  }

  function fetchUserPage() {

    curl_setopt_array($this->curl, array(
      CURLOPT_URL => "mijnknltb.toernooi.nl/user",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      //    CURLOPT_CUSTOMREQUEST => "GET",
      //  Have to use the "old" non-compliant CURLOPT_POST, due to redirect from POST to GET by mijnknltb.toernooi.nl
      CURLOPT_POST => false,
    ));

    $this->response = curl_exec($this->curl);
  }

  private function fetchEvents($leagueId, $teamId) {
    $httpHeader = array($this->utils->getCookieHeaderString(self::COOKIE_FILENAME));

    curl_setopt_array($this->curl, array(
      CURLOPT_URL => 'mijnknltb.toernooi.nl/league/' . $leagueId . '/team/' . $teamId,
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
  }

  private function setMatchDetails($matches) {
    foreach ($matches as $match) {
      $httpHeader = array($this->utils->getCookieHeaderString(self::COOKIE_FILENAME));

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

      $this->utils->setMatchDetails($this->response, $match);
    }
  }


  function getEvents($filters) {

    echoDebug('Trying to fetch user page');
    $this->fetchUserPage();

    if (strpos($this->response, self::KEYWORD_COOKIEWALL)) {
      // We bumped into the cookie wall, so we'll have to accept it to continue
      echo "\n ********** Facing cookiewall ********** \n";
      //  $response = fetchWebpage(URL_COOKIEWALL, HTTP_METHOD_POST, NULL, $payload);
      $this->acceptCookieWall();

    } else {
      echoDebug("Did not face cookiewall");
    }

    if (strpos($this->response, self::KEYWORD_LOGIN)) {
      //  if ($requestVerificationToken = findLogin($response)) {
      $requestVerificationToken = $this->utils->getRequestVerificationToken(
        $this->response,
        self::KEYWORD_LOGIN
      );
      // We bumped into the login page, so let's login
//      echoDebug("Logging in with self::KEYWORD_REQUEST_VERIFICATION_TOKEN: " . $requestVerificationToken);

      $this->loginToSite($requestVerificationToken);

    } else {
      // There was no login screen, guess we were already logged in or something went wrong
      echoDebug("Did not face login screen");
    }

//    echoDebug('PlayerProfileId: ' . $this->mijnknltbProfileId . "\n");

    $this->fetchPlayerProfile();

    $leaguesAndTeams = $this->utils->getLeaguesAndTeams($this->response);
    $matches = array();
    foreach ($leaguesAndTeams as list($leagueId, $teamId)) {
//      echo $leagueId . "\t" . $teamId . "\n";
      $this->fetchEvents($leagueId, $teamId);

      if (is_null($filters)) {
        // Let's grab all the events of this league and team combo
        echoDebug("Grabbing all matches");
        $matches += $this->utils->getMatches($this->response);
      } else {
        // We have to apply the filter, so let's check if this team is in the stream_get_filters
        echoDebug("Grabbing filtered matches");
        foreach ($filters as $filter) {
          if (strpos($this->response, $filter)) {
            // Yes, we hit the filter, so let's import these events
            $matches += $this->utils->getMatches($this->response);
            break;
          }
        }
      }
    }

    // Set the location for all the matches
    $this->setMatchDetails($matches);

    return($matches);
  }

  //    foreach ($matches as $event) {
  //      echo json_encode($event) . "\n";

  //    echo $this->response;
  //    echo "\n";
}
?>
