@extends('layouts.account')

@section('title', 'Settings')
@section('heading', 'Settings')
@section('subheading', 'Your profile, and the company details we bill against.')

@section('panel')

<section class="panel">
    <div class="panel-head"><h3>Your profile</h3></div>

    <form method="POST" action="{{ route('account.settings.update') }}">
        @csrf
        @method('PATCH')

        <div class="field-grid">
            <div class="field">
                <label class="label" for="name">Full name</label>
                <input class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name') <p class="err">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="job_title">Job title</label>
                <input class="input" id="job_title" name="job_title" value="{{ old('job_title', $user->job_title) }}">
            </div>
            <div class="field">
                <label class="label" for="email">Work email</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email') <p class="err">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="phone">Phone</label>
                <input class="input" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
            </div>
        </div>

        @can('update', $company)
            <fieldset class="reg-block">
                <legend>Company details</legend>

                <div class="field">
                    <label class="label" for="company_name">Legal company name</label>
                    <input class="input" id="company_name" name="company_name" value="{{ old('company_name', $company->name) }}">
                </div>

                <div class="field">
                    <label class="label" for="website">Website</label>
                    <input class="input" id="website" name="website" value="{{ old('website', $company->website) }}">
                </div>

                <div class="field">
                    <label class="label" for="billing_street">Billing street</label>
                    <input class="input" id="billing_street" name="billing_street" value="{{ old('billing_street', $company->billing_street) }}">
                </div>

                <div class="field-grid field-grid-3">
                    <div class="field">
                        <label class="label" for="billing_city">City</label>
                        <input class="input" id="billing_city" name="billing_city" value="{{ old('billing_city', $company->billing_city) }}">
                    </div>
                    <div class="field">
                        <label class="label" for="billing_state">State</label>
                        <input class="input" id="billing_state" name="billing_state" value="{{ old('billing_state', $company->billing_state) }}">
                    </div>
                    <div class="field">
                        <label class="label" for="billing_postcode">ZIP</label>
                        <input class="input" id="billing_postcode" name="billing_postcode" value="{{ old('billing_postcode', $company->billing_postcode) }}">
                    </div>
                </div>
            </fieldset>
        @endcan

        <button class="btn btn-primary" type="submit">Save</button>
    </form>
</section>

<section class="panel">
    <div class="panel-head"><h3>Password</h3></div>

    <form method="POST" action="{{ route('account.settings.password') }}">
        @csrf
        @method('PATCH')

        <div class="field-grid">
            <div class="field">
                <label class="label" for="current_password">Current password</label>
                <input class="input" id="current_password" type="password" name="current_password" required autocomplete="current-password">
                @error('current_password') <p class="err">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="new_password">New password</label>
                <input class="input" id="new_password" type="password" name="password" required autocomplete="new-password">
                @error('password') <p class="err">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label class="label" for="password_confirmation">Confirm new password</label>
                <input class="input" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
        </div>

        <button class="btn btn-outline" type="submit">Change password</button>
    </form>
</section>

{{-- Read-only: these are set by TBM, and saying so beats a disabled field with no explanation. --}}
<section class="panel">
    <div class="panel-head"><h3>Set by TBM</h3></div>
    <dl class="fact-grid">
        <div><dt>Account number</dt><dd class="mono">{{ $company->account_number }}</dd></div>
        <div><dt>Rate card</dt><dd>{{ $company->tier?->fullName() ?? 'Standard' }}</dd></div>
        <div><dt>Status</dt><dd><x-status-badge :status="$company->status"/></dd></div>
        <div><dt>Payment terms</dt><dd>{{ $company->payment_terms }}</dd></div>
        <div><dt>Credit limit</dt><dd>{{ \App\Support\Money::format($company->credit_limit) }}</dd></div>
        <div><dt>Resale certificate</dt><dd>{{ Str::headline($company->certificate_status) }}</dd></div>
    </dl>
    <p class="hint">To change any of these, speak to {{ $company->accountManager?->name ?? 'your account manager' }}.</p>
</section>

@endsection
