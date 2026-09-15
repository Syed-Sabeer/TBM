@extends('layouts.admin')

@section('title', $warehouse->exists ? $warehouse->code : 'New warehouse')
@section('heading', $warehouse->exists ? 'Edit '.$warehouse->code : 'Add a warehouse')

@section('content')

<section class="panel">
    <form method="POST" action="{{ $warehouse->exists ? route('admin.warehouses.update', $warehouse) : route('admin.warehouses.store') }}">
        @csrf
        @if ($warehouse->exists) @method('PUT') @endif

        <div class="field-grid">
            <div class="field">
                <label class="label" for="code">Code</label>
                <input class="input mono" id="code" name="code" value="{{ old('code', $warehouse->code) }}" maxlength="8" required>
                <p class="hint">Short, uppercase. This is what the mill sheet has to match.</p>
                @error('code') <p class="err">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="name">Name</label>
                <input class="input" id="name" name="name" value="{{ old('name', $warehouse->name) }}" required>
            </div>
            <div class="field">
                <label class="label" for="type">Type</label>
                <select class="select" id="type" name="type">
                    @foreach (['owned' => 'Owned', '3pl' => 'Third-party logistics', 'consignment' => 'Consignment'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $warehouse->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label" for="company_id">Consignment for <small>consignment only</small></label>
                <select class="select" id="company_id" name="company_id">
                    <option value="">—</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected($warehouse->company_id === $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label" for="region">Region</label>
                <input class="input" id="region" name="region" value="{{ old('region', $warehouse->region) }}">
            </div>
            <div class="field">
                <label class="label" for="lead_time">Lead time</label>
                <input class="input" id="lead_time" name="lead_time" value="{{ old('lead_time', $warehouse->lead_time) }}" placeholder="1–2 business days">
            </div>
        </div>

        <div class="field">
            <label class="label" for="street">Street</label>
            <input class="input" id="street" name="street" value="{{ old('street', $warehouse->street) }}">
        </div>

        <div class="field-grid field-grid-3">
            <div class="field"><label class="label" for="city">City</label><input class="input" id="city" name="city" value="{{ old('city', $warehouse->city) }}"></div>
            <div class="field"><label class="label" for="state">State</label><input class="input" id="state" name="state" value="{{ old('state', $warehouse->state) }}"></div>
            <div class="field"><label class="label" for="postcode">ZIP</label><input class="input" id="postcode" name="postcode" value="{{ old('postcode', $warehouse->postcode) }}"></div>
        </div>

        <div class="field-grid">
            <div class="field"><label class="label" for="floor_space">Floor space</label><input class="input" id="floor_space" name="floor_space" value="{{ old('floor_space', $warehouse->floor_space) }}"></div>
            <div class="field"><label class="label" for="position">Order in lists</label><input class="input" id="position" type="number" name="position" value="{{ old('position', $warehouse->position) }}"></div>
        </div>

        <label class="check"><input type="checkbox" name="has_decoration" value="1" @checked(old('has_decoration', $warehouse->has_decoration))><span>Decoration on site</span></label>
        <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $warehouse->is_active))><span>Active</span></label>
        <label class="check"><input type="checkbox" name="include_in_storefront" value="1" @checked(old('include_in_storefront', $warehouse->include_in_storefront))><span>Show its quantities on the storefront</span></label>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">{{ $warehouse->exists ? 'Save' : 'Add warehouse' }}</button>
            <a class="btn btn-ghost" href="{{ route('admin.warehouses.index') }}">Cancel</a>
        </div>
    </form>
</section>

@endsection
