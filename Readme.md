# AOE Profiler

http://www.fabrizio-branca.de/magento-profiler.html

## Usage

Enable profiler in System > Configuration > Developer > Debug > Profiler.

* Trigger profiling by appending `?profile=1` to the url.
* If you're using PHPStorm and have the RemoteCall plugin installed append `?profile=1&links=1` to the url to enable profiling including links to PHPStorm (this might be a slower).

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

