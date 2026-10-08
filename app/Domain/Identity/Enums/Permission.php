<?php

namespace App\Domain\Identity\Enums;

enum Permission: string
{
    case Administrator = 'administrator';
    case MembersView = 'members.view';
    case MembersCreate = 'members.create';
    case MembersUpdate = 'members.update';
    case MembersDelete = 'members.delete';
    case AuditView = 'audit.view';
    case SecurityAuditView = 'security.audit.view';
    case MembersMembershipManage = 'members.membership.manage';
    case MembersDepartmentsManage = 'members.departments.manage';
    case MembersFunctionsManage = 'members.functions.manage';
    case MembersPersonalDataView = 'members.personal-data.view';
    case MembershipTypesManage = 'membership-types.manage';
    case DepartmentsManage = 'departments.manage';
    case ClubFunctionsManage = 'club-functions.manage';
    case MembersDocumentsView = 'members.documents.view';
    case MembersExport = 'members.export';
    case ClubUsersView = 'club.users.view';
    case ClubUsersManage = 'club.users.manage';
    case ClubSettingsManage = 'club.settings.manage';
    case MembersDocumentsManage = 'members.documents.manage';
    case ContributionTypesManage = 'contribution-types.manage';
    case ContributionRatesManage = 'contribution-rates.manage';
    case ContributionsView = 'contributions.view';
    case MemberContributionsManage = 'members.contributions.manage';
    case ContributionChargesManage = 'contribution-charges.manage';
    case ContributionChargesView = 'contribution-charges.view';
    case ContributionRunsView = 'contribution-runs.view';
    case ContributionRunsCreate = 'contribution-runs.create';
    case SepaMandatesView = 'sepa-mandates.view';
    case SepaMandatesManage = 'sepa-mandates.manage';
    case SepaBankDataView = 'sepa-bank-data.view';
    case SepaConfigurationView = 'sepa-configuration.view';
    case SepaConfigurationManage = 'sepa-configuration.manage';
    case SepaConfigurationBankDataView = 'sepa-configuration.bank-data.view';
    case SepaDebitRunsView = 'sepa-debit-runs.view';
    case SepaDebitRunsCreate = 'sepa-debit-runs.create';
    case SepaDebitRunsExport = 'sepa-debit-runs.export';
    case SepaDebitRunsCancel = 'sepa-debit-runs.cancel';
    case SepaDebitRunsSubmit = 'sepa-debit-runs.submit';
    case SepaDebitItemsFeedbackManage = 'sepa-debit-items.feedback.manage';
    case PaymentsView = 'payments.view';
    case PaymentsManage = 'payments.manage';
    case PaymentsReverse = 'payments.reverse';
}
