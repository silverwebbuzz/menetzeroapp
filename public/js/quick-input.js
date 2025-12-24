/**
 * Quick Input JavaScript
 * Handles form validation, AJAX calculations, and dynamic form updates
 */

(function() {
    'use strict';

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeQuickInput();
    });

    function initializeQuickInput() {
        // Setup form validation
        setupFormValidation();
        
        // Setup AJAX calculation
        setupCalculation();
        
        // Setup unit conversion helper
        setupUnitConversion();
        
        // Setup real-time CO2e preview
        setupRealTimePreview();
    }

    /**
     * Setup form validation
     */
    function setupFormValidation() {
        const form = document.querySelector('form[action*="quick-input"]');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
                return false;
            }
        });

        // Real-time validation
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(input);
            });
        });
    }

    /**
     * Validate form
     */
    function validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Validate individual field
     */
    function validateField(field) {
        const value = field.value.trim();
        const fieldContainer = field.closest('.mb-6, div');
        let errorElement = fieldContainer.querySelector('.field-error');

        // Remove existing error
        if (errorElement) {
            errorElement.remove();
        }

        // Remove error styling
        field.classList.remove('border-red-500');
        field.classList.add('border-gray-300');

        // Check if required
        if (field.hasAttribute('required') && !value) {
            showFieldError(field, 'This field is required.');
            return false;
        }

        // Type-specific validation
        if (value) {
            if (field.type === 'email' && !isValidEmail(value)) {
                showFieldError(field, 'Please enter a valid email address.');
                return false;
            }
            if (field.type === 'number' && isNaN(value)) {
                showFieldError(field, 'Please enter a valid number.');
                return false;
            }
            if (field.type === 'number' && parseFloat(value) < 0) {
                showFieldError(field, 'Please enter a positive number.');
                return false;
            }
        }

        return true;
    }

    /**
     * Show field error
     */
    function showFieldError(field, message) {
        field.classList.remove('border-gray-300');
        field.classList.add('border-red-500');
        
        const errorElement = document.createElement('p');
        errorElement.className = 'mt-1 text-sm text-red-600 field-error';
        errorElement.textContent = message;
        
        field.parentNode.appendChild(errorElement);
    }

    /**
     * Validate email
     */
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    /**
     * Setup AJAX calculation
     */
    function setupCalculation() {
        const calculateButton = document.getElementById('calculate-btn');
        const quantityInput = document.getElementById('quantity') || document.getElementById('amount');
        const unitSelect = document.getElementById('unit') || document.getElementById('unit_of_measure');
        const form = document.querySelector('form[action*="store"]');

        if (!form || !calculateButton) return;
        
        // Attach click handler to calculate button
        calculateButton.addEventListener('click', function(e) {
            e.preventDefault();
            calculateEmissions(form);
        });

        // Add calculate button if it doesn't exist
        if (!calculateButton) {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                const calcBtn = document.createElement('button');
                calcBtn.type = 'button';
                calcBtn.id = 'calculate-btn';
                calcBtn.className = 'px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 mr-2';
                calcBtn.textContent = 'Calculate';
                calcBtn.onclick = function() {
                    calculateEmissions(form);
                };
                submitButton.parentNode.insertBefore(calcBtn, submitButton);
            }
        }

        // Auto-calculate on quantity/unit change
        quantityInput.addEventListener('input', debounce(function() {
            if (quantityInput.value && unitSelect.value) {
                calculateEmissions(form);
            }
        }, 500));

        unitSelect.addEventListener('change', function() {
            if (quantityInput.value && unitSelect.value) {
                calculateEmissions(form);
            }
        });
    }

    /**
     * Calculate emissions via AJAX
     */
    function calculateEmissions(form) {
        const quantity = document.getElementById('quantity')?.value || document.getElementById('amount')?.value;
        const unit = document.getElementById('unit')?.value || document.getElementById('unit_of_measure')?.value;
        const emissionSourceId = form.dataset.sourceId || getEmissionSourceIdFromUrl();

        if (!quantity || !unit || !emissionSourceId) {
            showError('Please enter quantity and select unit before calculating.');
            return;
        }

        // Show loading state
        const calculateBtn = document.getElementById('calculate-btn');
        const originalText = calculateBtn?.textContent;
        if (calculateBtn) {
            calculateBtn.disabled = true;
            calculateBtn.textContent = 'Calculating...';
        }

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        // Make AJAX request
        fetch('/api/quick-input/calculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                emission_source_id: emissionSourceId,
                quantity: parseFloat(quantity),
                unit: unit,
                // Include other form fields that might affect factor selection
                fuel_type: document.getElementById('fuel_type')?.value,
                region: document.getElementById('region')?.value || 'UAE',
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCalculationResult(data.calculation, data.factor);
            } else {
                showError(data.message || 'Calculation failed. Please check your inputs.');
            }
        })
        .catch(error => {
            console.error('Calculation error:', error);
            showError('An error occurred during calculation. Please try again.');
        })
        .finally(() => {
            if (calculateBtn) {
                calculateBtn.disabled = false;
                calculateBtn.textContent = originalText || 'Calculate';
            }
        });
    }

    /**
     * Display calculation result
     */
    function displayCalculationResult(calculation, factor) {
        // Use existing preview section if available
        const previewSection = document.getElementById('calculation-preview');
        const previewContent = document.getElementById('preview-content');
        
        if (previewSection && previewContent) {
            // Show the preview section
            previewSection.classList.remove('hidden');
            
            // Build HTML content
            let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
            html += '<div><strong>CO2e:</strong> <span class="text-lg font-bold text-green-700">' + parseFloat(calculation.co2e || calculation.total_co2e || 0).toFixed(2) + ' kg</span></div>';
            
            if (calculation.co2 !== null && calculation.co2 !== undefined) {
                html += '<div><strong>CO2:</strong> ' + parseFloat(calculation.co2).toFixed(2) + ' kg</div>';
            }
            if (calculation.ch4 !== null && calculation.ch4 !== undefined) {
                html += '<div><strong>CH4:</strong> ' + parseFloat(calculation.ch4).toFixed(6) + ' kg</div>';
            }
            if (calculation.n2o !== null && calculation.n2o !== undefined) {
                html += '<div><strong>N2O:</strong> ' + parseFloat(calculation.n2o).toFixed(6) + ' kg</div>';
            }
            
            if (factor) {
                html += '<div class="md:col-span-2 text-xs text-gray-600 mt-2">';
                html += '<strong>Emission Factor:</strong> ' + (factor.region || 'Default') + ' (' + (factor.source_standard || 'Standard') + ')';
                html += '</div>';
            }
            
            html += '</div>';
            previewContent.innerHTML = html;
            
            // Scroll to preview
            previewSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            // Fallback: create result display if preview section doesn't exist
            const existingResult = document.getElementById('calculation-result');
            if (existingResult) {
                existingResult.remove();
            }

            const resultDiv = document.createElement('div');
            resultDiv.id = 'calculation-result';
            resultDiv.className = 'mb-6 bg-green-50 border border-green-200 rounded-lg p-4';
            
            let html = '<h4 class="text-sm font-medium text-green-800 mb-2">Calculation Result</h4>';
            html += '<div class="text-sm text-green-700">';
            html += '<p class="mb-1"><strong>CO2e:</strong> ' + parseFloat(calculation.co2e || calculation.total_co2e || 0).toFixed(2) + ' kg</p>';
            
            if (calculation.co2 !== null && calculation.co2 !== undefined) {
                html += '<p class="mb-1"><strong>CO2:</strong> ' + parseFloat(calculation.co2).toFixed(2) + ' kg</p>';
            }
            if (calculation.ch4 !== null && calculation.ch4 !== undefined) {
                html += '<p class="mb-1"><strong>CH4:</strong> ' + parseFloat(calculation.ch4).toFixed(6) + ' kg</p>';
            }
            if (calculation.n2o !== null && calculation.n2o !== undefined) {
                html += '<p class="mb-1"><strong>N2O:</strong> ' + parseFloat(calculation.n2o).toFixed(6) + ' kg</p>';
            }
            
            if (factor) {
                html += '<p class="mt-2 text-xs text-green-600">';
                html += 'Using factor: ' + (factor.region || 'Default') + ' (' + (factor.source_standard || 'Standard') + ')';
                html += '</p>';
            }
            
            html += '</div>';
            resultDiv.innerHTML = html;

            // Insert before submit button
            const submitButton = document.querySelector('form button[type="submit"]');
            if (submitButton) {
                submitButton.parentNode.insertBefore(resultDiv, submitButton);
            } else {
                document.querySelector('form').appendChild(resultDiv);
            }

            // Scroll to result
            resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    /**
     * Get emission source ID from URL
     */
    function getEmissionSourceIdFromUrl() {
        // Try to get from hidden input or data attribute
        const hiddenInput = document.querySelector('input[name="emission_source_id"]');
        if (hiddenInput) {
            return hiddenInput.value;
        }
        
        const form = document.querySelector('form[action*="quick-input"]');
        if (form && form.dataset.sourceId) {
            return form.dataset.sourceId;
        }
        
        return null;
    }

    /**
     * Setup unit conversion helper
     */
    function setupUnitConversion() {
        const unitSelect = document.getElementById('unit');
        const quantityInput = document.getElementById('quantity');
        
        if (!unitSelect || !quantityInput) return;

        // Show conversion helper if unit changes
        unitSelect.addEventListener('change', function() {
            // This could show converted values if needed
            // For now, just clear any existing conversion display
            const conversionHelper = document.getElementById('unit-conversion-helper');
            if (conversionHelper) {
                conversionHelper.remove();
            }
        });
    }

    /**
     * Setup real-time CO2e preview
     */
    function setupRealTimePreview() {
        const quantityInput = document.getElementById('quantity');
        const unitSelect = document.getElementById('unit');
        
        if (!quantityInput || !unitSelect) return;

        // Debounced preview update
        quantityInput.addEventListener('input', debounce(function() {
            if (quantityInput.value && unitSelect.value) {
                // Could trigger a lightweight calculation here
                // For now, just validate
            }
        }, 1000));
    }

    /**
     * Show error message
     */
    function showError(message) {
        // Remove existing error
        const existingError = document.getElementById('calculation-error');
        if (existingError) {
            existingError.remove();
        }

        const errorDiv = document.createElement('div');
        errorDiv.id = 'calculation-error';
        errorDiv.className = 'mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative';
        errorDiv.innerHTML = '<span class="block sm:inline">' + message + '</span>';

        const form = document.querySelector('form[action*="quick-input"]');
        if (form) {
            form.insertBefore(errorDiv, form.firstChild);
        }
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
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

    /**
     * Setup confirmation dialogs for delete actions
     */
    function setupDeleteConfirmations() {
        const deleteForms = document.querySelectorAll('form[action*="destroy"]');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    }

    /**
     * Setup loading states for forms
     */
    function setupFormLoadingStates() {
        const forms = document.querySelectorAll('form[action*="quick-input"]');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton && !form.dataset.submitting) {
                    form.dataset.submitting = 'true';
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="inline-block animate-spin mr-2">⏳</span> Saving...';
                    
                    // Re-enable after 10 seconds as fallback
                    setTimeout(() => {
                        submitButton.disabled = false;
                        submitButton.innerHTML = submitButton.dataset.originalText || 'Submit';
                        delete form.dataset.submitting;
                    }, 10000);
                }
            });
        });
    }

    // Initialize additional features
    document.addEventListener('DOMContentLoaded', function() {
        setupDeleteConfirmations();
        setupFormLoadingStates();
        
        // Store original button text
        const submitButtons = document.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(button => {
            button.dataset.originalText = button.textContent;
        });
    });

    // Export functions for global use
    window.QuickInput = {
        calculate: calculateEmissions,
        validate: validateForm,
    };
})();

