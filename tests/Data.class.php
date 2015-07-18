<?php
require_once('../SpeedportHybrid.class.php');
/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
 */
class Data extends SpeedportHybrid {
	public function getDSL () {
		return $this->getData('dsl');
	}
	
	public function getInterfaces () {
		return $this->getData('interfaces');
	}
	
	public function getArp () {
		return $this->getData('arp');
	}
	
	public function getSession () {
		return $this->getData('session');
	}
	
	public function getDHCPClient () {
		return $this->getData('dhcp_client');
	}
	
	public function getDHCPServer () {
		return $this->getData('dhcp_server');
	}
	
	public function getIPv6 () {
		return $this->getData('ipv6');
	}
	
	public function getDNS () {
		return $this->getData('dns');
	}
	
	public function getRouting () {
		return $this->getData('routing');
	}
	
	public function getIGMPProxy () {
		return $this->getData('igmp_proxy');
	}
	
	public function getIGMPSnooping () {
		return $this->getData('igmp_snooping');
	}
	
	public function getWLAN () {
		return $this->getData('wlan');
	}
	
	public function getModule () {
		return $this->getData('module');
	}
	
	public function getMemory () {
		return $this->getData('memory');
	}
	
	public function getSpeed () {
		return $this->getData('speed');
	}
	
	public function getWebDAV () {
		return $this->getData('webdav');
	}
	
	public function getBondingClient () {
		return $this->getData('bonding_client');
	}
	
	public function getBondingTunnel () {
		return $this->getData('bonding_tunnel');
	}
	
	public function getFilterList () {
		return $this->getData('filterlist');
	}
	
	public function getBondingTR181 () {
		return $this->getData('bonding_tr181');
	}
	
	public function getLTEInfo () {
		return $this->getData('letinfo');
	}
	
	public function getStatus () {
		return $this->getData('Status');
	}
}
