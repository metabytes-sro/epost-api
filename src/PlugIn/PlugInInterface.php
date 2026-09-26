<?php

declare(strict_types=1);

namespace MetabytesSRO\EPost\Api\PlugIn;

/**
 * A plugin attached to a letter: an entry of the API's plugInList with a name
 * and a plugin-specific model.
 */
interface PlugInInterface
{
    /**
     * Name of the plugin as the API knows it, e.g. "UploadManagement".
     */
    public function name(): string;

    /**
     * The plugin model sent as plugInModel.
     *
     * @return array<string, mixed>
     */
    public function model(): array;
}
