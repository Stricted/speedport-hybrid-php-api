<?php
require_once('lib/exception/RebootException.class.php');
require_once('lib/exception/RouterException.class.php');
require_once('lib/exception/NotImplementedException.class.php');
require_once('CryptLib/CryptLib.php');
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
class SpeedportHybrid {
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
	 * router url
	 * @var	string
	 */
	private $url = '';
	
	/**
	 * inititalize this class
	 *
	 * @param	string	$url
	 */
	public function __construct ($url = 'http://speedport.ip/') {
		$this->url = $url;
		$this->checkRequirements();
	}
	
	/**
	 * check php requirements
	 */
	private function checkRequirements () {
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
	 * get the values from array
	 * 
	 * @param	array	$array
	 * @return	array
	 */
	private function getValues($array) {
		$data = array();
		foreach ($array as $item) {
			if (!isset($item['vartype']) || !isset($item['varid']) || !isset($item['varvalue'])) continue;
			
			// thank you telekom for this piece of shit
			if ($item['vartype'] == 'template') {
				if (is_array($item['varvalue'])) {
					$data[$item['varid']][] = $this->getValues($item['varvalue']);
				}
				else {
					// i dont know if we need this
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
		if (strpos($url, '.json') !== false) {
			$json = json_decode($body, true);
			
			if (is_array($json)) {
				$body = $json;
			}
		}
		
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
