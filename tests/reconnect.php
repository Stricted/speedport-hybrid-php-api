<?php
require_once('config.php');
print_r($sp->changeConnectionStatus('offline'));
print_r($sp->reconnectLte());
print_r($sp->changeConnectionStatus('online'));
$sp->logout();
