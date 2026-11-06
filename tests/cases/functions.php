<?php

function test_version_source()
{
    assertSame('0.7.0', PRISM_VERSION);
    assertSame(PRISM_VERSION, PHPInSimMod::VERSION);
}

function test_flags_to_integer()
{
    assertSame(7, flagsToInteger('abc'));
    assertSame(7, flagsToInteger('aabc'));
    assertSame(5, flagsToInteger('a1c'));
    assertSame(67108863, flagsToInteger('abcdefghijklmnopqrstuvwxyz'));
    assertFalse(flagsToInteger(''));
}

function test_flags_to_string()
{
    assertSame('abc', flagsToString(7));
    assertSame('', flagsToString(0));
    assertSame('abcdefghijklmnopqrstuvwxyz', flagsToString(134217727));
    assertSame(12345, flagsToInteger(flagsToString(12345)));
}

function test_time_formatting()
{
    assertSame('0:00.000', timeToString(0));
    assertSame('1:01.000', timeToString(61000));
    assertSame('1:01:01.000', timeToString(3661000));
    assertSame('1:01:01.000', timeToStr(3661000));
}

function test_ip_validation()
{
    $ip = '127.0.0.1';
    assertSame('127.0.0.1', verifyIP($ip));
    $bad = '999.1.1.1';
    assertFalse(verifyIP($bad));
}

function test_dir_in_dir()
{
    assertTrue(isDirInDir('/a/b', '/a/b/c'));
    assertTrue(isDirInDir('/a/b/', '/a/b/c'));
    assertFalse(isDirInDir('/a/b', '/a/c'));
    assertFalse(isDirInDir('/a/b', '/a/bc'));
}

function test_random_string_shape()
{
    $hex = createRandomString(16, RAND_HEX);
    assertSame(32, strlen($hex));
    assertTrue((bool) preg_match('/^[0-9a-f]+$/', $hex));
    assertSame(8, strlen(createRandomString(8, RAND_NUMERIC)));
}

function test_ucwords_by_char()
{
    assertSame('Foo-Bar', ucwordsByChar('foo-bar', '-'));
    assertSame('One Two', ucwordsByChar('one two', ' '));
}

function test_directory_structure()
{
    $dir = sys_get_temp_dir() . '/prism-test-' . uniqid();
    mkdir($dir);
    mkdir($dir . '/sub');
    file_put_contents($dir . '/a.php', 'a');
    file_put_contents($dir . '/b.txt', 'b');
    file_put_contents($dir . '/sub/c.php', 'c');

    $dirBase = $dir . '/';
    $flat = get_dir_structure($dirBase, false, '.php');
    $files = array();
    foreach ($flat as $item) {
        if (is_string($item)) {
            $files[] = $item;
        }
    }
    sort($files);
    assertSame(array('a.php'), $files);

    $nested = get_dir_structure($dirBase, true, '.php');
    assertTrue(isset($nested['sub']) && in_array('c.php', $nested['sub']));

    unlink($dir . '/a.php');
    unlink($dir . '/b.txt');
    unlink($dir . '/sub/c.php');
    rmdir($dir . '/sub');
    rmdir($dir);
}

return array(
    'functions/version'          => 'test_version_source',
    'functions/flags-to-integer' => 'test_flags_to_integer',
    'functions/flags-to-string'  => 'test_flags_to_string',
    'functions/time-formatting'  => 'test_time_formatting',
    'functions/ip-validation'    => 'test_ip_validation',
    'functions/dir-in-dir'       => 'test_dir_in_dir',
    'functions/random-string'    => 'test_random_string_shape',
    'functions/ucwords-by-char'  => 'test_ucwords_by_char',
    'functions/dir-structure'    => 'test_directory_structure',
);
