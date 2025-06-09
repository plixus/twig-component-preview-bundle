<?php

namespace Plixus\TwigComponentPreviewBundle\Service;

use Plixus\TwigComponentPreviewBundle\Attribute\PreviewableComponent;
use Plixus\TwigComponentPreviewBundle\Attribute\PreviewProperty;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Analyzes component classes for preview-related attributes and metadata.
 *
 * This service provides functionality to inspect component classes for
 * PreviewableComponent and PreviewProperty attributes, extract metadata,
 * and validate component instances against constraints.
 */
final class ComponentPreviewAnalyzer
{
    /**
     * Constructor.
     *
     * @param TranslatorInterface $translator The translator service for error messages
     */
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    /**
     * Checks if a class is marked as PreviewableComponent.
     *
     * @param string $className The fully qualified class name to check
     * @return bool True if the class has the PreviewableComponent attribute, false otherwise
     */
    public function isPreviewable(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        $reflection = new \ReflectionClass($className);
        $attributes = $reflection->getAttributes(PreviewableComponent::class);

        return count($attributes) > 0;
    }

    /**
     * Analyzes all PreviewProperty attributes of a component class.
     *
     * @param string $className The fully qualified class name to analyze
     * @return array<string, PreviewProperty> An associative array of property names to PreviewProperty instances
     */
    public function analyzePreviewProperties(string $className): array
    {
        if (!class_exists($className)) {
            return [];
        }

        $reflection = new \ReflectionClass($className);
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(PreviewProperty::class);

            if (count($attributes) > 0) {
                $attribute = $attributes[0]->newInstance();
                $properties[$property->getName()] = $attribute;
            }
        }

        return $properties;
    }

    /**
     * Gets PreviewableComponent metadata for a class.
     *
     * @param string $className The fully qualified class name to get metadata for
     * @return PreviewableComponent|null The PreviewableComponent instance or null if not found
     */
    public function getComponentMetadata(string $className): ?PreviewableComponent
    {
        if (!class_exists($className)) {
            return null;
        }

        $reflection = new \ReflectionClass($className);
        $attributes = $reflection->getAttributes(PreviewableComponent::class);

        if (count($attributes) === 0) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /**
     * Finds all PreviewableComponent classes in a namespace.
     *
     * @param string $namespace The namespace to search in
     * @return array<string> An array of fully qualified class names that have the PreviewableComponent attribute
     */
    public function discoverPreviewableComponents(string $namespace): array
    {
        // Here would be a more complex implementation that
        // actually searches all classes in a namespace
        // For now we return an empty array
        return [];
    }

    /**
     * Validates component instance against PreviewProperty constraints.
     *
     * Checks if all required properties have values.
     *
     * @param object $component The component instance to validate
     * @return array<string, string> An associative array of property names to error messages, empty if no errors
     */
    public function validateComponentInstance(object $component): array
    {
        $errors = [];
        $className = get_class($component);
        $properties = $this->analyzePreviewProperties($className);

        foreach ($properties as $propertyName => $previewProperty) {
            if ($previewProperty->required) {
                $reflection = new \ReflectionClass($component);
                $property = $reflection->getProperty($propertyName);
                $property->setAccessible(true);
                $value = $property->getValue($component);

                if (empty($value)) {
                    $errors[$propertyName] = $this->translator->trans('errors.field_required', [], 'PlixusTwigComponentPreviewBundle');
                }
            }
        }

        return $errors;
    }
}
