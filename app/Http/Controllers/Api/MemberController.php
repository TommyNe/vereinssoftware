<?php

namespace App\Http\Controllers\Api;

use App\Application\Club\CurrentClub;
use App\Application\Membership\Commands\AssignMemberFunction;
use App\Application\Membership\Commands\ChangeMemberAddress;
use App\Application\Membership\Commands\ChangeMemberContactData;
use App\Application\Membership\Commands\ChangeMemberPersonalData;
use App\Application\Membership\Commands\ChangeMembershipType;
use App\Application\Membership\Commands\EndMemberFunction;
use App\Application\Membership\Commands\JoinMemberDepartment;
use App\Application\Membership\Commands\LeaveMemberDepartment;
use App\Application\Membership\Commands\RegisterMember;
use App\Application\Membership\Handlers\AssignMemberFunctionHandler;
use App\Application\Membership\Handlers\ChangeMemberAddressHandler;
use App\Application\Membership\Handlers\ChangeMemberContactDataHandler;
use App\Application\Membership\Handlers\ChangeMemberPersonalDataHandler;
use App\Application\Membership\Handlers\ChangeMembershipTypeHandler;
use App\Application\Membership\Handlers\EndMemberFunctionHandler;
use App\Application\Membership\Handlers\JoinMemberDepartmentHandler;
use App\Application\Membership\Handlers\LeaveMemberDepartmentHandler;
use App\Application\Membership\Handlers\RegisterMemberHandler;
use App\Domain\Membership\Models\Member;
use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\AssignMemberFunctionRequest;
use App\Http\Requests\Membership\ChangeMemberAddressRequest;
use App\Http\Requests\Membership\ChangeMemberContactDataRequest;
use App\Http\Requests\Membership\ChangeMemberPersonalDataRequest;
use App\Http\Requests\Membership\ChangeMembershipTypeRequest;
use App\Http\Requests\Membership\EndMemberFunctionRequest;
use App\Http\Requests\Membership\JoinMemberDepartmentRequest;
use App\Http\Requests\Membership\LeaveMemberDepartmentRequest;
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
                memberId: $readModel->uuid,
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

    public function changeContactData(
        ChangeMemberContactDataRequest $request,
        string $member,
        ChangeMemberContactDataHandler $handler,
    ): JsonResponse {
        $readModel = Member::query()
            ->forCurrentClub()
            ->findOrFail($member);

        $data = $request->validated();

        $handler->handle(
            new ChangeMemberContactData(
                memberId: $readModel->uuid,
                email: $data['email'] ?? null,
                phone: $data['phone'] ?? null,
                mobile: $data['mobile'] ?? null,
            )
        );

        $readModel->refresh();

        return response()->json([
            'data' => $readModel,
        ]);
    }

    public function changePersonalData(
        ChangeMemberPersonalDataRequest $request,
        string $member,
        ChangeMemberPersonalDataHandler $handler,
    ): JsonResponse {
        $readModel = Member::query()
            ->forCurrentClub()
            ->findOrFail($member);

        $data = $request->validated();

        $handler->handle(
            new ChangeMemberPersonalData(
                memberId: $readModel->uuid,
                firstName: $data['first_name'],
                lastName: $data['last_name'],
                birthDate: $data['birth_date'] ?? null,
            )
        );

        $readModel->refresh();

        return response()->json([
            'data' => $readModel,
        ]);
    }

    public function changeMembershipType(
        ChangeMembershipTypeRequest $request,
        string $member,
        ChangeMembershipTypeHandler $handler,
    ): JsonResponse {
        $readModel = Member::query()
            ->forCurrentClub()
            ->findOrFail($member);

        $data = $request->validated();

        $handler->handle(
            new ChangeMembershipType(
                memberId: $readModel->uuid,
                membershipTypeId: $data['membership_type_id'],
            )
        );

        $readModel->refresh();

        return response()->json([
            'data' => $readModel,
        ]);
    }

    public function joinDepartment(
        JoinMemberDepartmentRequest $request,
        string $member,
        JoinMemberDepartmentHandler $handler,
    ): JsonResponse {
        $readModel = Member::query()
            ->forCurrentClub()
            ->findOrFail($member);

        $data = $request->validated();

        $handler->handle(
            new JoinMemberDepartment(
                memberId: $readModel->uuid,

                departmentId: $data['department_id'],

                joinedAt: CarbonImmutable::parse(
                    $data['joined_at']
                ),
            )
        );

        $readModel->load('departments');

        return response()->json([
            'data' => $readModel,
        ]);
    }

    public function leaveDepartment(
        LeaveMemberDepartmentRequest $request,
        string $member,
        string $department,
        LeaveMemberDepartmentHandler $handler,
    ): JsonResponse {
        $readModel = Member::query()
            ->forCurrentClub()
            ->findOrFail($member);

        $data = $request->validated();

        $handler->handle(
            new LeaveMemberDepartment(
                memberId: $readModel->uuid,

                departmentId: $department,

                leftAt: CarbonImmutable::parse(
                    $data['left_at']
                ),
            )
        );

        $readModel->load('departments');

        return response()->json([
            'data' => $readModel,
        ]);
    }

    public function assignFunction(
        AssignMemberFunctionRequest $request,
        string $member,
        AssignMemberFunctionHandler $handler,
    ): JsonResponse {
        $readModel = Member::query()
            ->forCurrentClub()
            ->findOrFail($member);

        $data = $request->validated();

        $handler->handle(
            new AssignMemberFunction(
                memberId: $readModel->uuid,

                clubFunctionId: $data['club_function_id'],

                validFrom: CarbonImmutable::parse(
                    $data['valid_from']
                ),
            )
        );

        $readModel->load([
            'activeFunctionAssignments.clubFunction',
        ]);

        return response()->json([
            'data' => $readModel,
        ]);
    }

    public function endFunction(
        EndMemberFunctionRequest $request,
        string $member,
        string $clubFunction,
        EndMemberFunctionHandler $handler,
    ): JsonResponse {
        $readModel = Member::query()
            ->forCurrentClub()
            ->findOrFail($member);

        $data = $request->validated();

        $handler->handle(
            new EndMemberFunction(
                memberId: $readModel->uuid,

                clubFunctionId: $clubFunction,

                validUntil: CarbonImmutable::parse(
                    $data['valid_until']
                ),
            )
        );

        $readModel->load([
            'functionAssignments.clubFunction',
        ]);

        return response()->json([
            'data' => $readModel,
        ]);
    }
}
