import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["form", "preview", "formElement"]
    static values = { 
        autoSubmit: Boolean,
        debounceDelay: Number 
    }
    
    connect() {
        console.log('PreviewStage controller connected', this.autoSubmitValue);
        if (this.autoSubmitValue) {
            this.setupAutoSubmit();
        }
    }
    
    setupAutoSubmit() {
        const form = this.hasFormElementTarget ? this.formElementTarget : this.element.querySelector('form');
        if (!form) {
            console.warn('No form found for auto-submit');
            return;
        }
        
        console.log('Setting up auto-submit on form', form);
        
        // Add event listeners to all form inputs
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', () => this.submitForm(form));
            
            // Debounced input for text fields
            if (input.type === 'text' || input.tagName === 'TEXTAREA') {
                input.addEventListener('input', this.debounce(() => this.submitForm(form), this.debounceDelayValue || 300));
            }
        });
    }
    
    submitForm(form) {
        console.log('Submitting form', form);
        if (form) {
            form.requestSubmit();
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