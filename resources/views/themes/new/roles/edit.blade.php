{{--
    MENetZero 2.0 - Edit Role (Phase 6 body migration).

    DUAL CONTEXT: renders in both the client and consultant portals. The layout
    comes from $teamLayout and the route names from $teamRoutes, with the same
    defensive fallbacks the original used. Never hard-code a route name here.

    PERMISSION MATRIX - the security-critical part. Each checkbox posts
    permission_ids[] with a permission id; the set of checked boxes IS the
    role's grant. Preserved exactly:
      - name="permission_ids[]" on every box
      - class .module-checkbox (the select-all script queries it)
      - id="selectAll"
      - the view / add / edit / delete column order

    The select-all script is page-local and identical in both themes, so it is
    carried across verbatim rather than shared - it is 20 lines and has no
    cross-page contract.

    Adds over create: @method('PUT'), $selectedPermissionIds preselection on
    every checkbox, and the is_active toggle. The preselection is what shows an
    admin the role's CURRENT grant - dropping it would render every box
    unchecked and a careless save would strip the role's permissions.

    Controller data: $permissions $role $selectedPermissionIds + TeamAccessService shared
--}}
@extends($teamLayout ?? 'layouts.app')

@section('title', 'Edit Role - MenetZero')
@section('page-title', 'Edit Role')

@push('styles')
    <style>
        .rl-matrix { border: 1px solid var(--line); }
        .rl-matrix__head, .rl-matrix__row { display: grid;
            grid-template-columns: 2fr repeat(4, 1fr); gap: 14px;
            align-items: center; padding: 10px 16px; }
        .rl-matrix__head { background: var(--canvas); border-bottom: 1px solid var(--line);
            font: 500 10.5px var(--mono); letter-spacing: .08em; text-transform: uppercase;
            color: var(--ink-4); }
        .rl-matrix__row { border-bottom: 1px solid var(--line-2); font-size: 12.5px; }
        .rl-matrix__row:last-child { border-bottom: 0; }
        .rl-matrix__row:hover { background: var(--canvas-2); }
        .rl-matrix .t-c { text-align: center; }
        .rl-err { font-size: 11.5px; color: var(--bad); margin-top: 4px; }
    </style>
@endpush

@section('content')
<div class="mnz-stack" data-pillar="g">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Governance · Access</div>
            <h1>Edit Role</h1>
            <p class="mnz-lead">Set role permissions</p>
        </div>
    </div>

    <div class="mnz-panel">
        <div class="mnz-panel__body">
            <form action="{{ route(($teamRoutes ?? [])['roles.update'] ?? 'roles.update', $role) }}" method="POST" id="roleForm">
                @csrf
                @method('PUT')

                <div class="mnz-field" style="margin-bottom:16px">
                    <label for="role_name" class="mnz-label">Role Name</label>
                    <input type="text" name="role_name" id="role_name" required
                           value="{{ old('role_name', $role->role_name) }}" placeholder="Enter a role name" class="mnz-input">
                    @error('role_name')<p class="rl-err">{{ $message }}</p>@enderror
                </div>

                <div class="mnz-field" style="margin-bottom:18px">
                    <label for="description" class="mnz-label">Description</label>
                    <textarea name="description" id="description" rows="3"
                              placeholder="Enter role description" class="mnz-textarea">{{ old('description', $role->description) }}</textarea>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                    <span class="mnz-label">Role Permissions</span>
                    <label style="display:inline-flex;align-items:center;gap:7px;font-size:12.5px">
                        <input type="checkbox" id="selectAll">
                        Select All
                    </label>
                </div>

                <div class="rl-matrix">
                    <div class="rl-matrix__head">
                        <div>Module</div>
                        <div class="t-c">View</div>
                        <div class="t-c">Add</div>
                        <div class="t-c">Edit</div>
                        <div class="t-c">Delete</div>
                    </div>
                    @foreach($permissions as $module => $modulePermissions)
                        @php
                            $viewPerm = $modulePermissions->firstWhere('action', 'view');
                            $addPerm = $modulePermissions->firstWhere('action', 'add');
                            $editPerm = $modulePermissions->firstWhere('action', 'edit');
                            $deletePerm = $modulePermissions->firstWhere('action', 'delete');
                        @endphp
                        <div class="rl-matrix__row">
                            <div style="font-weight:500">{{ ucfirst(str_replace('_', ' ', $module)) }}</div>
                            <div class="t-c">
                                @if($viewPerm)
                                    <input type="checkbox" name="permission_ids[]" value="{{ $viewPerm->id }}"
                                           {{ in_array($viewPerm->id, $selectedPermissionIds) ? 'checked' : '' }}
                                           class="module-checkbox">
                                @endif
                            </div>
                            <div class="t-c">
                                @if($addPerm)
                                    <input type="checkbox" name="permission_ids[]" value="{{ $addPerm->id }}"
                                           {{ in_array($addPerm->id, $selectedPermissionIds) ? 'checked' : '' }}
                                           class="module-checkbox">
                                @endif
                            </div>
                            <div class="t-c">
                                @if($editPerm)
                                    <input type="checkbox" name="permission_ids[]" value="{{ $editPerm->id }}"
                                           {{ in_array($editPerm->id, $selectedPermissionIds) ? 'checked' : '' }}
                                           class="module-checkbox">
                                @endif
                            </div>
                            <div class="t-c">
                                @if($deletePerm)
                                    <input type="checkbox" name="permission_ids[]" value="{{ $deletePerm->id }}"
                                           {{ in_array($deletePerm->id, $selectedPermissionIds) ? 'checked' : '' }}
                                           class="module-checkbox">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('permission_ids')<p class="rl-err">{{ $message }}</p>@enderror

                <div style="margin-top:18px">
                    <label style="display:inline-flex;align-items:center;gap:7px;font-size:12.5px">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $role->is_active) ? 'checked' : '' }}>
                        Active
                    </label>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px;padding-top:16px;border-top:1px solid var(--line)">
                    <a href="{{ route(($teamRoutes ?? [])['index'] ?? 'roles.index') }}" class="mnz-btn">Cancel</a>
                    <button type="submit" class="mnz-btn mnz-btn--primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const moduleCheckboxes = document.querySelectorAll('.module-checkbox');

    selectAll.addEventListener('change', function() {
        moduleCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    moduleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(moduleCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(moduleCheckboxes).some(cb => cb.checked);
            selectAll.checked = allChecked;
            selectAll.indeterminate = someChecked && !allChecked;
        });
    });
});
</script>
@endsection
