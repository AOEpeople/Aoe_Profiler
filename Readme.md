# AOE Profiler

http://www.fabrizio-branca.de/magento-profiler.html

## Usage

Copy the aoe_profiler.xml.sample file to var/aoe_profiler.xml and find some documentation on what the settings mean in that file.

Find some more settings in System > Configuration > Developer > Debug > Profiler.

## Enable database profiling

Add this to your local.xml:

```xml
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
```

## Notes

Depending on the complexity of your site a lot of data ends up being collected during a profile run. The first problem with this is that the database table
holding this information might be growing. Please double check the cron settings of the cleanup task.

By default MySQL comes with max_allowed_packet set to 1 MB. One profile run could exceed 1 MB. Please check var/system.log for error messages and increase this setting in our MySQL server settings (/etc/mysql/myconf). (Also see: http://dev.mysql.com/doc/refman/5.1/en/packet-too-large.html)

```
[mysqld]
max_allowed_packet=16M
```