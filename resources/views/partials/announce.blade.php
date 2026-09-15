{{--
    The bar above the header. For a signed-out visitor it says, once and
    plainly, that pricing needs an account — which is the single most common
    question a wholesale storefront gets.
--}}
<div class="announce">
    <div class="container">
        <div class="announce-items">
            <span><x-icon name="truck"/>Ships from Los Angeles, Edison NJ &amp; Dallas</span>
            <span><x-icon name="leaf"/>GOTS &amp; GRS certified programs</span>
            <span><x-icon name="box"/>Stock updated daily from the mill</span>
        </div>
        <div class="row">
            @auth
                <span>Signed in as <a href="{{ route('account.dashboard') }}">{{ auth()->user()->company?->name ?? 'TBM' }}</a></span>
            @else
                <span>Wholesale only &mdash; <a href="{{ route('register') }}">open an account</a> to see pricing</span>
            @endauth
        </div>
    </div>
</div>
