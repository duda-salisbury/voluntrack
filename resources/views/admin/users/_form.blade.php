{{--
    Shared form fields for creating and editing a User.
    Expected variables:
      $user          — model instance (new or existing)
      $organizations — collection of orgs (populated for super-admins, empty for org-admins)
      $adminOrgId    — int|null — the editing admin's own org scope
--}}

<div class="card mb-4">
    <div class="card-header fw-semibold">Account Details</div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-12 col-md-6">
                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $user->name ?? '') }}"
                    required maxlength="255" autocomplete="name">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-6">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" id="email"
                    class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email', $user->email ?? '') }}"
                    required maxlength="255" autocomplete="email">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-6">
                <label for="password" class="form-label">
                    Password
                    @if(isset($user) && $user->exists)
                        <span class="text-muted fw-normal">(leave blank to keep current)</span>
                    @else
                        <span class="text-danger">*</span>
                    @endif
                </label>
                <input type="password" name="password" id="password"
                    class="form-control @error('password') is-invalid @enderror"
                    {{ isset($user) && $user->exists ? '' : 'required' }}
                    minlength="8" autocomplete="new-password">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-6">
                <label for="password_confirmation" class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                    class="form-control"
                    minlength="8" autocomplete="new-password">
            </div>

        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header fw-semibold">Role &amp; Organization</div>
    <div class="card-body">
        <div class="row g-3">

            {{-- Organization — super-admins can assign to any org; org-admins see a read-only badge --}}
            @if($adminOrgId === null)
                <div class="col-12 col-md-6">
                    <label for="organization_id" class="form-label">Organization</label>
                    <select name="organization_id" id="organization_id" class="form-select @error('organization_id') is-invalid @enderror">
                        <option value="">— None (super-admin account)</option>
                        @foreach($organizations as $o)
                            <option value="{{ $o->id }}"
                                {{ old('organization_id', $user->organization_id ?? '') == $o->id ? 'selected' : '' }}>
                                {{ $o->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Super-admins have no organization.</div>
                    @error('organization_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label d-block">Role</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" role="switch"
                            name="is_admin" id="is_admin" value="1"
                            {{ old('is_admin', $user->is_admin ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_admin">Admin account</label>
                    </div>
                    <div class="form-text">Admins can access the admin panel. Volunteers can only log hours.</div>
                </div>
            @else
                {{-- Org-admin view: show org as read-only, role toggle limited to volunteer only --}}
                <div class="col-12 col-md-6">
                    <label class="form-label">Organization</label>
                    <input type="text" class="form-control" value="{{ $org->name ?? '' }}" disabled>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control"
                        value="{{ (isset($user) && $user->is_admin) ? 'Admin' : 'Volunteer' }}" disabled>
                    <div class="form-text">Contact a super-admin to change roles.</div>
                </div>
            @endif

        </div>
    </div>
</div>
