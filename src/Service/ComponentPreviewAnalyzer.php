<?php

namespace Plixus\TwigComponentPreviewBundle\Service;

use Plixus\TwigComponentPreviewBundle\Attribute\PreviewableComponent;
use Plixus\TwigComponentPreviewBundle\Attribute\PreviewProperty;

final class ComponentPreviewAnalyzer
{
    /**
     * Checks if class is marked as PreviewableComponent
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
     * Analyzes all PreviewProperty attributes of a component class
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
     * Gets PreviewableComponent metadata
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
     * Finds all PreviewableComponent classes in a namespace
     */
    public function discoverPreviewableComponents(string $namespace): array
    {
        // Here would be a more complex implementation that
        // actually searches all classes in a namespace
        // For now we return an empty array
        return [];
    }

    /**
     * Validates component instance against PreviewProperty constraints
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
                    $errors[$propertyName] = 'This field is required';
                }
            }
        }

        return $errors;
    }
}
