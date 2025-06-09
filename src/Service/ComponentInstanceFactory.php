<?php

namespace Plixus\TwigComponentPreviewBundle\Service;

final class ComponentInstanceFactory
{
    public function __construct(
        private ComponentPreviewAnalyzer $analyzer
    ) {
    }

    /**
     * Creates a component instance with default values from PreviewProperty
     */
    public function createWithDefaults(string $componentClassName): object
    {
        if (!class_exists($componentClassName)) {
            throw new \InvalidArgumentException("Class {$componentClassName} does not exist");
        }

        $instance = new $componentClassName();
        $properties = $this->analyzer->analyzePreviewProperties($componentClassName);

        foreach ($properties as $propertyName => $previewProperty) {
            if ($previewProperty->default !== null) {
                $this->setPropertyValue($instance, $propertyName, $previewProperty->default);
            }
        }

        return $instance;
    }

    /**
     * Creates a component instance from form data
     */
    public function createFromFormData(string $componentClassName, array $formData): object
    {
        if (!class_exists($componentClassName)) {
            throw new \InvalidArgumentException("Class {$componentClassName} does not exist");
        }

        $instance = new $componentClassName();

        foreach ($formData as $propertyName => $value) {
            $this->setPropertyValue($instance, $propertyName, $value);
        }

        return $instance;
    }

    /**
     * Updates existing component instance with new data
     */
    public function updateFromFormData(object $component, array $formData): object
    {
        foreach ($formData as $propertyName => $value) {
            $this->setPropertyValue($component, $propertyName, $value);
        }

        return $component;
    }

    /**
     * Extracts current property values as an array
     */
    public function extractPropertyValues(object $component): array
    {
        $className = get_class($component);
        $properties = $this->analyzer->analyzePreviewProperties($className);
        $values = [];

        foreach (array_keys($properties) as $propertyName) {
            $values[$propertyName] = $this->getPropertyValue($component, $propertyName);
        }

        return $values;
    }

    /**
     * Alias for extractPropertyValues - for template usage
     */
    public function extractPropsFromInstance(object $component): array
    {
        return $this->extractPropertyValues($component);
    }

    /**
     * Gets default values for a component class
     */
    public function getDefaultValuesForClass(string $componentClassName): array
    {
        $properties = $this->analyzer->analyzePreviewProperties($componentClassName);
        $defaults = [];

        foreach ($properties as $propertyName => $previewProperty) {
            if ($previewProperty->default !== null) {
                $defaults[$propertyName] = $previewProperty->default;
            }
        }

        return $defaults;
    }

    /**
     * Sets a property value on a component instance
     */
    private function setPropertyValue(object $instance, string $propertyName, mixed $value): void
    {
        $reflection = new \ReflectionClass($instance);

        if (!$reflection->hasProperty($propertyName)) {
            return;
        }

        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        // Handle type-specific conversions
        $value = $this->convertValueForProperty($property, $value);

        $property->setValue($instance, $value);
    }

    /**
     * Converts values according to the property type
     */
    private function convertValueForProperty(\ReflectionProperty $property, mixed $value): mixed
    {
        $type = $property->getType();

        if (!$type instanceof \ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();

        // Handle boolean properties (checkboxes)
        if ($typeName === 'bool') {
            // HTML checkboxes send null/empty when unchecked
            if ($value === null || $value === '' || $value === '0') {
                return false;
            }
            return (bool) $value;
        }

        // Handle nullable string properties  
        if ($typeName === 'string' && $type->allowsNull() && $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * Checks if a property is a nullable string
     */
    private function isNullableStringProperty(\ReflectionProperty $property): bool
    {
        $type = $property->getType();

        if (!$type instanceof \ReflectionNamedType) {
            return false;
        }

        return $type->getName() === 'string' && $type->allowsNull();
    }

    /**
     * Gets a property value from a component instance
     */
    private function getPropertyValue(object $instance, string $propertyName): mixed
    {
        $reflection = new \ReflectionClass($instance);

        if (!$reflection->hasProperty($propertyName)) {
            return null;
        }

        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($instance);
    }
}
