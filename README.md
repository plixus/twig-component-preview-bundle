# PlixusTwigComponentPreviewBundle

A Symfony bundle that automatically generates **live preview interfaces** for your Twig Components. Change form values and see your components update in real-time without page reloads.

## 🎯 What This Bundle Does

- **Automatic Form Generation**: Create forms from your component properties using PHP attributes
- **Live Preview**: Real-time updates using Symfony Live Components - no custom JavaScript needed
- **Multiple Syntax Support**: Preview both `{{ component() }}` and `<twig:Component />` syntax
- **Professional UI**: Swiss Design inspired interface with customizable CSS variables
- **Template Override**: Full customization via Symfony's template override system

## 🚀 Installation

```bash
composer require plixus/twig-component-preview-bundle
```

**Requirements:**
- Symfony 7.3+
- PHP 8.1+
- Symfony UX Twig Component
- Symfony UX Live Component

## ⚡ Quick Start (5 Minutes)

### Step 1: Create Your Component

First, create a regular Twig Component and mark it for preview with attributes:

```php
<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Plixus\TwigComponentPreviewBundle\Attribute\PreviewableComponent;
use Plixus\TwigComponentPreviewBundle\Attribute\PreviewProperty;

#[AsTwigComponent] // ← Normal Twig Component
#[PreviewableComponent(        // ← Add this for preview
    name: 'Alert Component',
    description: 'Displays contextual feedback messages'
)]
class Alert
{
    #[PreviewProperty(
        type: 'choice',
        label: 'Alert Type',
        choices: ['info', 'success', 'warning', 'danger'],
        default: 'info',
        help: 'The visual style and semantic meaning'
    )]
    public string $type = 'info';
    
    #[PreviewProperty(
        type: 'checkbox',
        label: 'Dismissible',
        default: false,
        help: 'Whether the alert can be closed by the user'
    )]
    public bool $dismissible = false;
    
    #[PreviewProperty(
        type: 'textarea',
        label: 'Message',
        default: 'This is an alert message',
        required: true,
        help: 'The main message text to display'
    )]
    public string $message = 'This is an alert message';
}
```

### Step 2: Create the Controller

**Important: You need to manually map component names to classes** - the bundle doesn't auto-discover them for security reasons:

```php
<?php

namespace App\Controller;

use App\Twig\Components\Alert;
use App\Twig\Components\Button;
use App\Twig\Components\Card;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ComponentPreviewController extends AbstractController
{
    #[Route('/preview/{component}', name: 'component_preview')]
    public function preview(string $component): Response
    {
        // Manual mapping - you define which components are previewable
        $componentClasses = [
            'alert' => Alert::class,
            'button' => Button::class,
            'card' => Card::class,
            // Add your components here
        ];

        if (!isset($componentClasses[$component])) {
            throw $this->createNotFoundException("Component '{$component}' not found");
        }

        return $this->render('preview/component.html.twig', [
            'component_class' => $componentClasses[$component],
            'component_name' => ucfirst($component)
        ]);
    }
}
```

### Step 3: Create the Template

```twig
{# templates/preview/component.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}{{ component_name }} Preview{% endblock %}

{% block body %}
    <h1>{{ component_name }} Component Preview</h1>
    
    {# This one line creates the entire preview interface! #}
    {{ component('PlixusPreviewStage', {
        componentClass: component_class
    }) }}
{% endblock %}
```

### Step 4: Visit Your Preview

Navigate to: `https://yourdomain.com/preview/alert`

**That's it!** You now have a live preview interface where you can:
- Change form values and see instant updates
- View component documentation  
- See generated Twig code examples
- Copy-paste code snippets

## 📝 Component Configuration

### Available Property Types

Configure your component properties with the `#[PreviewProperty]` attribute:

```php
class MyComponent 
{
    #[PreviewProperty(type: 'text', label: 'Name', default: 'John')]
    public string $name;
    
    #[PreviewProperty(type: 'email', label: 'Email Address')]
    public string $email;
    
    #[PreviewProperty(type: 'textarea', label: 'Description', formOptions: ['attr' => ['rows' => 4]])]
    public string $description;
    
    #[PreviewProperty(type: 'choice', choices: ['small', 'medium', 'large'], default: 'medium')]
    public string $size;
    
    #[PreviewProperty(type: 'checkbox', label: 'Is Active', default: true)]
    public bool $active;
    
    #[PreviewProperty(type: 'number', label: 'Count', default: 1)]
    public int $count;
    
    #[PreviewProperty(type: 'url', label: 'Website')]
    public ?string $website = null;  // Nullable properties supported
}
```

### Property Attribute Options

