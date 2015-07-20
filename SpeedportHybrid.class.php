<?php
require_once('RebootException.class.php');
require_once('RouterException.class.php');
require_once('CryptLib/CryptLib.php');

/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
 */
class SpeedportHybrid {
	/**
	 *
	 *
	 */
	const VERSION = '1.0.3';
	
	/**
	 * password-challenge
	 * @var	string
	 */
	private $challenge = '';
	
	/**
	 * csrf_token
	 * @var	string
	 */
	private $token = '';
	
	/**
	 * hashed password
	 * @var	string
	 */
	private $hash = '';
	
	/**
	 * session cookie
	 * @var	string
	 */
	private $cookie = '';
	
	/**
	 * router url
	 * @var	string
	 */
	private $url = '';
	
	/**
	 * derivedk cookie
	 * @var	string
	 */
	private $derivedk = '';
	
	public function __construct ($url = 'http://speedport.ip/') {
		$this->url = $url;
	}
	
	/**
	 * Requests the password-challenge from the router.
	 */
	private function getChallenge () {
		$path = 'data/Login.json';
		$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'challengev' => 'null');
		$data = $this->sentRequest($path, $fields);
		$data = $this->getValues($data['body']);
		
		if (isset($data['challengev']) && !empty($data['challengev'])) {
			return $data['challengev'];
		}
		else {
			throw new RouterException('unable to get the challenge from the router');
		}
	}
	
	/**
	 * login into the router with the given password
	 * 
	 * @param	string	$password
	 * @return	boolean
	 */
	public function login ($password) {
		$this->challenge = $this->getChallenge();
		
		$path = 'data/Login.json';
		$this->hash = hash('sha256', $this->challenge.':'.$password);
		$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'password' => $this->hash);
		$data = $this->sentRequest($path, $fields);
		$json = $this->getValues($data['body']);
		
		if (isset($json['login']) && $json['login'] == 'success') {
			$this->cookie = $this->getCookie($data);
			
			$this->derivedk = $this->getDerviedk($password);
			
			// get the csrf_token
			$this->token = $this->getToken();
			
			if ($this->checkLogin(false) === true) {
				return true;
			}
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
		// check if challenge or session is empty
		if (empty($this->challenge) || empty($this->cookie)) {
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
		$fields = array('csrf_token' =>  $this->token, 'logout' => 'byby');
		$data = $this->sentRequest($path, $fields, true);
		$data = $this->getValues($data['body']);
		if ((isset($data['status']) && $data['status'] == 'ok') && $this->checkLogin(false) === false) {
			// reset challenge and session
			$this->challenge = '';
			$this->cookie = '';
			$this->token = '';
			$this->derivedk = '';
			
			return true;
		}
		
		return false;
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
		$data = $this->sentEncryptedRequest($path, $fields, true);
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
	
	/**
	 * change dsl connection status
	 * 
	 * @param	string	$status
	 * @return	boolean
	 */
	public function changeConnectionStatus ($status) {
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
			throw new RouterException();
		}
	}
	
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
	 * get the Missed Calls from router
	 * 
	 * @return	array
	 */
	public function getMissedCalls() {
		$data = $this->getData('PhoneCalls');
		$data = $this->getValues($data);
		
		if (isset($data['addmissedcalls'])) {
			return $data['addmissedcalls'];
		}
		else {
			return array();
		}
	}
	
	/**
	 * get the Taken Calls from router
	 * 
	 * @return	array
	 */
	public function getTakenCalls() {
		$data = $this->getData('PhoneCalls');
		$data = $this->getValues($data);
		
		if (isset($data['addtakencalls'])) {
			return $data['addtakencalls'];
		}
		else {
			return array();
		}
	}
	
	/**
	 * get the Dialed Calls from router
	 * 
	 * @return	array
	 */
	public function getDialedCalls() {
		$data = $this->getData('PhoneCalls');
		$data = $this->getValues($data);
		
		if (isset($data['adddialedcalls'])) {
			return $data['adddialedcalls'];
		}
		else {
			return array();
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
	
	/**
	 * decrypt data from router
	 * 
	 * @param	string	$data
	 * @return	array
	 */
	private function decrypt ($data) {
		$factory = new CryptLib\Cipher\Factory();
		$aes = $factory->getBlockCipher('rijndael-128');
		
		$iv = hex2bin(substr($this->challenge, 16, 16));
		$adata = hex2bin(substr($this->challenge, 32, 16));
		$dkey = hex2bin($this->derivedk);
		$enc = hex2bin($data);
		
		$aes->setKey($dkey);
		$mode = $factory->getMode('ccm', $aes, $iv, [ 'adata' => $adata, 'lSize' => 7]);
		
		$mode->decrypt($enc);
		
		return $mode->finish();
	}

	/**
	 * decrypt data for the router
	 * 
	 * @param	array	$data
	 * @return	string
	 */
	private function encrypt ($data) {
		$factory = new CryptLib\Cipher\Factory();
		$aes = $factory->getBlockCipher('rijndael-128');
		
		$iv = hex2bin(substr($this->challenge, 16, 16));
		$adata = hex2bin(substr($this->challenge, 32, 16));
		$dkey = hex2bin($this->derivedk);
		
		$aes->setKey($dkey);
		$mode = $factory->getMode('ccm', $aes, $iv, [ 'adata' => $adata, 'lSize' => 7]);
		$mode->encrypt(http_build_query($data));
		
		return bin2hex($mode->finish());
	}
	
	/**
	 * get the values from array
	 * 
	 * @param	array	$array
	 * @return	array
	 */
	private function getValues($array) {
		$data = array();
		foreach ($array as $item) {
			// thank you telekom for this piece of shit
			if ($item['vartype'] == 'template') {
				if (is_array($item['varvalue'])) {
					$data[$item['varid']][] = $this->getValues($item['varvalue']);
				}
				else {
					$data[$item['varid']] = $item['varvalue'];
				}
			}
			else {
				if (is_array($item['varvalue'])) {
					$data[$item['varid']] = $this->getValues($item['varvalue']);
				}
				else {
					$data[$item['varid']] = $item['varvalue'];
				}
			}
		}
		
		return $data;
	}
	
	/**
	 * sends the encrypted request to router
	 * 
	 * @param	string	$path
	 * @param	mixed	$fields
	 * @param	string	$cookie
	 * @return	array
	 */
	private function sentEncryptedRequest ($path, $fields, $cookie = false) {
		$count = count($fields);
		$fields = $this->encrypt($fields);
		return $this->sentRequest($path, $fields, $cookie, $count);
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
	private function sentRequest ($path, $fields, $cookie = false, $count = 0) {
		$url = $this->url.$path.'?lang=en';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		
		if (!empty($fields)) {
			if (is_array($fields)) {
				curl_setopt($ch, CURLOPT_POST, count($fields));
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
			}
			else {
				curl_setopt($ch, CURLOPT_POST, $count);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			}
		}
		
		if ($cookie === true) {
			curl_setopt($ch, CURLOPT_COOKIE, 'lang=en; challengev='.$this->challenge.'; '.$this->cookie);
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		
		$result = curl_exec($ch);
		
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($result, 0, $header_size);
		$body = substr($result, $header_size);
		curl_close($ch);
		
		// check if response is empty
		if (empty($body)) {
			throw new RouterException('empty response');
		}
		
		// check if body is encrypted (hex instead of json)
		if (ctype_xdigit($body)) {
			$body = $this->decrypt($body);
		}
		
		// fix invalid json
		$body = preg_replace("/(\r\n)|(\r)/", "\n", $body);
		$body = preg_replace('/\'/i', '"', $body);
		$body = preg_replace("/\[\s+\]/i", '[ {} ]', $body);
		$body = preg_replace("/},\s+]/", "}\n]", $body);
		
		// decode json
		if (strpos($url, '.json') !== false) {
			$body = json_decode($body, true);
		}
		
		return array('header' => $this->parse_headers($header), 'body' => $body);
	}
	
	/**
	 * get the csrf_token
	 * 
	 * @return	string
	 */
	private function getToken () {
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
	 * calculate the derivedk
	 *
	 * @param	string	$password
	 * @return	string
	 */
	private function getDerviedk ($password) {
		$derivedk = '';
		
		// calculate derivedk
		if (!function_exists('hash_pbkdf2')) {
			$pbkdf2 = new CryptLib\Key\Derivation\PBKDF\PBKDF2(array('hash' => 'sha1'));
			$derivedk = bin2hex($pbkdf2->derive(hash('sha256', $password), substr($this->challenge, 0, 16), 1000, 32));
			$derivedk = substr($derivedk, 0, 32);
		}
		else {
			$derivedk = hash_pbkdf2('sha1', hash('sha256', $password), substr($this->challenge, 0, 16), 1000, 32);
		}
		
		if (empty($derivedk)) {
			throw new RouterException('unable to calculate derivedk');
		}
		
		return $derivedk;
	}
	
	/**
	 * get cookie from header data
	 *
	 * @param	array	$data
	 * @return	string
	 */
	private function getCookie ($data) {
		$cookie = '';
		if (isset($data['header']['Set-Cookie']) && !empty($data['header']['Set-Cookie'])) {
			preg_match('/^.*(SessionID_R3=[a-z0-9]*).*/i', $data['header']['Set-Cookie'], $match);
			if (isset($match[1]) && !empty($match[1])) {
				$cookie = $match[1];
			}
		}
		
		if (empty($cookie)) {
			throw new RouterException('unable to get the session cookie from the router');
		}
		
		return $cookie;
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
		
		$header_text = explode("\r\n", $header_text);
		foreach ($header_text as $i => $line) {
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
