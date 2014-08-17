<?php

class Aoe_Profiler_Helper_Data extends Mage_Core_Helper_Abstract
{

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

}
