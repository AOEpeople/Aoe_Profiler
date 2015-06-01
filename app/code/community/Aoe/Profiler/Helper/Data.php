<?php

class Aoe_Profiler_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_PROFILE_DIR = 'dev/debug/profileDir';

    public function format_time($duration, $precision = 0)
    {
        return round($duration * 1000, $precision);
    }

    public function format_realmem($bytes)
    {
        return $this->format_emalloc($bytes);
    }

    public function format_emalloc($bytes)
    {
        $res = number_format($bytes / (1024 * 1024), 2);
        if ($res == '-0.00') {
            $res = '0.00'; // looks silly otherwise
        }
        return $res;
    }

    public function formatTimeDecorator($value)
    {
        return round($value, 3);
    }

    public function formatMemoryDecorator($value)
    {
        return $this->format_emalloc($value);
    }

    /**
     * Renders Magento page with profiler output to file
     * Useful when profiling cli scripts
     *
     * @param string $title
     * @return string|bool The filename where the profile data was stored or false if there was an error.
     */
    public function renderProfilerOutputToFile($title = 'Aoe_Profiler') {

        // Render HTML
        $layout = Mage::getModel('core/layout');
        $layout->getUpdate()->load(array('default','adminhtml_profiler_view'));
        $layout->generateXml();
        $layout->generateBlocks();
        $content = "<html><head>";

        $content .= $layout->getBlock('head')->toHtml();

        $content .= "</head><body>";

        $content .= $layout->getBlock('content')->toHtml();
        $content .= "</body></html>";

        // Save HTML to file
        $profileDir = Mage::getStoreConfig(self::XML_PATH_PROFILE_DIR,0) ?: Mage::getBaseDir('var') . DS . 'profile';
        if ( ! is_dir($profileDir)) {
            if ( ! @mkdir($profileDir, 0777)) {
                Mage::log("Aoe_Profiler could not mkdir: $profileDir");
                return FALSE;
            }
        }
        list($ms,$time) = explode(' ',microtime());
        list(,$ms) = explode('.',$ms);
        $fileName = $profileDir . DS . "$time-$ms.html";
        if ( ! @file_put_contents($fileName, $content)) {
            Mage::log("Aoe_Profiler could not write profiler file: $fileName");
            return FALSE;
        }
        return "$time-$ms.html";
    }

}
