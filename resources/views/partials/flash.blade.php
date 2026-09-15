@if (session('status') || $errors->has('tbm'))
    <div class="container" style="margin-top:20px">
        @if (session('status'))
            <div class="notice notice-info">
                <x-icon name="check"/>
                <p>{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->has('tbm'))
            <div class="notice notice-warn">
                <x-icon name="shield"/>
                <p>{{ $errors->first('tbm') }}</p>
            </div>
        @endif
    </div>
@endif
