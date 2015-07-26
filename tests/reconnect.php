<?php
require_once('config.php');
print_r($sp->changeDSLStatus('offline'));
print_r($sp->reconnectLte());
print_r($sp->changeDSLStatus('online'));
$sp->logout();
