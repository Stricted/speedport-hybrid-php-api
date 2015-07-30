<?php
/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
 */
trait Firewall {
	/**
	 * get all Portforwarding Entrys
	 *
	 * @return	array
	 */
	public function getPortforwardingEntrys () {
		$data = $this->getData('Portforwarding');
		$data = $this->getValues($data);
		
		//print_r($data);
		
		if (isset($data['addportuw'])) {
			return $data['addportuw'];
		}
		else {
			return array();
		}
	}
	
	/**
	 * delete Portforwarding Entry
	 *
	 * @param	integer	$id
	 *
	 * @return	array
	 */
	public function deletePortforwardingEntry ($id) {
		$this->checkLogin();
		
		$path = 'data/Portforwarding.json';
		$fields = array('csrf_token' => $this->token,
						'id_portforward' => $id,
						'deleteEntry' => 'delete'
						);
		
		$data = $this->sentRequest($path, $fields, true);
		$data = $this->getValues($data['body']);
		
		if ($data['status'] == 'ok') {
			return $data;
		}
		else {
			throw new RouterException('can not delete Phone Book Entry');
		}
	}
	
	/**
	 * add Portforwarding Entry
	 *
	 * @param	string	$name
	 * @param	integer	$device
	 */
	public function addPortforwardingEntry ($name, $device) {
		// TODO: find a way to make this possible
		/* fields:
		 * 
		 * portuw_name
		 * portuw_device
		 * optvar_portuw_template = -1
		 * tcp_public_from
		 * tcp_public_to
		 * tcp_private_dest
		 * tcp_private_to
		 * portuwtcp_id = -1
		 *
		 * udp_public_from
		 * udp_public_to
		 * udp_private_dest
		 * udp_private_to
		 * portuwudp_id = -1
		 */
		/*
		var inputs = $("input[name^='tcp_public_'], input[name^='tcp_private_'], input[name^='udp_public_'], input[name^='udp_private_']");
		var updateToField = function() {
			var field = $(this);
			var prefix = field.is("[name^='tcp_']") ? "tcp" : "udp";
			var cont = field.closest("div[id^='template_add']");
			var pv = parseInt(cont.find("input[name^='"+prefix+"_public_from']").val());
			var pt = parseInt(cont.find("input[name^='"+prefix+"_public_to']").val());
			var pp = parseInt(cont.find("input[name^='"+prefix+"_private_dest']").val());
			var result = "";
			if (!isNaN(pv) && !isNaN(pt) && !isNaN(pp)) {
				if (pv <= pt) {
					result = pp + pt - pv;
				}
			}
			cont.find("input[name^='"+prefix+"_private_to']").val(result);
		};
		*/
	}
	
	/**
	 * edit Portforwarding Entry
	 *
	 * @param	integer	$id
	 * @param	string	$name
	 * @param	integer	$device
	 */
	public function editPortforwardingEntry ($id, $name, $device) {
		// TODO: find a way to make this possible
	}
}
