<?php

namespace App\Application\Sepa;

use App\Domain\Sepa\Models\SepaDebitItem;
use App\Domain\Sepa\Models\SepaDebitRun;

final class SepaIdentifierGenerator
{
    public function messageId(
        SepaDebitRun $run,
    ): string {
        return 'MSG'
            .str_replace(
                '-',
                '',
                (string) $run->getKey()
            );
    }

    public function paymentInformationId(
        SepaDebitRun $run,
    ): string {
        return 'PMT'
            .str_replace(
                '-',
                '',
                (string) $run->getKey()
            );
    }

    public function endToEndId(
        SepaDebitItem $item,
    ): string {
        return 'E2E'
            .str_replace(
                '-',
                '',
                (string) $item->getKey()
            );
    }
}
