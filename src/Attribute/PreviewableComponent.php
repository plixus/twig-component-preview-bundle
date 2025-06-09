<?php

namespace Plixus\TwigComponentPreviewBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class PreviewableComponent
{
    public function __construct(
        public ?string $name = null,           // Display name for Preview
        public ?string $description = null,    // Description for Component
        public ?string $category = null,       // Category (e.g. "Form", "Layout")
        public array $examples = []            // Predefined Example-Configuration
    ) {
    }
}