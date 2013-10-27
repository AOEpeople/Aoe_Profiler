# Magento Profiler

http://www.fabrizio-branca.de/magento-profiler.html


## Usage

Enable profiler in System > Configuration > Developer > Debug > Profiler.

* Trigger profiling by appending `?profile=1` to the url.
* If you're using PHPStorm and have the RemoteCall plugin installed append `?profile=1&links=1` to the url to enable profiling including links to PHPStorm (this might be a slower).

## Profile cli scripts

To profile shell scripts like `Mage_Shell_Compiler` change code at the end of the file to:

```php
$_GET['profile'] = true;
require_once   '../app/Mage.php';
Varien_Profiler::start("wrapper");
$shell = new Mage_Shell_Compiler();
$shell->run();
Varien_Profiler::stop("wrapper");
Mage::helper('aoe_profiler')->renderProfilerOutputToFile();
```

You will find html page with rendered profiler output in var/profile/{date}.html

Profile only slow requests in production
========================================

To profile slow requests in production you will need to modify index.php.
*Warning*: This *will* have an impact on overall performance as the timers will be
recorded for every request but only rendered for slow requests.

On the 2nd line, add:

```php
$_slowRequestTime = 10.0;  // Set to FALSE to disable
if ($_slowRequestTime) $_start = microtime(TRUE);
```

Before `Mage::run...)` add:

```php
if ($_slowRequestTime) Varien_Profiler::enable();
```

After `Mage::run(...)` add:
```php
if ($_slowRequestTime) {
    $_elapsed = microtime(true) - $_start;
    if ($_elapsed > $_slowRequestTime) {
        $_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'unknown';
        $_profileName = Mage::helper('aoe_profiler')->renderProfilerOutputToFile($_uri);
        // Log, email, etc..
        Mage::log("Request for $_uri took $_elapsed seconds. See profiler output in $_profileName", Zend_Log::INFO, 'slow_requests.log');
    }
}
```

## Enable database profiling

Add this to your local.xml:

    <config>
        <global>
            <resources>
                <default_setup>
                    <connection>
                        <profiler>1</profiler>
                    </connection>
                </default_setup>
            </resources>
        </global>
    </config>

