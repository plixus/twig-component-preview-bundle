# PlixusTwigComponentPreviewBundle - Symfony Bundle

## Bundle-Überblick

**Vendor:** Plixus  
**Bundle Name:** TwigComponentPreviewBundle  
**Namespace:** `Plixus\TwigComponentPreviewBundle`  
**Composer Package:** `plixus/twig-component-preview-bundle`

## Ziel
Bereitstellung von Live-Preview-Funktionalität für Symfony Twig Components mit automatischen Form-Updates. Das Bundle bietet sowohl High-Level (automatisch) als auch Low-Level (manuell) Ansätze für Component-Previews.

## Bundle-Architektur

### 1. Attribute (Core Features)

#### PreviewableComponent Attribut
```php
// src/Attribute/PreviewableComponent.php
<?php

namespace Plixus\TwigComponentPreviewBundle\Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class PreviewableComponent
{
    public function __construct(
        public ?string $name = null,           // Anzeigename für Preview
        public ?string $description = null,    // Beschreibung der Component
        public ?string $category = null,       // Kategorisierung (z.B. "Form", "Layout")
        public array $examples = []            // Vordefinierte Beispiel-Konfigurationen
    ) {}
}
```

#### PreviewProperty Attribut
```php
// src/Attribute/PreviewProperty.php
<?php

namespace Plixus\TwigComponentPreviewBundle\Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class PreviewProperty
{
    public function __construct(
        public string $type = 'text',           // Form field type
        public ?string $label = null,           // Form label
        public ?string $help = null,            // Help text
        public array $choices = [],             // For choice fields
        public mixed $default = null,           // Default value
        public bool $required = false,          // Required field
        public array $formOptions = [],        // Additional Symfony form options
        public ?string $group = null           // Property group for organization
    ) {}
}
```

### 2. Core Services

#### ComponentPreviewAnalyzer
```php
// src/Service/ComponentPreviewAnalyzer.php
<?php

namespace Plixus\TwigComponentPreviewBundle\Service;

final class ComponentPreviewAnalyzer
{
    // Prüft ob Klasse als PreviewableComponent markiert ist
    public function isPreviewable(string $className): bool
    
    // Analysiert alle PreviewProperty Attribute einer Component-Klasse
    public function analyzePreviewProperties(string $className): array
    
    // Holt PreviewableComponent Metadaten
    public function getComponentMetadata(string $className): ?PreviewableComponent
    
    // Findet alle PreviewableComponent Klassen in einem Namespace
    public function discoverPreviewableComponents(string $namespace): array
    
    // Validiert Component-Instance gegen PreviewProperty Constraints
    public function validateComponentInstance(object $component): array
}
```

#### PreviewFormBuilder
```php
// src/Service/PreviewFormBuilder.php
<?php

namespace Plixus\TwigComponentPreviewBundle\Service;

use Symfony\Component\Form\FormBuilderInterface;

final class PreviewFormBuilder
{
    public function __construct(
        private ComponentPreviewAnalyzer $analyzer
    ) {}
    
    // Baut Formular für Component basierend auf PreviewProperty Attributen
    public function buildPreviewForm(FormBuilderInterface $builder, string $componentClassName): void
    
    // Konvertiert PreviewProperty type zu Symfony Form Type
    private function resolveFormType(string $type): string
    
    // Baut Form-Optionen aus PreviewProperty
    private function buildFormOptions(PreviewProperty $property): array
    
    // Gruppiert Form-Fields nach PreviewProperty->group
    public function getPropertyGroups(string $componentClassName): array
}
```

#### ComponentInstanceFactory
```php
// src/Service/ComponentInstanceFactory.php
<?php

namespace Plixus\TwigComponentPreviewBundle\Service;

final class ComponentInstanceFactory
{
    // Erstellt Component-Instance mit Default-Werten aus PreviewProperty
    public function createWithDefaults(string $componentClassName): object
    
    // Erstellt Component-Instance aus Form-Daten
    public function createFromFormData(string $componentClassName, array $formData): object
    
    // Updated bestehende Component-Instance mit neuen Daten
    public function updateFromFormData(object $component, array $formData): object
    
    // Extrahiert aktuelle Property-Werte als Array
    public function extractPropertyValues(object $component): array
}
```

### 3. Live Components (für Bundle-Nutzer)

#### PreviewStage Live Component
```php
// src/TwigComponent/PreviewStage.php
<?php

namespace Plixus\TwigComponentPreviewBundle\TwigComponent;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent(name: 'PlixusPreviewStage')]
final class PreviewStage
{
    #[LiveProp]
    public string $componentClass;          // FQCN der zu previewenden Component
    
    #[LiveProp(writable: true)]
    public array $componentData = [];       // Live Component Data (auto-synced)
    
    public array $initialData = [];        // Initiale Form-Daten
    public bool $showDocumentation = true; // Zeige Component-Dokumentation
    public bool $showCodeExample = true;   // Zeige Code-Beispiel
    public string $layout = 'horizontal';  // 'horizontal' | 'vertical'
}
```

