@extends('layouts.admin')

@section('title', 'Run an import')
@section('heading', 'Run an import')
@section('subheading', 'Upload the file. You will map the columns and see exactly what changes before anything is written.')

@section('content')

<section class="panel">
    <form method="POST" action="{{ route('admin.imports.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="field">
            <span class="label">What kind of file</span>
            <div class="radio-grid">
                @foreach ($types as $type)
                    <label class="radio-card">
                        <input type="radio" name="type" value="{{ $type->value }}" @checked($loop->first)>
                        <span class="rc-body">
                            <b>{{ $type->label() }}</b>
                            <span>{{ $type->description() }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('type') <p class="err">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label class="label" for="file">The file</label>
            <div class="drop">
                <x-icon name="doc"/>
                <input type="file" id="file" name="file" accept=".csv,.tsv,.txt" required>
                <p>CSV or tab-separated, up to 10MB. Save an .xlsx as CSV first.</p>
            </div>
            @error('file') <p class="err">{{ $message }}</p> @enderror
        </div>

        <button class="btn btn-primary btn-lg" type="submit">Upload and map columns <x-icon name="arrow"/></button>
    </form>
</section>

<section class="panel">
    <div class="panel-head"><h3>What happens next</h3></div>
    <ol class="step-list">
        <li><b>Map</b><span>Tell us which of your columns is the mill reference, the warehouse and the quantity. We guess, you confirm.</span></li>
        <li><b>Preview</b><span>Every line is matched against the catalogue. You see what will change, and what matched nothing.</span></li>
        <li><b>Apply</b><span>Only this step writes stock. Each figure keeps a record of what it was, so the run can be rolled back.</span></li>
    </ol>
</section>

@endsection
