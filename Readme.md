# Magento Profiler

http://www.fabrizio-branca.de/magento-profiler.html


## Usage

Enable profiler in System > Configuration > Developer > Debug > Profiler.
Then trigger profiling can be done by appending ?profile=1 to the url.

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