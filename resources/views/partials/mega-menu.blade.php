{{--
    Categories come from the StorefrontComposer, grouped and cached. Adding a
    category in the back office puts it here without touching a view.
--}}
<div class="mega" id="mega">
    <div class="container">
        <div class="mega-inner">
            @foreach ($navCategories ?? [] as $group => $categories)
                <div>
                    <h5>{{ $group }}</h5>
                    <ul>
                        @foreach ($categories as $category)
                            <li>
                                <a href="{{ route('shop.category', $category) }}">
                                    {{ $category->name }}<span>{{ $category->products_count }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div>
                <h5>Shop by</h5>
                <ul>
                    <li><a href="{{ route('shop', ['flag' => 'bestseller']) }}">Bestsellers</a></li>
                    <li><a href="{{ route('shop', ['flag' => 'new']) }}">New this season</a></li>
                    <li><a href="{{ route('shop', ['flag' => 'eco']) }}">Certified organic &amp; recycled</a></li>
                    <li><a href="{{ route('shop', ['flag' => 'value']) }}">Lowest landed cost</a></li>
                    <li><a href="{{ route('shop', ['warehouse' => 'LAX']) }}">Ready in Los Angeles</a></li>
                    <li><a href="{{ route('shop', ['warehouse' => 'EWR']) }}">Ready in New Jersey</a></li>
                    <li><a href="{{ route('shop') }}">Full catalog</a></li>
                </ul>
            </div>

            <div class="mega-promo">
                <div>
                    <h4>Sample packs</h4>
                    <p>Five bags, your choice of construction, shipped free to approved accounts. Touch the goods before you commit a container.</p>
                </div>
                <a class="btn btn-light btn-sm" href="{{ route('customization') }}#samples">
                    Request samples <x-icon name="arrow"/>
                </a>
            </div>
        </div>
    </div>
</div>
