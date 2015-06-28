<?php
require_once('config.php');
print_r($sp->getData('wlan'));
$sp->logout();
