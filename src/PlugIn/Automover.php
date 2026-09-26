<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\PlugIn;

/**
 * Automover plugin: the API positions or repositions the sender and recipient
 * address on the first page so it fits the address window.
 *
 * With address data in the letter (recipient and sender), the API whitens the
 * address area and prints the given addresses. Without address data it searches
 * the document for the addresses within the search area; in that case the
 * recipient must be given as Recipient::forAutomover(). Not supported together
 * with registered mail or a generated cover sheet.
 */
final readonly class Automover implements PlugInInterface
{
    public const string NAME = 'Automover';

    /** Default search area in millimetres from the top left: x, y, width, height. */
    public const string DEFAULT_SEARCH_AREA = '0,0,120,110';

    /**
     * @param string|null $searchArea Search area "x,y,width,height" in millimetres for repositioning
     * @param bool $showSearchAreaOnTest Draw the search area as a rectangle in test mode
     */
    public function __construct(
        public ?string $searchArea = null,
        public bool $showSearchAreaOnTest = false,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    /**
     * @return array<string, mixed>
     */
    public function model(): array
    {
        $parameters = [];
        if ($this->searchArea !== null) {
            $parameters[] = ['name' => 'searchAreaXYWH', 'value' => $this->searchArea];
        }
        if ($this->showSearchAreaOnTest) {
            $parameters[] = ['name' => 'ShowSearchAreaOnTest', 'value' => '1'];
        }

        return ['useAutomover' => true, 'parameters' => $parameters];
    }
}
