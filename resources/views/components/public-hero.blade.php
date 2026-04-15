@props([
    'eyebrow' => null,
    'title' => '',
    'subtitle' => null,
    'showMeta' => true,
])

<section class="page-header">
    <div class="page-header__copy">
        @if($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif

        <h2 class="page-title">{{ $title }}</h2>

        @if($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($aside)
        <div class="page-header__aside">
            {{ $aside }}
        </div>
    @elseif($showMeta && auth()->check())
        <div class="meta-grid">
            <div class="meta-pill">
                <span>Nama</span>
                <strong>{{ auth()->user()->name }}</strong>
            </div>
            <div class="meta-pill">
                <span>Divisi</span>
                <strong>{{ auth()->user()->division->nama_divisi ?? '-' }}</strong>
            </div>
            <div class="meta-pill">
                <span>Role</span>
                <strong>{{ auth()->user()->role }}</strong>
            </div>
        </div>
    @endif
</section>
