<?php

namespace Plixus\TwigComponentPreviewBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class PreviewProperty
{
    public function __construct(
        public string $type = 'text',       // Form field type
        public ?string $label = null,       // Form label
        public ?string $help = null,        // Help text
        public array $choices = [],         // For choice fields
        public mixed $default = null,       // Default value
        public bool $required = false,      // Required field
        public array $formOptions = [],     // Additional Symfony form options
        public ?string $group = null        // Property group for organization
    ) {
    }
}