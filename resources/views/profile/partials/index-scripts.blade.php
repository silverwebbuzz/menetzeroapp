{{--
    Profile page scripts - shared VERBATIM by both themes.

    Extracted from profile/index.blade.php (lines 373-459 and 488-508) with no
    edits, following the precedent in redesign.md section 22.

    TWO BLOCKS, both bound to markup the themed page must reproduce exactly:

    1. Sector -> industry -> subcategory cascade.
       Reads ids: sector, industry, business_subcategory.
       Reads data-id on each sector/industry option.
       Calls /api/industries and /api/subcategories.

    2. showTab(name) - the Personal / Company / Password tabs.
       Requires classes .tab-button and .tab-content, the active/inactive class
       pair, and id pairs {name}-tab / {name}-content for personal, company and
       password. showTab is called from inline onclick handlers, so it must stay
       a global function.

    The tab CSS lives in the page, not here: each theme styles its own tabs, but
    both must keep the .tab-button / .tab-content / .active / .inactive contract
    above or the tabs stop switching.

    NOTE: do not write Blade directive names in this comment. Blade compiles
    directives before stripping comments, so a name here is counted by the
    compiler and unbalances the file (redesign.md section 31.8).
--}}
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const sectorSelect = document.getElementById('sector');
                        const industrySelect = document.getElementById('industry');
                        const subcategorySelect = document.getElementById('business_subcategory');
                        
                        if (!sectorSelect || !industrySelect || !subcategorySelect) {
                            return;
                        }
                        
                        sectorSelect.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            const sectorId = selectedOption ? selectedOption.getAttribute('data-id') : null;
                            
                            if (sectorId) {
                                fetch(`/api/industries?sector_id=${sectorId}`)
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error(`HTTP error! status: ${response.status}`);
                                        }
                                        return response.json();
                                    })
                                    .then(data => {
                                        if (Array.isArray(data)) {
                                            industrySelect.innerHTML = '<option value="">Select Industry</option>';
                                            data.forEach(industry => {
                                                industrySelect.innerHTML += `<option value="${industry.name}" data-id="${industry.id}">${industry.name}</option>`;
                                            });
                                            industrySelect.disabled = false;
                                            
                                            // Reset subcategory
                                            subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                                            subcategorySelect.disabled = true;
                                        } else {
                                            console.error('Invalid data format received:', data);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error fetching industries:', error);
                                        industrySelect.innerHTML = '<option value="">Error loading industries</option>';
                                        industrySelect.disabled = true;
                                        subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                                        subcategorySelect.disabled = true;
                                    });
                            } else {
                                industrySelect.innerHTML = '<option value="">Select Industry</option>';
                                industrySelect.disabled = true;
                                subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                                subcategorySelect.disabled = true;
                            }
                        });

                        industrySelect.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            const industryId = selectedOption ? selectedOption.getAttribute('data-id') : null;
                            
                            if (industryId) {
                                fetch(`/api/subcategories?industry_id=${industryId}`)
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error(`HTTP error! status: ${response.status}`);
                                        }
                                        return response.json();
                                    })
                                    .then(data => {
                                        if (Array.isArray(data)) {
                                            subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                                            data.forEach(subcategory => {
                                                subcategorySelect.innerHTML += `<option value="${subcategory.name}">${subcategory.name}</option>`;
                                            });
                                            subcategorySelect.disabled = false;
                                        } else {
                                            console.error('Invalid data format received:', data);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error fetching subcategories:', error);
                                        subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                                        subcategorySelect.disabled = true;
                                    });
                            } else {
                                subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                                subcategorySelect.disabled = true;
                            }
                        });
                    });
                    </script>

<script>
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
        button.classList.add('inactive');
    });
    
    // Show selected tab content
    document.getElementById(tabName + '-content').classList.add('active');
    
    // Add active class to selected tab button
    document.getElementById(tabName + '-tab').classList.remove('inactive');
    document.getElementById(tabName + '-tab').classList.add('active');
}
</script>
