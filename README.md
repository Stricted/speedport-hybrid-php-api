### speedport hybrid php api

[![Build Status](https://travis-ci.org/Stricted/speedport-hybrid-php-api.svg)](https://travis-ci.org/Stricted/speedport-hybrid-php-api) [![Release](https://img.shields.io/github/release/Stricted/speedport-hybrid-php-api.svg?style=flat-square)](https://github.com/Stricted/speedport-hybrid-php-api/releases/latest) [![License](https://img.shields.io/badge/license-LGPLv3-brightgreen.svg?style=flat-square)](https://github.com/Stricted/speedport-hybrid-php-api/blob/master/LICENSE)

Access Speedport Hybrid Router through PHP

**THIS CLASS IS ONLY FOR SPEEDPORT HYBRID**

### License
---
This project is licensed under [GNU LESSER GENERAL PUBLIC LICENSE Version 3](https://github.com/Stricted/speedport-hybrid-php-api/blob/master/LICENSE).

known $file values for getData() :
 * dsl
 * interfaces
 * arp
 * session
 * dhcp_client
 * dhcp_server
 * ipv6
 * dns
 * routing
 * igmp_proxy
 * igmp_snooping
 * wlan
 * module
 * memory
 * speed
 * webdav
 * bonding_client
 * bonding_tunnel
 * filterlist
 * bonding_tr181
 * letinfo
 * Connect
 * WLANBasic
 * WLANAccess
 * LAN
 * NASLight
 * INetIP
 * FilterAndTime
 * Portforwarding
 * PhoneBook
 * PhoneCalls
 * SystemMessages
 * DynDNS
 * Overview
 * Status
 
PHP requirements
============= 
 * PHP >= 5.4.0
 * PHP extension `rrd` (for rrd graphs)
 * PHP extension `mcrypt`

rrdtool integration
=============

![dsl status](assets/dsl-1h.png)
![lte status](assets/lteinfo-1h.png)

See the ```rrd``` directory for sample scripts.