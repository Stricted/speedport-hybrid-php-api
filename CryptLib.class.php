<?php
/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
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
	private function sentEncryptedRequest ($path, $fields, $cookie = false) {
		$count = count($fields);
		$fields = $this->encrypt(http_build_query($fields));
		return $this->sentRequest($path, $fields, $cookie, $count);
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
		
		$factory = new CryptLib\Cipher\Factory();
		$aes = $factory->getBlockCipher('rijndael-128');
		$aes->setKey($key);
		$mode = $factory->getMode('ccm', $aes, $iv, [ 'adata' => $adata, 'lSize' => 7]);
		
		$mode->decrypt($enc);
		
		return $mode->finish();
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
		
		$factory = new CryptLib\Cipher\Factory();
		$aes = $factory->getBlockCipher('rijndael-128');
		$aes->setKey($key);
		$mode = $factory->getMode('ccm', $aes, $iv, [ 'adata' => $adata, 'lSize' => 7]);
		$mode->encrypt($data);
		
		return bin2hex($mode->finish());
	}
}
