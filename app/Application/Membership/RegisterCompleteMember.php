<?php

namespace App\Application\Membership;

use App\Application\Membership\Commands\AssignMemberFunction;
use App\Application\Membership\Commands\ChangeMemberAddress;
use App\Application\Membership\Commands\ChangeMemberContactData;
use App\Application\Membership\Commands\ChangeMembershipType;
use App\Application\Membership\Commands\JoinMemberDepartment;
use App\Application\Membership\Commands\RegisterMember;
use App\Application\Membership\Handlers\AssignMemberFunctionHandler;
use App\Application\Membership\Handlers\ChangeMemberAddressHandler;
use App\Application\Membership\Handlers\ChangeMemberContactDataHandler;
use App\Application\Membership\Handlers\ChangeMembershipTypeHandler;
use App\Application\Membership\Handlers\JoinMemberDepartmentHandler;
use App\Application\Membership\Handlers\RegisterMemberHandler;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class RegisterCompleteMember
{
    public function __construct(
        private RegisterMemberHandler $registerMember,
        private ChangeMemberAddressHandler $changeAddress,
        private ChangeMemberContactDataHandler $changeContactData,
        private ChangeMembershipTypeHandler $changeMembershipType,
        private JoinMemberDepartmentHandler $joinDepartment,
        private AssignMemberFunctionHandler $assignFunction,
    ) {}

    /**
     * @param  array<int, string>  $departmentIds
     * @param array<int, array{
     *     club_function_id: string,
     *     valid_from: string
     * }> $functions
     */
    public function handle(
        string $memberId,
        string $memberNumber,
        string $firstName,
        string $lastName,
        ?CarbonImmutable $birthDate,
        CarbonImmutable $joinedAt,
        ?string $membershipTypeId,
        ?string $street,
        ?string $houseNumber,
        ?string $postalCode,
        ?string $city,
        ?string $countryCode,
        ?string $email,
        ?string $phone,
        ?string $mobile,
        array $departmentIds = [],
        array $functions = [],
    ): void {
        DB::transaction(function () use (
            $memberId,
            $memberNumber,
            $firstName,
            $lastName,
            $birthDate,
            $joinedAt,
            $membershipTypeId,
            $street,
            $houseNumber,
            $postalCode,
            $city,
            $countryCode,
            $email,
            $phone,
            $mobile,
            $departmentIds,
            $functions,
        ): void {
            $this->registerMember->handle(
                new RegisterMember(
                    memberId: $memberId,
                    memberNumber: $memberNumber,
                    firstName: $firstName,
                    lastName: $lastName,
                    birthDate: $birthDate,
                    joinedAt: $joinedAt,
                )
            );

            if (
                $street !== null
                || $houseNumber !== null
                || $postalCode !== null
                || $city !== null
            ) {
                $this->changeAddress->handle(
                    new ChangeMemberAddress(
                        memberId: $memberId,
                        street: $street ?? '',
                        houseNumber: $houseNumber ?? '',
                        postalCode: $postalCode ?? '',
                        city: $city ?? '',
                        countryCode: $countryCode ?? 'DE',
                    )
                );
            }

            if (
                $email !== null
                || $phone !== null
                || $mobile !== null
            ) {
                $this->changeContactData->handle(
                    new ChangeMemberContactData(
                        memberId: $memberId,
                        email: $email,
                        phone: $phone,
                        mobile: $mobile,
                    )
                );
            }

            if ($membershipTypeId !== null) {
                $this->changeMembershipType->handle(
                    new ChangeMembershipType(
                        memberId: $memberId,
                        membershipTypeId: $membershipTypeId,
                    )
                );
            }

            foreach ($departmentIds as $departmentId) {
                $this->joinDepartment->handle(
                    new JoinMemberDepartment(
                        memberId: $memberId,
                        departmentId: $departmentId,
                        joinedAt: $joinedAt,
                    )
                );
            }

            foreach ($functions as $function) {
                $this->assignFunction->handle(
                    new AssignMemberFunction(
                        memberId: $memberId,
                        clubFunctionId: $function['club_function_id'],
                        validFrom: CarbonImmutable::parse(
                            $function['valid_from']
                        ),
                    )
                );
            }
        });
    }
}
