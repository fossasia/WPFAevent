/**
 * WPFA Footer JavaScript Module
 * Handles footer functionality across all pages
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/js
 */

const WPFA_Footer = (function() {
    // Private variables
    let config = {};
    let elements = {};

    // Private Helper for consistency with events module
    function getErrorMessage(data, fallback) {
        if (data && typeof data.data === 'object' && data.data !== null && data.data.message) {
            return `${fallback}: ${data.data.message}`;
        }
        if (data && typeof data.data === 'string') {
            return `${fallback}: ${data.data}`;
        }
        return fallback;
    }
    
    /**
     * Initialize the footer module
     */
    function init(options) {
        config = options || {};
        
        // Ensure i18n object exists
        config.i18n = config.i18n || {};
        
        // Cache DOM elements
        cacheElements();
        
        // Setup event listeners
        setupEventListeners();
    }
    
    /**
     * Cache DOM elements
     */
    function cacheElements() {
        elements = {
            editFooterBtn: document.getElementById('edit-footer-btn'),
            footerModal: document.getElementById('edit-footer-modal'),
            closeFooterModal: document.querySelector('#edit-footer-modal .close-btn'),
            footerForm: document.getElementById('edit-footer-form'),
        };
    }
    
    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Edit footer button
        if (elements.editFooterBtn) {
            elements.editFooterBtn.addEventListener('click', openFooterModal);
        }
        
        // Modal close button
        if (elements.closeFooterModal) {
            elements.closeFooterModal.addEventListener('click', closeFooterModal);
        }
        
        // Close modal on background click
        if (elements.footerModal) {
            elements.footerModal.addEventListener('click', function(e) {
                if (e.target === this) closeFooterModal();
            });
        }
        
        // Form submission
        if (elements.footerForm) {
            elements.footerForm.addEventListener('submit', handleFooterFormSubmit);
        }
    }
    
    /**
     * Open footer modal
     */
    function openFooterModal() {
        // Get current footer text
        const footerTextElement = document.getElementById('footer-text-display');
        const footerTextInput = document.getElementById('footer-text');
        
        if (footerTextElement && footerTextInput) {
            footerTextInput.value = footerTextElement.textContent.trim();
        }
        
        if (elements.footerModal) {
            elements.footerModal.style.display = 'flex';
        }
    }
    
    /**
     * Handle footer form submission
     */
    function handleFooterFormSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Disable button during submission
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = config.i18n.saving || 'Saving...';
        }
        
        // Add AJAX action and nonce
        formData.append('action', 'wpfa_update_footer_text');
        formData.append('nonce', config.adminNonce);
        
        fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update footer text on page
                const footerTextElement = document.getElementById('footer-text-display');
                if (footerTextElement) {
                    footerTextElement.textContent = formData.get('footer_text');
                }
                
                alert(config.i18n.footerSaveSuccess || 'Footer text updated successfully.');
                closeFooterModal();
            } else {
                const baseMsg = config.i18n.footerSaveError || 'Error updating footer text';
                alert(getErrorMessage(data, baseMsg));
            }
            
            // Re-enable button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = config.i18n.saveFooter || 'Save Footer';
            }
        })
        .catch(error => {
            alert(config.i18n.footerSaveError || 'Error updating footer text.');
            
            // Re-enable button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = config.i18n.saveFooter || 'Save Footer';
            }
        });
    }
    
    /**
     * Close footer modal
     */
    function closeFooterModal() {
        if (elements.footerModal) {
            elements.footerModal.style.display = 'none';
        }
    }
    
    // Public API
    return {
        init: init,
        openFooterModal: openFooterModal,
        closeFooterModal: closeFooterModal
    };
})();

// Export to global
if (typeof window !== 'undefined') {
    window.WPFA_Footer = WPFA_Footer;
}

// Initialize when page loads
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function() {
        // Check if config exists (footer exists on all template pages)
        if ( typeof wpfaeventFooterConfig  !== 'undefined') {
            WPFA_Footer.init(wpfaeventFooterConfig);
        }
    });
}