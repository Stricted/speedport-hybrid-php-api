<?php
require_once('Data.class.php');
$password = 'your_router_password';
$url = 'http://speedport.ip/';
$sp = new Data($url);
$sp->login($password);
