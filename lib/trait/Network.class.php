<?php
/**
 * @author      Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @copyright   2015 Jan Altensen (Stricted)
 */
trait Network {
	/**
	 * get all devices in network
	 *
	 * @return	array
	 */
	public function lanDeviceOverview () {
		$data = $this->getData('LAN');
		$data = $this->getValues($data);
		
		if (isset($data['addmdevice'])) {
			return $data['addmdevice'];
		}
		else {
			return array();
		}
	}
}
