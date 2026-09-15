@extends('layouts.account')

@section('title', 'Addresses')
@section('heading', 'Addresses')
@section('subheading', 'Ship-to addresses on the account. Everyone here can use them.')

@section('actions')
    @can('create', App\Models\Address::class)
        <a class="btn btn-primary btn-sm" href="{{ route('account.addresses.create') }}">Add an address</a>
    @endcan
@endsection

@section('panel')

<div class="addr-grid">
    @forelse ($addresses as $address)
        <article class="panel addr-card">
            <div class="panel-head">
                <h3>{{ $address->label }}</h3>
                @if ($address->is_default)
                    <span class="badge badge-ok">Default</span>
                @endif
            </div>

            <p class="addr-lines">{!! implode('<br>', array_map('e', $address->lines())) !!}</p>

            @if ($address->is_residential)
                <span class="badge badge-warn">Residential — carrier surcharge</span>
            @endif

            @if ($address->notes)
                <p class="hint">{{ $address->notes }}</p>
            @endif

            <div class="addr-actions">
                @can('update', $address)
                    <a class="btn btn-outline btn-sm" href="{{ route('account.addresses.edit', $address) }}">Edit</a>
                @endcan
                @can('delete', $address)
                    <form method="POST" action="{{ route('account.addresses.destroy', $address) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-ghost btn-sm" type="submit">Remove</button>
                    </form>
                @endcan
            </div>
        </article>
    @empty
        <div class="empty empty-lg">
            <x-icon name="pin"/>
            <h3>No addresses yet</h3>
            <p>Add one and it will be available at checkout for everyone on the account.</p>
            @can('create', App\Models\Address::class)
                <a class="btn btn-primary btn-sm" href="{{ route('account.addresses.create') }}">Add an address</a>
            @endcan
        </div>
    @endforelse
</div>

<p class="hint">
    Editing an address here never changes a document that has already been issued — every order keeps
    its own copy of where it shipped.
</p>

@endsection
