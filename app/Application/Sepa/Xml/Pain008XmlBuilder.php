<?php

namespace App\Application\Sepa\Xml;

use App\Domain\Sepa\Models\ClubSepaConfiguration;
use App\Domain\Sepa\Models\SepaDebitItem;
use App\Domain\Sepa\Models\SepaDebitRun;
use DomainException;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Collection;

final class Pain008XmlBuilder
{
    public const FORMAT = 'pain.008.001.08';

    private const XML_NAMESPACE =
        'urn:iso:std:iso:20022:tech:xsd:pain.008.001.08';

    public function build(
        SepaDebitRun $run,
        ClubSepaConfiguration $configuration,
    ): string {
        $items = $run->items()
            ->orderBy('id')
            ->get();

        if ($items->isEmpty()) {
            throw new DomainException(
                'Der SEPA-Lauf enthält keine Lastschriftpositionen.'
            );
        }

        $document = new DOMDocument('1.0', 'UTF-8');

        $document->formatOutput = true;

        $root = $document->createElementNS(
            self::XML_NAMESPACE,
            'Document',
        );

        $document->appendChild($root);

        $initiation = $this->element(
            $root,
            'CstmrDrctDbtInitn',
        );

        $this->buildGroupHeader(
            $initiation,
            $run,
            $configuration,
        );

        $this->buildPaymentInformation(
            $initiation,
            $run,
            $configuration,
            $items,
        );

        $xml = $document->saveXML();

        if ($xml === false) {
            throw new DomainException(
                'Die SEPA-XML-Datei konnte nicht erzeugt werden.'
            );
        }

        return $xml;
    }

    private function element(
        DOMElement $parent,
        string $name,
        ?string $value = null,
    ): DOMElement {
        $document = $parent->ownerDocument;

        if ($document === null) {
            throw new DomainException(
                'Ungültiges XML-Dokument.'
            );
        }

        $element = $document->createElementNS(
            self::XML_NAMESPACE,
            $name,
        );

        if ($value !== null) {
            $element->appendChild(
                $document->createTextNode($value)
            );
        }

        $parent->appendChild($element);

        return $element;
    }

    private function buildGroupHeader(
        DOMElement $parent,
        SepaDebitRun $run,
        ClubSepaConfiguration $configuration,
    ): void {
        $header = $this->element(
            $parent,
            'GrpHdr',
        );

        $this->element(
            $header,
            'MsgId',
            (string) $run->message_id,
        );

        $this->element(
            $header,
            'CreDtTm',
            now()->format('Y-m-d\TH:i:s'),
        );

        $this->element(
            $header,
            'NbOfTxs',
            (string) $run->items_count,
        );

        $this->element(
            $header,
            'CtrlSum',
            (string) $run->total_amount,
        );

        $initiatingParty = $this->element(
            $header,
            'InitgPty',
        );

        $this->element(
            $initiatingParty,
            'Nm',
            $configuration->account_holder,
        );
    }

