<?php

class Aoe_Profiler_Helper_Data extends Mage_Core_Helper_Abstract {

	public function format_time($duration) {
		return round($duration * 1000, 0);
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

	public function getSkinFileContent($file) {
		$path = Mage::getSingleton('core/design_package')
			->setArea('frontend')
			->getFilename($file, array('_type' => 'skin'));
		$content = file_get_contents($path);
		return $content;
	}

}
