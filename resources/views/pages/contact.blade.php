@extends('layouts.storefront')

@section('title', 'Contact')

@section('breadcrumb')
    <a href="{{ route('home') }}">Home</a><x-icon name="chev"/><span>Contact</span>
@endsection

@section('content')
<div class="container page-shell">

    <header class="page-hero">
        <span class="eyebrow"><x-icon name="phone"/>Talk to a person</span>
        <h1>Someone who knows the stock will answer.</h1>
        <p class="lede">{{ config('tbm.company.hours') }}. Accounts have a named manager; everyone else gets whoever is free, which is usually quicker.</p>
    </header>

    <section class="section">
        <div class="contact-grid">

            <form method="POST" action="{{ route('contact.store') }}" class="panel contact-form">
                @csrf

                <div class="panel-head"><h3>Send us a note</h3></div>

                <div class="field-grid">
                    <div class="field">
                        <label class="label" for="name">Your name</label>
                        <input class="input" id="name" name="name" value="{{ old('name') }}">
                    </div>
                    <div class="field">
                        <label class="label" for="company">Company</label>
                        <input class="input" id="company" name="company" value="{{ old('company') }}">
                    </div>
                    <div class="field">
                        <label class="label" for="email">Work email</label>
                        <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required>
                        @error('email') <p class="err">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label class="label" for="phone">Phone <small>optional</small></label>
                        <input class="input" id="phone" name="phone" value="{{ old('phone') }}">
                    </div>
                </div>

                <div class="field">
                    <label class="label" for="topic">What is this about</label>
                    <select class="select" id="topic" name="topic">
                        @foreach (['A new wholesale account', 'A sample pack', 'Pricing on a specific item', 'An existing order', 'Decoration and artwork', 'Something else'] as $topic)
                            <option value="{{ $topic }}" @selected(old('topic') === $topic)>{{ $topic }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label class="label" for="message">Message</label>
                    <textarea class="input" id="message" name="message" rows="5">{{ old('message') }}</textarea>
                </div>

                <button class="btn btn-primary btn-lg" type="submit">Send <x-icon name="arrow"/></button>
            </form>

            <aside class="contact-aside">
                <div class="panel">
                    <div class="panel-head"><h3>Direct lines</h3></div>
                    <ul class="contact-list">
                        <li><x-icon name="phone"/><div><b>Sales</b><a href="tel:{{ preg_replace('/[^0-9+]/', '', config('tbm.company.phone')) }}">{{ config('tbm.company.phone') }}</a></div></li>
                        <li><x-icon name="mail"/><div><b>New accounts</b><a href="mailto:{{ config('tbm.company.email') }}">{{ config('tbm.company.email') }}</a></div></li>
                        <li><x-icon name="palette"/><div><b>Artwork</b><a href="mailto:{{ config('tbm.company.art_email') }}">{{ config('tbm.company.art_email') }}</a></div></li>
                        <li><x-icon name="doc"/><div><b>Accounts payable</b><a href="mailto:{{ config('tbm.company.accounts_email') }}">{{ config('tbm.company.accounts_email') }}</a></div></li>
                        <li><x-icon name="shield"/><div><b>Certificates &amp; compliance</b><a href="mailto:{{ config('tbm.company.compliance_email') }}">{{ config('tbm.company.compliance_email') }}</a></div></li>
                    </ul>
                </div>
            </aside>
        </div>
    </section>

    <section class="section section-tint" id="locations">
        <h2>Where the stock is</h2>
        <div class="wh-grid">
            @foreach ($warehouses as $warehouse)
                <article class="wh-card">
                    <span class="mono wh-code">{{ $warehouse->code }}</span>
                    <h3>{{ $warehouse->name }}</h3>
                    <p>{{ $warehouse->street }}<br>{{ $warehouse->locationLine() }} {{ $warehouse->postcode }}</p>
                    <ul class="wh-facts">
                        <li><x-icon name="truck"/>{{ $warehouse->lead_time }}</li>
                        @if ($warehouse->floor_space)
                            <li><x-icon name="box"/>{{ $warehouse->floor_space }}</li>
                        @endif
                        @if ($warehouse->has_decoration)
                            <li><x-icon name="printer"/>Decoration on site</li>
                        @endif
                        <li><x-icon name="grid"/>{{ \App\Support\Money::compactNumber($warehouse->unitsOnHand()) }} pieces on hand</li>
                    </ul>
                </article>
            @endforeach
        </div>
        <p class="panel-note">Collections are welcome with notice. Give the dock a heads-up and bring the order reference.</p>
    </section>
</div>
@endsection
