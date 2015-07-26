<?php
/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
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
			$data = $this->sentRequest($path, $fields, true);
			$data = $this->getValues($data['body']);
			
			if ($data['status'] == 'ok') {
				return true;
			}
			else {
				return false;
			}
		}
		else {
			throw new RouterException('unknown status');
		}
	}
	
	/**
	 * change lte connection status
	 * 
	 * @param	string	$status
	 * @return	boolean
	 */
	public function changeLTEStatus ($status) {
		throw new Exception('unstable funtion');
		$path = 'data/Modules.json';
		
		if ($status == '0' || $status == '1' || $status == 'yes' || $status == 'no') {
			if ($status == 'yes') $status = '1';
			else if ($status == 'no') $status = '0';
			
			$fields = array('csrf_token' => $this->token, 'use_lte' => $status);
			$data = $this->sentEncryptedRequest($path, $fields, true);
			
			// debug only
			return $data;
		}
		else {
			throw new RouterException('unknown status');
		}
	}
	
	/**
	 * reconnect LTE
	 *
	 * @return	array
	 */
	public function reconnectLte () {
		$this->checkLogin();
		
		$path = 'data/modules.json';
		$fields = array('csrf_token' => $this->token, 'lte_reconn' => '1');
		$data = $this->sentEncryptedRequest($path, $fields, true);
		
		return $data['body'];
	}
}
