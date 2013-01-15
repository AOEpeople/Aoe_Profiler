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

	/**
	 * Renders Magento page with profiler output to file
	 * Useful when profiling cli scripts
	 */
	public function renderProfilerOutputToFile() {

		$layout = Mage::app()->getLayout();
		$layout->getUpdate()->addHandle(array('default', 'page_one_column'));
		$layout->getUpdate()->load();
		$layout->generateXml()->generateBlocks();

		$root = $layout->getBlock('root');
		$template = "page/empty.phtml";
		$root->setTemplate($template);
		$block = $layout->createBlock('core/profiler', 'profiler');
		/** @var $content Mage_Core_Block_Text_List */
		$content = $root->getChild('content');
		$content->append($block, 'profiler_output');
		$content = $root->toHtml();
		file_put_contents(Mage::getBaseDir('var') . DS . 'log' . DS . "profile".time().".html", $content);
	}
}
