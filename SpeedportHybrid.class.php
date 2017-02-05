<?php
require_once('lib/exception/RebootException.class.php');
require_once('lib/exception/RouterException.class.php');
require_once('lib/exception/NotImplementedException.class.php');
require_once('CryptLib/CryptLib.php');
require_once('Speedport.class.php');
require_once('ISpeedport.class.php');
require_once('lib/trait/Connection.class.php');
require_once('lib/trait/CryptLib.class.php');
require_once('lib/trait/Login.class.php');
require_once('lib/trait/Firewall.class.php');
require_once('lib/trait/Network.class.php');
require_once('lib/trait/Phone.class.php');
require_once('lib/trait/System.class.php');

/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015-2016 Jan Altensen (Stricted)
 */
class SpeedportHybrid extends Speedport implements ISpeedport {
	use Connection;
	use CryptLib;
	use Firewall;
	use Login;
	use Network;
	use Phone;
	use System;
	
	/**
	 * class version
	 * @const	string
	 */
	const VERSION = '1.0.5';
	
	/**
	 * check php requirements
	 */
	protected function checkRequirements () {
		if (!extension_loaded('curl')) {
			throw new Exception("The PHP Extension 'curl' is missing.");
		}
		else if (!extension_loaded('json')) {
			throw new Exception("The PHP Extension 'json' is missing.");
		}
		else if (!extension_loaded('pcre')) {
			throw new Exception("The PHP Extension 'pcre' is missing.");
		}
		else if (!extension_loaded('ctype')) {
			throw new Exception("The PHP Extension 'ctype' is missing.");
		}
		else if (!extension_loaded('hash')) {
			throw new Exception("The PHP Extension 'hash' is missing.");
		}
		else if (!in_array('sha256', hash_algos())) {
			throw new Exception('SHA-256 algorithm is not Supported.');
		}
	}
	
	/**
	 * sends the encrypted request to router
	 * 
	 * @param	string	$path
	 * @param	mixed	$fields
	 * @param	string	$cookie
	 * @return	array
	 */
	protected function sentEncryptedRequest ($path, $fields, $cookie = false) {
		$count = count($fields);
		$fields = $this->encrypt(http_build_query($fields));
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
	private function sendRequest ($path, $fields, $cookie = false, $count = 0) {
		$url = $this->url.$path;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		
		if (!empty($fields)) {
			curl_setopt($ch, CURLOPT_POST, true);
			
			if (is_array($fields)) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
			}
			else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			}
		}
		
		if ($cookie === true) {
			curl_setopt($ch, CURLOPT_COOKIE, 'challengev='.$this->challenge.'; '.$this->cookie);
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
		if (strpos($path, '.json') !== false) {
			$json = json_decode($body, true);
			
			if (is_array($json)) {
				$body = $json;
			}
		}
		
		return array('header' => $header, 'body' => $body);
	}
}
