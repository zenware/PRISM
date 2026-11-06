<?php

require_once __DIR__ . '/../packet_cases.php';

function test_packet_fixtures()
{
    static $fixtures = null;
    if ($fixtures === null) {
        $file = __DIR__ . '/../fixtures/packets.php';
        if (!file_exists($file)) {
            test_fail('Missing fixtures/packets.php; run tests/tools/capture_packets.php first');
        }
        $fixtures = include $file;
    }
    return $fixtures;
}

function test_packet_golden_bytes()
{
    $fixtures = test_packet_fixtures();
    assertTrue(is_array($fixtures) && count($fixtures) > 0, 'packet fixtures are empty');

    foreach ($fixtures as $class => $expectedHex) {
        $packet = test_packet_make($class);
        ob_start();
        $bytes = $packet->pack();
        ob_end_clean();
        assertHex($expectedHex, $bytes, $class . ' pack()');
    }
}

function test_packet_coverage()
{
    $fixtures = test_packet_fixtures();
    foreach (test_packet_classes() as $class) {
        assertTrue(isset($fixtures[$class]), 'Missing fixture for ' . $class);
    }
}

function test_packet_size_byte()
{
    $skip = test_packet_size_skip_classes();
    foreach (test_packet_fixtures() as $class => $hex) {
        if (isset($skip[$class]) || !property_exists($class, 'Size')) {
            continue;
        }
        $bytes = test_hex_bytes($hex);
        $packet = test_packet_make($class);
        ob_start();
        $packet->pack(); // Some packets (IS_AXM) compute Size inside pack().
        ob_end_clean();
        assertSame(strlen($bytes), ord($bytes[0]), $class . ' size byte');
        if (property_exists($class, 'Size')) {
            assertSame(strlen($bytes), $packet->Size, $class . ' declared Size');
        }
    }
}

function test_packet_type_byte()
{
    foreach (test_packet_fixtures() as $class => $hex) {
        if (!property_exists($class, 'Type')) {
            continue;
        }
        $bytes = test_hex_bytes($hex);
        $packet = test_packet_make($class);
        assertSame($packet->Type, ord($bytes[1]), $class . ' type byte');
    }
}

function test_packet_roundtrip_fixed_size()
{
    $skip = test_packet_size_skip_classes();
    foreach (test_packet_fixtures() as $class => $hex) {
        if (isset($skip[$class])) {
            continue;
        }
        $bytes = test_hex_bytes($hex);
        if (ord($bytes[0]) !== strlen($bytes)) {
            continue;
        }
        if (!defined($class . '::UNPACK')) {
            continue;
        }
        $packet = new $class($bytes);
        ob_start();
        $again = $packet->pack();
        ob_end_clean();
        assertHex($hex, $again, $class . ' unpack()->pack()');
    }
}

function test_packet_chain_and_get()
{
    $packet = IS_TINY();
    $packet->ReqI(1)->SubT(TINY_VER);
    assertSame(1, $packet->ReqI);
    assertSame(TINY_VER, $packet->SubT);
    assertHex('04030101', $packet->pack(), 'IS_TINY chained fields');
}

function test_packet_extra_property_is_ignored()
{
    $packet = IS_TINY();
    $packet->ReqI(1)->SubT(TINY_VER);
    $packet->Extra = 'not-on-the-wire';
    assertHex('04030101', $packet->pack(), 'IS_TINY with a dynamic property');
}

function test_packet_invoke()
{
    $packet = IS_TINY();
    $packet(1, TINY_VER);
    assertSame(1, $packet->ReqI);
    assertSame(TINY_VER, $packet->SubT);
    assertHex('04030101', $packet->pack(), 'IS_TINY __invoke');
}

function test_packet_send_path()
{
    global $PRISM;
    $PRISM->hosts->sent = array();

    $packet = IS_TINY();
    $packet->ReqI(1)->SubT(TINY_VER);
    $packet->send('unit-host');

    assertSame(1, count($PRISM->hosts->sent), 'send() did not reach HostHandler');
    assertSame('unit-host', $PRISM->hosts->sent[0]['host']);
    assertHex('04030101', $PRISM->hosts->sent[0]['packet']->pack(), 'sent packet bytes');
}

function test_packet_unpack_tiny()
{
    $packet = new IS_TINY(test_hex_bytes('04030101'));
    assertSame(4, $packet->Size);
    assertSame(ISP_TINY, $packet->Type);
    assertSame(1, $packet->ReqI);
    assertSame(TINY_VER, $packet->SubT);
}

function test_packet_endianness()
{
    assertHex('3412', pack('v', 0x1234), 'pack v');
    assertHex('78563412', pack('V', 0x12345678), 'pack V');
    assertHex('0000c03f', pack('f', 1.5), 'pack f');
    assertSame(4, strlen(pack('l', 1)), 'pack l must stay 32-bit');
}

return array(
    'packet/fixtures'            => 'test_packet_fixtures',
    'packet/golden-bytes'        => 'test_packet_golden_bytes',
    'packet/coverage'            => 'test_packet_coverage',
    'packet/size-byte'           => 'test_packet_size_byte',
    'packet/type-byte'           => 'test_packet_type_byte',
    'packet/roundtrip'           => 'test_packet_roundtrip_fixed_size',
    'packet/chain-and-get'       => 'test_packet_chain_and_get',
    'packet/extra-property'      => 'test_packet_extra_property_is_ignored',
    'packet/invoke'              => 'test_packet_invoke',
    'packet/send-path'           => 'test_packet_send_path',
    'packet/unpack-tiny'         => 'test_packet_unpack_tiny',
    'packet/endianness'          => 'test_packet_endianness',
);
