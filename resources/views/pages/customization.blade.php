@extends('layouts.storefront')

@section('title', 'Customization and decoration')
@section('meta', 'Screen print, DTF transfer, embroidery and sublimation on stocked blank bags.')

@section('breadcrumb')
    <a href="{{ route('home') }}">Home</a><x-icon name="chev"/><span>Customization</span>
@endsection

@section('content')
<div class="container page-shell">

    <header class="page-hero">
        <span class="eyebrow"><x-icon name="printer"/>Decoration</span>
        <h1>Your artwork, on stock we already hold.</h1>
        <p class="lede">
            Everything in the catalogue is a blank. Decoration is quoted per colour and per location once
            we have your artwork, then added to the order before it is confirmed — so you see the whole
            number before anything goes on press.
        </p>
    </header>

    <section class="section">
        <h2>Methods</h2>
        <div class="method-grid">
            @foreach ($methods as $method)
                <article class="method-card">
                    <h4>{{ $method['name'] }}</h4>
                    <p>{{ $method['note'] }}</p>
                    @isset($method['requires_material'])
                        <span class="badge badge-warn">{{ $method['requires_material'] }} fabrics only</span>
                    @endisset
                </article>
            @endforeach
        </div>
    </section>

    <section class="section section-tint">
        <h2>How a decorated order runs</h2>
        <ol class="step-list">
            <li><b>Send artwork</b><span>Vector where you have it — .ai, .eps or a layered .pdf. Raster works for DTF at 300dpi or better. {{ config('tbm.company.art_email') }}.</span></li>
            <li><b>We quote</b><span>Per colour, per location, against your quantity. Set-up is quoted separately where the method needs it.</span></li>
            <li><b>Digital proof</b><span>You approve placement and size before anything is made. Nothing runs on an unapproved proof.</span></li>
            <li><b>Production</b><span>Lead time is quoted with the proof and depends on the method and the run.</span></li>
        </ol>
    </section>

    <section class="section" id="samples">
        <div class="split">
            <div>
                <h2>Sample packs</h2>
                <p>
                    Five bags of your choosing, shipped free to approved accounts. Fabric weight and handle
                    construction are hard to judge from a screen, and a container is a lot to commit on a guess.
                </p>
                <a class="btn btn-primary" href="{{ route('contact') }}">Request a sample pack <x-icon name="arrow"/></a>
            </div>
            <div class="split-art">
                {!! app(\App\Services\Rendering\BagRenderer::class)->render('tote') !!}
            </div>
        </div>
    </section>

    <section class="section">
        <h2>Artwork guidance</h2>
        <div class="feature-grid">
            <article class="feature-card">
                <x-icon name="palette"/>
                <h3>Colour matching</h3>
                <p>Give us Pantone references where the brand matters. Dyed fabric shifts a printed ink, so we proof on the actual body colour rather than on white.</p>
            </article>
            <article class="feature-card">
                <x-icon name="thread"/>
                <h3>Stitch counts</h3>
                <p>Embroidery is priced on stitches, not on size. A dense fill costs more than an outline of the same dimensions — worth knowing before you finalise the logo.</p>
            </article>
            <article class="feature-card">
                <x-icon name="shield"/>
                <h3>Print areas</h3>
                <p>Each shape has a usable area that clears seams, gussets and handles. The dashed rectangle on every product illustration is roughly it; the proof is exact.</p>
            </article>
        </div>
    </section>
</div>
@endsection
