<?php
/**
 * Dependency-free test runner. Runs on PHP 5.6 and PHP 8.x.
 * Usage: php tests/run.php [filter]
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE & ~E_USER_NOTICE);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/assertions.php';

function test_error_handler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        return false;
    }

    if ($errno === E_USER_ERROR || $errno === E_RECOVERABLE_ERROR
        || $errno === E_WARNING || $errno === E_USER_WARNING) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    return true;
}

set_error_handler('test_error_handler');

$GLOBALS['test_count'] = 0;
$GLOBALS['test_failures'] = 0;
$GLOBALS['test_filter'] = isset($argv[1]) ? $argv[1] : '';

function test_report($name, $ok, $detail = '')
{
    $GLOBALS['test_count']++;
    if ($ok) {
        echo "ok - {$name}\n";
    } else {
        $GLOBALS['test_failures']++;
        echo "not ok - {$name}" . ($detail !== '' ? ": {$detail}" : '') . "\n";
    }
}

$cases = array();
foreach (glob(__DIR__ . '/cases/*.php') as $file) {
    $loaded = include $file;
    if (!is_array($loaded)) {
        test_report(basename($file), false, 'case file did not return an array');
        continue;
    }
    foreach ($loaded as $name => $callback) {
        if ($GLOBALS['test_filter'] !== '' && strpos($name, $GLOBALS['test_filter']) === false) {
            continue;
        }
        $cases[$name] = $callback;
    }
}

foreach ($cases as $name => $callback) {
    try {
        call_user_func($callback);
        test_report($name, true);
    } catch (Exception $e) {
        test_report($name, false, $e->getMessage());
    } catch (Throwable $e) {
        // PHP 8 only; the catch type is inert on 5.6.
        test_report($name, false, get_class($e) . ': ' . $e->getMessage());
    }
}

echo "\n" . $GLOBALS['test_count'] . " tests, " . $GLOBALS['test_failures'] . " failures\n";
exit($GLOBALS['test_failures'] === 0 ? 0 : 1);
