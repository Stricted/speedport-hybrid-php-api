<?php
require_once('config.php');
print_r($sp->getSession());
$sp->logout();
