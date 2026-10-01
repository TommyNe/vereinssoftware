<?php

use App\Domain\Sepa\ValueObjects\Iban;

test('masking shows only the country code and last four characters without changing the IBAN', function (string $input, string $normalized, string $masked) {
    $iban = new Iban($input);

    $result = $iban->masked();

    expect($result)->toBe($masked);
    expect((string) $iban)->toBe($normalized);
})->with([
    'German IBAN' => ['DE89370400440532013000', 'DE89370400440532013000', 'DE•• •••• •••• •••• 3000'],
    'formatted lowercase IBAN' => ['de89 3704 0044 0532 0130 00', 'DE89370400440532013000', 'DE•• •••• •••• •••• 3000'],
    'shorter IBAN' => ['BE68539007547034', 'BE68539007547034', 'BE•• •••• •••• •••• 7034'],
]);
