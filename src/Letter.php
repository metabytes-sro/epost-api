<?php

/**
 * This file is part of metabytes-sro/epost-api.
 *
 * @package   metabytes-sro/epost-api
 * @author    Mantas Samaitis <mantas.samaitis@integrus.lt>, Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 */

namespace MetabytesSRO\EPost\Api;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\MultipartStream;
use Illuminate\Http\Response;
use InvalidArgumentException;
use LogicException;
use MetabytesSRO\EPost\Api\Exception\InvalidFileFormat;
use MetabytesSRO\EPost\Api\Exception\MissingAuthorizationTokenException;
use MetabytesSRO\EPost\Api\Exception\MissingAttachmentException;
use MetabytesSRO\EPost\Api\Exception\MissingEnvelopeException;
use MetabytesSRO\EPost\Api\Exception\MissingPreconditionException;
use MetabytesSRO\EPost\Api\Exception\MissingRecipientException;
use MetabytesSRO\EPost\Api\Metadata\DeliveryOptions;
use MetabytesSRO\EPost\Api\Metadata\Envelope;


/**
 * Class Letter
 *
 * @package MetabytesSRO\EPost\Api
 */
class Letter
{

    /**
     * EPost endpoint and integration environment
     *
     * @var string
     */
    const API_ENDPOINT = 'https://api.epost.docuguide.com';

    /**
     * A toggle to enable test and integration environment
     *
     * @var bool
     */
    private $testEnvironment;

    /**
     * EPost Test Email for Test Environment being enabled
     *
     * @var bool
     */
    private $testEmail;

    /**
     * The OAuth access token instance
     *
     * @var AccessToken
     */
    private $accessToken;

    /**
     * The envelope (metadata)
     *
     * @var Envelope
     */
    private $envelope;

    /**
     * The optional cover letter html formatted
     *
     * @var string
     */
    private $coverLetter;

    /**
     * The attachment paths
     *
     * @var string
     */
    private $attachment;

    /**
     * The delivery options
     *
     * @var DeliveryOptions
     */
    private $deliveryOptions;

    /**
     * The letter's id available after the draft was created
     *
     * @var string
     */
    private $letterId;

    /**
     * Set the access token
     *
     * @param AccessToken $accessToken
     *
     * @return self
     */
    public function setAccessToken(AccessToken $accessToken): Letter
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * Get the access token
     *
     * @return AccessToken
     * @throws MissingAuthorizationTokenException If the AccessToken is missing
     */
    public function getAccessToken(): AccessToken
    {
        if (null === $this->accessToken) {
            throw new MissingAuthorizationTokenException('An AccessToken instance must be passed');
        }

        return $this->accessToken;
    }

    /**
     * Set the envelope
     *
     * @param Envelope $envelope
     *
     * @return self
     */
    public function setEnvelope(Envelope $envelope): Letter
    {
        $this->envelope = $envelope;

        return $this;
    }

    /**
     * Get the envelope
     *
     * @return Envelope
     * @throws MissingEnvelopeException If the envelope is missing
     * @throws MissingRecipientException If there are no recipients
     */
    public function getEnvelope(): Envelope
    {
        if (null === $this->envelope) {
            throw new MissingEnvelopeException('No Envelope provided! Provide one beforehand');
        }

        if ($this->envelope->getData() == null) {
            throw new MissingRecipientException('No recipient provided! Add them beforehand');
        }

        return $this->envelope;
    }

    /**
     * Set the cover letter as path
     *
     * @param string $coverLetter
     *
     * @return self
     */
    public function setCoverLetter($coverLetter): Letter
    {
        $this->coverLetter = $coverLetter;

        return $this;
    }

    /**
     * Get the pdf formatted cover letter path
     *
     * @return string
     */
    public function getCoverLetter()
    {
        return $this->coverLetter;
    }

    /**
     * Set attachment as path
     *
     * @param string $attachment The attachment path
     *
     * @return self
     */
    public function setAttachment($attachment): Letter
    {
        if(mime_content_type($attachment) != 'application/pdf') {
            throw new InvalidFileFormat('Unallowed file format. Allowed: pdf');
        }

        $this->attachment = $attachment;

        return $this;
    }

    /**
     * Get the attachment path
     *
     * @return string
     * @throws MissingAttachmentException If the attachment is missing
     */
    public function getAttachment()
    {
        if (empty($this->attachment)) {
            throw new MissingAttachmentException('No attachment provided! Please add an attachment.');
        }

        return $this->attachment;
    }

    /**
     * Gather letter status from API
     *
     * @param null $letterId
     * @return LetterStatus
     */
    public function getLetterStatus($letterId = null): LetterStatus
    {
        $letterId = $letterId ?? $this->getLetterId();

        try {
            $response = $this->getHttpClient(static::API_ENDPOINT)
                ->request('GET', '/api/Letter/' . $letterId);
        } catch (ClientException $e) {
            $this->throwErrorException($e);
        }

        return new LetterStatus(
            \GuzzleHttp\json_decode(
                $response->getBody()->getContents(),
                true
            )
        );
    }

