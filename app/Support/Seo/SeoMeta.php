<?php

declare(strict_types=1);

namespace App\Support\Seo;

/** Metadatos de una página tal como se imprimen en el <head> (ver web/partials/seo). */
final readonly class SeoMeta
{
    /**
     * @param  list<string>  $keywords
     * @param  list<array<string, mixed>>  $schema  Bloques JSON-LD (schema.org).
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public array $keywords = [],
        public ?string $canonical = null,
        public ?string $image = null,
        public ?string $imageAlt = null,
        public string $type = 'website',
        public string $robots = 'index, follow, max-image-preview:large',
        public array $schema = [],
    ) {}

    public function with(array $changes): self
    {
        return new self(...array_merge(get_object_vars($this), $changes));
    }
}
