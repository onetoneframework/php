<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

echo "<?php\n";

echo "
// https://www.cloudflare.com/ips-v4/#\n
// https://www.cloudflare.com/ips-v6/#\n
";

echo "return [\n";
foreach ($ipRanges as $ip) {
    $parts = explode("\n", $ip);
    echo "\t'".str_replace("\n","/",$ip)."',\n";
}
echo '];';