#### ComponentDocumentation Component
```php
// src/TwigComponent/ComponentDocumentation.php
<?php

namespace Plixus\TwigComponentPreviewBundle\TwigComponent;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'PlixusComponentDocumentation')]
final class ComponentDocumentation
{
    public string $componentClass;          // FQCN der Component
    public bool $showProperties = true;     // Zeige Property-Dokumentation
    public bool $showExamples = true;       // Zeige Beispiele
    public bool $showMetadata = true;       // Zeige Component-Metadaten
}
```

### 4. Live Component Features

#### Automatische Form-Updates
- **Two-Way Data Binding**: `data-model="componentData[fieldName]"`
- **Live Updates**: Änderungen werden sofort übertragen ohne Page-Reload
- **Kein Scrollen**: Updates passieren in-place
- **Debouncing**: Text-Eingaben werden automatisch verzögert übertragen

#### Template Integration
```twig
{# Form fields with live binding #}
{{ form_widget(child, {
    'attr': {
        'data-model': 'componentData[' ~ child.vars.name ~ ']',
        'data-action': 'input->live#update change->live#update'
    }
}) }}
```

## Template-Struktur

### PreviewStage Template
```twig
{# templates/components/PlixusPreviewStage.html.twig #}
<div 
    class="plixus-preview-stage plixus-preview-stage--{{ layout }}"
    {{ attributes }}
>
    <div class="plixus-preview-stage__container">
        <div class="plixus-preview-stage__form">
            <h3>Component Properties</h3>
            {{ form_start(form) }}
            
            {% for child in form.children %}
                <div class="form-group">
                    {{ form_label(child) }}
                    {{ form_widget(child, {
                        'attr': {
                            'data-model': 'componentData[' ~ child.vars.name ~ ']',
                            'data-action': 'input->live#update change->live#update'
                        }
                    }) }}
                    {{ form_help(child) }}
                    {{ form_errors(child) }}
                </div>
            {% endfor %}
            
            {{ form_end(form) }}
        </div>
        
        <div class="plixus-preview-stage__preview">
            <h3>Live Preview</h3>
            
            <div class="plixus-preview-stage__component">
                {{ component(componentName, componentProps) }}
            </div>
            
            {% if showDocumentation %}
                {{ component('PlixusComponentDocumentation', {
                    componentClass: componentClass
                }) }}
            {% endif %}
            
            {% if showCodeExample %}
                <div class="plixus-preview-stage__code">
                    <h4>Twig Code</h4>
                    <pre><code>{{ '{{' }} component('{{ componentName }}', {{ componentProps|json_encode|raw }}) {{ '}}' }}</code></pre>
                </div>
            {% endif %}
        </div>
    </div>
</div>
```

## Bundle Configuration

### Bundle-Klasse
```php
// src/PlixusTwigComponentPreviewBundle.php
<?php

namespace Plixus\TwigComponentPreviewBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class PlixusTwigComponentPreviewBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
```

### Services Configuration
```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    Plixus\TwigComponentPreviewBundle\Service\:
        resource: '../src/Service/'
        public: true

    Plixus\TwigComponentPreviewBundle\TwigComponent\:
        resource: '../src/TwigComponent/'
        tags: [twig.component]
```

## Nutzung durch Bundle-User

### 1. Component mit Attributen markieren
```php
<?php

namespace App\Twig\Components;

use Plixus\TwigComponentPreviewBundle\Attribute\PreviewableComponent;
use Plixus\TwigComponentPreviewBundle\Attribute\PreviewProperty;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
#[PreviewableComponent(
    name: 'Alert Component',
    description: 'Displays contextual feedback messages',
    category: 'Feedback'
)]
final class Alert
{
    #[PreviewProperty(
        type: 'choice',
        label: 'Alert Type',
        choices: ['info', 'success', 'warning', 'danger'],
        default: 'info',
        help: 'The visual style of the alert'
    )]
    public string $type = 'info';

    #[PreviewProperty(
        type: 'checkbox',
        label: 'Dismissible',
        default: false
    )]
    public bool $dismissible = false;

    #[PreviewProperty(
        type: 'textarea',
        label: 'Message',
        default: 'This is an alert message',
        required: true,
        formOptions: ['attr' => ['rows' => 3]]
    )]
    public string $message = 'This is an alert message';
}
```

