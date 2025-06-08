<?php

namespace Plixus\TwigComponentPreviewBundle\TwigComponent;

use Plixus\TwigComponentPreviewBundle\Service\ComponentPreviewAnalyzer;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'PlixusComponentDocumentation')]
final class ComponentDocumentation
{
    public string $componentClass;          // FQCN der Component
    public bool $showProperties = true;     // Zeige Property-Dokumentation
    public bool $showExamples = true;       // Zeige Beispiele
    public bool $showMetadata = true;       // Zeige Component-Metadaten

    public function __construct(
        private ComponentPreviewAnalyzer $analyzer
    ) {
    }

    public function mount(
        string $componentClass,
        bool $showProperties = true,
        bool $showExamples = true,
        bool $showMetadata = true
    ): void {
        $this->componentClass = $componentClass;
        $this->showProperties = $showProperties;
        $this->showExamples = $showExamples;
        $this->showMetadata = $showMetadata;
    }

    public function getMetadata(): ?\Plixus\TwigComponentPreviewBundle\Attribute\PreviewableComponent
    {
        return $this->analyzer->getComponentMetadata($this->componentClass);
    }

    public function getProperties(): array
    {
        return $this->analyzer->analyzePreviewProperties($this->componentClass);
    }
}