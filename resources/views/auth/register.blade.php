@extends('layouts.auth')

@section('title', 'Open a wholesale account')

@section('content')
<div class="auth-shell auth-shell-wide">

    <div class="auth-panel">
        <div class="auth-inner auth-inner-wide">
            @include('partials.logo')

            <h1>Open a wholesale account</h1>
            <p class="auth-sub">
                We sell to the trade only. A person reviews every application — usually within one business day.
            </p>

            @if ($errors->any())
                <div class="notice notice-warn">
                    <x-icon name="shield"/>
                    <div>
                        <b>Please check the form</b>
                        <ul class="err-list">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="auth-form">
                @csrf

                <fieldset class="reg-block">
                    <legend>Your business</legend>

                    <div class="field-grid">
                        <div class="field">
                            <label class="label" for="company_name">Legal company name</label>
                            <input class="input" id="company_name" name="company_name" value="{{ old('company_name') }}" required>
                        </div>
                        <div class="field">
                            <label class="label" for="trading_name">Trading name <small>if different</small></label>
                            <input class="input" id="trading_name" name="trading_name" value="{{ old('trading_name') }}">
                        </div>
                        <div class="field">
                            <label class="label" for="business_type">What kind of business</label>
                            <select class="select" id="business_type" name="business_type" required>
                                <option value="">Choose one</option>
                                @foreach ($businessTypes as $type)
                                    <option value="{{ $type }}" @selected(old('business_type') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label class="label" for="website">Website <small>optional</small></label>
                            <input class="input" id="website" name="website" value="{{ old('website') }}" placeholder="example.com">
                        </div>
                        <div class="field">
                            <label class="label" for="ein">EIN <small>optional now</small></label>
                            <input class="input" id="ein" name="ein" value="{{ old('ein') }}">
                        </div>
                        <div class="field">
                            <label class="label" for="resale_certificate">Resale certificate number <small>optional now</small></label>
                            <input class="input" id="resale_certificate" name="resale_certificate" value="{{ old('resale_certificate') }}">
                        </div>
                    </div>

                    <p class="hint">
                        We need a current resale certificate on file before the first order ships.
                        Send it to {{ config('tbm.company.compliance_email') }} whenever it is to hand.
                    </p>
                </fieldset>

                <fieldset class="reg-block">
                    <legend>Billing address</legend>

                    <div class="field">
                        <label class="label" for="billing_street">Street</label>
                        <input class="input" id="billing_street" name="billing_street" value="{{ old('billing_street') }}" required>
                    </div>

                    <div class="field-grid field-grid-3">
                        <div class="field">
                            <label class="label" for="billing_city">City</label>
                            <input class="input" id="billing_city" name="billing_city" value="{{ old('billing_city') }}" required>
                        </div>
                        <div class="field">
                            <label class="label" for="billing_state">State</label>
                            <input class="input" id="billing_state" name="billing_state" value="{{ old('billing_state') }}" required>
                        </div>
                        <div class="field">
                            <label class="label" for="billing_postcode">ZIP</label>
                            <input class="input" id="billing_postcode" name="billing_postcode" value="{{ old('billing_postcode') }}" required>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="reg-block">
                    <legend>Your login</legend>
                    <p class="hint">
                        This becomes the account admin. You can add colleagues afterwards — they will share
                        the same pricing, addresses and order history.
                    </p>

                    <div class="field-grid">
                        <div class="field">
                            <label class="label" for="name">Full name</label>
                            <input class="input" id="name" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="field">
                            <label class="label" for="job_title">Job title <small>optional</small></label>
                            <input class="input" id="job_title" name="job_title" value="{{ old('job_title') }}">
                        </div>
                        <div class="field">
                            <label class="label" for="email">Work email</label>
                            <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                        </div>
                        <div class="field">
                            <label class="label" for="phone">Phone</label>
                            <input class="input" id="phone" name="phone" value="{{ old('phone') }}" required>
                        </div>
                        <div class="field">
                            <label class="label" for="password">Password</label>
                            <input class="input" id="password" type="password" name="password" required autocomplete="new-password">
                            <p class="hint">At least 10 characters.</p>
                        </div>
                        <div class="field">
                            <label class="label" for="password_confirmation">Confirm password</label>
                            <input class="input" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                        </div>
                    </div>
                </fieldset>

                <label class="check" id="terms">
                    <input type="checkbox" name="terms" value="1" @checked(old('terms')) required>
                    <span>I have read the wholesale terms and confirm I am buying for resale or decoration.</span>
                </label>

                <button class="btn btn-primary btn-block btn-lg" type="submit">
                    Submit application <x-icon name="arrow"/>
                </button>
            </form>

            <p class="auth-alt">Already have a login? <a href="{{ route('login') }}">Sign in</a>.</p>
        </div>
    </div>

    <aside class="auth-aside">
        <div class="auth-aside-inner">
            <h2>How pricing works here</h2>

            <ol class="auth-steps">
                <li><b>Apply</b><span>Two minutes. We need enough to check you are a trade buyer.</span></li>
                <li><b>A person reviews it</b><span>Usually inside a business day. You can browse the catalogue meanwhile.</span></li>
                <li><b>Your rate card opens</b><span>Every price on the site becomes yours — tier plus anything negotiated.</span></li>
                <li><b>Order on terms</b><span>No card. Submit against your account, we confirm, then invoice.</span></li>
            </ol>

            <div class="auth-aside-note">
                <x-icon name="lock"/>
                <p>We never publish prices. Two accounts looking at the same bag routinely see different numbers.</p>
            </div>
        </div>
    </aside>
</div>
@endsection
