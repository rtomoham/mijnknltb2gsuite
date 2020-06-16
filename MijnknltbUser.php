<?php
class MijnknltbUser {
  private $username;
  private $password;

  function __construct($username, $password) {
    $this->username = $username;
    $this->password = $password;
  }

  function getLogin() {
    return $this->getUsername();
  }

  function getUsername() {
    return $this->username;
  }

  function getPassword() {
    return $this->password;
  }
} ?>
