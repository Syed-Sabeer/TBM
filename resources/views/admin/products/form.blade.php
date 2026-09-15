@extends('layouts.admin')

@section('title', $product->exists ? $product->sku : 'New item')
@section('heading', $product->exists ? $product->sku.' — '.$product->name : 'Add an item')

@section('content')

<section class="panel">
    <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
        @csrf
        @if ($product->exists) @method('PATCH') @endif

        {{--
            The two identifiers, side by side and explained. This is the one
            screen where getting them the wrong way round would matter.
        --}}
        <fieldset class="reg-block">
            <legend>Identifiers</legend>

            <div class="field-grid">
                <div class="field">
                    <label class="label" for="parent_sku">Mill reference</label>
                    <input class="input mono" id="parent_sku" name="parent_sku" value="{{ old('parent_sku', $product->parent_sku) }}" required>
                    <p class="hint">What the mill calls it, and what the morning sheet is matched on. Appears on purchase orders and pick lists — never on anything a customer sees.</p>
                    @error('parent_sku') <p class="err">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label class="label" for="sku">Item number</label>
                    <input class="input mono" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" required>
                    <p class="hint">What the customer orders by. Printed on the site, the packing slip and the invoice. Changing it changes what customers see; past documents keep the old one.</p>
                    @error('sku') <p class="err">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        <fieldset class="reg-block">
            <legend>The goods</legend>

            <div class="field-grid">
                <div class="field">
                    <label class="label" for="name">Name</label>
                    <input class="input" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                </div>
                <div class="field">
                    <label class="label" for="category_id">Category</label>
                    <select class="select" id="category_id" name="category_id" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($product->category_id === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="label" for="shape">Illustration shape</label>
                    <select class="select" id="shape" name="shape">
                        @foreach ($shapes as $shape)
                            <option value="{{ $shape }}" @selected(old('shape', $product->shape) === $shape)>{{ Str::headline($shape) }}</option>
                        @endforeach
                    </select>
                    <p class="hint">Drives the product illustration until real photography replaces it.</p>
                </div>
                <div class="field">
                    <label class="label" for="material">Material</label>
                    <input class="input" id="material" name="material" value="{{ old('material', $product->material) }}" required>
                </div>
                <div class="field">
                    <label class="label" for="fabric_weight">Fabric weight</label>
                    <input class="input" id="fabric_weight" name="fabric_weight" value="{{ old('fabric_weight', $product->fabric_weight) }}">
                </div>
                <div class="field">
                    <label class="label" for="origin">Origin</label>
                    <input class="input" id="origin" name="origin" value="{{ old('origin', $product->origin) }}">
                </div>
            </div>

            <div class="field">
                <label class="label" for="description">Description</label>
                <textarea class="input" id="description" name="description" rows="4">{{ old('description', $product->description) }}</textarea>
            </div>

            <div class="field">
                <label class="label" for="sizes">Sizes <small>one per line</small></label>
                <textarea class="input" id="sizes" name="sizes" rows="3" required>{{ old('sizes', implode("\n", $product->sizeLabels())) }}</textarea>
            </div>

            <div class="field">
                <span class="label">Colourways</span>
                <div class="swatch-filter">
                    @foreach ($colourways as $colourway)
                        <label class="swatch-pick {{ $product->colourways->contains($colourway) ? 'is-active' : '' }}" title="{{ $colourway->name }}">
                            <input type="checkbox" name="colourways[]" value="{{ $colourway->id }}" @checked($product->colourways->contains($colourway))>
                            <span class="swatch" style="background:{{ $colourway->hex_body }}"></span>
                        </label>
                    @endforeach
                </div>
            </div>
        </fieldset>

        <fieldset class="reg-block">
            <legend>Commercials</legend>

            <div class="field-grid">
                <div class="field">
                    <label class="label" for="base_price">Base price</label>
                    <input class="input mono" id="base_price" type="number" step="0.0001" name="base_price" value="{{ old('base_price', $product->base_price) }}" required>
                    <p class="hint">Tier C at the smallest break. Every other price in the system derives from this.</p>
                </div>
                <div class="field">
                    <label class="label" for="cost_price">FOB cost</label>
                    <input class="input mono" id="cost_price" type="number" step="0.0001" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}">
                    <p class="hint">Usually written by the cost import. Margin reporting only — never shown to a customer.</p>
                </div>
                <div class="field">
                    <label class="label" for="moq">Minimum order</label>
                    <input class="input" id="moq" type="number" name="moq" value="{{ old('moq', $product->moq) }}" required>
                </div>
                <div class="field">
                    <label class="label" for="order_step">Order step</label>
                    <input class="input" id="order_step" type="number" name="order_step" value="{{ old('order_step', $product->order_step) }}" required>
                </div>
                <div class="field">
                    <label class="label" for="carton_quantity">Carton quantity</label>
                    <input class="input" id="carton_quantity" type="number" name="carton_quantity" value="{{ old('carton_quantity', $product->carton_quantity) }}" required>
                </div>
            </div>

            <div class="field">
                <span class="label">Flags</span>
                @foreach (['bestseller' => 'Bestseller', 'new' => 'New this season', 'eco' => 'Organic or recycled', 'value' => 'Lowest landed cost'] as $flag => $label)
                    <label class="check">
                        <input type="checkbox" name="flags[]" value="{{ $flag }}" @checked($product->hasFlag($flag))>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <label class="check">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $product->is_published))>
                <span>Published — visible on the storefront</span>
            </label>
        </fieldset>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">{{ $product->exists ? 'Save' : 'Add item' }}</button>
            <a class="btn btn-ghost" href="{{ route('admin.products.index') }}">Back to the catalogue</a>
        </div>
    </form>
</section>

@if ($product->exists && isset($matrix))
    <section class="panel">
        <div class="panel-head">
            <h3>What each tier pays</h3>
            <a class="link-arrow btn-sm" href="{{ route('admin.pricing.matrix', $product) }}">Full matrix with margin</a>
        </div>

        <div class="table-wrap">
            <table class="table table-compact adm-table">
                <thead>
                    <tr><th>Tier</th>@foreach ($breaks as $break)<th class="num">{{ number_format($break) }}+</th>@endforeach</tr>
                </thead>
                <tbody>
                    @foreach ($tiers as $tier)
                        <tr>
                            <td>{{ $tier->fullName() }}</td>
                            @foreach ($matrix[$tier->code] as $break)
                                <td class="num">{{ \App\Support\Money::unit($break->unitPrice) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@endsection
