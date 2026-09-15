@php $co = config('tbm.company'); @endphp

<footer class="site-footer" id="contact">
    <div class="container">
        <div class="footer-top">
            <div class="footer-brand">
                @include('partials.logo')
                <p>Wholesale bag supply for distributors, promotional agencies, retailers and brands. Stocked in the US, made in audited mills.</p>

                <form class="footer-news" method="POST" action="{{ route('contact.store') }}">
                    @csrf
                    <input type="hidden" name="topic" value="Newsletter">
                    <input class="input" type="email" name="email" placeholder="Work email" aria-label="Work email" required>
                    <button class="btn btn-accent" type="submit">Join</button>
                </form>

                <div class="social">
                    <a href="#" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5ZM3 9.5h4v11H3v-11Zm6.5 0h3.8v1.5h.05c.53-.95 1.83-1.95 3.77-1.95 4.03 0 4.78 2.5 4.78 5.75v5.7h-4v-5.05c0-1.2-.02-2.75-1.7-2.75-1.7 0-1.96 1.3-1.96 2.66v5.14h-4v-11Z"/></svg></a>
                    <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1.1" fill="currentColor"/></svg></a>
                    <a href="mailto:{{ $co['email'] }}" aria-label="Email"><x-icon name="mail"/></a>
                </div>
            </div>

            <div>
                <h5>Catalog</h5>
                <ul>
                    @foreach (($navCategories ?? collect())->flatten()->take(7) as $category)
                        <li><a href="{{ route('shop.category', $category) }}">{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h5>Wholesale</h5>
                <ul>
                    <li><a href="{{ route('register') }}">Open an account</a></li>
                    <li><a href="{{ route('login') }}">Log in</a></li>
                    <li><a href="{{ route('register') }}#terms">Price tiers &amp; terms</a></li>
                    <li><a href="{{ route('customization') }}#samples">Sample packs</a></li>
                    <li><a href="{{ route('contact') }}#locations">Warehouses</a></li>
                    <li><a href="{{ route('contact') }}">Drop shipping</a></li>
                    <li><a href="{{ route('contact') }}">Purchase orders</a></li>
                </ul>
            </div>

            <div>
                <h5>Company</h5>
                <ul>
                    <li><a href="{{ route('story') }}">Our story</a></li>
                    <li><a href="{{ route('sustainability') }}">Sustainability</a></li>
                    <li><a href="{{ route('sustainability') }}#certifications">Certifications</a></li>
                    <li><a href="{{ route('customization') }}">Customization</a></li>
                    <li><a href="{{ route('contact') }}">Contact</a></li>
                    <li><a href="{{ route('story') }}#careers">Careers</a></li>
                </ul>
            </div>

            <div>
                <h5>Talk to a rep</h5>
                <ul>
                    <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $co['phone']) }}" style="display:flex;gap:10px;align-items:center"><x-icon name="phone"/>{{ $co['phone'] }}</a></li>
                    <li><a href="mailto:{{ $co['email'] }}" style="display:flex;gap:10px;align-items:center"><x-icon name="mail"/>{{ $co['email'] }}</a></li>
                    <li style="display:flex;gap:10px;align-items:flex-start;font-size:.875rem"><x-icon name="clock"/><span>{{ $co['hours'] }}</span></li>
                    <li style="display:flex;gap:10px;align-items:flex-start;font-size:.875rem"><x-icon name="pin"/><span>{!! nl2br(e($co['address'])) !!}</span></li>
                </ul>
            </div>
        </div>

        <div class="footer-bot">
            <span>&copy; {{ date('Y') }} {{ $co['legal_name'] }}. Wholesale only. Prices shown to approved accounts.</span>
            <ul>
                <li><a href="#">Terms</a></li>
                <li><a href="#">Privacy</a></li>
                <li><a href="#">Shipping policy</a></li>
                <li><a href="#">Returns</a></li>
                <li><a href="#">Prop 65</a></li>
            </ul>
        </div>

        <div class="footer-credit">
            <span>Powered by <a href="{{ $poweredBy['url'] }}" target="_blank" rel="noopener">{{ $poweredBy['name'] }}</a></span>
        </div>
    </div>
</footer>
