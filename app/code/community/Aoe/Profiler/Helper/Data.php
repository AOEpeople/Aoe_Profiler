<?php

class Aoe_Profiler_Helper_Data extends Mage_Core_Helper_Abstract {

	public function format_time($duration, $precision=0) {
		return round($duration * 1000, $precision);
	}

	public function format_realmem($bytes) {
		return $this->format_emalloc($bytes);
	}

	public function format_emalloc($bytes) {
		$res =  number_format($bytes / (1024 * 1024), 2);
		if ($res == '-0.00') {
			$res = '0.00'; // looks silly otherwise
		}
		return $res;
	}

	/**
	 * Get skin file content
	 *
	 * @param $file
	 * @return string
	 */
	public function getSkinFileContent($file) {
		$package = Mage::getSingleton('core/design_package');
		$areaBackup = $package->getArea();
		$path = $package
			->setArea('frontend')
			->getFilename($file, array('_type' => 'skin'));
		$content = file_get_contents($path);
		$package->setArea($areaBackup);
		return $content;
	}

}
