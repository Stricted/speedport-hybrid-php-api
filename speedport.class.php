<?php
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
	private $url = 'http://speedport.ip/';
	
	public function __construct ($password) {
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
		$url = 'data/Login.json';
		$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'challengev' => 'null');
		$data = $this->sentRequest($url, $fields);
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
		$url = 'data/Login.json';
		$this->hash = hash('sha256', $this->challenge.':'.$password);
		$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'password' => $this->hash);
		$data = $this->sentRequest($url, $fields);
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
		$url = 'data/Login.json';
		$fields = array('logout' => 'byby');
		$data = $this->sentRequest($url, $fields);
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
		$url = 'data/Reboot.json';
		$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'password' => $this->hash, 'reboot_device' => 'true');
		$cookie = 'challengev='.$this->challenge.'; '.$this->session;
		$data = $this->sentRequest($url, $fields, $cookie);
		$json = json_decode($data['body'], true);
		
		return $json;
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
		$url = 'data/'.$file.'.json';
		$fields = array();
		$cookie = 'challengev='.$this->challenge.'; '.$this->session;
		$data = $this->sentRequest($url, $fields, $cookie);
		
		if (empty($data['body'])) {
			throw new Exception('unable to get '.$file.' data');
		}
		
		$json = json_decode($data['body'], true);
		
		return $json;
	}
	
	/**
	 * sends the request to router
	 *
	 * @param	string	$url
	 * @param	array	$fields
	 * @param	string	$cookie
	 * @return	array
	 */
	private function sentRequest ($url, $fields = array(), $cookie = '') {
		$url = $this->url.$url;
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
		$body = preg_replace("/},\n\n]/", "}\n]", $body);
		$body = preg_replace('/\s+/', ' ', $body);
		$body = preg_replace("/\[ \]/i", '[ {} ]', $body);
		$body = preg_replace("/}, ]/", "} ]", $body);
		$body = preg_replace("/\n/", " ", $body);
		
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