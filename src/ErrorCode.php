<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Error, warning and info codes of the E-POSTBUSINESS API (v2.6.1).
 *
 * The descriptions are the German texts from the API definition; codes with a
 * placeholder receive further details in the description of the actual message.
 *
 * @see https://api.epost.docuguide.com/swagger/v2/swagger.json Error schema
 */
enum ErrorCode: string
{
    // Errors: processing stopped, action needed.
    case E001 = 'E001';
    case E002 = 'E002';
    case E003 = 'E003';
    case E004 = 'E004';
    case E101 = 'E101';
    case E201 = 'E201';
    case E203 = 'E203';
    case E301 = 'E301';
    case E302 = 'E302';
    case E303 = 'E303';
    case E304 = 'E304';
    case E305 = 'E305';
    case E306 = 'E306';
    case E307 = 'E307';
    case E308 = 'E308';
    case E309 = 'E309';
    case E311 = 'E311';
    case E312 = 'E312';
    case E313 = 'E313';
    case E314 = 'E314';
    case E315 = 'E315';
    case E316 = 'E316';
    case E317 = 'E317';
    case E318 = 'E318';
    case E319 = 'E319';
    case E320 = 'E320';
    case E322 = 'E322';
    case E323 = 'E323';
    case E324 = 'E324';
    case E333 = 'E333';
    case E399 = 'E399';
    case E501 = 'E501';
    case E601 = 'E601';
    case E900 = 'E900';

    // Warnings: possible action needed, processing continues.
    case W101 = 'W101';
    case W201 = 'W201';
    case W202 = 'W202';
    case W203 = 'W203';
    case W220 = 'W220';
    case W301 = 'W301';
    case W501 = 'W501';
    case W601 = 'W601';

    // Infos: no action needed.
    case I101 = 'I101';
    case I501 = 'I501';
    case I601 = 'I601';
    case I701 = 'I701';
    case I751 = 'I751';

    public function level(): ErrorLevel
    {
        return match ($this->value[0]) {
            'E' => ErrorLevel::Error,
            'W' => ErrorLevel::Warning,
            default => ErrorLevel::Info,
        };
    }

    /**
     * German description from the API definition.
     */
    public function description(): string
    {
        return match ($this) {
            self::E001 => 'Ungültige Zugangsdaten - Bitte überprüfen Sie Ihre Anmeldedaten',
            self::E002 => 'Ungültige Zugangsdaten - VendorSubID ungültig',
            self::E003 => 'Zu viele SMS Anfragen - Es wurden innerhalb kurzer Zeit zu viele SMS-Codes angefordert. Die Anfrage über diese Kennung ist für 15 min blockiert.',
            self::E004 => 'Diese EKP wurde von der Deutsche Post AG gesperrt',
            self::E101 => 'Ungültiges Token - Abgelaufen',
            self::E201 => 'Ungültige Statusabfrage - Es wurde keine Sendung über dieses Suchkriterium gefunden',
            self::E203 => 'Adress-Metadaten: Abweichungen festgestellt',
            self::E301 => 'Kein PDF-Format erkannt',
            self::E302 => 'Erkannte Verletzung des DVF Sperrbereich',
            self::E303 => 'Maximale Dateigröße von 20 MB überschritten',
            self::E304 => 'Maximale Seitenanzahl von 94 Seiten überschritten',
            self::E305 => 'Ungültiger Ländercode',
            self::E306 => 'Ungültiges Format der Empfängeradresse erkannt',
            self::E307 => '1. Seite im Querformat eingeliefert',
            self::E308 => 'Kein DinA4 Format',
            self::E309 => 'Dublette bei eingelieferten Dateinamen erkannt',
            self::E311 => 'Unzulässige Kombination: Einschreiben und Ausland',
            self::E312 => 'Unzulässige Kombination: Duplex + Einschreiben',
            self::E313 => 'Verletzung des Letter-Schemas',
            self::E314 => 'Maximale Deckblatt-Dateigröße von 1 MB überschritten',
            self::E315 => 'Fehler in Sendungsverarbeitung',
            self::E316 => 'Fehler bei Sendungsupload',
            self::E317 => 'Unzulässiges Einschreiben-Format in Feld RegisteredLetter',
            self::E318 => 'Ablehnung in Druckzentrum',
            self::E319 => 'EKP ist nicht für das Druckzentrum freigegeben. Ein Live-Versand ist aktuell noch nicht möglich.',
            self::E320 => 'Die maximal zulässige Menge an Testsendungen pro Tag wurde erreicht. Es sind heute keine weiteren Testsendungen möglich',
            self::E322 => 'Zu hohe Abfragefrequenz von Letter-Status Anfragen. Die Mindestdauer zwischen 2 Statusabfragen liegt bei 5 Sekunden.',
            self::E323 => 'Kein Inhalt im Empfängerbereich der PDF erkannt (Weiße Fläche)',
            self::E324 => 'Duplikatsprüfung: Diese Sendung wurde bereits innerhalb der letzten Stunde eingeliefert',
            self::E333 => 'Senderadresse auf Dokument nicht vorhanden. Dies muss seit dem 1.1.2025 zwingend platziert werden.',
            self::E399 => 'Allgemeiner Fehler',
            self::E501 => 'API-Status: Inaktiv',
            self::E601 => 'PlugIn Fehler',
            self::E900 => 'Unspezifizierter Fehler',
            self::W101 => 'PDF/A Konvertierungswarnungen',
            self::W201 => 'Überschreitung Adressbereich',
            self::W202 => 'Überschreitung Sperrfläche Links: Dieser Bereich wird im Druckzentrum geweißt',
            self::W203 => 'Adress-Metadaten: Abweichungen festgestellt',
            self::W220 => 'Die angegebenen Daten zum Rückschein von Einschreiben werden seit dem 01.10.2022 nur noch automatisch aus dem Sichtfenster des Briefes ermittelt.',
            self::W301 => 'Senderadresse auf Dokument nicht vorhanden. Dies muss bis zum 1.1.2025 zwingend platziert werden.',
            self::W501 => 'API-Status: Wartungsankündigung',
            self::W601 => 'PlugIn Warnung',
            self::I101 => 'Zusatzoption: PDF -> PDFA Konvertierung',
            self::I501 => 'API-Status: OK',
            self::I601 => 'PlugIn Info',
            self::I701 => 'Track And Trace Statusmeldung',
            self::I751 => 'Statusmeldung zum Zielgebiet',
        };
    }
}
