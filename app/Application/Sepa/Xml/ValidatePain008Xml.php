<?php

namespace App\Application\Sepa\Xml;

use DomainException;
use DOMDocument;

final class ValidatePain008Xml
{
    public function validate(string $xml): void
    {
        $schema = resource_path(
            'sepa/xsd/EPC130-08_2025_V1.0_pain.008.001.08.xsd'
        );

        if (! is_file($schema)) {
            throw new DomainException(
                'Das SEPA-XSD-Schema wurde nicht gefunden.'
            );
        }

        $document = new DOMDocument;

        $previous = libxml_use_internal_errors(true);

        try {
            if (! $document->loadXML(
                $xml,
                LIBXML_NONET,
            )) {
                throw new DomainException(
                    'Das erzeugte SEPA-XML ist syntaktisch ungültig.'
                );
            }

            if (! $document->schemaValidate($schema)) {
                throw new DomainException(
                    'Das erzeugte SEPA-XML entspricht nicht dem XSD-Schema.'
                );
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
