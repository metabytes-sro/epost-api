<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

/**
 * Feedback of a plugin the letter was sent with, as reported in a letter status.
 * The model is plugin-specific; PremiumAdress reports the corrected address
 * fields, UploadManagement echoes the due options.
 */
final readonly class PlugInFeedback
{
    /**
     * @param array<string, mixed> $model Raw plugInFeedbackModel
     */
    public function __construct(
        public string $name,
        public array $model,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $model = $data['plugInFeedbackModel'] ?? null;

        return new self(
            Json::string($data['plugInName'] ?? null) ?? '',
            is_array($model) ? Json::objectList([$model])[0] : [],
        );
    }
}
