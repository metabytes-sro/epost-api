<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

class LetterStatus
{
    public function __construct(
        private readonly array $data,
    ) {
    }

    public function getLetterId(): int
    {
        return (int) ($this->data['letterID'] ?? 0);
    }

    public function getStatusId(): int
    {
        return (int) ($this->data['statusID'] ?? 0);
    }

    /**
     * Map statusID to LetterStatusId enum. Returns null for unknown status IDs.
     */
    public function getStatus(): ?LetterStatusId
    {
        $statusId = $this->getStatusId();
        return $statusId > 0 ? LetterStatusId::fromStatusId($statusId) : null;
    }

    public function getFileName(): ?string
    {
        return isset($this->data['fileName']) ? (string) $this->data['fileName'] : null;
    }

    public function getStatusDetails(): ?string
    {
        return isset($this->data['statusDetails']) ? (string) $this->data['statusDetails'] : null;
    }

    public function getCreatedDate(): ?string
    {
        return isset($this->data['createdDate']) ? (string) $this->data['createdDate'] : null;
    }

    public function getProcessedDate(): ?string
    {
        return isset($this->data['processedDate']) ? (string) $this->data['processedDate'] : null;
    }

    public function getPrintUploadDate(): ?string
    {
        return isset($this->data['printUploadDate']) ? (string) $this->data['printUploadDate'] : null;
    }

    public function getPrintFeedbackDate(): ?string
    {
        return isset($this->data['printFeedbackDate']) ? (string) $this->data['printFeedbackDate'] : null;
    }

    public function isTestFlag(): bool
    {
        return (bool) ($this->data['testFlag'] ?? false);
    }

    public function getTestEmail(): ?string
    {
        return isset($this->data['testEMail']) ? (string) $this->data['testEMail'] : null;
    }

    public function getBatchId(): ?int
    {
        return isset($this->data['batchID']) ? (int) $this->data['batchID'] : null;
    }

    public function getCustom1(): ?string
    {
        return isset($this->data['custom1']) ? (string) $this->data['custom1'] : null;
    }

    public function getCustom2(): ?string
    {
        return isset($this->data['custom2']) ? (string) $this->data['custom2'] : null;
    }

    public function getCustom3(): ?string
    {
        return isset($this->data['custom3']) ? (string) $this->data['custom3'] : null;
    }

    public function getCustom4(): ?string
    {
        return isset($this->data['custom4']) ? (string) $this->data['custom4'] : null;
    }

    public function getCustom5(): ?string
    {
        return isset($this->data['custom5']) ? (string) $this->data['custom5'] : null;
    }

    public function getRegisteredLetterId(): ?string
    {
        return isset($this->data['registeredLetterID']) ? (string) $this->data['registeredLetterID'] : null;
    }

    /**
     * Einschreiben tracking status code (e.g. DELIVERED, IN_DELIVERY).
     * Resolve description via TrackStatusCodes::getDescription().
     */
    public function getRegisteredLetterStatus(): ?string
    {
        return isset($this->data['registeredLetterStatus']) ? (string) $this->data['registeredLetterStatus'] : null;
    }

    /**
     * Date of the latest Einschreiben status update.
     */
    public function getRegisteredLetterStatusDate(): ?string
    {
        return isset($this->data['registeredLetterStatusDate']) ? (string) $this->data['registeredLetterStatusDate'] : null;
    }

    /**
     * Access raw data by key. Prefer typed getters when available.
     */
    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * @return LetterStatusError[]
     */
    public function getErrors(): array
    {
        $list = $this->data['errorList'] ?? [];
        return array_map(fn (array $item) => LetterStatusError::fromArray($item), $list);
    }
}
