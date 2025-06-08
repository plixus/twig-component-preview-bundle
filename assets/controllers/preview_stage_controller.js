import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["form", "formContainer", "preview"]
    static values = { 
        autoSubmit: Boolean,
        debounceDelay: Number 
    }
    
    connect() {
        console.log('PlixusPreviewStage controller connected', {
            autoSubmit: this.autoSubmitValue,
            debounceDelay: this.debounceDelayValue
        });
        
        if (this.autoSubmitValue) {
            this.setupAutoSubmit();
        }
        
        this.setupSyntaxTabs();
    }
    
    setupSyntaxTabs() {
        const tabs = this.element.querySelectorAll('.plixus-tab');
        const contents = this.element.querySelectorAll('.plixus-syntax-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                const targetSyntax = tab.getAttribute('data-syntax');
                
                // Update active tab
                tabs.forEach(t => t.classList.remove('plixus-tab--active'));
                tab.classList.add('plixus-tab--active');
                
                // Update visible content
                contents.forEach(content => {
                    content.classList.remove('plixus-syntax-content--active');
                    if (content.getAttribute('data-syntax') === targetSyntax) {
                        content.classList.add('plixus-syntax-content--active');
                    }
                });
            });
        });
    }
    
    setupAutoSubmit() {
        const form = this.hasFormTarget ? this.formTarget : this.element.querySelector('form');
        
        if (!form) {
            console.warn('PlixusPreviewStage: No form found for auto-submit');
            return;
        }
        
        console.log('PlixusPreviewStage: Setting up auto-submit on form', form);
        
        // Add event listeners to all form inputs
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            console.log('PlixusPreviewStage: Adding listeners to', input.type, input.name || input.id);
            
            // Immediate submit for dropdowns, checkboxes, radio buttons
            if (input.type === 'select-one' || input.type === 'checkbox' || input.type === 'radio' || input.tagName === 'SELECT') {
                input.addEventListener('change', () => this.submitForm(form));
            }
            
            // Debounced submit for text inputs and textareas
            if (input.type === 'text' || input.type === 'email' || input.type === 'url' || input.tagName === 'TEXTAREA') {
                input.addEventListener('input', this.debounce(() => this.submitForm(form), this.debounceDelayValue || 300));
            }
        });
        
        console.log('PlixusPreviewStage: Auto-submit setup complete for', inputs.length, 'inputs');
    }
    
    submitForm(form) {
        console.log('PlixusPreviewStage: Submitting form');
        
        if (form && typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else if (form) {
            // Fallback for older browsers
            form.submit();
        }
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}