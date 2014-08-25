<?php
/**
 * Class Aoe_Profiler_Test_Model_Profiler
 *
 * @author Fabrizio Branca
 * @since 2014-08-24
 */
class Aoe_Profiler_Test_Model_Profiler extends EcomDev_PHPUnit_Test_Case {

    /**
     * @test
     */
    public function triggerNever() {
        Varien_Profiler::reInit();

        $mockConfig = new stdClass();
        $mockConfig->trigger = 'never';

        Varien_Profiler::setConfiguration($mockConfig);
        $this->assertFalse(Varien_Profiler::isEnabled());
    }

    /**
     * @test
     */
    public function triggerAlwaysNoFilters() {
        Varien_Profiler::reInit();

        $mockConfig = new stdClass();
        $mockConfig->trigger = 'always';
        $mockConfig->enableFilters = false;
        $mockConfig->captureModelInfo = false;
        $mockConfig->captureBacktraces = false;

        Varien_Profiler::setConfiguration($mockConfig);
        $this->assertTrue(Varien_Profiler::isEnabled());
    }

    /**
     * @test
     * @dataProvider samplingRates
     */
    public function triggerAlwaysSampling($rate, $expectedMax, $expectedMin) {
        $mockConfig = new stdClass();
        $mockConfig->trigger = 'always';
        $mockConfig->enableFilters = true;
        $mockConfig->filters = new stdClass();
        $mockConfig->filters->sampling = $rate;
        $mockConfig->filters->timeThreshold = 0;
        $mockConfig->filters->memoryThreshold = 0;
        $mockConfig->filters->requestUriWhiteList = '';
        $mockConfig->filters->requestUriBlackList = '';
        $mockConfig->captureModelInfo = false;
        $mockConfig->captureBacktraces = false;

        $count = 0;
        $maxRequests = 100000;

        for ($i=0; $i<$maxRequests; $i++) {
            Varien_Profiler::reInit();
            Varien_Profiler::setConfiguration($mockConfig);
            if (Varien_Profiler::isEnabled()) {
                $count++;
            }
        }

        // let's allow a range of +-10%
        $this->assertLessThanOrEqual($expectedMax, $count);
        $this->assertGreaterThanOrEqual($expectedMin, $count);
    }

    public function samplingRates() {
        return array(
            array(100, 100000, 100000),
            array(1, 1100, 900),
            array(50, 52000, 48000),
            array(0.1, 200, 50),
            array(0.01, 50, 0)
        );
    }

    /**
     * @test
     */
    public function checkTimeThresholdTooFast() {
        $mockConfig = new stdClass();
        $mockConfig->trigger = 'always';
        $mockConfig->enableFilters = true;
        $mockConfig->filters = new stdClass();
        $mockConfig->filters->sampling = 100;
        $mockConfig->filters->timeThreshold = 5;
        $mockConfig->filters->memoryThreshold = 0;
        $mockConfig->filters->requestUriWhiteList = '';
        $mockConfig->filters->requestUriBlackList = '';
        $mockConfig->captureModelInfo = false;
        $mockConfig->captureBacktraces = false;

        Varien_Profiler::reInit();
        Varien_Profiler::setConfiguration($mockConfig);

        Varien_Profiler::start('TEST');
        Varien_Profiler::stop('TEST');

        $this->assertFalse(Varien_Profiler::checkThresholds());

    }

    /**
     * @test
     */
    public function checkTimeThresholdSlowEnough() {
        $mockConfig = new stdClass();
        $mockConfig->trigger = 'always';
        $mockConfig->enableFilters = true;
        $mockConfig->filters = new stdClass();
        $mockConfig->filters->sampling = 100;
        $mockConfig->filters->timeThreshold = 2;
        $mockConfig->filters->memoryThreshold = 0;
        $mockConfig->filters->requestUriWhiteList = '';
        $mockConfig->filters->requestUriBlackList = '';
        $mockConfig->captureModelInfo = false;
        $mockConfig->captureBacktraces = false;

        Varien_Profiler::reInit();
        Varien_Profiler::setConfiguration($mockConfig);

        Varien_Profiler::start('TEST');
        sleep(3);
        Varien_Profiler::stop('TEST');

        $this->assertTrue(Varien_Profiler::checkThresholds());
    }

    /**
     * @test
     */
    public function checkMemoryThresholdNoMemory() {
        $mockConfig = new stdClass();
        $mockConfig->trigger = 'always';
        $mockConfig->enableFilters = true;
        $mockConfig->filters = new stdClass();
        $mockConfig->filters->sampling = 100;
        $mockConfig->filters->timeThreshold = 0;
        $mockConfig->filters->memoryThreshold = 5*1024; // 5KB
        $mockConfig->filters->requestUriWhiteList = '';
        $mockConfig->filters->requestUriBlackList = '';
        $mockConfig->captureModelInfo = false;
        $mockConfig->captureBacktraces = false;

        Varien_Profiler::reInit();
        Varien_Profiler::setConfiguration($mockConfig);

        Varien_Profiler::start('TEST');
        Varien_Profiler::stop('TEST');

        $this->assertFalse(Varien_Profiler::checkThresholds());

    }

    /**
     * @test
     */
    public function checkMemoryThresholdEnoughMemory() {
        $mockConfig = new stdClass();
        $mockConfig->trigger = 'always';
        $mockConfig->enableFilters = true;
        $mockConfig->filters = new stdClass();
        $mockConfig->filters->sampling = 100;
        $mockConfig->filters->timeThreshold = 0;
        $mockConfig->filters->memoryThreshold = 5*1024*1024; // 5MB
        $mockConfig->filters->requestUriWhiteList = '';
        $mockConfig->filters->requestUriBlackList = '';
        $mockConfig->captureModelInfo = false;
        $mockConfig->captureBacktraces = false;

        Varien_Profiler::reInit();
        Varien_Profiler::setConfiguration($mockConfig);

        Varien_Profiler::start('TEST');
        $memory = array();
        for ($i=0; $i<6*1024; $i++) {
            $memory[] = str_repeat('M', 1024);
        }
        Varien_Profiler::stop('TEST');

        $this->assertTrue(Varien_Profiler::checkThresholds());
    }

}
