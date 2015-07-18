<?php
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
	const VERSION = '1.0.2';
	
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
	private $session = '';
	
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
		$data = json_decode($data['body'], true);
		$data = $this->getValues($data);
		
		if (isset($data['challengev']) && !empty($data['challengev'])) {
			$this->challenge = $data['challengev'];
		}
	}
	
	/**
	 * login into the router with the given password
	 * 
	 * @param	string	$password
	 * @return	boolean
	 */
	public function login ($password) {
		$this->getChallenge();
		
		if (empty($this->challenge)) {
			throw new Exception('unable to get the challenge from the router');
		}
		
		$path = 'data/Login.json';
		$this->hash = hash('sha256', $this->challenge.':'.$password);
		$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'password' => $this->hash);
		$data = $this->sentRequest($path, $fields);
		$json = json_decode($data['body'], true);
		$json = $this->getValues($json);
		if (isset($json['login']) && $json['login'] == 'success') {
			if (isset($data['header']['Set-Cookie']) && !empty($data['header']['Set-Cookie'])) {
				preg_match('/^.*(SessionID_R3=[a-z0-9]*).*/i', $data['header']['Set-Cookie'], $match);
				if (isset($match[1]) && !empty($match[1])) {
					$this->session = $match[1];
				}
				else {
					throw new Exception('unable to get the session cookie from the router');
				}
				
				// calculate derivedk
				if (!function_exists("hash_pbkdf2")) {
					require_once 'CryptLib/CryptLib.php';
					$pbkdf2 = new CryptLib\Key\Derivation\PBKDF\PBKDF2(array('hash' => 'sha1'));
					$this->derivedk = bin2hex($pbkdf2->derive(hash('sha256', $password), substr($this->challenge, 0, 16), 1000, 32));
					$this->derivedk = substr($this->derivedk, 0, 32);
				}
				else {
					$this->derivedk = hash_pbkdf2('sha1', hash('sha256', $password), substr($this->challenge, 0, 16), 1000, 32);
				}
				
				// get the csrf_token
				$this->token = $this->getToken();
				
				if ($this->checkLogin() === true) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * check if we are logged in
	 *
	 * @return boolean
	 */
	public function checkLogin () {
		if (empty($this->challenge) && empty($this->session)) {
			return false;
		}
		
		$path = 'data/SecureStatus.json';
		$fields = array();
		$data = $this->sentRequest($path, $fields, true);
		
		if (empty($data['body'])) {
			throw new Exception('unable to get SecureStatus data');
		}
		
		$json = json_decode($data['body'], true);
		$json = $this->getValues($json);
		
		if ($json['loginstate'] != 1) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * logout
	 * 
	 * @return	array
	 */
	public function logout () {
		if ($this->checkLogin() !== true) throw new Exception('you musst be logged in to use this method');
		
		$path = 'data/Login.json';
		$fields = array('csrf_token' =>  $this->token, 'logout' => 'byby');
		$data = $this->sentRequest($path, $fields, true);
		if ($this->checkLogin() === false) {
			// reset challenge and session
			$this->challenge = '';
			$this->session = '';
			$this->token = "";
			
			$json = json_decode($data['body'], true);
			
			return $json;
		}
		else {
			throw new Exception('logout failed');
		}
	}
	
	/**
	 * reboot the router
	 * 
	 * @return	array
	 */
	public function reboot () {
		if ($this->checkLogin() !== true) throw new Exception('you musst be logged in to use this method');
		
		$path = 'data/Reboot.json';
		$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'password' => $this->hash, 'reboot_device' => 'true');
		$data = $this->sentRequest($path, $fields, true);
		
		$json = json_decode($data['body'], true);
		
		return $json;
	}
	
	/**
	 * change dsl connection status
	 * 
	 * @param	string	$status
	 */
	public function changeConnectionStatus ($status) {
		if ($this->checkLogin() !== true) throw new Exception('you musst be logged in to use this method');
		
		$path = 'data/Connect.json';
		
		if ($status == 'online' || $status == 'offline') {
			$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'password' => $this->hash, 'req_connect' => $status);
			$data = $this->sentRequest($path, $fields, true);
			
			$json = json_decode($this->decrypt($data['body']), true);
			
			return $json;
		}
		else {
			throw new Exception();
		}
	}
	
	/**
	 * return the given json as array
	 * 
	 * @param	string	$file
	 * @return	array
	 */
	public function getData ($file) {
		if ($this->checkLogin() !== true && $file != "Status") throw new Exception('you musst be logged in to use this method');
		
		$path = 'data/'.$file.'.json';
		$fields = array();
		$data = $this->sentRequest($path, $fields, true);
		
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
		return $this->exportData('0');
	}
	
	/**
	 * get the Missed Calls from router
	 * 
	 * @return	array
	 */
	public function getMissedCalls() {
		return $this->exportData('1');
	}
	
	/**
	 * get the Taken Calls from router
	 * 
	 * @return	array
	 */
	public function getTakenCalls() {
		return $this->exportData('2');
	}
	
	/**
	 * get the Dialed Calls from router
	 * 
	 * @return	array
	 */
	public function getDialedCalls() {
		return $this->exportData('3');
	}
	
	/**
	 * export data from router
	 * 
	 * @return	array
	 */
	private function exportData ($type) {
		if ($this->checkLogin() !== true) throw new Exception('you musst be logged in to use this method');
		
		$path = 'data/ExportDialedCalls.json';
		$fields = array('exporttype' => $type);
		$data = $this->sentRequest($path, $fields, true);
		
		if (empty($data['body'])) {
			throw new Exception('unable to get export data');
		}
		
		return explode("\n", $data['body']);
	}
	
	/**
	 * reconnect LTE
	 *
	 * @return	array
	 */
	public function reconnectLte () {
		if ($this->checkLogin() !== true) throw new Exception('you musst be logged in to use this method');
		
		$path = 'data/modules.json';
		$fields = array('csrf_token' => $this->token, 'lte_reconn' => '1');
		$fields = $this->encrypt($fields);
		$data = $this->sentRequest($path, $fields, true, 2);
		$json = json_decode($data['body'], true);
		
		return $json;
	}
	
	/**
	 * reset the router to Factory Default
	 * not tested
	 *
	 * @return	array
	 */
	public function resetToFactoryDefault () {
		if ($this->checkLogin() !== true) throw new Exception('you musst be logged in to use this method');
		
		$path = 'data/resetAllSetting.json';
		$fields = array('csrf_token' => 'nulltoken', 'showpw' => 0, 'password' => $this->hash, 'reset_all' => 'true');
		$data = $this->sentRequest($path, $fields, true);
		$json = json_decode($data['body'], true);
		
		return $json;
	}
	
	
	/**
	 * check if firmware is actual
	 * 
	 * @return	array
	 */
	public function checkFirmware () {
		if ($this->checkLogin() !== true) throw new Exception('you musst be logged in to use this method');
		
		$path = 'data/checkfirmware.json';
		$fields = array('checkfirmware' => 'true');
		$data = $this->sentRequest($path, $fields, true);
		
		if (empty($data['body'])) {
			throw new Exception('unable to get checkfirmware data');
		}
		
		$json = json_decode($data['body'], true);
		
		return $json;
	}
	
	/**
	 * decrypt data from router
	 * 
	 * @param	string	$data
	 * @return	array
	 */
	private function decrypt ($data) {
		require_once 'CryptLib/CryptLib.php';
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
		require_once 'CryptLib/CryptLib.php';
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
			$data[$item['varid']] = $item['varvalue'];
		}
		
		return $data;
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
		$url = $this->url.$path;
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
			curl_setopt($ch, CURLOPT_COOKIE, 'challengev='.$this->challenge.'; '.$this->session);
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
	 * get the csrf_token
	 * 
	 * @return	string
	 */
	private function getToken () {
		if ($this->checkLogin() !== true) throw new Exception('you musst be logged in to use this method');
		
		$path = 'html/content/overview/index.html?lang=de';
		$fields = array();
		$data = $this->sentRequest($path, $fields, true);
		
		if (empty($data['body'])) {
			throw new Exception('unable to get csrf_token');
		}
		
		$a = explode('csrf_token = "', $data['body']);
		$a = explode('";', $a[1]);
		
		if (isset($a[0]) && !empty($a[0])) {
			return $a[0];
		}
		else {
			throw new Exception('unable to get csrf_token');
		}
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
