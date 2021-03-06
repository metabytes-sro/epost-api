<?php

/**
 * This file is part of metabytes-sro/epost-api.
 *
 * @package   metabytes-sro/epost-api
 * @author    Mantas Samaitis <mantas.samaitis@integrus.lt>, Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 */

namespace MetabytesSRO\EPost\Api;

/**
 * Class LetterStatus
 *
 * @package MetabytesSRO\EPost\Api
 */
class LetterStatus
{
    const ACCEPTANCE_OF_SHIPMENT_ID = 1;
    const PROCESSING_THE_SHIPMENT_ID = 2;
    const DELIVERY_TO_THE_PRINTING_CENTER_ID = 3;
    const PROCESSING_IN_PRINTING_CENTER_ID = 4;
    const PROCESSING_ERROR_ID = 99;

    protected array $data;

    /**
     * LetterStatus constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * get a letter id
     *
     * @return int
     */
    public function getLetterId(): int
    {
        return $this->data['letterID'];
    }

    /**
     * get status id
     *
     * @return int
     */
    public function getStatusId(): int
    {
        return $this->data['statusID'];
    }

    /**
     * get a value by key from details
     *
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->data[$key];
    }

    /**
     * get a full status details
     *
     * @return array
     */
    public function getStatusDetails(): string
    {
        return $this->data['statusDetails'];
    }

    /**
     * get array of errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->data['errorList'];
    }
}