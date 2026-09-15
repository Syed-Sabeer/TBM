@extends('layouts.storefront')

@section('title', 'Checkout')

@section('breadcrumb')
    <a href="{{ route('cart.index') }}">Basket</a>
    <x-icon name="chev"/>
    <span>Checkout</span>
@endsection

@section('content')
<div class="container checkout-shell">

    <header class="page-head">
        <h1>Submit your order</h1>
        <p>No payment is taken here. This goes to your rep, who confirms stock, freight and any decoration, then invoices on your terms.</p>
    </header>

    <form method="POST" action="{{ route('checkout.store') }}" class="checkout-grid">
        @csrf

        <div class="checkout-main">

            {{-- ----------------------------------------- Ship to --- --}}
            <section class="panel">
                <div class="panel-head">
                    <h3>Ship to</h3>
                    <a class="link-arrow btn-sm" href="{{ route('account.addresses.index') }}">Manage addresses</a>
                </div>

                <div class="radio-grid">
                    @forelse ($addresses as $address)
                        <label class="radio-card">
                            <input type="radio" name="address_id" value="{{ $address->id }}" @checked($loop->first || $address->is_default)>
                            <span class="rc-body">
                                <b>{{ $address->label }}</b>
                                <span>{{ $address->singleLine() }}</span>
                                @if ($address->is_residential)
                                    <em class="badge badge-warn">Residential — surcharge applies</em>
                                @endif
                            </span>
                        </label>
                    @empty
                        <p class="hint">No addresses on file. <a href="{{ route('account.addresses.create') }}">Add one</a> to continue.</p>
                    @endforelse
                </div>
                @error('address_id') <p class="err">{{ $message }}</p> @enderror
            </section>

            {{-- -------------------------------------- Ship from --- --}}
            <section class="panel">
                <div class="panel-head"><h3>Ship from</h3></div>

                <div class="radio-grid">
                    @foreach ($warehouses as $warehouse)
                        <label class="radio-card">
                            <input type="radio" name="warehouse_id" value="{{ $warehouse->id }}" @checked($loop->first)>
                            <span class="rc-body">
                                <b>{{ $warehouse->name }} <span class="mono">{{ $warehouse->code }}</span></b>
                                <span>{{ $warehouse->locationLine() }} &middot; {{ $warehouse->lead_time }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="hint">Splitting a line across warehouses is fine — say so in the notes and we will arrange it.</p>
                @error('warehouse_id') <p class="err">{{ $message }}</p> @enderror
            </section>

            {{-- ---------------------------------------- Service --- --}}
            <section class="panel">
                <div class="panel-head"><h3>Carrier and dates</h3></div>

                <div class="field-grid">
                    <div class="field">
                        <label class="label" for="shipping_service">Shipping service</label>
                        <select class="select" id="shipping_service" name="shipping_service">
                            @foreach ($shippingServices as $service)
                                <option value="{{ $service }}" @selected(old('shipping_service') === $service)>{{ $service }}</option>
                            @endforeach
                        </select>
                        @error('shipping_service') <p class="err">{{ $message }}</p> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="in_hands_on">In-hands date <small>optional</small></label>
                        <input class="input" id="in_hands_on" type="date" name="in_hands_on" value="{{ old('in_hands_on') }}">
                        @error('in_hands_on') <p class="err">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- ------------------------------------ References --- --}}
            <section class="panel">
                <div class="panel-head"><h3>Your references</h3></div>

                <div class="field-grid">
                    <div class="field">
                        <label class="label" for="customer_po">
                            Purchase order number
                            @if ($totals->requiresPurchaseOrder()) <em class="req">required</em> @else <small>optional</small> @endif
                        </label>
                        <input class="input" id="customer_po" name="customer_po" value="{{ old('customer_po') }}"
                               placeholder="PO-4821">
                        @error('customer_po') <p class="err">{{ $message }}</p> @enderror
                        @if ($totals->requiresPurchaseOrder())
                            <p class="hint">Orders over {{ \App\Support\Money::format(config('tbm.storefront.require_po_over')) }} need a PO so your finance team can match the invoice.</p>
                        @endif
                    </div>

                    <div class="field">
                        <label class="label" for="job_reference">Job or end-client reference <small>optional</small></label>
                        <input class="input" id="job_reference" name="job_reference" value="{{ old('job_reference') }}"
                               placeholder="Summer festival — Northside">
                    </div>
                </div>

                <div class="field">
                    <label class="label" for="customer_notes">Notes for the warehouse <small>optional</small></label>
                    <textarea class="input" id="customer_notes" name="customer_notes" rows="3"
                              placeholder="Delivery window, dock details, split shipment instructions…">{{ old('customer_notes') }}</textarea>
                </div>
            </section>
        </div>

        {{-- --------------------------------------------- Summary --- --}}
        <aside class="checkout-summary">
            <h3>{{ $lines->count() }} {{ Str::plural('line', $lines->count()) }}</h3>

            <ul class="co-lines">
                @foreach ($lines as $line)
                    <li>
                        <span class="mono">{{ $line->product->sku }}</span>
                        <span class="co-desc">{{ $line->colourName() }} · {{ $line->size }}</span>
                        <span class="co-qty">{{ number_format($line->quantity) }}</span>
                        <span class="co-val">{{ \App\Support\Money::format($line->lineTotal()) }}</span>
                    </li>
                @endforeach
            </ul>

            <dl class="sum-list">
                <div><dt>Merchandise</dt><dd>{{ \App\Support\Money::format($totals->merchandise) }}</dd></div>
                <div><dt>Freight <small>estimate</small></dt><dd>{{ $totals->freight > 0 ? \App\Support\Money::format($totals->freight) : 'Free' }}</dd></div>
                <div><dt>Decoration</dt><dd class="muted">Quoted after artwork</dd></div>
                <div class="sum-total"><dt>Estimated total</dt><dd>{{ \App\Support\Money::format($totals->estimatedTotal()) }}</dd></div>
            </dl>

            @if ($issues)
                <div class="notice notice-warn">
                    <x-icon name="shield"/>
                    <div>
                        @foreach ($issues as $issue)
                            <p>{{ $issue['message'] }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <button class="btn btn-primary btn-block btn-lg" type="submit" @disabled($addresses->isEmpty())>
                Submit this order <x-icon name="arrow"/>
            </button>

            <p class="sum-note">
                Terms: {{ auth()->user()->company->payment_terms ?? 'Prepay' }}.
                Available credit {{ \App\Support\Money::format(auth()->user()->company->creditAvailable()) }}.
                You will get an acknowledgement with confirmed freight before anything ships.
            </p>
        </aside>
    </form>
</div>
@endsection
