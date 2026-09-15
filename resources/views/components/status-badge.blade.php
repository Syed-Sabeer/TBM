@props(['status'])

<span class="badge st {{ $status->badgeClass() }}">{{ $status->label() }}</span>
