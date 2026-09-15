@extends('layouts.account')

@section('title', $address->exists ? 'Edit address' : 'Add an address')
@section('heading', $address->exists ? 'Edit '.$address->label : 'Add an address')

@section('panel')

<section class="panel">
    <form method="POST" action="{{ $address->exists ? route('account.addresses.update', $address) : route('account.addresses.store') }}">
        @csrf
        @if ($address->exists)
            @method('PUT')
        @endif

        <div class="field-grid">
            <div class="field">
                <label class="label" for="label">Label</label>
                <input class="input" id="label" name="label" value="{{ old('label', $address->label) }}"
                       placeholder="Warehouse — receiving dock" required>
                @error('label') <p class="err">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="company_name">Company on the label <small>optional</small></label>
                <input class="input" id="company_name" name="company_name"
                       value="{{ old('company_name', $address->company_name ?? auth()->user()->company->name) }}">
            </div>
        </div>

        <div class="field">
            <label class="label" for="street">Street</label>
            <input class="input" id="street" name="street" value="{{ old('street', $address->street) }}" required>
            @error('street') <p class="err">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label class="label" for="street_2">Suite, unit or dock <small>optional</small></label>
            <input class="input" id="street_2" name="street_2" value="{{ old('street_2', $address->street_2) }}">
        </div>

        <div class="field-grid field-grid-3">
            <div class="field">
                <label class="label" for="city">City</label>
                <input class="input" id="city" name="city" value="{{ old('city', $address->city) }}" required>
                @error('city') <p class="err">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="state">State</label>
                <input class="input" id="state" name="state" value="{{ old('state', $address->state) }}" required>
            </div>
            <div class="field">
                <label class="label" for="postcode">ZIP</label>
                <input class="input" id="postcode" name="postcode" value="{{ old('postcode', $address->postcode) }}" required>
            </div>
        </div>

        <div class="field">
            <label class="label" for="notes">Delivery notes <small>optional</small></label>
            <input class="input" id="notes" name="notes" value="{{ old('notes', $address->notes) }}"
                   placeholder="Dock 4, deliveries before 3pm, call on arrival">
        </div>

        <label class="check">
            <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address->is_default))>
            <span>Use this as the default ship-to</span>
        </label>

        <label class="check">
            <input type="checkbox" name="is_residential" value="1" @checked(old('is_residential', $address->is_residential))>
            <span>This is a residential address <small>carriers charge a surcharge</small></span>
        </label>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">{{ $address->exists ? 'Save changes' : 'Add address' }}</button>
            <a class="btn btn-ghost" href="{{ route('account.addresses.index') }}">Cancel</a>
        </div>
    </form>
</section>

@endsection
