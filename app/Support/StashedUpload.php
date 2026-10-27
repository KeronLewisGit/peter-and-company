<?php

namespace App\Support;

/** A file a visitor attached on an earlier, unsuccessful try of a form (see UploadStash). */
final readonly class StashedUpload
{
    public function __construct(
        public string $path,
        public string $originalName,
        public ?string $mime,
        public int $size,
    ) {}
}
