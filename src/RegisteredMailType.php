<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Registered-mail (Einschreiben) options accepted by the E-POST API for
 * national letters. Registered mail cannot be combined with duplex printing
 * (E312) or with an international address (E311).
 */
enum RegisteredMailType: string
{
    /** Einschreiben: delivery against signature. */
    case Standard = 'Einschreiben';

    /** Einwurf Einschreiben: the delivery into the mailbox is documented. */
    case Submission = 'Einwurf Einschreiben';

    /**
     * Einschreiben Rückschein: delivery against signature, the receipt is
     * returned to the sender line shown in the letter's address window.
     */
    case ReturnReceipt = 'Einschreiben Rückschein';
}
