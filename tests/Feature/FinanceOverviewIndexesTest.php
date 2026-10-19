<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('finance overview queries have composite indexes in the required column order', function (): void {
    expect(Schema::hasIndex('contribution_charges', ['club_id', 'status', 'due_date']))->toBeTrue();
    expect(Schema::hasIndex('payments', ['club_id', 'status', 'booking_date']))->toBeTrue();
});

test('rolling back the finance indexes preserves existing indexes', function (): void {
    $migration = require database_path('migrations/2026_10_19_105558_add_finance_overview_indexes_to_contribution_charges_and_payments.php');

    $migration->down();

    expect(Schema::hasIndex('contribution_charges', ['club_id', 'status', 'due_date']))->toBeFalse();
    expect(Schema::hasIndex('payments', ['club_id', 'status', 'booking_date']))->toBeFalse();
    expect(Schema::hasIndex('contribution_charges', ['club_id', 'due_date']))->toBeTrue();
    expect(Schema::hasIndex('contribution_charges', ['club_id', 'member_id', 'status']))->toBeTrue();
    expect(Schema::hasIndex('payments', ['club_id', 'status']))->toBeTrue();
    expect(Schema::hasIndex('payments', ['club_id', 'member_id', 'booking_date']))->toBeTrue();

    $migration->up();

    expect(Schema::hasIndex('contribution_charges', ['club_id', 'status', 'due_date']))->toBeTrue();
    expect(Schema::hasIndex('payments', ['club_id', 'status', 'booking_date']))->toBeTrue();
});
