<?php

/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
 */
interface ISpeedport {
	/**
	 * login into the router with the given password
	 * 
	 * @param	string	$password
	 */
	public function login ($password);
	
	/**
	 * get the csrf_token
	 * 
	 * @return	string
	 */
	protected function getToken ();
}
