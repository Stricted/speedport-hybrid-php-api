<?php
/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
 */
trait System {
	/**
	 * get uptime based on online (connection) time
	 *
	 * @return	string
	 */
	public function getUptime () {
		$data = $this->getData('LAN');
		$data = $this->getValues($data);
		
		return $data['days_online'];
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
		$data = $this->sentRequest($path, $fields, true);
		
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
	 * reset the router to Factory Default
	 * not tested
	 *
	 * @return	array
	 */
	public function resetToFactoryDefault () {
		$this->checkLogin();
		
		$path = 'data/resetAllSetting.json';
		$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'password' => $this->hash, 'reset_all' => 'true');
		$data = $this->sentRequest($path, $fields, true);
		
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
		$data = $this->sentRequest($path, $fields, true);
		
		return $data['body'];
	}
}
