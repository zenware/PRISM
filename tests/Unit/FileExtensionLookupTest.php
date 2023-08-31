<?php

declare(strict_types=1);

use PRISM\Modules\file;

test('test known media type lookup', function (string $extension, string $mediatype) {

    $result = file::extensionLookup($extension);
    expect($result)
        ->toBeString()
        ->toBe($mediatype);
})->with([
    ['3gp', 'video/3gpp'],
    ['cpp', 'text/x-c'],
    ['exe', 'application/x-msdownload']
]);

test('test fallback media type lookup', function (string $extension) {

    $result = file::extensionLookup($extension);
    $fallback_response = 'application/octet-stream';
    expect($result)
        ->toBeString()
        ->toBe($fallback_response);
})->with(['9999', 'asdfj02', '!@#$:{']);
