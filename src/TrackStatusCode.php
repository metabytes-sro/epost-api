<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Tracking status codes for registered letters (Einschreiben), as reported in
 * LetterStatus::$registeredLetterStatus.
 *
 * Source: https://api.epost.docuguide.com/trackStatusCodes.json. A snapshot is
 * kept in docs/api and the test suite checks that this enum matches it.
 */
enum TrackStatusCode: string
{
    case Announced = 'ANNOUNCED';
    case Initiated = 'INITIATED';
    case Posted = 'POSTED';
    case PassedLetterCenter = 'PASSED_LETTER_CENTER';
    case InDelivery = 'IN_DELIVERY';
    case Redirected = 'REDIRECTED';
    case Notified = 'NOTIFIED';
    case NotifiedKeyAccount = 'NOTIFIED_KEY_ACCOUNT';
    case NotifiedPoBox = 'NOTIFIED_PO_BOX';
    case NotifiedPackstation = 'NOTIFIED_PACKSTATION';
    case SentBack = 'SENT_BACK';
    case Delivered = 'DELIVERED';
    case Marburg = 'MARBURG';
    case FetchedPackstation = 'FETCHED_PACKSTATION';
    case Fetched = 'FETCHED';
    case FetchedNotified = 'FETCHED_NOTIFIED';
    case DeliveredPoBox = 'DELIVERED_PO_BOX';
    case Confirmed = 'CONFIRMED';
    case ConfirmationPending = 'CONFIRMATION_PENDING';
    case FetchedPendingDeliveryConfirmation = 'FETCHED_PENDING_DELIVERY_CONFIRMATION';
    case FetchedPoBoxPendingDeliveryConfirmation = 'FETCHED_PO_BOX_PENDING_DELIVERY_CONFIRMATION';
    case FetchedKeyAccountPendingDeliveryConfirmation = 'FETCHED_KEY_ACCOUNT_PENDING_DELIVERY_CONFIRMATION';
    case ReturnedToSender = 'RETURNED_TO_SENDER';
    case ConfirmedBySender = 'CONFIRMED_BY_SENDER';
    case Undeliverable = 'UNDELIVERABLE';
    case NoInfo = 'NO_INFO';
    case Ambiguous = 'AMBIGUOUS';
    case PostedWrongDate = 'POSTED_WRONG_DATE';

    /**
     * True once the delivery process has ended (delivered, fetched, returned or
     * stored in the lost-letter centre).
     */
    public function isFinal(): bool
    {
        return match ($this) {
            self::Delivered,
            self::Marburg,
            self::FetchedPackstation,
            self::Fetched,
            self::FetchedNotified,
            self::DeliveredPoBox,
            self::Confirmed,
            self::ConfirmationPending,
            self::FetchedPendingDeliveryConfirmation,
            self::FetchedPoBoxPendingDeliveryConfirmation,
            self::FetchedKeyAccountPendingDeliveryConfirmation,
            self::ReturnedToSender,
            self::ConfirmedBySender,
            self::Ambiguous => true,
            default => false,
        };
    }

    /**
     * German description from Deutsche Post.
     */
    public function description(): string
    {
        return match ($this) {
            self::Announced => 'Die Sendung wurde (vor)angekündigt. Eine Einlieferung erfolgte noch nicht.',
            self::Initiated => 'Das Label (Funketikett) für den Auslieferungsnachweis wurde aktiviert.',
            self::Posted => 'Die Sendung wurde eingeliefert.',
            self::PassedLetterCenter => 'Die Sendung wurde im Briefzentrum verarbeitet.',
            self::InDelivery => 'Die Sendung befindet sich in der Zustellung.',
            self::Redirected => 'Sendung wird nachgesandt.',
            self::Notified => 'Der Empfänger konnte nicht angetroffen werden und wurde vom Zusteller benachrichtigt.',
            self::NotifiedKeyAccount => 'Die Sendung wurde für die Auslieferung an einen Großkunden (Empfänger) bereitgestellt.',
            self::NotifiedPoBox => 'Die Benachrichtigung für die Sendung wurde in das Postfach des Empfängers eingelegt.',
            self::NotifiedPackstation => 'Die Sendung wurde in die Packstation eingelegt.',
            self::SentBack => 'Die Sendung geht an den Absender zurück.',
            self::Delivered => 'Die Sendung wurde zugestellt.',
            self::Marburg => 'Die Sendung lagert / lagerte in Briefermittlung Marburg.',
            self::FetchedPackstation => 'Die Sendung wurde aus der Packstation abgeholt',
            self::Fetched => 'Die Sendung wurde ausgeliefert.',
            self::FetchedNotified => 'Die Sendung wurde nach Benachrichtigung abgeholt.',
            self::DeliveredPoBox => 'Auslieferung via Postfach',
            self::Confirmed => 'Der Empfang der Sendung wurde vom Großkunden (Empfänger) quittiert.',
            self::ConfirmationPending => 'Sendung wurde vom Großkunden (Empfänger) angenommen, aber der Auslieferungsbeleg liegt noch nicht vor.',
            self::FetchedPendingDeliveryConfirmation => 'Benachrichtigte / postlagernde Sendung wurde vom Empfänger abgeholt, aber der Auslieferungsbeleg liegt noch nicht vor.',
            self::FetchedPoBoxPendingDeliveryConfirmation => 'Zeigt an, dass die Sendung an einen Postfach-Kunden ausgegeben wurde, der Auslieferungsbeleg aber noch fehlt bzw. noch nicht erfasst wurde.',
            self::FetchedKeyAccountPendingDeliveryConfirmation => 'Die Sendung wurde ausgeliefert, aber der Auslieferungsbeleg liegt noch nicht vor.',
            self::ReturnedToSender => 'Die Sendung wurde dem Absender zugestellt.',
            self::ConfirmedBySender => 'Der Empfang der unzustellbaren Sendung wurde vom Großkunden (Absender) quittiert.',
            self::Undeliverable => 'Die Sendung ist unanbringlich. Sie konnte weder dem Empfänger noch dem Absender zugestellt werden. Sie wird an die Briefermittlungsstelle nach Marburg abgeleitet.',
            self::NoInfo => 'Es liegen keine Informationen zur Sendung mit der angegebenen Sendungsnummer vor.',
            self::Ambiguous => 'Die vorliegenden Sendungsinformationen lassen keine eindeutige Statusbildung zu.',
            self::PostedWrongDate => 'Das angegebene Einlieferungsdatum ist falsch.',
        };
    }
}
