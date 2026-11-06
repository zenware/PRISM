<?php
/**
 * Emit a stable name/hex transcript for cross-version diffing.
 * Usage: php tests/tools/dump_packets.php
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE & ~E_USER_NOTICE);

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../packet_cases.php';

foreach (test_packet_classes() as $class) {
    $packet = test_packet_make($class);
    ob_start();
    $bytes = $packet->pack();
    ob_end_clean();
    echo $class . ' ' . bin2hex($bytes) . PHP_EOL;
}
