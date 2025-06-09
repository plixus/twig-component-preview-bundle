<?php

namespace Plixus\TwigComponentPreviewBundle\Service;

use ReflectionProperty;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Factory service for creating and manipulating component instances.
 *
 * This service provides functionality to create component instances with default values,
 * create instances from form data, update existing instances, and extract property values.
 */
final class ComponentInstanceFactory
{
    /**
     * Constructor.
     *
     * @param ComponentPreviewAnalyzer $analyzer The component preview analyzer service
     * @param TranslatorInterface $translator The translator service for error messages
     */
    public function __construct(
        private ComponentPreviewAnalyzer $analyzer,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * Creates a component instance with default values from PreviewProperty attributes.
     *
     * @param string $componentClassName The fully qualified class name of the component to create
     * @return object The created component instance with default values set
     * @throws \InvalidArgumentException If the component class does not exist
     */
    public function createWithDefaults(string $componentClassName): object
    {
        if (!class_exists($componentClassName)) {
            throw new \InvalidArgumentException(
                $this->translator->trans('errors.class_not_exist', ['%class%' => $componentClassName], 'PlixusTwigComponentPreviewBundle')
            );
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
     * Creates a component instance from form data.
     *
     * @param string $componentClassName The fully qualified class name of the component to create
     * @param array<string, mixed> $formData An associative array of property names to values
     * @return object The created component instance with form data values set
     * @throws \InvalidArgumentException If the component class does not exist
     */
    public function createFromFormData(string $componentClassName, array $formData): object
    {
        if (!class_exists($componentClassName)) {
            throw new \InvalidArgumentException(
                $this->translator->trans('errors.class_not_exist', ['%class%' => $componentClassName], 'PlixusTwigComponentPreviewBundle')
            );
        }

        $instance = new $componentClassName();

        foreach ($formData as $propertyName => $value) {
            $this->setPropertyValue($instance, $propertyName, $value);
        }

        return $instance;
    }

    /**
     * Updates an existing component instance with new data.
     *
     * @param object $component The component instance to update
     * @param array<string, mixed> $formData An associative array of property names to values
     * @return object The updated component instance
     */
    public function updateFromFormData(object $component, array $formData): object
    {
        foreach ($formData as $propertyName => $value) {
            $this->setPropertyValue($component, $propertyName, $value);
        }

        return $component;
    }

    /**
     * Extracts current property values as an array.
     *
     * @param object $component The component instance to extract values from
     * @return array<string, mixed> An associative array of property names to values
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
     * Alias for extractPropertyValues - for template usage.
     *
     * @param object $component The component instance to extract values from
     * @return array<string, mixed> An associative array of property names to values
     */
    public function extractPropsFromInstance(object $component): array
    {
        return $this->extractPropertyValues($component);
    }

    /**
     * Gets default values for a component class.
     *
     * @param string $componentClassName The fully qualified class name of the component
     * @return array<string, mixed> An associative array of property names to default values
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
     * Sets a property value on a component instance.
     *
     * @param object $instance The component instance to set the property on
     * @param string $propertyName The name of the property to set
     * @param mixed $value The value to set
     * @return void
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
     * Converts values according to the property type.
     *
     * @param ReflectionProperty $property The reflection property to get type information from
     * @param mixed $value The value to convert
     * @return mixed The converted value
     */
    private function convertValueForProperty(ReflectionProperty $property, mixed $value): mixed
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
     * Checks if a property is a nullable string.
     *
     * @param ReflectionProperty $property The reflection property to check
     * @return bool True if the property is a nullable string, false otherwise
     */
    private function isNullableStringProperty(ReflectionProperty $property): bool
    {
        $type = $property->getType();

        if (!$type instanceof \ReflectionNamedType) {
            return false;
        }

        return $type->getName() === 'string' && $type->allowsNull();
    }

    /**
     * Gets a property value from a component instance.
     *
     * @param object $instance The component instance to get the property value from
     * @param string $propertyName The name of the property to get
     * @return mixed The value of the property
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
