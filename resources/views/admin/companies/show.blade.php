@extends('layouts.admin')

@section('title', $company->name)
@section('heading', $company->name)
@section('subheading', $company->account_number.' · '.($company->tier?->fullName() ?? 'no rate card').' · '.$company->payment_terms)

@section('toolbar')
    <x-status-badge :status="$company->status"/>
@endsection

@section('content')

{{-- ---------------------------------------------------------- Approval --- --}}
@if ($company->status === \App\Enums\AccountStatus::PendingApproval)
    @can('approve', $company)
        <section class="panel panel-accent">
            <div class="panel-head">
                <h3>Approve this application</h3>
                <span class="small muted">Approving is what makes pricing visible to their logins.</span>
            </div>

            <dl class="fact-grid">
                <div><dt>Business type</dt><dd>{{ $company->business_type }}</dd></div>
                <div><dt>EIN</dt><dd class="mono">{{ $company->ein ?: 'Not supplied' }}</dd></div>
                <div><dt>Resale certificate</dt><dd class="mono">{{ $company->resale_certificate ?: 'Not supplied' }}</dd></div>
                <div><dt>Website</dt><dd>{{ $company->website ?: '—' }}</dd></div>
                <div><dt>Applied</dt><dd>{{ $company->created_at->format('j F Y') }}</dd></div>
                <div><dt>Billing</dt><dd>{!! implode('<br>', array_map('e', $company->billingAddressLines())) !!}</dd></div>
            </dl>

            <form method="POST" action="{{ route('admin.companies.approve', $company) }}">
                @csrf

                <div class="field-grid">
                    <div class="field">
                        <label class="label" for="price_tier_id">Rate card</label>
                        <select class="select" id="price_tier_id" name="price_tier_id" required>
                            @foreach ($tiers as $tier)
                                <option value="{{ $tier->id }}" @selected($tier->is_default)>
                                    {{ $tier->fullName() }} — {{ number_format($tier->discountPercent(), 1) }}% off standard
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label class="label" for="payment_terms">Payment terms</label>
                        <select class="select" id="payment_terms" name="payment_terms" required>
                            @foreach (['Prepay', 'Net 15', 'Net 30', 'Net 45', 'Net 60'] as $term)
                                <option value="{{ $term }}">{{ $term }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label class="label" for="credit_limit">Credit limit</label>
                        <input class="input" id="credit_limit" type="number" step="500" min="0" name="credit_limit" value="0" required>
                    </div>
                    <div class="field">
                        <label class="label" for="account_manager_id">Account manager</label>
                        <select class="select" id="account_manager_id" name="account_manager_id">
                            <option value="">Unassigned</option>
                            @foreach ($managers as $manager)
                                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit">Approve and open pricing <x-icon name="arrow"/></button>
            </form>
        </section>
    @endcan
@endif

{{-- ---------------------------------------------------------- Headline --- --}}
<div class="mini-kpi">
    <x-stat label="Spend, last 12 months" :value="\App\Support\Money::compact($headline['value'])" :delta="$headline['value_change']" sub="vs the 12 before"/>
    <x-stat label="Pieces" :value="\App\Support\Money::compactNumber($headline['pieces'])" :delta="$headline['pieces_change']" sub="vs the 12 before"/>
    <x-stat label="Orders" :value="$headline['orders']" :sub="$headline['open_orders'].' still open'"/>
    <x-stat label="Credit in use" :value="\App\Support\Money::format($company->credit_used)" :sub="'of '.\App\Support\Money::format($company->credit_limit)"/>
</div>

{{-- -------------------------------------------------------- Rate card --- --}}
@can('setRates', $company)
    <section class="panel">
        <div class="panel-head">
            <h3>Rate card</h3>
            <span class="small muted">Changing this reprices the whole catalogue for this account. Past orders are untouched.</span>
        </div>

        <form method="POST" action="{{ route('admin.companies.tier', $company) }}" class="inline-form">
            @csrf
            @method('PATCH')
            <div class="field" style="flex:1">
                <label class="label" for="tier">Tier</label>
                <select class="select" id="tier" name="price_tier_id">
                    @foreach ($tiers as $tier)
                        <option value="{{ $tier->id }}" @selected($company->price_tier_id === $tier->id)>
                            {{ $tier->fullName() }} — {{ number_format($tier->discountPercent(), 1) }}% off standard
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-outline" type="submit">Move this account</button>
        </form>

        <h4 class="sub-head">Negotiated rates on individual items</h4>

        @if ($company->priceOverrides->isEmpty())
            <p class="hint">None. This account pays straight tier pricing on everything.</p>
        @else
            <div class="table-wrap">
                <table class="table table-compact adm-table">
                    <thead><tr><th>Item</th><th>Description</th><th>Concession</th><th>Note</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($company->priceOverrides as $override)
                            <tr>
                                <td class="mono">{{ $override->product->sku }}</td>
                                <td><span class="trunc" style="max-width:200px">{{ $override->product->name }}</span></td>
                                <td><span class="badge badge-ok">{{ $override->label() }}</span></td>
                                <td class="muted">{{ $override->note ?: '—' }}</td>
                                <td class="num">
                                    <form method="POST" action="{{ route('admin.companies.overrides.destroy', [$company, $override]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-ghost btn-sm" type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.companies.overrides.store', $company) }}" class="inline-form">
            @csrf
            <div class="field" style="flex:2">
                <label class="label" for="product_id">Item</label>
                <select class="select" id="product_id" name="product_id" required>
                    @foreach (\App\Models\Product::published()->ordered()->get() as $product)
                        <option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label" for="discount_percent">Further off, %</label>
                <input class="input" id="discount_percent" type="number" step="0.5" name="discount_percent" value="5" required>
            </div>
            <div class="field" style="flex:2">
                <label class="label" for="note">Why</label>
                <input class="input" id="note" name="note" placeholder="Volume commitment, 2026 programme">
            </div>
            <button class="btn btn-primary" type="submit">Set rate</button>
        </form>
    </section>
@endcan

{{-- ----------------------------------------------------------- Charts --- --}}
<div class="panel-pair">
    <section class="panel">
        <div class="panel-head"><h3>What they spend</h3></div>
        <x-column-chart :rows="$byMonth" value-label="Spend" format="money"/>
    </section>

    <section class="panel">
        <div class="panel-head"><h3>What they buy</h3></div>
        <x-bar-list :rows="$byItem->map(fn ($r) => ['label' => $r['sku'], 'meta' => $r['name'], 'value' => $r['pieces']])" format="number"/>
    </section>
</div>

{{-- ----------------------------------------------------------- Orders --- --}}
<section class="panel">
    <div class="panel-head">
        <h3>Recent orders</h3>
        <a class="link-arrow btn-sm" href="{{ route('admin.orders.index', ['company' => $company->id]) }}">All <x-icon name="arrow"/></a>
    </div>

    <div class="table-wrap">
        <table class="table table-compact adm-table">
            <thead><tr><th>Reference</th><th>Date</th><th>Placed by</th><th class="num">Value</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td><a class="lk mono" href="{{ route('admin.orders.show', $order) }}">{{ $order->reference }}</a></td>
                        <td class="nw muted">{{ $order->placed_at?->format('j M y') }}</td>
                        <td>{{ $order->placedBy?->name ?? '—' }}</td>
                        <td class="num">{{ \App\Support\Money::format($order->merchandise_total) }}</td>
                        <td><x-status-badge :status="$order->status"/></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- ------------------------------------------------------------ Users --- --}}