```php
#[PreviewProperty(
    type: 'choice',                    // Form field type
    label: 'Display Name',             // Form label
    help: 'Additional help text',      // Help text below field
    choices: ['a', 'b', 'c'],         // For choice fields
    default: 'a',                      // Default value
    required: true,                    // Required field
    formOptions: ['attr' => ['class' => 'my-class']], // Additional Symfony form options
    group: 'styling'                   // Group related properties
)]
```

## 🎨 Syntax Configuration

The bundle supports both Twig Component syntax styles. You can configure which syntax to show in the code examples:

### Option 1: Configure Syntax Preference

```twig
{{ component('PlixusPreviewStage', {
    componentClass: component_class,
    codeSyntax: 'function'  // or 'html'
}) }}
```

**Function Syntax (default):**
```twig
{{ component('Alert', {
    type: 'success',
    message: 'Operation completed!',
    dismissible: true
}) }}
```

**HTML Syntax:**
```twig
<twig:Alert 
    type="success" 
    message="Operation completed!" 
    :dismissible="true" 
/>
```

### Option 2: Show Both Syntax Styles

```twig
{{ component('PlixusPreviewStage', {
    componentClass: component_class,
    showBothSyntax: true
}) }}
```

This will display tabs showing both syntax styles so users can choose their preference.

## ⚙️ Advanced Configuration

### Component Options

```twig
{{ component('PlixusPreviewStage', {
    componentClass: component_class,
    layout: 'vertical',              // 'horizontal' | 'vertical'
    theme: 'dark',                   // 'light' | 'dark'
    formWidth: '400px',              // Custom form width
    showDocumentation: true,         // Show component docs
    showCodeExample: true,           // Show Twig code
    codeSyntax: 'html',              // 'function' | 'html'
    showBothSyntax: false,           // Show tabs with both syntax styles
    showExamples: false,             // Show preset examples
    customOptions: {
        additionalContent: '<p>Custom HTML content</p>'
    }
}) }}
```

### Layout Options

```twig
{# Vertical layout for mobile/narrow screens #}
{{ component('PlixusPreviewStage', {
    componentClass: component_class,
    layout: 'vertical'
}) }}

{# Custom form width #}
{{ component('PlixusPreviewStage', {
    componentClass: component_class,
    formWidth: '500px'
}) }}

{# Minimal preview without docs #}
{{ component('PlixusPreviewStage', {
    componentClass: component_class,
    showDocumentation: false,
    showCodeExample: false
}) }}
```

## 🎨 Template Customization

### Override Bundle Templates

Create a template in your app to override the bundle's default:

```twig
{# templates/bundles/PlixusTwigComponentPreviewBundle/components/PlixusPreviewStage.html.twig #}
<div class="my-custom-preview-stage {{ layout }}">
    <div class="my-form-section">
        <h2>{{ componentName }} Configuration</h2>
        {{ form_start(form) }}
        
        {% for child in form.children %}
            <div class="my-form-group">
                {{ form_label(child) }}
                {{ form_widget(child, {
                    'attr': {
                        'data-model': 'componentData[' ~ child.vars.name ~ ']'
                    }
                }) }}
                {{ form_help(child) }}
            </div>
        {% endfor %}
        
        {{ form_end(form) }}
    </div>
    
    <div class="my-preview-section">
        <h2>Live Preview</h2>
        <div class="my-component-wrapper">
            {{ component(componentName, componentProps) }}
        </div>
        
        {% if showCodeExample %}
            <div class="my-code-section">
                <h3>Twig Code</h3>
                {% if showBothSyntax %}
                    <div class="syntax-tabs">
                        <button class="tab active" data-syntax="function">Function Syntax</button>
                        <button class="tab" data-syntax="html">HTML Syntax</button>
                    </div>
                    
                    <div class="syntax-content active" data-syntax="function">
                        <pre><code>{{ '{{' }} component('{{ componentName }}', {{ componentProps|json_encode|raw }}) {{ '}}' }}</code></pre>
                    </div>
                    
                    <div class="syntax-content" data-syntax="html">
                        <pre><code>&lt;twig:{{ componentName|title }}{% for key, value in componentProps %} {{ key }}="{{ value }}"{% endfor %} /&gt;</code></pre>
                    </div>
                {% else %}
                    {% if codeSyntax == 'html' %}
                        <pre><code>&lt;twig:{{ componentName|title }}{% for key, value in componentProps %} {{ key }}="{{ value }}"{% endfor %} /&gt;</code></pre>
                    {% else %}
                        <pre><code>{{ '{{' }} component('{{ componentName }}', {{ componentProps|json_encode|raw }}) {{ '}}' }}</code></pre>
                    {% endif %}
                {% endif %}
            </div>
        {% endif %}
    </div>
</div>
```

### Partial Template Overrides

Override specific parts by extending the original template:

