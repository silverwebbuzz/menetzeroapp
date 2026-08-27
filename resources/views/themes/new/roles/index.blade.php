{{--
    MENetZero 2.0 - Team & Access (Phase 6 body migration).

    DUAL CONTEXT - the defining constraint. This view renders in BOTH the client
    and consultant portals. TeamAccessService supplies:
        $teamLayout    'layouts.app' or 'consultant.layouts.app'
        $teamRoutes    an array that swaps every route name between portals
        $teamContext   'client' or 'consultant', drives the copy
    Route names are therefore NEVER hard-coded here. Hard-coding a client route
    would silently break the consultant portal, and vice versa.

    Verified: neither themed shell renders page-title, and this page supplies its
    own h1 - so one body serves both contexts without the duplicate-heading bug
    (redesign.md section 16 rule 2).

    SEAT LIMIT GATE: $canAddUser['allowed'] decides whether the invite button
    opens the modal or calls showUpgradeMessage(). Rendering the live button
    unconditionally would let a company exceed its paid seat count.

    SCRIPT: shared verbatim from roles/partials/index-scripts. All 7 onclick
    handlers below are defined there. Two of its modals (viewUser, editUserRole)
    inject markup as JS template literals with hard-coded Tailwind, so they keep
    the OLD look in this theme - accepted deliberately, see section 36.3.

    Element ids this page must provide for that script: addUserModal, email.

    Controller data: $customRoles $staffMembers $pendingInvitations
    + TeamAccessService::viewShared(): $teamLayout $teamContext $teamRoutes
      $teamMenuLabel $canAddUser $userLimitMessage $teamUpgradeRoute
      $showConsultantTrialNotice
--}}
@extends($teamLayout ?? 'layouts.app')

@section('title', ($teamMenuLabel ?? 'Team & Access') . ' - MenetZero')
@section('page-title', $teamMenuLabel ?? 'Team & Access')

