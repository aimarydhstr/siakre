@extends('layout.base')
@section('title','Daftar HKI')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')
  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <h4 class="font-weight-bold my-3 mt-md-4">Data HKI</h4>

      @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif

      <div class="card rounded shadow mt-3 mb-5">
        <div class="card-body">
          <div class="mb-2">Total HKI : {{ method_exists($hkis,'total') ? $hkis->total() : $hkis->count() }}</div>
          <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <form class="form-inline mb-2" method="GET" action="{{ route('hki.index') }}">
              <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari nama/nomor/pemegang/dosen/mahasiswa">
                <div class="input-group-append">
                  <button class="btn btn-outline-secondary" type="submit"><i class="fa fa-search"></i></button>
                </div>
              </div>
            </form>


            <div class="mb-2">
              <a class="btn btn-primary btn-sm" href="{{ route('hki.create') }}">
                <i class="fa fa-plus mr-1"></i> Tambah HKI
              </a>
              
              <a class="btn btn-success btn-sm" href="{{ route('hki.import.form') }}">
                <i class="fa fa-upload mr-1"></i> Import Data
              </a>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width:64px;">#</th>
                  <th>Nama HKI</th>
                  <th>Prodi</th>
                  <th>Nomor</th>
                  <th>Pemegang</th>
                  <th>Kontributor</th>
                  <th>Tanggal</th>
                  <th style="width:120px;">File</th>
                  <th style="width:180px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($hkis as $i => $hki)
                  <tr>
                    <td>{{ ($hkis->currentPage()-1)*$hkis->perPage() + $i + 1 }}</td>
                    <td class="text-break">{{ $hki->name }}</td>
                    <td>{{ $hki->department->name }}</td>
                    <td class="text-break">{{ $hki->number }}</td>
                    <td class="text-break">{{ $hki->holder }}</td>
                    <td class="text-break">
                      @php
                        $lectNames = $hki->lecturers->map(fn($lec) => optional($lec)->name)->filter();
                        $studNames = $hki->students->map(fn($s) => $s->name ?: $s->nim)->filter();
                        $all       = $lectNames->merge($studNames)->values();
                      @endphp
                      {{ $all->isNotEmpty() ? $all->implode(', ') : '—' }}
                    </td>
                    <td class="text-break">{{ $hki->date }}</td>
                    <td class="text-center">
                      @if($hki->file)
                        <a href="{{ route('hki.download', $hki->file) }}" class="btn btn-outline-secondary btn-sm">
                          <i class="fa fa-download"></i> Unduh
                        </a>
                      @else
                        <span class="text-muted">Tidak ada</span>
                      @endif
                    </td>
                    <td>
                      <div class="btn-group" role="group">
                        <a href="{{ route('hki.edit', $hki->id) }}" class="btn btn-warning btn-sm" title="Edit"><i class="fa fa-edit"></i></a>
                        <form action="{{ route('hki.destroy', $hki->id) }}" method="POST" onsubmit="return confirm('Yakin hapus HKI ini?');">
                          @csrf @method('DELETE')
                          <button class="btn btn-danger btn-sm" title="Hapus"><i class="fa fa-trash"></i></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="7" class="text-center">Belum ada data.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if(method_exists($hkis,'links'))
            <div class="mt-3">{{ $hkis->withQueryString()->links() }}</div>
          @endif
        </div>
      </div>
    </div>

    @include('template.footer')
  </div>
</div>
@endsection
