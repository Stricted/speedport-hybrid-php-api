<?php
require_once('../SpeedportHybrid.class.php');
/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
 */
class Data extends SpeedportHybrid {
	public function getDSL () {
		return $this->getData('data/dsl.json');
	}
	
	public function getInterfaces () {
		return $this->getData('data/interfaces.json');
	}
	
	public function getArp () {
		return $this->getData('data/arp.json');
	}
	
	public function getSession () {
		return $this->getData('data/session.json');
	}
	
	public function getDHCPClient () {
		return $this->getData('data/dhcp_client.json');
	}
	
	public function getDHCPServer () {
		return $this->getData('data/dhcp_server.json');
	}
	
	public function getIPv6 () {
		return $this->getData('data/ipv6.json');
	}
	
	public function getDNS () {
		return $this->getData('data/dns.json');
	}
	
	public function getRouting () {
		return $this->getData('data/routing.json');
	}
	
	public function getIGMPProxy () {
		return $this->getData('data/igmp_proxy.json');
	}
	
	public function getIGMPSnooping () {
		return $this->getData('data/igmp_snooping.json');
	}
	
	public function getWLAN () {
		return $this->getData('data/wlan.json');
	}
	
	public function getModule () {
		return $this->getData('data/module.json');
	}
	
	public function getMemory () {
		return $this->getData('data/memory.json');
	}
	
	public function getSpeed () {
		return $this->getData('data/speed.json');
	}
	
	public function getWebDAV () {
		return $this->getData('data/webdav.json');
	}
	
	public function getBondingClient () {
		return $this->getData('data/bonding_client.json');
	}
	
	public function getBondingTunnel () {
		return $this->getData('data/bonding_tunnel.json');
	}
	
	public function getFilterList () {
		return $this->getData('data/filterlist.json');
	}
	
	public function getBondingTR181 () {
		return $this->getData('data/bonding_tr181.json');
	}
	
	public function getLTEInfo () {
		return $this->getData('data/letinfo.json');
	}
	
	public function getStatus () {
		return $this->getData('data/Status.json');
	}
}
