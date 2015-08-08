<?php
require_once('SpeedportHybrid.class.php');

/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
 */
class Speedportw724v extends SpeedportHybrid {
	public function login ($password) {
		/* this is experimental, i dont have a speedport w724v so i cant test this
		 * feel free to test it and report if it dosent work
		 */
		$path = 'data/Login.json';
		$this->hash = md5($password);
		$fields = array('password' => $this->hash, 'password_shadowed' => $this->hash, 'showpw' => 0);
		$data = $this->sentRequest($path, $fields);
		$json = $this->getValues($data['body']);
		
		if (isset($json['login']) && $json['login'] == 'success') {
			$this->cookie = $data['header']['Set-Cookie'];
			
			return true;
		}
		
		return false;
	}
}