@push('styles')
    <style>
        .rl-grid { display: grid; gap: 16px;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
        .rl-card { border: 1px solid var(--line); background: var(--surface); padding: 18px 20px; }
        .rl-card--new { border-style: dashed; border-color: var(--line-3);
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 10px; text-align: center; }
        .rl-avatars { display: flex; }
        .rl-avatars > div { width: 28px; height: 28px; border-radius: 50%;
            background: var(--canvas-2); border: 2px solid var(--surface);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 500; color: var(--ink-2); margin-right: -7px; }
        .rl-user { display: flex; align-items: center; gap: 11px; }
        .rl-user__ini { flex-shrink: 0; width: 34px; height: 34px; border-radius: 50%;
            background: var(--accent-tint); border: 1px solid var(--accent-line);
            color: var(--accent); display: flex; align-items: center;
            justify-content: center; font-size: 12.5px; font-weight: 600; }
        .rl-actions { display: flex; align-items: center; justify-content: flex-end; gap: 8px; }
        .rl-actions a, .rl-actions button { color: var(--ink-3); background: none;
            border: 0; padding: 0; cursor: pointer; }
        .rl-actions a:hover, .rl-actions button:hover { color: var(--accent); }
        .rl-actions .is-danger:hover { color: var(--bad); }
        .rl-modal { position: fixed; inset: 0; background: rgba(20,22,26,.45);
            z-index: 50; display: flex; align-items: center; justify-content: center; }
        .rl-modal.hidden { display: none; }
        .rl-modal__box { background: var(--surface); border: 1px solid var(--line);
            width: 100%; max-width: 640px; margin: 0 16px; max-height: 90vh; overflow-y: auto; }
        .rl-cols { display: grid; gap: 18px; grid-template-columns: repeat(2, minmax(0,1fr)); }
        @media (max-width: 720px) { .rl-cols { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
@php
    $teamRoutes = $teamRoutes ?? [
        'index' => 'roles.index',
        'roles.create' => 'roles.create',
        'roles.edit' => 'roles.edit',
        'roles.store' => 'roles.store',
        'staff.store' => 'staff.store',
        'staff.destroy' => 'staff.destroy',
        'staff.update_role' => 'staff.update-role',
        'staff.invitation_success' => 'staff.invitation-success',
        'staff.resend_invitation' => 'staff.resend-invitation',
        'staff.cancel_invitation' => 'staff.cancel-invitation',
    ];
    $canAddUser = $canAddUser ?? ['allowed' => true, 'message' => null];
@endphp

<div class="mnz-stack" data-pillar="g">

    @if(!empty($showConsultantTrialNotice))
        <div class="mnz-panel" style="border-color:var(--warn-line);background:var(--warn-tint)">
            <div class="mnz-panel__body" style="font-size:12.5px;color:var(--warn)">
                <strong>Free trial — solo workspace.</strong> You can review roles and team settings here.
                Inviting colleagues requires a paid agency pack
                (<a href="{{ route($teamUpgradeRoute ?? 'consultant.packs.index') }}">Consultant 5 and above</a>).
            </div>
        </div>
    @endif

    {{-- Roles --}}
    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Governance · Access</div>
            <h1>Roles</h1>
            <p class="mnz-lead">
                @if(($teamContext ?? 'client') === 'consultant')
                    Control what agency colleagues can do in your consultant workspace and managed client workspaces.
                @else
                    Define what each role can see and do across locations, emissions data, reports, and settings.
                @endif
            </p>
        </div>
    </div>

    <div class="rl-grid">
        @forelse($customRoles as $role)
            <div class="rl-card">
                <div class="mnz-label">Total {{ $role->users_count ?? 0 }} users</div>
                <h3 style="font-size:14px;font-weight:600;margin:10px 0 6px">{{ $role->role_name }}</h3>
                <a href="{{ route($teamRoutes['roles.edit'], $role) }}" style="font-size:12.5px">Edit Role</a>

                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:16px">
                    <div class="rl-avatars">
                        @php
                            $users = $role->users()->with('user')->where('is_active', true)->limit(4)->get();
                            $totalUsers = $role->users_count ?? $role->users()->where('is_active', true)->count();
                        @endphp
                        @foreach($users as $userRole)
                            @if($userRole->user)
                                <div>{{ strtoupper(substr($userRole->user->name, 0, 1)) }}</div>
                            @endif
                        @endforeach
                        @if($totalUsers > 4)
                            <div>+{{ $totalUsers - 4 }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div style="grid-column:1/-1">
                <div class="mnz-panel">
                    <div class="mnz-empty">
                        <div class="mnz-empty__text">No roles found. Create your first role to get started.</div>
                    </div>
                </div>
            </div>
        @endforelse

        <div class="rl-card rl-card--new">
            <a href="{{ route($teamRoutes['roles.create']) }}" class="mnz-btn mnz-btn--primary" style="width:100%">
                Add New Role
            </a>
            <p style="font-size:12px;color:var(--ink-3);margin:0">Add new role, if it doesn't exist.</p>
        </div>
    </div>

    {{-- Team members --}}
    <div class="mnz-pagehead" style="border-bottom:0;padding-bottom:0">
        <div>
            <h2 style="font-size:17px;font-weight:600;margin:0">Team members</h2>
            <p class="mnz-lead" style="margin-top:6px">
                People with access to this {{ ($teamContext ?? 'client') === 'consultant' ? 'agency' : 'company' }} workspace and their assigned roles.
            </p>
        </div>
        <div class="mnz-pagehead__actions">
            @if($canAddUser['allowed'])
                <button onclick="openAddUserModal()" class="mnz-btn mnz-btn--primary">Invite team member</button>
            @else
                <button onclick="showUpgradeMessage()" class="mnz-btn" title="{{ $userLimitMessage }}" disabled>
                    Invite team member
                </button>
            @endif
        </div>
    </div>

    <div class="mnz-panel">
        <div style="overflow-x:auto">
            <table class="mnz-table" style="width:100%">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffMembers as $staff)
                        <tr>
                            <td>
                                <div class="rl-user">
                                    <div class="rl-user__ini">{{ strtoupper(substr($staff->user->name ?? 'U', 0, 1)) }}</div>
                                    <div style="min-width:0">
                                        <div style="font-weight:500">{{ $staff->user->name ?? 'N/A' }}</div>
                                        <div style="font-size:11.5px;color:var(--ink-3)">{{ $staff->user->email ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $staff->companyCustomRole->role_name ?? 'N/A' }}</td>
                            <td><span class="mnz-chip mnz-chip--ok">Active</span></td>
                            <td style="text-align:right">
                                <div class="rl-actions">
                                    <button onclick="viewUser({{ $staff->user->id ?? 0 }}, '{{ $staff->user->name ?? 'N/A' }}', '{{ $staff->user->email ?? 'N/A' }}', '{{ $staff->user->phone ?? 'N/A' }}', '{{ $staff->companyCustomRole->role_name ?? 'N/A' }}', '{{ $staff->is_active ? 'Active' : 'Inactive' }}')" title="View">
                                        <svg style="width:17px;height:17px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="editUserRole({{ $staff->id }}, {{ $staff->company_custom_role_id ?? 0 }})" title="Edit Role">
                                        <svg style="width:17px;height:17px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <form action="{{ route($teamRoutes['staff.destroy'], $staff->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to remove this user from your company?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="is-danger" title="Delete">
                                            <svg style="width:17px;height:17px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="mnz-empty">
                                    <div class="mnz-empty__title">No users found.</div>
                                    <div class="mnz-empty__text">Invite new users to get started.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pending invitations --}}
    <div class="mnz-pagehead" style="border-bottom:0;padding-bottom:0">
        <div>
            <h2 style="font-size:17px;font-weight:600;margin:0">Pending Invitations</h2>
            <p class="mnz-lead" style="margin-top:6px">Invitations that have been sent but not yet accepted.</p>
        </div>
    </div>

    <div class="mnz-panel">
        <div style="overflow-x:auto">
            <table class="mnz-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Invited By</th>
                        <th>Invited At</th>
                        <th>Expires At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingInvitations as $invitation)
                        <tr>
                            <td style="font-weight:500">{{ $invitation->email }}</td>
                            <td>
                                @php
                                    $roleId = $invitation->company_custom_role_id ?? $invitation->custom_role_id;
                                    $role = $roleId ? \App\Models\CompanyCustomRole::find($roleId) : null;
                                @endphp
                                @if($role)
                                    <span class="mnz-chip">{{ $role->role_name }}</span>
                                @else
                                    <span style="color:var(--ink-3)">No role assigned</span>
                                @endif
                            </td>
                            <td>
                                @if($invitation->inviter)
                                    <div>{{ $invitation->inviter->name }}</div>
                                    <div style="font-size:11.5px;color:var(--ink-3)">{{ $invitation->inviter->email }}</div>
                                @else
                                    <span style="color:var(--ink-3)">Unknown</span>
                                @endif
                            </td>
                            <td style="white-space:nowrap">
                                <div>{{ $invitation->invited_at ? $invitation->invited_at->format('M d, Y') : 'N/A' }}</div>
                                <div style="font-size:11.5px;color:var(--ink-3)">{{ $invitation->invited_at ? $invitation->invited_at->format('h:i A') : '' }}</div>
                            </td>
                            <td style="white-space:nowrap">
                                @if($invitation->expires_at)
                                    @if($invitation->expires_at->isPast())
                                        <span class="mnz-chip mnz-chip--bad">Expired</span>
                                    @else
                                        <div>{{ $invitation->expires_at->format('M d, Y') }}</div>
                                        <div style="font-size:11.5px;color:var(--ink-3)">{{ $invitation->expires_at->diffForHumans() }}</div>
                                    @endif
                                @else
                                    <span style="color:var(--ink-3)">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($invitation->status === 'pending')
                                    <span class="mnz-chip mnz-chip--warn">Pending</span>
                                @elseif($invitation->status === 'accepted')
                                    <span class="mnz-chip mnz-chip--ok">Accepted</span>
                                @elseif($invitation->status === 'rejected')
                                    <span class="mnz-chip mnz-chip--bad">Rejected</span>
                                @else
                                    <span class="mnz-chip">{{ ucfirst($invitation->status) }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="rl-actions" style="justify-content:flex-start">
                                    @if($invitation->status === 'pending')
                                        <a href="{{ route($teamRoutes['staff.invitation_success'], $invitation->id) }}" title="View Invitation Link">
                                            <svg style="width:17px;height:17px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                        <button onclick="resendInvitation({{ $invitation->id }})" title="Resend Invitation">
                                            <svg style="width:17px;height:17px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        </button>
                                        <button onclick="cancelInvitation({{ $invitation->id }})" class="is-danger" title="Cancel Invitation">
                                            <svg style="width:17px;height:17px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="mnz-empty">
                                    <div class="mnz-empty__text">No pending invitations.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add user modal. id="addUserModal" and id="email" are read by the shared
     script - do not rename. --}}
<div id="addUserModal" class="rl-modal hidden">
    <div class="rl-modal__box">
        <div class="mnz-panel__head">
            <h2 style="font-size:15px;font-weight:600;margin:0">Invite team member</h2>
            <button onclick="closeAddUserModal()" class="mnz-btn mnz-btn--ghost">Close</button>
        </div>

        <div class="mnz-panel__body">
            <form action="{{ route($teamRoutes['staff.store']) }}" method="POST" id="addUserForm" onsubmit="return submitAddUserForm(event)">
                @csrf

                @if($errors->any())
                    <div class="mnz-panel" style="border-color:var(--bad-line);background:var(--bad-tint);margin-bottom:16px">
                        <div class="mnz-panel__body" style="color:var(--bad);font-size:12.5px">
                            <p style="margin:0 0 6px;font-weight:600">Please fix the following errors:</p>
                            <ul style="margin:0;padding-left:18px">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="rl-cols">
                    <div class="mnz-field">
                        <label for="email" class="mnz-label">Email *</label>
                        <input type="email" name="email" id="email" required value="{{ old('email') }}"
                               class="mnz-input" placeholder="user@example.com">
                        @error('email')<p style="font-size:11.5px;color:var(--bad);margin-top:4px">{{ $message }}</p>@enderror
                        <p style="font-size:11.5px;color:var(--ink-3);margin-top:4px">User will set their password when accepting the invitation</p>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:14px">
                        <div class="mnz-field">
                            <label for="custom_role_id" class="mnz-label">Role *</label>
                            <select name="custom_role_id" id="custom_role_id" required class="mnz-select">
                                <option value="">Select a role</option>
                                @foreach($customRoles as $role)
                                    <option value="{{ $role->id }}" {{ old('custom_role_id') == $role->id ? 'selected' : '' }}>{{ $role->role_name }}</option>
                                @endforeach
                            </select>
                            @error('custom_role_id')<p style="font-size:11.5px;color:var(--bad);margin-top:4px">{{ $message }}</p>@enderror
                        </div>
                        <div class="mnz-field">
                            <label for="phone" class="mnz-label">Phone Number</label>
                            <input type="tel" name="phone" id="phone" class="mnz-input">
                        </div>
                        <div class="mnz-field">
                            <label for="notes" class="mnz-label">Notes (Optional)</label>
                            <textarea name="notes" id="notes" rows="3" class="mnz-textarea"
                                      placeholder="Add any additional notes for this invitation...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px;padding-top:16px;border-top:1px solid var(--line)">
                    <button type="button" onclick="closeAddUserModal()" class="mnz-btn">Cancel</button>
                    <button type="submit" class="mnz-btn mnz-btn--primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Shared with the old theme - see roles/partials/index-scripts. --}}
@include('roles.partials.index-scripts')
@endsection
