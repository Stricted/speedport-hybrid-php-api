<?php
$path = "/home/rrd/rrd/";
$password = 'your_router_password';
$url = 'http://speedport.ip/';

if (!file_exists($path.'dsl.rrd')) {
	$options = array(
	"--step", "60",
	"DS:uactual:GAUGE:120:0:100000",
	"DS:dactual:GAUGE:120:0:200000",
	"DS:uattainable:GAUGE:120:0:100000",
	"DS:dattainable:GAUGE:120:0:200000",
	"DS:uSNR:GAUGE:120:0:1000",
	"DS:dSNR:GAUGE:120:0:1000",
	"DS:uSignal:GAUGE:120:0:1000",
	"DS:dSignal:GAUGE:120:0:200000",
	"DS:uLine:GAUGE:120:0:2000",
	"DS:dLine:GAUGE:120:0:2000",
	"DS:uCRC:DERIVE:120:0:10000000",
	"DS:dCRC:DERIVE:120:0:10000000",
	"DS:uHEC:DERIVE:120:0:10000000",
	"DS:dHEC:DERIVE:120:0:10000000",
	"DS:uFEC:DERIVE:120:0:10000000",
	"DS:dFEC:DERIVE:120:0:10000000",
	"RRA:AVERAGE:0.5:1:2880",
	"RRA:MAX:0.5:1:2880",
	"RRA:AVERAGE:0.5:10:144",
	"RRA:MAX:0.5:10:144"
	);
	
	rrd_create($path.'dsl.rrd', $options);
}

if (!file_exists($path.'lteinfo.rrd')) {
	$options = array(
	"--step", "60",
	"DS:rsrp:GAUGE:120:-200:0",
	"DS:rsrq:GAUGE:120:-200:0",
	"RRA:AVERAGE:0.5:1:2880",
	"RRA:MAX:0.5:1:2880",
	"RRA:AVERAGE:0.5:10:144",
	"RRA:MAX:0.5:10:144",
	);
	
	rrd_create($path.'lteinfo.rrd', $options);
}

require_once('../SpeedportHybrid.class.php');
$sp = new SpeedportHybrid($password, $url);

$data = $sp->getData('dsl');
$options = array(time().':'.$data['Line']['uactual'].':'.$data['Line']['dactual'].':'.$data['Line']['uattainable'].':'.$data['Line']['dattainable'].':'.$data['Line']['uSNR'].':'.$data['Line']['dSNR'].':'.$data['Line']['uSignal'].':'.$data['Line']['dSignal'].':'.$data['Line']['uLine'].':'.$data['Line']['dLine'].':'.$data['Line']['uCRC'].':'.$data['Line']['dCRC'].':'.$data['Line']['uHEC'].':'.$data['Line']['dHEC'].':'.$data['Line']['uFEC'].':'.$data['Line']['dFEC']);
rrd_update($path.'dsl.rrd', $options);

$data = $sp->getData('lteinfo');
$options = array(time().':'.$data['rsrp'].':'.$data['rsrq']);
rrd_update($path.'lteinfo.rrd', $options);

$sp->logout();
