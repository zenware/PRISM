<?php

function test_pth_minimal_file()
{
    $header = pack('a6CCVV', 'LFSPTH', 0, 0, 2, 1);
    $node = pack('l3', 1, 2, 3)
          . pack('f3', 0, 0, 0)
          . pack('f2', 1, 1)
          . pack('f2', 0, 0);

    $file = tempnam(sys_get_temp_dir(), 'prism-pth-');
    file_put_contents($file, $header . $node . $node);

    $path = new Path($file);
    assertSame('LFSPTH', $path->LFSPTH);
    assertSame(0, $path->Version);
    assertSame(0, $path->Revision);
    assertSame(2, $path->NumNodes);
    assertSame(2, count($path->Nodes));
    assertSame(3, count($path->polyRoad)); // two segments plus the closing polygon
    assertSame(3, count($path->polyLimit));

    // Historical name used by plugins/LVS.php.
    assertTrue(class_exists('PTH', false));
    $legacy = new PTH($file);
    assertTrue($legacy instanceof Path);

    unlink($file);
}

return array(
    'pth/minimal-file' => 'test_pth_minimal_file',
);
