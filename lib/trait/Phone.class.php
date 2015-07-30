<?php
/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
 */
trait Phone {
	/**
	 * get phone book entrys
	 *
	 * @return	array
	 */
	public function getPhoneBookEntrys () {
		$data = $this->getData('PhoneBook');
		$data = $this->getValues($data);
		
		if (isset($data['addbookentry'])) {
			return $data['addbookentry'];
		}
		else {
			return array();
		}
	}
	
	/**
	 * add Phone Book Entry
	 *
	 * @param	string	$name
	 * @param	string	$firstname
	 * @param	string	$private
	 * @param	string	$work
	 * @param	string	$mobile
	 * @param	integer	$id
	 *
	 * @return	array
	 */
	public function addPhoneBookEntry ($name, $firstname, $private, $work, $mobile, $id = -1) {
		$this->checkLogin();
		
		$path = 'data/PhoneBook.json';
		$fields = array('csrf_token' => $this->token,
						'id' => $id,
						'search' => '',
						'phonebook_name' => $name,
						'phonebook_vorname' => $firstname,
						'phonebook_number_p' => $private,
						'phonebook_number_a' => $work,
						'phonebook_number_m' => $mobile
						);
		
		$data = $this->sentRequest($path, $fields, true);
		$data = $this->getValues($data['body']);
		
		if ($data['status'] == 'ok') {
			return $data;
		}
		else {
			throw new RouterException('can not add/edit Phone Book Entry');
		}
	}
	
	/**
	 * edit Phone Book Entry
	 *
	 * @param	integer	$id
	 * @param	string	$name
	 * @param	string	$firstname
	 * @param	string	$private
	 * @param	string	$work
	 * @param	string	$mobile
	 *
	 * @return	array
	 */
	public function changePhoneBookEntry ($id, $name, $firstname, $private, $work, $mobile) {
		return $this->addPhoneBookEntry($name, $firstname, $private, $work, $private, $id);
	}
	
	/**
	 * delete Phone Book Entry
	 *
	 * @param	integer	$id
	 *
	 * @return	array
	 */
	public function deletePhoneBookEntry ($id) {
		$this->checkLogin();
		
		$path = 'data/PhoneBook.json';
		$fields = array('csrf_token' => $this->token,
						'id' => $id,
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
	 * get the Missed Calls from router
	 * 
	 * @return	array
	 */
	public function getMissedCalls() {
		$lines = $this->exportData('1');
		$calls = array();
		$c = count($lines) -2;
		
		foreach ($lines as $line) {
			if (empty($line) || strpos($line, 'Date') !== false) continue;
			
			$exp = explode(' ', $line);
			
			$data = array();
			$data['id'] = $c;
			$data['missedcalls_date'] = $exp[0];
			$data['missedcalls_time'] = $exp[1];
			$data['missedcalls_who'] = $exp[2];
			$c--;
			$calls[] = $data;
		}
		
		return $calls;
	}
	
	/**
	 * get the Taken Calls from router
	 * 
	 * @return	array
	 */
	public function getTakenCalls() {
		$lines = $this->exportData('2');
		$calls = array();
		$c = count($lines) -2;
		
		foreach ($lines as $line) {
			if (empty($line) || strpos($line, 'Date') !== false) continue;
			
			$exp = explode(' ', $line);
			
			$data = array();
			$data['id'] = $c;
			$data['takencalls_date'] = $exp[0];
			$data['takencalls_time'] = $exp[1];
			$data['takencalls_who'] = $exp[2];
			$data['takencalls_duration'] = $exp[3];
			$c--;
			$calls[] = $data;
		}
		
		return $calls;
		
	}
	
	/**
	 * get the Dialed Calls from router
	 * 
	 * @return	array
	 */
	public function getDialedCalls() {
		$lines = $this->exportData('3');
		$calls = array();
		$c = count($lines) -2;
		
		foreach ($lines as $line) {
			if (empty($line) || strpos($line, 'Date') !== false) continue;
			
			$exp = explode(' ', $line);
			
			$data = array();
			$data['id'] = $c;
			$data['dialedcalls_date'] = $exp[0];
			$data['dialedcalls_time'] = $exp[1];
			$data['dialedcalls_who'] = $exp[2];
			$data['dialedcalls_duration'] = $exp[3];
			$c--;
			$calls[] = $data;
		}
		
		return $calls;
	}
	
	/**
	 * export data from router
	 * 
	 * @return	array
	 */
	private function exportData ($type) {
		$this->checkLogin();
		
		$path = 'data/Syslog.json';
		$fields = array('exporttype' => $type);
		$data = $this->sentRequest($path, $fields, true);
		
		return explode("\n", $data['body']);
	}
}
