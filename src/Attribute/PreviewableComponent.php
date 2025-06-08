<?php

namespace Plixus\TwigComponentPreviewBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class PreviewableComponent
{
    public function __construct(
        public ?string $name = null,           // Anzeigename für Preview
        public ?string $description = null,    // Beschreibung der Component
        public ?string $category = null,       // Kategorisierung (z.B. "Form", "Layout")
        public array $examples = []            // Vordefinierte Beispiel-Konfigurationen
    ) {
    }
}