```twig
{# templates/bundles/PlixusTwigComponentPreviewBundle/components/PlixusPreviewStage.html.twig #}
{% extends '@PlixusTwigComponentPreview/components/PlixusPreviewStage.html.twig' %}

{% block form_section %}
    <div class="custom-form-header">
        <h2>🎛️ Component Settings</h2>
        <p>Adjust the properties below to see real-time changes</p>
    </div>
    {{ parent() }}
{% endblock %}

{% block preview_section %}
    <div class="custom-preview-header">
        <h2>👀 Live Preview</h2>
    </div>
    {{ parent() }}
{% endblock %}
```

## 🎨 CSS Customization

### CSS Variables

Override CSS variables to customize the appearance:

```css
:root {
  /* Colors */
  --plixus-preview-accent: #ff6b35;
  --plixus-preview-background: #ffffff;
  --plixus-preview-surface: #f8fafc;
  
  /* Layout */
  --plixus-preview-form-width: 400px;
  --plixus-preview-border-radius: 12px;
  --plixus-preview-gap: 32px;
  
  /* Typography */
  --plixus-preview-font-family: 'Inter', sans-serif;
  --plixus-preview-font-size-base: 16px;
}
```

### Custom CSS Classes

Add your own styling:

```css
.plixus-preview-stage {
  /* Your custom base styles */
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.plixus-preview-stage--vertical {
  /* Custom vertical layout styles */
}

.plixus-preview-stage--dark {
  /* Dark theme customizations */
  --plixus-preview-background: #1a1a1a;
  --plixus-preview-primary: #ffffff;
}
```

## 🎯 Common Use Cases

### 1. Documentation Site

```twig
{# Comprehensive documentation display #}
{{ component('PlixusPreviewStage', {
    componentClass: component_class,
    showDocumentation: true,
    showCodeExample: true,
    showBothSyntax: true,
    showExamples: true,
    layout: 'horizontal'
}) }}
```

### 2. Design System Preview

```twig
{# Minimal preview for design system #}
{{ component('PlixusPreviewStage', {
    componentClass: component_class,
    showDocumentation: false,
    showCodeExample: true,
    codeSyntax: 'html',
    theme: 'light',
    formWidth: '300px'
}) }}
```

### 3. Developer Testing

```twig
{# Full-featured testing environment #}
{{ component('PlixusPreviewStage', {
    componentClass: component_class,
    layout: 'vertical',
    showDocumentation: true,
    showCodeExample: true,
    showBothSyntax: true,
    customOptions: {
        additionalContent: '
            <div class="test-info">
                <h4>Testing Notes</h4>
                <ul>
                    <li>Test all property combinations</li>
                    <li>Verify responsive behavior</li>
                    <li>Check accessibility features</li>
                </ul>
            </div>
        '
    }
}) }}
```

### 4. Component Library Index

Create an overview of all your components:

```php
#[Route('/components', name: 'component_index')]
public function index(): Response
{
    $components = [
        'alert' => ['name' => 'Alert', 'description' => 'Contextual feedback messages'],
        'button' => ['name' => 'Button', 'description' => 'Interactive button elements'],
        'card' => ['name' => 'Card', 'description' => 'Content containers'],
    ];

    return $this->render('components/index.html.twig', [
        'components' => $components
    ]);
}
```

```twig
{# templates/components/index.html.twig #}
<h1>Component Library</h1>

{% for key, component in components %}
    <div class="component-card">
        <h3>{{ component.name }}</h3>
        <p>{{ component.description }}</p>
        <a href="{{ path('component_preview', {component: key}) }}">Preview →</a>
    </div>
{% endfor %}
```

## 🔧 Troubleshooting

### Common Issues

**"Component not found" Error**
- Make sure you've added your component to the `$componentClasses` array in your controller
- Check that the component class exists and is imported

**"Expected argument of type bool, null given"**
- This is automatically handled by the bundle for boolean properties
- If you see this error, make sure you're using the latest version

**Live updates not working**
- Ensure Symfony UX Live Component is properly installed
- Check that your component properties have `#[PreviewProperty]` attributes
- Verify that Stimulus is loaded on your page

**Styling issues**
- The bundle includes its own CSS - you may need to override CSS variables
- Check that the bundle's CSS is being loaded

### Bundle Requirements Check

```bash
# Verify requirements are installed
composer show symfony/ux-twig-component
composer show symfony/ux-live-component

# Check Symfony version
php bin/console --version
```

## 📦 What's Next?

This bundle is perfect for:
- **Design Systems**: Document and preview your component library
- **Development**: Test components during development
- **Documentation**: Generate interactive component docs
- **Prototyping**: Quickly experiment with component variations

## 🤝 Contributing

Found a bug or have a feature request? Please open an issue on GitHub.

## 📄 License

This bundle is released under the MIT License.