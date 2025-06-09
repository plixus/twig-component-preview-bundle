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
        
        // Note: For Live Components, we don't need to submit the form manually
        // The data-model bindings handle automatic updates
        // This method exists for compatibility but does nothing for Live Components
        
        console.log('PlixusPreviewStage: Auto-submit setup complete (Live Component mode)');
    }
    
    submitForm(form) {
        console.log('PlixusPreviewStage: Form submission not needed for Live Components');
        // Live Components use data-model bindings for automatic updates
        // No manual form submission required
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