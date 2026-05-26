<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared!\n";
}
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "APCu cleared!\n";
}
echo "Done.";
