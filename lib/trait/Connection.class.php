<?php
/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015-2016 Jan Altensen (Stricted)
 */
trait Connection {
	/**
	 * change dsl connection status
	 * 
	 * @param	string	$status
	 * @return	boolean
	 */
	public function changeDSLStatus ($status) {
		$this->checkLogin();
		
		$path = 'data/Connect.json';
		
		if ($status == 'online' || $status == 'offline') {
			$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'password' => $this->hash, 'req_connect' => $status);
			$data = $this->sendRequest($path, $fields, true);
			$data = $this->getValues($data['body']);
			
			if ($data['status'] == 'ok') {
				return true;
			}
		}
		else {
			throw new RouterException('unknown status');
		}
		
		return false;
	}
	
	/**
	 * change lte connection status
	 * 
	 * @param	string	$status
	 * @return	boolean
	 */
	public function changeLTEStatus ($status) {
		$this->checkLogin();
		
		/* we have to wait 400ms before we can send the request (idk whats wrong with the router) */
		usleep(400);
		
		$path = 'data/Modules.json';
		
		if ($status == '0' || $status == '1' || $status == 'yes' || $status == 'no') {
			if ($status == 'yes') $status = '1';
			else if ($status == 'no') $status = '0';
			
			$fields = array('csrf_token' => $this->token, 'use_lte' => $status);
			$data = $this->sendEncryptedRequest($path, $fields, true);
			$data = $this->getValues($data['body']);
			
			if ($data['status'] == 'ok') {
				return true;
			}
		}
		else {
			throw new RouterException('unknown status');
		}
		
		return false;
	}
	
	/**
	 * reconnect LTE
	 *
	 * @return	array
	 */
	public function reconnectLte () {
		$this->checkLogin();
		
		/* we have to wait 400ms before we can send the request (idk whats wrong with the router) */
		usleep(400);
		
		$path = 'data/modules.json';
		$fields = array('csrf_token' => $this->token, 'lte_reconn' => '1');
		$data = $this->sendEncryptedRequest($path, $fields, true);
		if ($data['status'] == 'ok') {
			return true;
		}
		
		return false;
	}
}
