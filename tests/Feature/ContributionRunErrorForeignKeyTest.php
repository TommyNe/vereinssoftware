<?php

it('sets the member reference to null when the member is deleted', function (): void {
    $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();

    $clubId = (string) Str::uuid();
    $contributionTypeId = (string) Str::uuid();
    $memberId = (string) Str::uuid();
    $contributionRunId = (string) Str::uuid();
    $now = now();

    DB::table('clubs')->insert([
        'id' => $clubId,
        'name' => 'Test Club',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('contribution_types')->insert([
        'id' => $contributionTypeId,
        'club_id' => $clubId,
        'name' => 'Annual contribution',
        'interval' => 'yearly',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('members')->insert([
        'uuid' => $memberId,
        'club_id' => $clubId,
        'member_number' => '0001',
        'first_name' => 'Test',
        'last_name' => 'Member',
        'joined_at' => '2026-01-01',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('contribution_runs')->insert([
        'id' => $contributionRunId,
        'club_id' => $clubId,
        'contribution_type_id' => $contributionTypeId,
        'status' => 'pending',
        'calculation_date' => '2026-01-01',
        'period_from' => '2026-01-01',
        'due_date' => '2026-02-01',
        'description' => 'Test run',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('contribution_run_errors')->insert([
        'contribution_run_id' => $contributionRunId,
        'member_id' => $memberId,
        'message' => 'Test error',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('members')->where('uuid', $memberId)->delete();

    expect(DB::table('contribution_run_errors')->value('member_id'))->toBeNull();
});
