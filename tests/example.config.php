<?php
require_once('../SpeedportHybrid.class.php');
$password = 'your_router_password';
$url = 'http://speedport.ip/';
$sp = new SpeedportHybrid($url);
$sp->login($password);