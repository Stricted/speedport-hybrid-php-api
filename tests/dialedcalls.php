<?php
require_once('config.php');
print_r($sp->getDialedCalls());
$sp->logout();
