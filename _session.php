<?php

include "tournaments.php";

session_id("test-session");
session_start();

var_dump(unserialize($_SESSION["popup"]));