<?php
/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
 */
trait Login {
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
	 * derivedk cookie
	 * @var	string
	 */
	private $derivedk = '';
	
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
		$fields = array('csrf_token' => $this->token, 'logout' => 'byby');
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
}