    /**
     * @param  Collection<int,SepaDebitItem>  $items
     */
    private function buildPaymentInformation(
        DOMElement $parent,
        SepaDebitRun $run,
        ClubSepaConfiguration $configuration,
        Collection $items,
    ): void {
        $payment = $this->element(
            $parent,
            'PmtInf',
        );

        $this->element(
            $payment,
            'PmtInfId',
            (string) $run->payment_information_id,
        );

        $this->element($payment, 'PmtMtd', 'DD');

        $this->element(
            $payment,
            'NbOfTxs',
            (string) $items->count(),
        );

        $this->element(
            $payment,
            'CtrlSum',
            (string) $run->total_amount,
        );

        $paymentType = $this->element(
            $payment,
            'PmtTpInf',
        );

        $serviceLevel = $this->element(
            $paymentType,
            'SvcLvl',
        );

        $this->element($serviceLevel, 'Cd', 'SEPA');

        $localInstrument = $this->element(
            $paymentType,
            'LclInstrm',
        );

        $this->element($localInstrument, 'Cd', 'CORE');

        $this->element($paymentType, 'SeqTp', 'RCUR');

        $this->element(
            $payment,
            'ReqdColltnDt',
            $run->collection_date->format('Y-m-d'),
        );

        $creditor = $this->element($payment, 'Cdtr');

        $this->element(
            $creditor,
            'Nm',
            $configuration->account_holder,
        );

        $creditorAccount = $this->element(
            $payment,
            'CdtrAcct',
        );

        $creditorAccountId = $this->element(
            $creditorAccount,
            'Id',
        );

        $this->element(
            $creditorAccountId,
            'IBAN',
            $configuration->iban,
        );

        $this->element(
            $creditorAccount,
            'Ccy',
            'EUR',
        );

        $creditorAgent = $this->element(
            $payment,
            'CdtrAgt',
        );

        $financialInstitution = $this->element(
            $creditorAgent,
            'FinInstnId',
        );

        $this->element(
            $financialInstitution,
            'BICFI',
            (string) $configuration->bic,
        );

        $this->element(
            $payment,
            'ChrgBr',
            'SLEV',
        );

        $this->buildCreditorSchemeId(
            $payment,
            $configuration->creditor_identifier,
        );

        foreach ($items as $item) {
            $this->buildDebitTransaction(
                $payment,
                $item,
            );
        }
    }

    private function buildCreditorSchemeId(
        DOMElement $parent,
        string $creditorIdentifier,
    ): void {
        $scheme = $this->element(
            $parent,
            'CdtrSchmeId',
        );

        $id = $this->element($scheme, 'Id');

        $privateId = $this->element(
            $id,
            'PrvtId',
        );

        $other = $this->element(
            $privateId,
            'Othr',
        );

        $this->element(
            $other,
            'Id',
            $creditorIdentifier,
        );

        $schemeName = $this->element(
            $other,
            'SchmeNm',
        );

        $this->element(
            $schemeName,
            'Prtry',
            'SEPA',
        );
    }

    private function buildDebitTransaction(
        DOMElement $parent,
        SepaDebitItem $item,
    ): void {
        $transaction = $this->element(
            $parent,
            'DrctDbtTxInf',
        );

        $paymentId = $this->element(
            $transaction,
            'PmtId',
        );

        $this->element(
            $paymentId,
            'EndToEndId',
            (string) $item->end_to_end_id,
        );

        $amount = $this->element(
            $transaction,
            'InstdAmt',
            (string) $item->amount,
        );

        $amount->setAttribute('Ccy', 'EUR');

        $directDebit = $this->element(
            $transaction,
            'DrctDbtTx',
        );

        $mandateInformation = $this->element(
            $directDebit,
            'MndtRltdInf',
        );

        $this->element(
            $mandateInformation,
            'MndtId',
            $item->mandate_reference,
        );

        $this->element(
            $mandateInformation,
            'DtOfSgntr',
            $item->mandate_signed_at->format('Y-m-d'),
        );

        $debtorAgent = $this->element(
            $transaction,
            'DbtrAgt',
        );

        $debtorInstitution = $this->element(
            $debtorAgent,
            'FinInstnId',
        );

        $this->element(
            $debtorInstitution,
            'BICFI',
            (string) $item->bic,
        );

        $debtor = $this->element(
            $transaction,
            'Dbtr',
        );

        $this->element(
            $debtor,
            'Nm',
            $item->account_holder,
        );

        $debtorAccount = $this->element(
            $transaction,
            'DbtrAcct',
        );

        $debtorAccountId = $this->element(
            $debtorAccount,
            'Id',
        );

        $this->element(
            $debtorAccountId,
            'IBAN',
            $item->iban,
        );

        $remittanceInformation = $this->element(
            $transaction,
            'RmtInf',
        );

        $this->element(
            $remittanceInformation,
            'Ustrd',
            $item->purpose,
        );
    }
}
