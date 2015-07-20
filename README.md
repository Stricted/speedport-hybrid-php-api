### speedport hybrid php api

[![Build Status](https://travis-ci.org/Stricted/speedport-hybrid-php-api.svg)](https://travis-ci.org/Stricted/speedport-hybrid-php-api) [![Release](https://img.shields.io/github/release/Stricted/speedport-hybrid-php-api.svg?style=flat-square)](https://github.com/Stricted/speedport-hybrid-php-api/releases/latest) [![License](https://img.shields.io/badge/license-LGPLv3-brightgreen.svg?style=flat-square)](https://github.com/Stricted/speedport-hybrid-php-api/blob/master/LICENSE)

Access Speedport Hybrid Router through PHP

**THIS CLASS IS ONLY FOR SPEEDPORT HYBRID**

### License
---
This project is licensed under [GNU LESSER GENERAL PUBLIC LICENSE Version 3](https://github.com/Stricted/speedport-hybrid-php-api/blob/master/LICENSE).

known valid paths for getData() :
 * data/dsl.json
 * data/interfaces.json
 * data/arp.json
 * data/session.json
 * data/dhcp_client.json
 * data/dhcp_server.json
 * data/ipv6.json
 * data/dns.json
 * data/routing.json
 * data/igmp_proxy.json
 * data/igmp_snooping.json
 * data/wlan.json
 * data/module.json
 * data/memory.json
 * data/speed.json
 * data/webdav.json
 * data/bonding_client.json
 * data/bonding_tunnel.json
 * data/filterlist.json
 * data/bonding_tr181.json
 * data/letinfo.json
 * data/Connect.json
 * data/Status.json
 
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