<?php
require_once('config.php');
print_r($sp->getData('dhcp_client'));
$sp->logout();
