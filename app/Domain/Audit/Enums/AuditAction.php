<?php

namespace App\Domain\Audit\Enums;

enum AuditAction: string
{
    case MemberViewed = 'member.viewed';

    case MemberRegistered = 'member.registered';

    case MemberAddressChanged =
    'member.address.changed';

    case MemberContactDataChanged =
    'member.contact-data.changed';

    case MemberPersonalDataChanged =
    'member.personal-data.changed';

    case MembershipTypeChanged =
    'member.membership-type.changed';

    case MemberSuspended =
    'member.suspended';

    case MemberReactivated =
    'member.reactivated';

    case MemberLeftClub =
    'member.left-club';

    case DepartmentJoined =
    'member.department.joined';

    case DepartmentLeft =
    'member.department.left';

    case FunctionAssigned =
    'member.function.assigned';

    case FunctionEnded =
    'member.function.ended';

    case MembersExported =
    'members.exported';

    case DocumentViewed =
    'member.document.viewed';

    case DocumentDownloaded =
    'member.document.downloaded';
}
