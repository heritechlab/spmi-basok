<?php

require_once "../config/config.php";

if(!isset($_SESSION['login'])){

header("Location: ../auth/login.php");

exit;

}