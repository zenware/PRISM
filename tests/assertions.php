<?php

function test_fail($message)
{
    throw new Exception($message);
}

function assertTrue($condition, $message = '')
{
    if ($condition !== true) {
        test_fail($message !== '' ? $message : 'Expected true');
    }
}

function assertFalse($condition, $message = '')
{
    if ($condition !== false) {
        test_fail($message !== '' ? $message : 'Expected false');
    }
}

function assertSame($expected, $actual, $message = '')
{
    if ($expected !== $actual) {
        test_fail(
            ($message !== '' ? $message . ': ' : '') .
            'expected ' . var_export($expected, true) .
            ', got ' . var_export($actual, true)
        );
    }
}

function assertNotSame($expected, $actual, $message = '')
{
    if ($expected === $actual) {
        test_fail(($message !== '' ? $message . ': ' : '') . 'values are identical');
    }
}

function assertHex($expectedHex, $bytes, $message = '')
{
    assertSame(strtolower($expectedHex), bin2hex($bytes), $message);
}

function assertBytes($expectedBytes, $bytes, $message = '')
{
    assertHex(bin2hex($expectedBytes), $bytes, $message);
}

function assertArrayKey($key, $expected, array $array, $message = '')
{
    if (!array_key_exists($key, $array)) {
        test_fail(($message !== '' ? $message . ': ' : '') . 'missing key ' . var_export($key, true));
    }
    assertSame($expected, $array[$key], $message);
}

function assertThrows($callback, $message = '')
{
    try {
        call_user_func($callback);
    } catch (Exception $e) {
        return $e;
    }
    test_fail($message !== '' ? $message : 'Expected an exception');
}

function test_hex_bytes($hex)
{
    return pack('H*', $hex);
}
