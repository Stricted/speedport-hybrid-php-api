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
	/**
	 * login into the router with the given password
	 * 
	 * @param	string	$password
	 * @return	boolean
	 */
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
	 * check if we are logged in
	 *
	 * @param	boolean	$exception
	 * @return	boolean
	 */
	public function checkLogin ($exception = true) {
		// check if session is empty
		if (empty($this->cookie)) {
			if ($exception === true) {
				throw new RouterException('you musst be logged in to use this method');
			}
			
			return false;
		}
		
		$path = 'data/SecureStatus.json';
		$fields = array();
		$data = $this->sentRequest($path, $fields, true);
		$data = $this->getValues($data['body']);
		
		if ($data['loginstate'] != 1) {
			if ($exception === true) {
				throw new RouterException('you musst be logged in to use this method');
			}
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * logout
	 * 
	 * @return	boolean
	 */
	public function logout () {
		$this->checkLogin();
		
		$path = 'data/Login.json';
		$fields = array('csrf_token' => $this->token, 'logout' => 'byby');
		$data = $this->sentRequest($path, $fields, true);
		$data = $this->getValues($data['body']);
		if ((isset($data['status']) && $data['status'] == 'ok') && $this->checkLogin(false) === false) {
			// reset challenge and session
			$this->cookie = '';
			$this->token = '';
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * get the csrf_token
	 * 
	 * @return	string
	 */
	protected function getToken () {
		$this->checkLogin();
		
		$path = 'html/content/overview/index.html';
		$fields = array();
		$data = $this->sentRequest($path, $fields, true);
		
		$a = explode('csrf_token = "', $data['body']);
		$a = explode('";', $a[1]);
		
		if (isset($a[0]) && !empty($a[0])) {
			return $a[0];
		}
		else {
			throw new RouterException('unable to get csrf_token');
		}
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
