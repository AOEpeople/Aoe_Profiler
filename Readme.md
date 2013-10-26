# Magento Profiler

http://www.fabrizio-branca.de/magento-profiler.html


## Usage

Enable profiler in System > Configuration > Developer > Debug > Profiler.

* Trigger profiling by appending `?profile=1` to the url.
* If you're using PHPStorm and have the RemoteCall plugin installed append `?profile=1&links=1` to the url to enable profiling including links to PHPStorm (this might be a slower).

## Profile cli scripts

To profile shell scripts like Mage_Shell_Compiler change code at the end of the file to:

```php
$_GET['profile'] = true;
require_once   '../app/Mage.php';
Varien_Profiler::start("wrapper");
$shell = new Mage_Shell_Compiler();
$shell->run();
Varien_Profiler::stop("wrapper");
Mage::helper('aoe_profiler')->renderProfilerOutputToFile();
```
You will find html page with rendered profiler output in var/log/profile<date>.html

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
