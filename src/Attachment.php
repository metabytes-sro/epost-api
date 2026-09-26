<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api;

use MetabytesSRO\EPost\Api\Exception\ValidationException;

/**
 * A PDF document to send: the letter itself or a custom cover sheet.
 *
 * The API requires PDF/A-1b in DIN A4 portrait, at most 20 MB and 94 pages for
 * the letter and at most 500 KB for a cover sheet. The file name must be unique
 * per submission and may only contain letters, digits, dots, dashes and
 * underscores.
 */
final readonly class Attachment
{
    public const int MAX_LETTER_BYTES = 20 * 1024 * 1024;
    public const int MAX_COVER_SHEET_BYTES = 500 * 1024;
    public const int MAX_FILE_NAME_LENGTH = 200;

    private const string FILE_NAME_PATTERN = '/^[A-Za-z0-9._-]+$/';

    /**
     * @param string $fileName Name reported to the API and shown in status queries
     * @param string $contents Raw PDF bytes
     *
     * @throws ValidationException when the contents are not a PDF or the name is invalid
     */
    public function __construct(
        public string $fileName,
        public string $contents,
    ) {
        Validate::notBlank('fileName', $fileName);
        Validate::maxLength('fileName', $fileName, self::MAX_FILE_NAME_LENGTH);
        Validate::matches('fileName', $fileName, self::FILE_NAME_PATTERN, 'may only contain letters, digits, dots, dashes and underscores');
        if (!str_starts_with($contents, '%PDF-')) {
            throw new ValidationException(sprintf('"%s" is not a PDF document', $fileName));
        }
    }

    /**
     * @param string|null $fileName Defaults to the file's base name
     *
     * @throws ValidationException when the file does not exist, cannot be read or is not a PDF
     */
    public static function fromFile(string $path, ?string $fileName = null): self
    {
        if (!is_file($path)) {
            throw new ValidationException(sprintf('The file "%s" does not exist', $path));
        }
        $contents = @file_get_contents($path);
        if ($contents === false) {
            throw new ValidationException(sprintf('The file "%s" is not readable', $path));
        }

        return new self($fileName ?? basename($path), $contents);
    }

    /**
     * @throws ValidationException
     */
    public static function fromString(string $contents, string $fileName): self
    {
        return new self($fileName, $contents);
    }

    public function size(): int
    {
        return strlen($this->contents);
    }

    /**
     * @throws ValidationException when the document is larger than the given limit
     */
    public function assertMaxSize(int $maxBytes, string $what): void
    {
        if ($this->size() > $maxBytes) {
            throw new ValidationException(sprintf(
                'The %s "%s" is %d bytes, the API accepts at most %d bytes',
                $what,
                $this->fileName,
                $this->size(),
                $maxBytes,
            ));
        }
    }

    /**
     * Base64 representation as the API expects it (line-wrapped at 76 characters).
     */
    public function base64(): string
    {
        return chunk_split(base64_encode($this->contents));
    }
}
