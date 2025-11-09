@extends('layout.base')
@section('title','Cari Luaran Dosen')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <style>
      /* --- Mini enhancements --- */
      .hero-card {
        background: #ddd;
        color: #333;
        border: 0;
      }
      .hero-card .form-control {
        border: none !important;
        box-shadow: none !important;
      }
      .result-card {
        transition: transform .08s ease, box-shadow .08s ease;
        border: 1px solid rgba(0,0,0,.06);
      }
      .result-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 24px rgba(0,0,0,.08);
      }
      .avatar {
        width: 42px; height: 42px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        background: #e9f2ff;
        color: #2e5aac;
      }
      .muted {
        color: #06090bff !important;
      }
      .kw {
        background: #fff1a8; padding: .08rem .25rem; border-radius: .25rem;
      }
      .badge-soft {
        background: rgba(13,110,253,.1);
        color: #0d6efd;
      }
      .empty-emoji {
        font-size: 40px; line-height: 1;
      }
      .input-group-lg .form-control::placeholder { color: rgba(255,255,255,.85); }
      .shortcut {
        border: 1px solid rgba(255,255,255,.3);
        border-radius: 6px;
        padding: .15rem .4rem;
        font-size: .75rem;
        display: inline-flex; align-items: center; gap: .25rem;
        color: #fff;
      }
      .link-stretched {
        position: relative;
      }
      .link-stretched > a.stretched-link {
        z-index: 1;
      }
    </style>

    <div class="content">
      <div class="card hero-card rounded shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
          <div class="d-flex align-items-start justify-content-between flex-wrap">
            <div class="mb-3">
              <h2 class="mb-2" style="font-weight:800;">Detail Luaran Dosen</h2>
              <div class="muted" style="opacity:.95">
                Cari berdasarkan <strong>NIDN</strong> atau <strong>Nama Dosen</strong>.
              </div>
            </div>
            <div class="shortcut d-none d-md-inline-flex">
              <span class="muted">Tekan</span>
              <kbd style="background: rgba(51, 0, 255, 0.5);">Enter</kbd>
              <span class="muted">untuk mencari</span>
            </div>
          </div>

          @if ($errors->any())
            <div class="alert alert-warning mt-3">
              <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
          @endif
          @if (session('status'))
            <div class="alert alert-success mt-3">{{ session('status') }}</div>
          @endif

          <form action="{{ route('output.find') }}" method="POST" class="mt-3">
            @csrf
            <div class="input-group input-group-lg">
              <div class="input-group-prepend d-none d-md-flex">
                <span class="input-group-text bg-white border-0">
                  <i class="fa fa-search"></i>
                </span>
              </div>
              <input
                type="text"
                class="form-control form-control-lg"
                name="q"
                id="q"
                value="{{ $q }}"
                placeholder="Ketik NIDN atau Nama Dosen…"
                autocomplete="off">
              <div class="input-group-append">
                <button class="btn btn-dark" type="submit">
                  <i class="fa fa-search mr-1"></i> Cari
                </button>
              </div>
            </div>
            <small class="d-block mt-2" style="opacity:.9">
              Contoh: <span class="badge badge-soft">1019xxxx</span> &middot; <span class="badge badge-soft">Budi Santoso</span>
            </small>
          </form>
        </div>
      </div>

      {{-- ======= HASIL ======= --}}
      @if($q !== '')
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h5 class="mb-0">
            Hasil untuk: <em>"{{ $q }}"</em>
          </h5>
          <a href="{{ route('output.index') }}" class="btn btn-sm btn-outline-secondary">
            Reset
          </a>
        </div>

        @if($lecturers->isEmpty())
          <div class="card rounded shadow-sm">
            <div class="card-body text-center py-5">
              <div class="empty-emoji mb-2">🔍</div>
              <h5 class="mb-1">Tidak ada dosen yang cocok</h5>
              <div class="muted">Coba variasi lain dari NIDN atau nama.</div>
            </div>
          </div>
        @else
          <div class="row">
            @foreach($lecturers as $lec)
              @php
                $nama = $lec->name ?? 'Tanpa Nama';
                $nidn = $lec->nidn ?? '-';
                $dept = optional($lec->department)->name; // pastikan controller with('department')
                // Inisial avatar
                $init = trim(collect(explode(' ', (string)$nama))->map(function($p){ return mb_substr($p,0,1); })->join(''));
                $init = $init !== '' ? mb_strtoupper(mb_substr($init,0,2)) : 'DN';
                // highlight sederhana
                $hNama = e($nama);
                $hNidn = e($nidn);
                if ($q !== '') {
                  $pattern = '/(' . preg_quote($q, '/') . ')/i';
                  $hNama = preg_replace($pattern, '<span class="kw">$1</span>', $hNama);
                  $hNidn = preg_replace($pattern, '<span class="kw">$1</span>', $hNidn);
                }
              @endphp

              <div class="col-md-6 col-lg-4 mb-3">
                <div class="card result-card h-100 link-stretched">
                  <div class="card-body d-flex">
                    <div class="mr-3">
                      <div class="avatar">{{ $init }}</div>
                    </div>
                    <div class="flex-grow-1">
                      <h6 class="mb-1" style="font-weight:700;">
                        {!! $hNama !!}
                      </h6>
                      <div class="muted small mb-2">NIDN: {!! $hNidn !!}</div>
                      @if($dept)
                        <div class="mb-2">
                          <span class="badge badge-light" title="Program Studi">{{ $dept }}</span>
                        </div>
                      @endif
                      <a href="{{ route('output.show', $lec->id) }}" class="stretched-link"></a>
                      <div class="d-flex align-items-center">
                        <i class="fa fa-file-text-o mr-2 muted"></i>
                        <span class="muted small">Lihat detail luaran</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      @endif
    </div>

    @include('template.footer')
  </div>
</div>

{{-- Mini JS: fokus ke input --}}
<script>
  (function(){
    const q = document.getElementById('q');
    if (q) { setTimeout(()=> q.focus(), 200); }
  })();
</script>
@endsection
