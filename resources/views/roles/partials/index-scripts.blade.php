{{--
    Team & Access scripts - shared VERBATIM by both themes.

    Extracted from roles/index.blade.php (lines 464-720) with no edits, per the
    precedent in redesign.md section 22.

    DUAL CONTEXT: this page renders in BOTH the client and consultant portals.
    TeamAccessService::routesFor() swaps the route names and layoutFor() swaps
    the shell, so route names arrive through the $teamRoutes array rather than
    being hard-coded. Every route reference below therefore reads from
    $teamRoutes and must keep doing so - hard-coding a client route name would
    silently break the consultant portal, and vice versa.

    Defines 11 globals, called from inline onclick handlers in the page body:
        openAddUserModal   closeAddUserModal   submitAddUserForm
        viewUser           closeViewUserModal
        editUserRole       closeEditUserRoleModal
        resendInvitation   cancelInvitation
        togglePassword     showUpgradeMessage

    Element ids it requires the page to provide:
        addUserModal, email
    Element ids it CREATES at runtime:
        viewUserModal, viewUserName, viewUserEmail, viewUserPhone,
        viewUserRole, viewUserStatus,
        editUserRoleModal, editUserRoleForm, editRoleSelect

    KNOWN LIMITATION, accepted deliberately: viewUser() and editUserRole()
    inject their modal markup as JS template literals with hard-coded Tailwind
    classes. Those two modals therefore keep the OLD look in the new theme.
    Restyling them means editing JS that drives role assignment - the edit-role
    modal posts to $teamRoutes['staff.update_role'] - so it was left alone.
    Recorded in redesign.md section 36.

    NOTE: do not write Blade directive names in this comment. Blade compiles
    directives before stripping comments, so a name here is counted by the
    compiler and unbalances the file (redesign.md section 31.8).
--}}
<script>
function openAddUserModal() {
    document.getElementById('addUserModal').classList.remove('hidden');
}

function closeAddUserModal() {
    document.getElementById('addUserModal').classList.add('hidden');
}

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    field.type = field.type === 'password' ? 'text' : 'password';
}

// View User Modal
function viewUser(userId, userName, userEmail, userPhone, userRole, userStatus) {
    // Create modal if it doesn't exist
    if (!document.getElementById('viewUserModal')) {
        const modalHTML = `
            <div id="viewUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">User Details</h2>
                            <button onclick="closeViewUserModal()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Name</label>
                                <p class="text-lg font-semibold text-gray-900" id="viewUserName"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                                <p class="text-lg text-gray-900" id="viewUserEmail"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Phone</label>
                                <p class="text-lg text-gray-900" id="viewUserPhone"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Role</label>
                                <p class="text-lg text-gray-900" id="viewUserRole"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                                <span id="viewUserStatus"></span>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button onclick="closeViewUserModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    document.getElementById('viewUserName').textContent = userName;
    document.getElementById('viewUserEmail').textContent = userEmail;
    document.getElementById('viewUserPhone').textContent = userPhone || 'N/A';
    document.getElementById('viewUserRole').textContent = userRole;
    document.getElementById('viewUserStatus').textContent = userStatus;
    document.getElementById('viewUserStatus').className = userStatus === 'Active' 
        ? 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800'
        : 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800';
    
    document.getElementById('viewUserModal').classList.remove('hidden');
}

function closeViewUserModal() {
    document.getElementById('viewUserModal').classList.add('hidden');
}

// Edit User Role Modal
function editUserRole(userCompanyRoleId, currentRoleId) {
    // Create modal if it doesn't exist
    if (!document.getElementById('editUserRoleModal')) {
        const modalHTML = `
            <div id="editUserRoleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">Edit User Role</h2>
                            <button onclick="closeEditUserRoleModal()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <form id="editUserRoleForm" method="POST" action="">
                            @csrf
                            @method('PUT')
                            <div class="mb-4">
                                <label for="editRoleSelect" class="block text-sm font-medium text-gray-700 mb-2">Select Role</label>
                                <select name="company_custom_role_id" id="editRoleSelect" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select a role</option>
                                    @foreach($customRoles as $role)
                                        <option value="{{ $role->id }}">{{ $role->role_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center justify-end gap-3 mt-6">
                                <button type="button" onclick="closeEditUserRoleModal()" 
                                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    Update Role
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    const form = document.getElementById('editUserRoleForm');
    // Set form action to the correct route
    form.action = @json(route($teamRoutes['staff.update_role'], ['access' => 999999])).replace('999999', String(userCompanyRoleId));
    document.getElementById('editRoleSelect').value = currentRoleId;
    
    document.getElementById('editUserRoleModal').classList.remove('hidden');
}

function closeEditUserRoleModal() {
    document.getElementById('editUserRoleModal').classList.add('hidden');
}

function submitAddUserForm(event) {
    // Validate form before submission (invitation only - no password needed)
    const email = document.getElementById('email').value.trim();
    const customRoleId = document.getElementById('custom_role_id').value;
    
    // Validate required fields
    if (!email || !customRoleId) {
        alert('Please fill in all required fields (Email and Role).');
        event.preventDefault();
        return false;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        event.preventDefault();
        return false;
    }
    
    // Form will submit normally
    return true;
}

// Close modal on outside click
document.getElementById('addUserModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddUserModal();
    }
});

// Close view user modal on outside click
document.addEventListener('click', function(e) {
    const viewModal = document.getElementById('viewUserModal');
    if (viewModal && e.target === viewModal) {
        closeViewUserModal();
    }
    
    const editModal = document.getElementById('editUserRoleModal');
    if (editModal && e.target === editModal) {
        closeEditUserRoleModal();
    }
});

// Close modal on successful form submission
@if(session('success'))
    closeAddUserModal();
@endif

// Keep modal open if there are errors
@if($errors->any())
    document.addEventListener('DOMContentLoaded', function() {
        openAddUserModal();
    });
@endif

function showUpgradeMessage() {
    const message = @json($userLimitMessage ?? 'You have reached your plan limit for team members. Please upgrade to add more.');
    if (confirm(message + '\n\nOpen upgrade page now?')) {
        window.location.href = @json(route($teamUpgradeRoute ?? 'subscriptions.upgrade'));
    }
}

// Resend Invitation
function resendInvitation(invitationId) {
    if (!confirm('Are you sure you want to resend this invitation?')) {
        return;
    }
    
    fetch(@json(route($teamRoutes['staff.resend_invitation'], ['invitation' => 999999])).replace('999999', String(invitationId)), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Invitation resent successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to resend invitation'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while resending the invitation.');
    });
}

// Cancel Invitation
function cancelInvitation(invitationId) {
    if (!confirm('Are you sure you want to cancel this invitation? This action cannot be undone.')) {
        return;
    }
    
    fetch(@json(route($teamRoutes['staff.cancel_invitation'], ['invitation' => 999999])).replace('999999', String(invitationId)), {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Invitation cancelled successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to cancel invitation'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while cancelling the invitation.');
    });
}
</script>
