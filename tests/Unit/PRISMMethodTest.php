<?php

declare(strict_types=1);

use PRISM\PHPInSimMod;

test('PRISM has expected methods', function () {
    /**
     * This tests that the correct class is autoloading.
     * During Refactoring this will ultimately need to be altered or removed.
     */
    $PRISM = new \PRISM\PHPInSimMod();
    $classMethods = get_class_methods($PRISM);

    expect($classMethods)->toContain(
        '__construct',
        '_errorHandler',
        'initialise',
        'start',
        '__destruct',
    );
});
