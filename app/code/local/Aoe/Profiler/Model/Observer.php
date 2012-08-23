<?php

class Aoe_Profiler_Model_Observer {

	public function core_block_abstract_to_html_before($event) {
		$block = $event->getBlock(); /* @var $block Mage_Core_Block_Abstract */
		Varien_Profiler::start($block->getNameInLayout(), Varien_Profiler::TYPE_BLOCK);
	}

	public function core_block_abstract_to_html_after($event) {
		$block = $event->getBlock(); /* @var $block Mage_Core_Block_Abstract */
		Varien_Profiler::stop($block->getNameInLayout());
	}

}