<?php
/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
 */
class speedport {
	/**
	 * password-challenge
	 * @var	string
	 */
	private $challenge = '';
	
	/**
	 * hashed password
	 * @var	string
	 */
	private $hash = '';
	
	/**
	 * session cookie
	 * @var	string
	 */
	private $session = '';
	
	/**
	 * router url
	 * @var	string
	 */
	private $url = '';
	
	public function __construct ($password, $url = 'http://speedport.ip/') {
		$this->url = $url;
		$this->getChallenge();
		
		if (empty($this->challenge)) {
			throw new Exception('unable to get the challenge from the router');
		}
		
		$login = $this->login($password);
		
		if ($login === false) {
			throw new Exception('unable to login');
		}
	}
	
	/**
	 * Requests the password-challenge from the router.
	 */
	public function getChallenge () {
		$path = 'data/Login.json';
		$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'challengev' => 'null');
		$data = $this->sentRequest($path, $fields);
		$data = json_decode($data['body'], true);
		if ($data[1]['varid'] == 'challengev') {
			$this->challenge = $data[1]['varvalue'];
		}
	}
	
	/**
	 * login into the router with the given password
	 * 
	 * @param	string	$password
	 * @return	boolean
	 */
	public function login ($password) {
		$path = 'data/Login.json';
		$this->hash = hash('sha256', $this->challenge.':'.$password);
		$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'password' => $this->hash);
		$data = $this->sentRequest($path, $fields);
		$json = json_decode($data['body'], true);
		if ($json[15]['varid'] == 'login' && $json[15]['varvalue'] == 'success') {
			if (isset($data['header']['Set-Cookie']) && !empty($data['header']['Set-Cookie'])) {
				preg_match('/^.*(SessionID_R3=[a-z0-9]*).*/i', $data['header']['Set-Cookie'], $match);
				if (isset($match[1]) && !empty($match[1])) {
					$this->session = $match[1];
				}
				else {
					throw new Exception('unable to get the session cookie from the router');
				}
				
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * logout
	 * 
	 * @return	array
	 */
	public function logout () {
		$path = 'data/Login.json';
		$fields = array('logout' => 'byby');
		$data = $this->sentRequest($path, $fields);
		// reset challenge and session
		$this->challenge = '';
		$this->session = '';
		
		$json = json_decode($data['body'], true);
		
		return $json;
	}
	
	/**
	 * reboot the router
	 * 
	 * @return	array
	 */
	public function reboot () {
		$path = 'data/Reboot.json';
		$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'password' => $this->hash, 'reboot_device' => 'true');
		$cookie = 'challengev='.$this->challenge.'; '.$this->session;
		$data = $this->sentRequest($path, $fields, $cookie);
		$json = json_decode($data['body'], true);
		
		return $json;
	}
	
	/**
	 * change dsl connection status
	 * 
	 * @param	string	$status
	 */
	public function changeConnectionStatus ($status) {
		$path = 'data/Connect.json';
		
		if ($status == 'online' || $status == 'offline') {
			$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'password' => $this->hash, 'req_connect' => $status);
			$cookie = 'challengev='.$this->challenge.'; '.$this->session;
			$this->sentRequest($path, $fields, $cookie);
		}
		else {
			throw new Exception();
		}
	}
	
	/**
	 * return the given json as array
	 * 
	 * the following paths are known to be valid:
	 * /data/dsl.json
	 * /data/interfaces.json
	 * /data/arp.json
	 * /data/session.json
	 * /data/dhcp_client.json
	 * /data/dhcp_server.json
	 * /data/ipv6.json
	 * /data/dns.json
	 * /data/routing.json
	 * /data/igmp_proxy.json
	 * /data/igmp_snooping.json
	 * /data/wlan.json
	 * /data/module.json
	 * /data/memory.json
	 * /data/speed.json
	 * /data/webdav.json
	 * /data/bonding_client.json
	 * /data/bonding_tunnel.json
	 * /data/filterlist.json
	 * /data/bonding_tr181.json
	 * /data/letinfo.json
	 * 
	 * /data/Status.json (No login needed)
	 * 
	 * @param	string	$file
	 * @return	array
	 */
	public function getData ($file) {
		$path = 'data/'.$file.'.json';
		$fields = array();
		$cookie = 'challengev='.$this->challenge.'; '.$this->session;
		$data = $this->sentRequest($path, $fields, $cookie);
		
		if (empty($data['body'])) {
			throw new Exception('unable to get '.$file.' data');
		}
		
		$json = json_decode($data['body'], true);
		
		return $json;
	}
	
	/**
	 * get the router syslog
	 * 
	 * @return	array
	 */
	public function getSyslog() {
		$path = 'data/Syslog.json';
		$fields = array('exporttype' => '0');
		$cookie = 'challengev='.$this->challenge.'; '.$this->session;
		$data = $this->sentRequest($path, $fields, $cookie);
		
		if (empty($data['body'])) {
			throw new Exception('unable to get syslog data');
		}
		
		return explode("\n", $data['body']);
	}
	
	/**
	 * get the Missed Calls from router
	 * 
	 * @return	array
	 */
	public function getMissedCalls() {
		$path = 'data/ExportMissedCalls.json';
		$fields = array('exporttype' => '1');
		$cookie = 'challengev='.$this->challenge.'; '.$this->session;
		$data = $this->sentRequest($path, $fields, $cookie);
		
		if (empty($data['body'])) {
			throw new Exception('unable to get syslog data');
		}
		
		return explode("\n", $data['body']);
	}
	
	/**
	 * get the Taken Calls from router
	 * 
	 * @return	array
	 */
	public function getTakenCalls() {
		$path = 'data/ExportTakenCalls.json';
		$fields = array('exporttype' => '2');
		$cookie = 'challengev='.$this->challenge.'; '.$this->session;
		$data = $this->sentRequest($path, $fields, $cookie);
		
		if (empty($data['body'])) {
			throw new Exception('unable to get syslog data');
		}
		
		return explode("\n", $data['body']);
	}
	
	/**
	 * get the Dialed Calls from router
	 * 
	 * @return	array
	 */
	public function getDialedCalls() {
		$path = 'data/ExportDialedCalls.json';
		$fields = array('exporttype' => '3');
		$cookie = 'challengev='.$this->challenge.'; '.$this->session;
		$data = $this->sentRequest($path, $fields, $cookie);
		print_r($data);
		if (empty($data['body'])) {
			throw new Exception('unable to get syslog data');
		}
		
		return explode("\n", $data['body']);
	}
	
	/**
	 * check if firmware is actual
	 * 
	 * @return	array
	 */
	public function checkFirmware () {
		$path = 'data/checkfirmware.json';
		$fields = array('checkfirmware' => 'true');
		$cookie = 'challengev='.$this->challenge.'; '.$this->session;
		$data = $this->sentRequest($path, $fields, $cookie);
		
		if (empty($data['body'])) {
			throw new Exception('unable to get checkfirmware data');
		}
		
		$json = json_decode($data['body'], true);
		
		return $json;
	}
	
	/**
	 * sends the request to router
	 * 
	 * @param	string	$path
	 * @param	array	$fields
	 * @param	string	$cookie
	 * @return	array
	 */
	private function sentRequest ($path, $fields = array(), $cookie = '') {
		$url = $this->url.$path;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		
		if (!empty($fields)) {
			curl_setopt($ch, CURLOPT_POST, count($fields));
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		}
		
		if (!empty($cookie)) {
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		
		
		if ($cookie) {
			
		}
		
		$result = curl_exec($ch);
		
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($result, 0, $header_size);
		$body = substr($result, $header_size);
		curl_close($ch);
		
		// fix invalid json
		$body = preg_replace("/(\r\n)|(\r)/", "\n", $body);
		$body = preg_replace('/\'/i', '"', $body);
		$body = preg_replace("/\[\s+\]/i", '[ {} ]', $body);
		$body = preg_replace("/},\s+]/", "}\n]", $body);
		
		return array('header' => $this->parse_headers($header), 'body' => $body);
	}
	
	/**
	 * parse the curl return header into an array
	 * 
	 * @param	string	$response
	 * @return	array
	 */
	private function parse_headers($response) {
		$headers = array();
		$header_text = substr($response, 0, strpos($response, "\r\n\r\n"));
		
		foreach (explode("\r\n", $header_text) as $i => $line) {
			if ($i === 0) {
				$headers['http_code'] = $line;
			}
			else {
				list ($key, $value) = explode(': ', $line);
				$headers[$key] = $value;
			}
		}
		
		return $headers;
	}
}