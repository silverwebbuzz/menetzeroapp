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
        // Use event delegation for dynamically rendered forms
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'calculate-btn') {
                e.preventDefault();
                e.stopPropagation();
                const form = e.target.closest('form') || document.querySelector('form[data-source-id]');
                if (form) {
                    console.log('Calculate button clicked');
                    calculateEmissions(form);
                }
            }
        });

        // Also setup for existing form (in case it's already rendered)
        function attachHandlers() {
            const calculateButton = document.getElementById('calculate-btn');
            const quantityInput = document.getElementById('quantity') || document.getElementById('amount');
            const unitSelect = document.getElementById('unit') || document.getElementById('unit_of_measure');
            const form = document.querySelector('form[action*="store"]') || document.querySelector('form[data-source-id]');

            if (form && calculateButton && !calculateButton.dataset.handlerAttached) {
                calculateButton.dataset.handlerAttached = 'true';
                
                // Auto-calculate on quantity/unit change (only if inputs exist)
                if (quantityInput) {
                    quantityInput.addEventListener('input', debounce(function() {
                        if (quantityInput.value && unitSelect && unitSelect.value) {
                            calculateEmissions(form);
                        }
                    }, 500));
                }

                if (unitSelect) {
                    unitSelect.addEventListener('change', function() {
                        if (quantityInput && quantityInput.value && unitSelect.value) {
                            calculateEmissions(form);
                        }
                    });
                }
            }
        }

        // Try immediately
        attachHandlers();
        
        // Also try after a delay (for conditionally rendered forms)
        setTimeout(attachHandlers, 500);
        
        // Watch for form appearance
        const observer = new MutationObserver(function(mutations) {
            attachHandlers();
        });
        
        const targetNode = document.body;
        if (targetNode) {
            observer.observe(targetNode, {
                childList: true,
                subtree: true
            });
        }
    }

    /**
     * Calculate emissions via AJAX
     */
    function calculateEmissions(form) {
        console.log('calculateEmissions called', { form: !!form });
        const quantity = document.getElementById('quantity')?.value || document.getElementById('amount')?.value;
        const unit = document.getElementById('unit')?.value || document.getElementById('unit_of_measure')?.value;
        const emissionSourceId = form?.dataset?.sourceId || document.querySelector('input[name="emission_source_id"]')?.value || getEmissionSourceIdFromUrl();

        console.log('Calculation inputs:', { quantity, unit, emissionSourceId });

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
                fuel_category: document.getElementById('fuel_category')?.value,
                fuel_type: document.getElementById('fuel_type')?.value,
                region: document.getElementById('region')?.value || 'UAE',
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Calculation failed');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayCalculationResult(data.calculation, data.factor);
            } else {
                showError(data.message || 'Calculation failed. Please check your inputs.');
            }
        })
        .catch(error => {
            console.error('Calculation error:', error);
            showError(error.message || 'An error occurred during calculation. Please try again.');
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

    /**
     * Setup cascading dropdowns for Fuel and other sources
     */
    function setupCascadingDropdowns() {
        const form = document.querySelector('form[data-source-id]');
        if (!form) {
            console.log('Quick Input: Form with data-source-id not found');
            return;
        }

        const emissionSourceId = form.dataset.sourceId;
        if (!emissionSourceId) {
            console.error('Quick Input: Emission source ID not found in form data-source-id');
            return;
        }

        console.log('Quick Input: Setting up cascading dropdowns for source ID:', emissionSourceId);

        const fuelCategorySelect = document.getElementById('fuel_category');
        const fuelTypeSelect = document.getElementById('fuel_type');
        const unitSelect = document.getElementById('unit_of_measure') || document.getElementById('unit');

        console.log('Quick Input: Found elements:', {
            fuelCategory: !!fuelCategorySelect,
            fuelType: !!fuelTypeSelect,
            unit: !!unitSelect
        });

        // Handle fuel_category change -> update fuel_type
        if (fuelCategorySelect && !fuelCategorySelect.dataset.handlerAttached) {
            fuelCategorySelect.dataset.handlerAttached = 'true';
            console.log('Quick Input: Attaching handler to fuel_category');
            fuelCategorySelect.addEventListener('change', function() {
                const category = this.value;
                console.log('Quick Input: fuel_category changed to:', category);
                if (category && fuelTypeSelect) {
                    loadFuelTypes(emissionSourceId, category, fuelTypeSelect);
                    // Clear unit options when category changes
                    if (unitSelect) {
                        unitSelect.innerHTML = '<option value="">Select an option</option>';
                    }
                } else if (fuelTypeSelect) {
                    fuelTypeSelect.innerHTML = '<option value="">Select an option</option>';
                }
            });
            
            // If editing and fuel_category has a value, load fuel types
            const initialFuelCategory = document.getElementById('fuel_category_initial_value')?.value;
            if (initialFuelCategory && fuelCategorySelect.value === initialFuelCategory && fuelTypeSelect) {
                console.log('Quick Input: Loading initial fuel types for category:', initialFuelCategory);
                loadFuelTypes(emissionSourceId, initialFuelCategory, fuelTypeSelect, document.getElementById('fuel_type_initial_value')?.value);
            }
        } else if (fuelCategorySelect) {
            console.log('Quick Input: fuel_category handler already attached');
        }

        // Handle fuel_type change -> update unit_of_measure
        if (fuelTypeSelect && !fuelTypeSelect.dataset.handlerAttached) {
            fuelTypeSelect.dataset.handlerAttached = 'true';
            fuelTypeSelect.addEventListener('change', function() {
                const fuelType = this.value;
                const fuelCategory = fuelCategorySelect ? fuelCategorySelect.value : null;
                if (fuelType && unitSelect) {
                    loadUnits(emissionSourceId, fuelType, fuelCategory, unitSelect);
                } else if (unitSelect) {
                    unitSelect.innerHTML = '<option value="">Select an option</option>';
                }
            });
            
            // If editing and fuel_type has a value, load units
            const initialFuelType = document.getElementById('fuel_type_initial_value')?.value;
            if (initialFuelType && fuelTypeSelect.value === initialFuelType && unitSelect) {
                const fuelCategory = fuelCategorySelect ? fuelCategorySelect.value : null;
                console.log('Quick Input: Loading initial units for fuel type:', initialFuelType);
                loadUnits(emissionSourceId, initialFuelType, fuelCategory, unitSelect, document.getElementById('unit_of_measure_initial_value')?.value || document.getElementById('unit_initial_value')?.value);
            }
        }
    }

    /**
     * Load fuel types based on fuel category
     */
    function loadFuelTypes(sourceId, category, selectElement, initialValue = null) {
        if (!category) {
            selectElement.innerHTML = '<option value="">Select an option</option>';
            return;
        }

        selectElement.disabled = true;
        const currentValue = selectElement.value || initialValue;
        selectElement.innerHTML = '<option value="">Loading...</option>';

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const url = `/api/quick-input/fuel-types/${sourceId}?fuel_category=${encodeURIComponent(category)}`;
        console.log('Quick Input: Loading fuel types from:', url);

        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => {
            console.log('Quick Input: Fuel types response status:', response.status);
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || `HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Quick Input: Fuel types data:', data);
            selectElement.innerHTML = '<option value="">Select an option</option>';
            if (data.success && data.fuel_types && data.fuel_types.length > 0) {
                data.fuel_types.forEach(fuelType => {
                    const option = document.createElement('option');
                    option.value = fuelType;
                    option.textContent = fuelType;
                    if (currentValue && fuelType === currentValue) {
                        option.selected = true;
                    }
                    selectElement.appendChild(option);
                });
                console.log('Quick Input: Loaded', data.fuel_types.length, 'fuel types');
                // If we have an initial value and it wasn't selected, try to select it
                if (currentValue && selectElement.value !== currentValue) {
                    selectElement.value = currentValue;
                }
            } else {
                console.warn('Quick Input: No fuel types returned', data);
                selectElement.innerHTML = '<option value="">No options available</option>';
            }
            selectElement.disabled = false;
        })
        .catch(error => {
            console.error('Quick Input: Error loading fuel types:', error);
            selectElement.innerHTML = '<option value="">Error loading options</option>';
            selectElement.disabled = false;
        });
    }

    /**
     * Load units based on fuel type
     */
    function loadUnits(sourceId, fuelType, fuelCategory, selectElement, initialValue = null) {
        if (!fuelType) {
            selectElement.innerHTML = '<option value="">Select an option</option>';
            return;
        }

        selectElement.disabled = true;
        const currentValue = selectElement.value || initialValue;
        selectElement.innerHTML = '<option value="">Loading...</option>';

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        let url = `/api/quick-input/units/${sourceId}?fuel_type=${encodeURIComponent(fuelType)}`;
        if (fuelCategory) {
            url += `&fuel_category=${encodeURIComponent(fuelCategory)}`;
        }

        console.log('Quick Input: Loading units from:', url);

        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => {
            console.log('Quick Input: Units response status:', response.status);
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || `HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Quick Input: Units data:', data);
            selectElement.innerHTML = '<option value="">Select an option</option>';
            if (data.success && data.units && data.units.length > 0) {
                data.units.forEach(unit => {
                    const option = document.createElement('option');
                    option.value = unit;
                    option.textContent = unit;
                    if (currentValue && unit === currentValue) {
                        option.selected = true;
                    }
                    selectElement.appendChild(option);
                });
                console.log('Quick Input: Loaded', data.units.length, 'units');
                // If we have a current value and it wasn't selected, try to select it
                if (currentValue && selectElement.value !== currentValue) {
                    selectElement.value = currentValue;
                }
            } else {
                console.warn('Quick Input: No units returned', data);
                selectElement.innerHTML = '<option value="">No options available</option>';
            }
            selectElement.disabled = false;
        })
        .catch(error => {
            console.error('Quick Input: Error loading units:', error);
            selectElement.innerHTML = '<option value="">Error loading options</option>';
            selectElement.disabled = false;
        });
    }

    // Initialize additional features
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Quick Input: DOM loaded, initializing features');
        setupDeleteConfirmations();
        setupFormLoadingStates();
        setupCascadingDropdowns();
        
        // Store original button text
        const submitButtons = document.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(button => {
            button.dataset.originalText = button.textContent;
        });
        
        // Also setup cascading dropdowns after delays (for conditionally rendered forms)
        setTimeout(function() {
            console.log('Quick Input: Retrying setup after 500ms');
            setupCascadingDropdowns();
        }, 500);
        
        setTimeout(function() {
            console.log('Quick Input: Retrying setup after 1500ms');
            setupCascadingDropdowns();
        }, 1500);
        
        // Watch for form appearance
        const observer = new MutationObserver(function(mutations) {
            const form = document.querySelector('form[data-source-id]');
            if (form) {
                console.log('Quick Input: Form detected in DOM changes, setting up cascading dropdowns');
                setupCascadingDropdowns();
            }
        });
        
        if (document.body) {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    });

    // Export functions for global use
    window.QuickInput = {
        calculate: calculateEmissions,
        validate: validateForm,
        loadFuelTypes: loadFuelTypes,
        loadUnits: loadUnits,
    };
})();

