<?php
/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015-2016 Jan Altensen (Stricted)
 */
trait System {
	/**
	 * get uptime based on last reboot
	 *
	 * @return	string
	 */
	public function getUptime () {
		$lastReboot = $this->getLastReboot();
		
		$dtF = new DateTime("@0");
		$dtT = new DateTime("@".$lastReboot);
		
		return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
	}
	
	/**
	 * return the given json as array
	 * 
	 * @param	string	$file
	 * @return	array
	 */
	public function getData ($file) {
		if ($file != 'Status') $this->checkLogin();
		
		$path = 'data/'.$file.'.json';
		$fields = array();
		$data = $this->sendRequest($path, $fields, true);
		
		return $data['body'];
	}
	
	/**
	 * get the router syslog
	 * 
	 * @return	array
	 */
	public function getSyslog() {
		$data = $this->getData('SystemMessages');
		$data = $this->getValues($data);
		
		if (isset($data['addmessage'])) {
			return $data['addmessage'];
		}
		else {
			return array();
		}
	}
	
	/**
	 * get Last Reboot time
	 * 
	 * @return	int
	 */
	public function getLastReboot () {
		$response = $this->sendRequest("data/Reboot.json");
		$response = $this->getValues($response);

		$lastReboot = time() - strtotime($response['reboot_date']." ".$response['reboot_time']);
		
		return $lastReboot;
	}
	
	/**
	 * reset the router to Factory Default
	 * not tested
	 *
	 * @return	array
	 */
	public function resetToFactoryDefault () {
		$this->checkLogin();
		
		$path = 'data/resetAllSetting.json';
		$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'password' => $this->hash, 'reset_all' => 'true');
		$data = $this->sendRequest($path, $fields, true);
		
		return $data['body'];
	}
	
	
	/**
	 * check if firmware is actual
	 * 
	 * @return	array
	 */
	public function checkFirmware () {
		$this->checkLogin();
		
		$path = 'data/checkfirmware.json';
		$fields = array('checkfirmware' => 'true');
		$data = $this->sendRequest($path, $fields, true);
		
		return $data['body'];
	}
	
	/**
	 * reboot the router
	 * 
	 * @return	boolean
	 */
	public function reboot () {
		$this->checkLogin();
		
		$path = 'data/Reboot.json';
		$fields = array('csrf_token' => $this->token, 'reboot_device' => 'true');
		$data = $this->sendEncryptedRequest($path, $fields, true);
		$data = $this->getValues($data['body']);
		
		if ($data['status'] == 'ok') {
			// reset challenge and session
			$this->challenge = '';
			$this->cookie = '';
			$this->token = '';
			$this->derivedk = ''; 
			
			// throw an exception because router is unavailable for other tasks
			// like $this->logout() or $this->checkLogin
			throw new RebootException('Router Reboot');
		}
		
		return false;
	}
}
