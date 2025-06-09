<?php

namespace Plixus\TwigComponentPreviewBundle\TwigComponent;

use Plixus\TwigComponentPreviewBundle\Service\ComponentInstanceFactory;
use Plixus\TwigComponentPreviewBundle\Service\ComponentPreviewAnalyzer;
use Plixus\TwigComponentPreviewBundle\Service\PreviewFormBuilder;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'PlixusPreviewStage')]
final class PreviewStage
{
    use DefaultActionTrait;
    
    #[LiveProp]
    public string $componentClass;
    
    #[LiveProp(writable: true)]
    public array $componentData = [];
    
    #[LiveProp]
    public array $initialData = [];
    
    #[LiveProp]
    public bool $showDocumentation = true;
    
    #[LiveProp]
    public bool $showCodeExample = true;
    
    #[LiveProp]
    public string $layout = 'horizontal';
    
    #[LiveProp]
    public string $theme = 'light';
    
    #[LiveProp]
    public ?string $formWidth = null;
    
    #[LiveProp]
    public bool $showExamples = false;
    
    #[LiveProp]
    public string $codeSyntax = 'function';
    
    #[LiveProp]
    public bool $showBothSyntax = false;
    
    #[LiveProp]
    public array $customOptions = [];

    public function __construct(
        private PreviewFormBuilder $formBuilder,
        private ComponentInstanceFactory $instanceFactory,
        private ComponentPreviewAnalyzer $analyzer,
        private FormFactoryInterface $formFactory
    ) {
    }

    public function mount(
        string $componentClass,
        array $initialData = [],
        bool $showDocumentation = true,
        bool $showCodeExample = true,
        string $layout = 'horizontal',
        string $theme = 'light',
        ?string $formWidth = null,
        bool $showExamples = false,
        string $codeSyntax = 'function',
        bool $showBothSyntax = false,
        array $customOptions = []
    ): void {
        $this->componentClass = $componentClass;
        $this->initialData = $initialData;
        $this->showDocumentation = $showDocumentation;
        $this->showCodeExample = $showCodeExample;
        $this->layout = $layout;
        $this->theme = $theme;
        $this->formWidth = $formWidth;
        $this->showExamples = $showExamples;
        $this->codeSyntax = $codeSyntax;
        $this->showBothSyntax = $showBothSyntax;
        $this->customOptions = $customOptions;
        
        // Initialize component data with defaults if empty
        if (empty($this->componentData)) {
            $this->componentData = array_merge(
                $this->instanceFactory->getDefaultValuesForClass($this->componentClass),
                $this->initialData
            );
        }
    }
    
    #[ExposeInTemplate]
    public function getForm()
    {
        $formBuilder = $this->formFactory->createBuilder(FormType::class, null, [
            // CSRF protection is enabled by default - Live Components handle this automatically
        ]);
        $this->formBuilder->buildPreviewForm($formBuilder, $this->componentClass);
        
        return $formBuilder->getForm()->createView();
    }
    
    #[ExposeInTemplate]
    public function getComponentInstance(): object
    {
        // Clean the component data to handle null values for boolean properties
        $cleanedData = $this->cleanComponentData($this->componentData);
        
        return $this->instanceFactory->createFromFormData(
            $this->componentClass,
            $cleanedData
        );
    }
    
    /**
     * Clean component data to handle null values for boolean properties
     */
    private function cleanComponentData(array $data): array
    {
        $properties = $this->analyzer->analyzePreviewProperties($this->componentClass);
        $cleanedData = [];
        $reflection = new \ReflectionClass($this->componentClass);
        
        // Process all preview properties, not just the ones in data
        foreach ($properties as $propertyName => $previewProperty) {
            $value = $data[$propertyName] ?? null;
            
            if ($reflection->hasProperty($propertyName)) {
                $property = $reflection->getProperty($propertyName);
                $type = $property->getType();
                
                if ($type instanceof \ReflectionNamedType && $type->getName() === 'bool') {
                    // Convert null/empty values to false for boolean properties
                    $cleanedData[$propertyName] = ($value === null || $value === '' || $value === '0') ? false : (bool) $value;
                } else if ($type instanceof \ReflectionNamedType && $type->getName() === 'string' && $type->allowsNull() && $value === '') {
                    // Handle nullable strings
                    $cleanedData[$propertyName] = null;
                } else {
                    $cleanedData[$propertyName] = $value;
                }
            } else {
                $cleanedData[$propertyName] = $value;
            }
        }
        
        // Add any additional data that might not be preview properties
        foreach ($data as $propertyName => $value) {
            if (!isset($cleanedData[$propertyName])) {
                $cleanedData[$propertyName] = $value;
            }
        }
        
        return $cleanedData;
    }
    
    #[ExposeInTemplate]
    public function getComponentName(): string
    {
        $parts = explode('\\', $this->componentClass);
        return end($parts);
    }
    
    #[ExposeInTemplate]
    public function getComponentProps(): array
    {
        return $this->cleanComponentData($this->componentData);
    }
    
}