<section class="panel">
    <div class="panel-head">
        <h3>Logins on this account</h3>
        <span class="small muted">All of them share this rate card and this order history.</span>
    </div>

    <div class="table-wrap">
        <table class="table table-compact adm-table">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last seen</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($company->users as $user)
                    <tr>
                        <td><b>{{ $user->name }}</b> <div class="sku-parent">{{ $user->job_title }}</div></td>
                        <td class="muted">{{ $user->email }}</td>
                        <td><span class="badge">{{ $user->companyRole()?->label() ?? '—' }}</span></td>
                        <td class="muted nw">{{ $user->last_seen_at?->diffForHumans(short: true) ?? 'Never' }}</td>
                        <td><span class="badge {{ $user->is_active ? 'badge-ok' : 'badge-bad' }}">{{ $user->is_active ? 'Active' : 'Off' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @can('inviteUsers', $company)
        <form method="POST" action="{{ route('admin.companies.users.store', $company) }}" class="inline-form">
            @csrf
            <div class="field"><label class="label" for="u_name">Name</label><input class="input" id="u_name" name="name" required></div>
            <div class="field" style="flex:1"><label class="label" for="u_email">Email</label><input class="input" id="u_email" type="email" name="email" required></div>
            <div class="field">
                <label class="label" for="u_role">Role</label>
                <select class="select" id="u_role" name="role">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}">{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label class="label" for="u_pass">Temp password</label><input class="input" id="u_pass" type="password" name="password" required></div>
            <button class="btn btn-outline" type="submit">Add login</button>
        </form>
    @endcan
</section>

{{-- ------------------------------------------------------- Admin edit --- --}}
@can('update', $company)
    <section class="panel">
        <div class="panel-head"><h3>Account details</h3></div>

        <form method="POST" action="{{ route('admin.companies.update', $company) }}">
            @csrf
            @method('PATCH')

            <div class="field-grid">
                <div class="field"><label class="label" for="name">Legal name</label><input class="input" id="name" name="name" value="{{ old('name', $company->name) }}" required></div>
                <div class="field"><label class="label" for="trading_name">Trading name</label><input class="input" id="trading_name" name="trading_name" value="{{ old('trading_name', $company->trading_name) }}"></div>
                <div class="field"><label class="label" for="website">Website</label><input class="input" id="website" name="website" value="{{ old('website', $company->website) }}"></div>
                <div class="field">
                    <label class="label" for="account_manager_id">Account manager</label>
                    <select class="select" id="account_manager_id" name="account_manager_id">
                        <option value="">Unassigned</option>
                        @foreach ($managers as $manager)
                            <option value="{{ $manager->id }}" @selected($company->account_manager_id === $manager->id)>{{ $manager->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="label" for="payment_terms">Payment terms</label>
                    <select class="select" id="payment_terms" name="payment_terms">
                        @foreach (['Prepay', 'Net 15', 'Net 30', 'Net 45', 'Net 60'] as $term)
                            <option value="{{ $term }}" @selected($company->payment_terms === $term)>{{ $term }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label class="label" for="credit_limit">Credit limit</label><input class="input" id="credit_limit" type="number" step="500" name="credit_limit" value="{{ old('credit_limit', $company->credit_limit) }}"></div>
                <div class="field"><label class="label" for="ein">EIN</label><input class="input" id="ein" name="ein" value="{{ old('ein', $company->ein) }}"></div>
                <div class="field"><label class="label" for="resale_certificate">Resale certificate</label><input class="input" id="resale_certificate" name="resale_certificate" value="{{ old('resale_certificate', $company->resale_certificate) }}"></div>
                <div class="field">
                    <label class="label" for="certificate_status">Certificate status</label>
                    <select class="select" id="certificate_status" name="certificate_status">
                        @foreach (['unverified' => 'Unverified', 'verified' => 'Verified', 'expired' => 'Expired'] as $value => $label)
                            <option value="{{ $value }}" @selected($company->certificate_status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="hint">An unverified or expired certificate stops this account ordering, whatever its status.</p>
                </div>
                <div class="field"><label class="label" for="certificate_expires_at">Certificate expires</label><input class="input" id="certificate_expires_at" type="date" name="certificate_expires_at" value="{{ old('certificate_expires_at', $company->certificate_expires_at?->format('Y-m-d')) }}"></div>
            </div>

            <div class="field">
                <label class="label" for="notes">Internal notes</label>
                <textarea class="input" id="notes" name="notes" rows="3">{{ old('notes', $company->notes) }}</textarea>
            </div>

            <button class="btn btn-primary" type="submit">Save</button>
        </form>

        <hr class="rule">

        <form method="POST" action="{{ route('admin.companies.hold', $company) }}" class="inline-form">
            @csrf
            <div class="field">
                <label class="label" for="status">Account status</label>
                <select class="select" id="status" name="status">
                    @foreach (\App\Enums\AccountStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected($company->status->value === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="flex:1"><label class="label" for="reason">Reason</label><input class="input" id="reason" name="reason" placeholder="Overdue on Net 30"></div>
            <button class="btn btn-outline" type="submit">Change status</button>
        </form>
    </section>
@endcan

@endsection
