<?php

function test_connection_find_next_packet()
{
    $vars = array(
        'connType' => CONNTYPE_HOST,
        'socketType' => SOCKTYPE_TCP,
        'id' => 'test',
        'ip' => '127.0.0.1',
        'port' => 0,
        'flags' => 0,
        'pps' => 1,
        'adminPass' => '',
        'prefix' => '!',
    );
    $conn = new InsimConnection($vars);

    $first = test_hex_bytes('04030000');
    $second = test_hex_bytes('04030101');

    $buffer = $first . $second;
    $conn->appendToBuffer($buffer);
    assertHex(bin2hex($first), $conn->findNextPacket());
    assertHex(bin2hex($second), $conn->findNextPacket());
    assertFalse($conn->findNextPacket());

    $partial = substr($first, 0, 2);
    $conn->clearBuffer();
    $conn->appendToBuffer($partial);
    assertFalse($conn->findNextPacket());
    $rest = substr($first, 2);
    $conn->appendToBuffer($rest);
    assertHex(bin2hex($first), $conn->findNextPacket());
}

function test_connection_zero_size_is_stable()
{
    $vars = array(
        'connType' => CONNTYPE_HOST,
        'socketType' => SOCKTYPE_TCP,
        'id' => 'test',
        'ip' => '127.0.0.1',
        'port' => 0,
        'flags' => 0,
        'pps' => 1,
        'adminPass' => '',
        'prefix' => '!',
    );
    $conn = new InsimConnection($vars);
    $zero = "\x00";
    $conn->appendToBuffer($zero);
    assertFalse($conn->findNextPacket());
}

return array(
    'connection/find-next-packet' => 'test_connection_find_next_packet',
    'connection/zero-size'        => 'test_connection_zero_size_is_stable',
);
