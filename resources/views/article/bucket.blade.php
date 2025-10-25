@extends('layout.base')
@section('title', 'Daftar Artikel')
@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    {{ Breadcrumbs::render('home') }}
    @include('template.nav')

    <div class="content site-content">
      <div class="d-md-flex align-items-center justify-content-between mt-3 mb-2">
        <h4 class="font-weight-bold mb-0">
          Artikel: {{ ucfirst($category) }} • {{ $bucket }} • {{ $type }}
        </h4>
        @if($start && $end)
          <span class="text-muted small">Range: {{ $start }} s.d. {{ $end }}</span>
        @endif
      </div>

      <div class="card rounded shadow mt-2">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm table-striped table-bordered align-middle">
              <thead class="thead-dark">
                <tr>
                  <th style="width:60px" class="text-center">#</th>
                  <th>Judul</th>
                  <th style="width:140px">Jenis</th>
                  <th>URL</th>
                  <th>DOI</th>
                  <th style="width:220px">Dosen</th>
                  <th style="width:220px">Mahasiswa</th>
                  <th style="width:160px">NIM</th>
                  <th style="width:160px">Penerbit</th>
                  <th style="width:90px" class="text-center">Volume</th>
                  <th style="width:90px" class="text-center">Nomor</th>
                  <th style="width:110px" class="text-center">Tanggal</th>
                </tr>
              </thead>
              <tbody>
                @forelse($articles as $a)
                  @php
                    $lecturers = collect($a->lecturers)->map(fn($l) => optional($l->user)->name)->filter()->implode('; ');
                    $students  = collect($a->students)->pluck('name')->filter()->implode('; ');
                    $nims      = collect($a->students)->pluck('nim')->filter()->implode('; ');
                  @endphp
                  <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $a->title }}</td>
                    <td>{{ $a->type_journal }}</td>
                    <td>
                      @if($a->url)
                        <a href="{{ $a->url }}" target="_blank" rel="noopener noreferrer">{{ $a->url }}</a>
                      @else — @endif
                    </td>
                    <td>{{ $a->doi ?: '—' }}</td>
                    <td>{{ $lecturers ?: '—' }}</td>
                    <td>{{ $students  ?: '—' }}</td>
                    <td>{{ $nims      ?: '—' }}</td>
                    <td>{{ $a->publisher ?: '—' }}</td>
                    <td class="text-center">{{ $a->volume ?: '—' }}</td>
                    <td class="text-center">{{ $a->number ?: '—' }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($a->date)->format('Y-m-d') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="12" class="text-center text-muted">Tidak ada data.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <a href="{{ url()->previous() }}" class="btn btn-light">← Kembali</a>
        </div>
      </div>
    </div>

    @include('template.footer')
  </div>
</div>
@endsection
