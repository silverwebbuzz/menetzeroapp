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
        const quantityInput = document.getElementById('quantity');
        const unitSelect = document.getElementById('unit');
        const form = document.querySelector('form[action*="quick-input"]');

        if (!form || !quantityInput || !unitSelect) return;

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
        const quantity = document.getElementById('quantity')?.value;
        const unit = document.getElementById('unit')?.value;
        const emissionSourceId = form.dataset.sourceId || getEmissionSourceIdFromUrl();

        if (!quantity || !unit || !emissionSourceId) {
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
        // Remove existing result display
        const existingResult = document.getElementById('calculation-result');
        if (existingResult) {
            existingResult.remove();
        }

        // Create result display
        const resultDiv = document.createElement('div');
        resultDiv.id = 'calculation-result';
        resultDiv.className = 'mb-6 bg-green-50 border border-green-200 rounded-lg p-4';
        
        let html = '<div class="flex items-start">';
        html += '<div class="flex-shrink-0">';
        html += '<svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">';
        html += '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>';
        html += '</svg></div>';
        html += '<div class="ml-3 flex-1">';
        html += '<h4 class="text-sm font-medium text-green-800 mb-2">Calculation Result</h4>';
        html += '<div class="text-sm text-green-700">';
        html += '<p class="mb-1"><strong>CO2e:</strong> ' + parseFloat(calculation.co2e).toFixed(2) + ' kg</p>';
        
        if (calculation.co2 !== null) {
            html += '<p class="mb-1"><strong>CO2:</strong> ' + parseFloat(calculation.co2).toFixed(2) + ' kg</p>';
        }
        if (calculation.ch4 !== null) {
            html += '<p class="mb-1"><strong>CH4:</strong> ' + parseFloat(calculation.ch4).toFixed(6) + ' kg</p>';
        }
        if (calculation.n2o !== null) {
            html += '<p class="mb-1"><strong>N2O:</strong> ' + parseFloat(calculation.n2o).toFixed(6) + ' kg</p>';
        }
        
        if (factor) {
            html += '<p class="mt-2 text-xs text-green-600">';
            html += 'Using factor: ' + (factor.region || 'Default') + ' (' + (factor.source_standard || 'Standard') + ')';
            html += '</p>';
        }
        
        html += '</div></div></div>';
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

    // Export functions for global use
    window.QuickInput = {
        calculate: calculateEmissions,
        validate: validateForm,
    };
})();

