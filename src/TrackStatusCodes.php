<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Einschreiben (registered letter) tracking status codes.
 *
 * Data source: https://api.epost.docuguide.com/trackStatusCodes.json
 * Check this URL for updates when maintaining the package.
 */
class TrackStatusCodes
{
    /**
     * @see https://api.epost.docuguide.com/trackStatusCodes.json
     */
    private const SOURCE_URL = 'https://api.epost.docuguide.com/trackStatusCodes.json';

    /**
     * @var array<string, array{description: string, final: bool}>
     */
    private const CODES = [
        'ANNOUNCED' => [
            'description' => 'Die Sendung wurde (vor)angekündigt. Eine Einlieferung erfolgte noch nicht.',
            'final' => false,
        ],
        'INITIATED' => [
            'description' => 'Das Label (Funketikett) für den Auslieferungsnachweis wurde aktiviert.',
            'final' => false,
        ],
        'POSTED' => [
            'description' => 'Die Sendung wurde eingeliefert.',
            'final' => false,
        ],
        'PASSED_LETTER_CENTER' => [
            'description' => 'Die Sendung wurde im Briefzentrum verarbeitet.',
            'final' => false,
        ],
        'IN_DELIVERY' => [
            'description' => 'Die Sendung befindet sich in der Zustellung.',
            'final' => false,
        ],
        'REDIRECTED' => [
            'description' => 'Sendung wird nachgesandt.',
            'final' => false,
        ],
        'NOTIFIED' => [
            'description' => 'Der Empfänger konnte nicht angetroffen werden und wurde vom Zusteller benachrichtigt.',
            'final' => false,
        ],
        'NOTIFIED_KEY_ACCOUNT' => [
            'description' => 'Die Sendung wurde für die Auslieferung an einen Großkunden (Empfänger) bereitgestellt.',
            'final' => false,
        ],
        'NOTIFIED_PO_BOX' => [
            'description' => 'Die Benachrichtigung für die Sendung wurde in das Postfach des Empfängers eingelegt.',
            'final' => false,
        ],
        'NOTIFIED_PACKSTATION' => [
            'description' => 'Die Sendung wurde in die Packstation eingelegt.',
            'final' => false,
        ],
        'SENT_BACK' => [
            'description' => 'Die Sendung geht an den Absender zurück.',
            'final' => false,
        ],
        'DELIVERED' => [
            'description' => 'Die Sendung wurde zugestellt.',
            'final' => true,
        ],
        'MARBURG' => [
            'description' => 'Die Sendung lagert / lagerte in Briefermittlung Marburg.',
            'final' => true,
        ],
        'FETCHED_PACKSTATION' => [
            'description' => 'Die Sendung wurde aus der Packstation abgeholt',
            'final' => true,
        ],
        'FETCHED' => [
            'description' => 'Die Sendung wurde ausgeliefert.',
            'final' => true,
        ],
        'FETCHED_NOTIFIED' => [
            'description' => 'Die Sendung wurde nach Benachrichtigung abgeholt.',
            'final' => true,
        ],
        'DELIVERED_PO_BOX' => [
            'description' => 'Auslieferung via Postfach',
            'final' => true,
        ],
        'CONFIRMED' => [
            'description' => 'Der Empfang der Sendung wurde vom Großkunden (Empfänger) quittiert.',
            'final' => true,
        ],
        'CONFIRMATION_PENDING' => [
            'description' => 'Sendung wurde vom Großkunden (Empfänger) angenommen, aber der Auslieferungsbeleg liegt noch nicht vor.',
            'final' => true,
        ],
        'FETCHED_PENDING_DELIVERY_CONFIRMATION' => [
            'description' => 'Benachrichtigte / postlagernde Sendung wurde vom Empfänger abgeholt, aber der Auslieferungsbeleg liegt noch nicht vor.',
            'final' => true,
        ],
        'FETCHED_PO_BOX_PENDING_DELIVERY_CONFIRMATION' => [
            'description' => 'Zeigt an, dass die Sendung an einen Postfach-Kunden ausgegeben wurde, der Auslieferungsbeleg aber noch fehlt bzw. noch nicht erfasst wurde.',
            'final' => true,
        ],
        'FETCHED_KEY_ACCOUNT_PENDING_DELIVERY_CONFIRMATION' => [
            'description' => 'Die Sendung wurde ausgeliefert, aber der Auslieferungsbeleg liegt noch nicht vor.',
            'final' => true,
        ],
        'RETURNED_TO_SENDER' => [
            'description' => 'Die Sendung wurde dem Absender zugestellt.',
            'final' => true,
        ],
        'CONFIRMED_BY_SENDER' => [
            'description' => 'Der Empfang der unzustellbaren Sendung wurde vom Großkunden (Absender) quittiert.',
            'final' => true,
        ],
        'UNDELIVERABLE' => [
            'description' => 'Die Sendung ist unanbringlich. Sie konnte weder dem Empfänger noch dem Absender zugestellt werden. Sie wird an die Briefermittlungsstelle nach Marburg abgeleitet.',
            'final' => false,
        ],
        'NO_INFO' => [
            'description' => 'Es liegen keine Informationen zur Sendung mit der angegebenen Sendungsnummer vor.',
            'final' => false,
        ],
        'AMBIGUOUS' => [
            'description' => 'Die vorliegenden Sendungsinformationen lassen keine eindeutige Statusbildung zu.',
            'final' => true,
        ],
        'POSTED_WRONG_DATE' => [
            'description' => 'Das angegebene Einlieferungsdatum ist falsch.',
            'final' => false,
        ],
    ];

    public static function getSourceUrl(): string
    {
        return self::SOURCE_URL;
    }

    public static function getDescription(string $code): ?string
    {
        return self::CODES[$code]['description'] ?? null;
    }

    public static function isFinal(string $code): ?bool
    {
        return self::CODES[$code]['final'] ?? null;
    }

    /**
     * @return array<string, array{description: string, final: bool}>
     */
    public static function getAll(): array
    {
        return self::CODES;
    }

    public static function hasCode(string $code): bool
    {
        return isset(self::CODES[$code]);
    }
}
