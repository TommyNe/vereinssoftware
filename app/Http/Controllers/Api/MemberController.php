<?php

namespace App\Http\Controllers\Api;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Commands\ChangeMemberAddress;
use App\Application\Membership\Commands\RegisterMember;
use App\Application\Membership\Handlers\ChangeMemberAddressHandler;
use App\Application\Membership\Handlers\RegisterMemberHandler;
use App\Domain\Membership\Models\Member;
use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\ChangeMemberAddressRequest;
use App\Http\Requests\Membership\RegisterMemberRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class MemberController extends Controller
{
    public function store(
        RegisterMemberRequest $request,
        RegisterMemberHandler $handler,
        CurrentClub $currentClub
    ): JsonResponse {
        $memberId = (string) Str::uuid();

        $handler->handle(
            new RegisterMember(
                memberId: $memberId,
                clubId: $currentClub->id(),
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

    public function changeAddress(
        ChangeMemberAddressRequest $request,
        string $member,
        ChangeMemberAddressHandler $handler,
    ): JsonResponse {
        $readModel = Member::query()
            ->forCurrentClub()
            ->findOrFail($member);

        $handler->handle(
            new ChangeMemberAddress(
                memberId: $readModel->id,
                street: $request
                    ->string('street')
                    ->trim()
                    ->toString(),
                houseNumber: $request
                    ->string('house_number')
                    ->trim()
                    ->toString(),
                postalCode: $request
                    ->string('postal_code')
                    ->trim()
                    ->toString(),
                city: $request
                    ->string('city')
                    ->trim()
                    ->toString(),
                countryCode: strtoupper(
                    $request
                        ->string('country_code')
                        ->trim()
                        ->toString()
                ),
            )
        );

        $readModel->refresh();

        return response()->json([
            'data' => $readModel,
        ]);
    }
}
