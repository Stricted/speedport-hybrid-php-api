<?php
require_once('../SpeedPort.class.php');
$password = 'your_router_password';
$url = 'http://speedport.ip/';
$sp = new SpeedPort($password, $url);
