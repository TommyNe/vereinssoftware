<?php

namespace App\Domain\Audit\Enums;

enum AuditAction: string
{
    case PaymentCreated = 'payment.created';

    case PaymentAllocated = 'payment.allocated';

    case PaymentReversed = 'payment.reversed';

    case ContributionRunFailed = 'contribution.run.failed';

    case DocumentUploaded = 'member.document.uploaded';

    case DocumentUpdated = 'member.document.updated';

    case DocumentDeleted = 'member.document.deleted';

    case ClubInvitationAccepted = 'club.invitation.accepted';

    case ClubInvitationResent = 'club.invitation.resent';

    case ClubInvitationRevoked = 'club.invitation.revoked';

    case ContributionTypeCreated = 'contribution.type.created';

    case ContributionTypeUpdated = 'contribution.type.updated';

    case ContributionTypeDeleted = 'contribution.type.deleted';

    case ContributionRateCreated = 'contribution.rate.created';

    case ContributionRateUpdated = 'contribution.rate.updated';

    case ContributionRateDeleted = 'contribution.rate.deleted';

    case MemberContributionOverrideCreated = 'member.contribution-override.created';

    case MemberContributionOverrideUpdated = 'member.contribution-override.updated';

    case MemberContributionOverrideDeleted = 'member.contribution-override.deleted';

    case MembershipTypeCreated = 'membership-type.created';

    case MembershipTypeUpdated = 'membership-type.updated';

    case MembershipTypeDeleted = 'membership-type.deleted';

    case DepartmentCreated = 'department.created';

    case DepartmentUpdated = 'department.updated';

    case DepartmentDeleted = 'department.deleted';

    case ClubFunctionCreated = 'club-function.created';

    case ClubFunctionUpdated = 'club-function.updated';

    case ClubFunctionDeleted = 'club-function.deleted';

    case MemberViewed = 'member.viewed';

    case MemberRegistered = 'member.registered';

    case MemberAddressChanged = 'member.address.changed';

    case MemberContactDataChanged = 'member.contact-data.changed';

    case MemberPersonalDataChanged = 'member.personal-data.changed';

    case MembershipTypeChanged = 'member.membership-type.changed';

    case MemberSuspended = 'member.suspended';

    case MemberReactivated = 'member.reactivated';

    case MemberLeftClub = 'member.left-club';

    case DepartmentJoined = 'member.department.joined';

    case DepartmentLeft = 'member.department.left';

    case FunctionAssigned = 'member.function.assigned';

    case FunctionEnded = 'member.function.ended';

    case MembersExported = 'members.exported';

    case DocumentViewed = 'member.document.viewed';

    case DocumentDownloaded = 'member.document.downloaded';

    case ClubUserInvited = 'club.user.invited';

    case ClubUserAdded = 'club.user.added';

    case ClubUserRemoved = 'club.user.removed';

    case ClubUserRoleChanged = 'club.user.role-changed';

    case UserProfileChanged = 'user.profile.changed';

    case UserEmailChanged = 'user.email.changed';

    case UserPasswordChanged = 'user.password.changed';

    case UserMfaEnabled = 'user.mfa.enabled';

    case UserMfaDisabled = 'user.mfa.disabled';

    case UserLoggedIn = 'user.logged-in';

    case UserLoggedOut = 'user.logged-out';

    case OtherSessionsLoggedOut = 'user.other-sessions-logged-out';

    case SessionRevoked = 'user.session-revoked';

    case ContributionRunStarted = 'contribution.run.started';

    case ContributionRunCompleted = 'contribution.run.completed';

    case ContributionChargeCreated = 'contribution.charge.created';

    case ContributionChargePaid = 'contribution.charge.paid';

    case ContributionChargeCancelled = 'contribution.charge.cancelled';

    case SepaConfigurationCreated = 'sepa.configuration.created';

    case SepaConfigurationChanged = 'sepa.configuration.changed';

    case SepaMandateCreated = 'sepa.mandate.created';

    case SepaMandateRevoked = 'sepa.mandate.revoked';

    case SepaBankDataViewed = 'sepa.bank-data.viewed';

    case SepaDebitRunPrepared = 'sepa.debit-run.prepared';

    case SepaDebitRunCancelled = 'sepa.debit-run.cancelled';

    case SepaDebitRunExported = 'sepa.debit-run.exported';

    case SepaDebitRunDownloaded = 'sepa.debit-run.downloaded';

    case SepaDebitRunSubmitted = 'sepa.debit-run.submitted';

    case SepaDebitRunAccepted = 'sepa.debit-run.accepted';

    case SepaDebitRunRejected = 'sepa.debit-run.rejected';

    case SepaDebitItemAccepted = 'sepa.debit-item.accepted';

    case SepaDebitItemRejected = 'sepa.debit-item.rejected';

    case SepaDebitItemSettled = 'sepa.debit-item.settled';

    case SepaDebitItemReturned = 'sepa.debit-item.returned';

    case SepaDebitItemRefunded = 'sepa.debit-item.refunded';
}
