<?php

namespace Plixus\TwigComponentPreviewBundle\Service;

use Plixus\TwigComponentPreviewBundle\Attribute\PreviewableComponent;
use Plixus\TwigComponentPreviewBundle\Attribute\PreviewProperty;

final class ComponentPreviewAnalyzer
{
    /**
     * Prüft ob Klasse als PreviewableComponent markiert ist
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
     * Analysiert alle PreviewProperty Attribute einer Component-Klasse
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
     * Holt PreviewableComponent Metadaten
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
     * Findet alle PreviewableComponent Klassen in einem Namespace
     */
    public function discoverPreviewableComponents(string $namespace): array
    {
        // Hier würde eine komplexere Implementierung kommen, die
        // tatsächlich alle Klassen in einem Namespace durchsucht
        // Für jetzt returnieren wir ein leeres Array
        return [];
    }

    /**
     * Validiert Component-Instance gegen PreviewProperty Constraints
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