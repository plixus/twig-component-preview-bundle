<?php

namespace Plixus\TwigComponentPreviewBundle\Service;

use Plixus\TwigComponentPreviewBundle\Attribute\PreviewProperty;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Service for building forms for component previews.
 *
 * This service creates Symfony forms based on PreviewProperty attributes
 * defined on component classes, allowing for dynamic form generation.
 */
final class PreviewFormBuilder
{
    /**
     * Constructor.
     *
     * @param ComponentPreviewAnalyzer $analyzer The component preview analyzer service
     */
    public function __construct(
        private ComponentPreviewAnalyzer $analyzer
    ) {
    }

    /**
     * Builds a form for a component based on PreviewProperty attributes.
     *
     * @param FormBuilderInterface $builder The form builder to add fields to
     * @param string $componentClassName The fully qualified class name of the component
     * @return void
     */
    public function buildPreviewForm(FormBuilderInterface $builder, string $componentClassName): void
    {
        $properties = $this->analyzer->analyzePreviewProperties($componentClassName);
        $groups = $this->getPropertyGroups($componentClassName);

        foreach ($properties as $propertyName => $previewProperty) {
            $formType = $this->resolveFormType($previewProperty->type);
            $options = $this->buildFormOptions($previewProperty);

            $builder->add($propertyName, $formType, $options);
        }
    }

    /**
     * Converts PreviewProperty type to Symfony Form Type.
     *
     * @param string $type The PreviewProperty type
     * @return string The corresponding Symfony Form Type class
     */
    private function resolveFormType(string $type): string
    {
        return match ($type) {
            'text' => TextType::class,
            'textarea' => TextareaType::class,
            'number' => NumberType::class,
            'checkbox' => CheckboxType::class,
            'choice' => ChoiceType::class,
            default => TextType::class,
        };
    }

    /**
     * Builds form options from a PreviewProperty.
     *
     * @param PreviewProperty $property The PreviewProperty to build options from
     * @return array<string, mixed> The form options
     */
    private function buildFormOptions(PreviewProperty $property): array
    {
        $options = [
            'required' => $property->required,
            'attr' => [],
        ];

        if ($property->label !== null) {
            $options['label'] = $property->label;
        }

        if ($property->help !== null) {
            $options['help'] = $property->help;
        }

        if ($property->default !== null) {
            $options['data'] = $property->default;
        }

        if (!empty($property->choices)) {
            $choices = [];
            foreach ($property->choices as $choice) {
                $choices[ucfirst($choice)] = $choice;
            }
            $options['choices'] = $choices;
        }

        // Merge additional form options
        if (!empty($property->formOptions)) {
            $options = array_merge($options, $property->formOptions);
        }

        return $options;
    }

    /**
     * Groups form fields by PreviewProperty->group.
     *
     * @param string $componentClassName The fully qualified class name of the component
     * @return array<string, array<string>> An associative array of group names to arrays of property names
     */
    public function getPropertyGroups(string $componentClassName): array
    {
        $properties = $this->analyzer->analyzePreviewProperties($componentClassName);
        $groups = [];

        foreach ($properties as $propertyName => $previewProperty) {
            $groupName = $previewProperty->group ?? 'default';

            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [];
            }

            $groups[$groupName][] = $propertyName;
        }

        return $groups;
    }
}
