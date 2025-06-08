<?php

namespace Plixus\TwigComponentPreviewBundle\Service;

use Plixus\TwigComponentPreviewBundle\Attribute\PreviewProperty;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class PreviewFormBuilder
{
    public function __construct(
        private ComponentPreviewAnalyzer $analyzer
    ) {
    }

    /**
     * Baut Formular für Component basierend auf PreviewProperty Attributen
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
     * Konvertiert PreviewProperty type zu Symfony Form Type
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
     * Baut Form-Optionen aus PreviewProperty
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
     * Gruppiert Form-Fields nach PreviewProperty->group
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