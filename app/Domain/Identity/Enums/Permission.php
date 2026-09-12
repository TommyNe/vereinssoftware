<?php

namespace App\Domain\Identity\Enums;

enum Permission: string
{
    case MembersView = 'members.view';
    case MembersCreate = 'members.create';
    case MembersUpdate = 'members.update';
    case MembersDelete = 'members.delete';
    case AuditView = 'audit.view';
    case SecurityAuditView = 'security.audit.view';
    case MembersDepartmentsManage = 'members.departments.manage';
    case MembersFunctionsManage = 'members.functions.manage';
    case MembersPersonalDataView = 'members.personal-data.view';
    case MembersDocumentsView = 'members.documents.view';
    case MembersExport = 'members.export';
    case ClubUsersView = 'club.users.view';
    case ClubUsersManage = 'club.users.manage';
    case ClubSettingsManage = 'club.settings.manage';
}
