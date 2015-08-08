<?php

/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
 */
class Speedport {
	/**
	 * router url
	 * @var	string
	 */
	protected $url = '';
	
	/**
	 * hashed password
	 * @var	string
	 */
	protected $hash = '';
	
	/**
	 * session cookie
	 * @var	string
	 */
	protected $cookie = '';
	
	/**
	 * csrf_token
	 * @var	string
	 */
	protected $token = '';
	
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
	protected function checkRequirements () {
	}
	
	/**
	 * get the values from array
	 * 
	 * @param	array	$array
	 * @return	array
	 */
	protected function getValues($array) {
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
	protected function sentRequest ($path, $fields, $cookie = false, $count = 0) {
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
			curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
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