### 2. High-Level Ansatz (Empfohlen)
```php
<?php

namespace App\Controller;

use App\Twig\Components\Alert;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ComponentPreviewController extends AbstractController
{
    #[Route('/preview/high-level/{component}')]
    public function highLevel(string $component): Response
    {
        $componentClasses = ['alert' => Alert::class];
        
        return $this->render('preview/high_level.html.twig', [
            'component_class' => $componentClasses[$component]
        ]);
    }
}
```

### 3. High-Level Template (Ein-Zeiler!)
```twig
{# templates/preview/high_level.html.twig #}
{{ component('PlixusPreviewStage', {
    componentClass: component_class,
    showDocumentation: true,
    showCodeExample: true
}) }}
```

### 4. Low-Level Ansatz (Vollständige Kontrolle)
```php
#[Route('/preview/low-level/{component}')]
public function lowLevel(Request $request, string $component): Response
{
    $componentClass = $componentClasses[$component];
    
    // Manuelle Form-Erstellung
    $formBuilder = $this->createFormBuilder()
        ->setAction($this->generateUrl('preview_low_level', ['component' => $component]))
        ->setMethod('POST');
    
    $this->formBuilder->buildPreviewForm($formBuilder, $componentClass);
    $form = $formBuilder->getForm();
    
    // Manuelle Component-Instance-Verwaltung
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        $componentInstance = $this->instanceFactory->createFromFormData(
            $componentClass, 
            $form->getData()
        );
    } else {
        $componentInstance = $this->instanceFactory->createWithDefaults($componentClass);
    }

    return $this->render('preview/low_level.html.twig', [
        'component_class' => $componentClass,
        'component_instance' => $componentInstance,
        'form' => $form->createView()
    ]);
}
```

## Bundle-Struktur

```
PlixusTwigComponentPreviewBundle/
├── src/
│   ├── Attribute/
│   │   ├── PreviewableComponent.php
│   │   └── PreviewProperty.php
│   ├── Service/
│   │   ├── ComponentPreviewAnalyzer.php
│   │   ├── PreviewFormBuilder.php
│   │   └── ComponentInstanceFactory.php
│   ├── TwigComponent/
│   │   ├── PreviewStage.php
│   │   └── ComponentDocumentation.php
│   └── PlixusTwigComponentPreviewBundle.php
├── templates/
│   └── components/
│       ├── PlixusPreviewStage.html.twig
│       └── PlixusComponentDocumentation.html.twig
├── assets/
│   └── dist/
│       ├── preview_stage_controller.js
│       └── preview.css
├── config/
│   └── services.yaml
└── composer.json
```

## Composer.json
```json
{
    "name": "plixus/twig-component-preview-bundle",
    "description": "Symfony Bundle für Preview-System von Twig Live Components",
    "type": "symfony-bundle",
    "require": {
        "php": ">=8.1",
        "symfony/framework-bundle": "^6.3|^7.0",
        "symfony/twig-bundle": "^6.3|^7.0",
        "symfony/ux-twig-component": "^2.8"
    },
    "autoload": {
        "psr-4": {
            "Plixus\\TwigComponentPreviewBundle\\": "src/"
        }
    }
}
```

## Vorteile dieser Architektur

✅ **Live-Updates ohne Page-Reload** - Symfony Live Components für echte Reactivity  
✅ **Zwei Ansätze** - High-Level (automatisch) und Low-Level (manuell) für verschiedene Use Cases  
✅ **Kein Scrollen** - Updates passieren in-place ohne Navigation  
✅ **Symfony Standard-konform** - Nutzt Live Components, Services, und Attribute  
✅ **Erweiterbar** - Services sind public und können erweitert werden  
✅ **Typsicher** - Starke Typisierung durch PHP 8+ Attribute  
✅ **Testbar** - Services sind gut isoliert und testbar  
✅ **Wiederverwendbar** - Bundle kann überall eingesetzt werden

## Features

### ✨ High-Level Ansatz
- **Ein-Zeiler**: Nur ein Component-Aufruf im Template
- **Automatische Form-Generierung**: Basierend auf PreviewProperty Attributen  
- **Live Data Binding**: Automatische Synchronisation zwischen Form und Component
- **Instant Updates**: Änderungen sind sofort sichtbar

### 🔧 Low-Level Ansatz  
- **Vollständige Kontrolle**: Manuelles Form-Building und Component-Handling
- **Custom Logic**: Eigene Validierung, Transformationen, etc.
- **Flexibilität**: Für komplexe Use Cases und spezielle Anforderungen

### 🚀 Live Component Features
- **Two-Way Data Binding**: `#[LiveProp(writable: true)]`
- **Auto-Submit**: Text-Eingaben mit Debouncing, sofortige Updates bei Dropdowns/Checkboxes
- **Kein JavaScript im Template**: Alles über Symfony Live Components