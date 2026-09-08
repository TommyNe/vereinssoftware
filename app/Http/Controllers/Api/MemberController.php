<?php

namespace App\Http\Controllers\Api;

use App\Application\Membership\Commands\RegisterMember;
use App\Application\Membership\Handlers\RegisterMemberHandler;
use App\Domain\Membership\Models\Member;
use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\RegisterMemberRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class MemberController extends Controller
{
    public function store(
        RegisterMemberRequest $request,
        RegisterMemberHandler $handler,
    ): JsonResponse {
        $memberId = (string) Str::uuid();

        $handler->handle(
            new RegisterMember(
                memberId: $memberId,
                clubId: $request->string('club_id')->toString(),
                memberNumber: $request
                    ->string('member_number')
                    ->toString(),
                firstName: $request
                    ->string('first_name')
                    ->toString(),
                lastName: $request
                    ->string('last_name')
                    ->toString(),
                birthDate: $request->date('birth_date')
                    ? CarbonImmutable::parse(
                        $request->date('birth_date')
                    )
                    : null,
                joinedAt: CarbonImmutable::parse(
                    $request->date('joined_at')
                ),
            )
        );

        $member = Member::query()
            ->findOrFail($memberId);

        return response()->json(
            data: [
                'data' => $member,
            ],
            status: 201,
        );
    }
}
