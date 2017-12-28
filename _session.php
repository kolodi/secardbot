<?php

include "tournaments.php";

session_id("popup");
session_start();

var_dump(unserialize($_SESSION["popup"]));