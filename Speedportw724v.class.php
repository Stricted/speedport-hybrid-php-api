<?php
require_once('ISpeedport.class.php');
require_once('lib/exception/RouterException.class.php');
require_once('SpeedportHybrid.class.php');

/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
 */
class Speedportw724v extends SpeedportHybrid implements ISpeedport {
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
			if (isset($data['header']['Set-Cookie']) && !empty($data['header']['Set-Cookie'])) {
				$this->cookie = $data['header']['Set-Cookie'];
			}
			else {
				throw new RouterException('unable to get the session cookie from the router');
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * sends the request to router
	 * 
	 * @param	string	$path
	 * @param	mixed	$fields
	 * @param	string	$cookie
	 * @param	integer	$count
	 * @return	array
	 */
	protected function sentRequest ($path, $fields, $cookie = false, $count = 0) {
		$data = parent::sentRequest($path, $fields, $cookie, $count);
		$header = $data['header'];
		$body = $data['body'];
		
		// fix invalid json
		$body = preg_replace("/(\r\n)|(\r)/", "\n", $body);
		$body = preg_replace('/\'/i', '"', $body);
		$body = preg_replace("/\[\s+\]/i", '[ {} ]', $body);
		$body = preg_replace("/},\s+]/", "}\n]", $body);
		
		// decode json
		if (strpos($path, '.json') !== false) {
			$json = json_decode($body, true);
			
			if (is_array($json)) {
				$body = $json;
			}
		}
		
		return array('header' => $header, 'body' => $body);
	}
}
