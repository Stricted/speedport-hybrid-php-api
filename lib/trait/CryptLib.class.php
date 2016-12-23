<?php
/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015-2016 Jan Altensen (Stricted)
 */
trait CryptLib {
	/**
	 * sends the encrypted request to router
	 * 
	 * @param	string	$path
	 * @param	mixed	$fields
	 * @param	string	$cookie
	 * @return	array
	 */
	private function sendEncryptedRequest ($path, $fields, $cookie = false) {
		$count = count($fields);
		$fields = $this->encrypt(http_build_query($fields));
		return $this->sendRequest($path, $fields, $cookie, $count);
	}
	
	/**
	 * decrypt data from router
	 * 
	 * @param	string	$data
	 * @return	array
	 */
	private function decrypt ($data) {
		$iv = hex2bin(substr($this->challenge, 16, 16));
		$adata = hex2bin(substr($this->challenge, 32, 16));
		$key = hex2bin($this->derivedk);
		$enc = hex2bin($data);
		
		if (PHP_VERSION_ID >= 70100) {
			$ciphertext = substr($enc, 0, -8);
			$tag = substr($enc, strlen($enc)-8);
			
			return openssl_decrypt($ciphertext, 'aes-128-ccm', $key, OPENSSL_RAW_DATA, $iv, $tag, $adata);
		}
		else {
			$factory = new CryptLib\Cipher\Factory();
			$aes = $factory->getBlockCipher('rijndael-128');
			$aes->setKey($key);
			$mode = $factory->getMode('ccm', $aes, $iv, [ 'adata' => $adata, 'lSize' => 7]);
			
			$mode->decrypt($enc);
			
			return $mode->finish();
		}
	}

	/**
	 * decrypt data for the router
	 * 
	 * @param	string	$data
	 * @return	string
	 */
	private function encrypt ($data) {
		$iv = hex2bin(substr($this->challenge, 16, 16));
		$adata = hex2bin(substr($this->challenge, 32, 16));
		$key = hex2bin($this->derivedk);
		
		if (empty($data)) {
			return $data;
		}
		
		if (PHP_VERSION_ID >= 70100) {
			$tag = null;
			$encdata = openssl_encrypt($data, 'aes-128-ccm', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv, $tag, $adata, 8);
			return bin2hex($encdata . $tag);
		}
		else {
			$factory = new CryptLib\Cipher\Factory();
			$aes = $factory->getBlockCipher('rijndael-128');
			$aes->setKey($key);
			$mode = $factory->getMode('ccm', $aes, $iv, [ 'adata' => $adata, 'lSize' => 7]);
			$mode->encrypt($data);
			var_dump(bin2hex($mode->finish()));
			return bin2hex($mode->finish());
		}
	}
}
