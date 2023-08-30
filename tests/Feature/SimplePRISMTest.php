<?php

declare(strict_types=1);

use PRISM\PHPInSimMod;

test('prism_start_calls_main', function () {
    /**
     * PHPInSimMod->main() was originally private, which is impossible to test.
     * It has been converted to protected in order to support this.
     *
     * For some reason this is still attempting to launch network connections.
     * This tests that we're still able to load the code, and that if we call
     * it's methods, something will actually work. It does not guarantee that
     * the entire program will function correctly.
     */

    $mock = \Mockery::mock(PHPInSimMod::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();

    $mock->shouldReceive('initialise')->never();
    $mock->shouldReceive('start')
        ->withNoArgs()
        ->andReturnNull()
        ->passthru();
    $mock->shouldReceive('main')
        ->once()
        ->withNoArgs()
        ->andReturnNull();

    $mock->start();
    $mock->start();
});