    /**
     * Execute Letter Status Query with specified ids and result in batch
     *
     * @param array $letterIds
     * @param bool $onlyIssues
     * @return array
     */
    public function getMultipleLetterStatuses($letterIds = [], $onlyIssues = false) : array
    {
        $options = [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => json_encode($letterIds)
        ];

        try {
            $response = $this->getHttpClient(static::API_ENDPOINT)
                ->request('POST', '/api/Letter/StatusQuery?onlyIssues=' .( $onlyIssues ? 'true' : 'false' ),
                    $options);
        } catch (ClientException $e) {
            $this->throwErrorException($e);
        }

        $result = \GuzzleHttp\json_decode(
            $response->getBody()->getContents(),
            true
        );

        $letterStatuses = [];

        foreach($result as $elementData) {
            $letterStatuses[] = new LetterStatus($elementData);
        }

        return $letterStatuses ?? $result;
    }

    public function getLetterStatusByDateRange($fromDate, $tillDate, $onlyIssues = false): array
    {
        $onlyIssues = $onlyIssues ? 'true' : 'false';

        try {
            $response = $this->getHttpClient(static::API_ENDPOINT)
                ->request('GET', '/api/Letter/Date', [
                    'query' => compact(['fromDate',
                        'tillDate',
                        'onlyIssues'])
                ]);
        } catch (ClientException $e) {
            $this->throwErrorException($e);
        }

        return \GuzzleHttp\json_decode(
            $response->getBody()->getContents(),
            true
        );
    }

    /**
     * Set the delivery options
     *
     * @param DeliveryOptions $deliveryOptions
     *
     * @return self
     * @throws LogicException If the letter isn't a hybrid (printed) letter
     */
    public function setDeliveryOptions(DeliveryOptions $deliveryOptions): Letter
    {
        $this->deliveryOptions = $deliveryOptions;

        return $this;
    }

    /**
     * Get the delivery options
     *
     * @return DeliveryOptions
     */
    public function getDeliveryOptions()
    {
        return $this->deliveryOptions;
    }

    /**
     * Set the letter id
     *
     * @param string $letterId
     *
     * @return self
     */
    public function setLetterId($letterId): Letter
    {
        $this->letterId = $letterId;

        return $this;
    }

    /**
     * Get the letter id
     *
     * @return string
     * @throws MissingPreconditionException If the letter id is missing
     */
    public function getLetterId()
    {
        if (!$this->letterId) {
            throw new MissingPreconditionException('No letter id provided! Set letter id beforehand');
        }

        return $this->letterId;
    }

    /**
     * Enable/disable the test and integration environment
     *
     * @param boolean $testEnvironment
     *
     * @return self
     */
    public function setTestEnvironment($testEnvironment): Letter
    {
        $this->testEnvironment = $testEnvironment;

        return $this;
    }

    /**
     * Sets Email for Test Environment
     *
     * @param string $email
     *
     * @return self
     */
    public function setTestEmail($testEmail): Letter
    {
        $this->testEmail = $testEmail;

        return $this;
    }

    /**
     * Return true for enabled test and integration environment
     *
     * @return bool
     */
    public function isTestEnvironment()
    {
        return $this->testEnvironment;
    }

    /**
     * Send the given letter. Delivery options should be set optionally for physical letters
     *
     * @return self
     * @throws BadResponseException See API Send Reference
     */
    public function send(): Letter
    {
        $data = $this->getEnvelope()->getData();

        if ($this->getCoverLetter()) {
            $data['coverLetter'] = true;
            $data['coverData'] = chunk_split(base64_encode(
                file_get_contents($this->getCoverLetter()))
            );
        } else {
            $data['coverLetter'] = false;
        }

        $attachment = $this->getAttachment();
        $data['fileName'] = basename($attachment);
        $data['data'] = chunk_split(base64_encode(
            file_get_contents($attachment))
        );

        if (null !== $this->getDeliveryOptions()) {
            $data = array_merge($data, $this->getDeliveryOptions()->getData());
        }

        if(!empty($this->testEmail)) {
            $data = array_merge($data, [
                'testFlag' => true,
                'testEMail' => $this->testEmail
            ]);
        }

        $options = [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => json_encode([$data]),
        ];

        try {
            $response = $this->getHttpClient(static::API_ENDPOINT)
                ->request('POST', '/api/Letter', $options);
        } catch (ClientException $e) {
            $this->throwErrorException($e);
        }

        $data = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);

        $this->setLetterId($data[0]['letterID']);

        return $this;
    }

    /**
     * Get a http client by given base uri and set the access token header
     *
     * @param string $baseUri
     *
     * @return HttpClient
     */
    private function getHttpClient($baseUri): HttpClient
    {
        return new HttpClient(
            [
                'base_uri' => $baseUri,
                'headers'  => [
                    'Authorization' => 'Bearer '. $this->getAccessToken()->getToken(),
                ],
            ]
        );
    }

    /**
     * Throws an exception
     *
     * @param $e
     */
    protected function throwErrorException($e): void
    {
        throw new Exception\ErrorException(
            new Error(
                \GuzzleHttp\json_decode(
                    $e->getResponse()->getBody()->getContents(),
                    true
                )
            )
        );
    }
}
