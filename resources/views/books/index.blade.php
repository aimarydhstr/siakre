@extends('layout.base')
@section('title','Daftar Buku')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')
  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <h4 class="font-weight-bold my-3 mt-md-4">Data Buku</h4>

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
          <div class="mb-2">
            Total Buku :
            {{ method_exists($books,'total') ? $books->total() : (is_countable($books) ? count($books) : $books->count()) }}
          </div>

          <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <form class="form-inline mb-2" method="GET" action="{{ route('books.index') }}">
              <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control"
                       value="{{ request('search') }}"
                       placeholder="Cari judul/ISBN/penerbit/kota/tahun/dosen/mahasiswa">
                <div class="input-group-append">
                  <button class="btn btn-outline-secondary" type="submit"><i class="fa fa-search"></i></button>
                </div>
              </div>
            </form>

            <div class="mb-2">
              @if(in_array(Auth::user()->role, ['admin','faculty_head','department_head'], true))
                <a class="btn btn-primary btn-sm" href="{{ route('books.create') }}">
                  <i class="fa fa-plus mr-1"></i> Tambah Buku
                </a>
                <a class="btn btn-success btn-sm" href="{{ route('books.import.form') }}">
                  <i class="fa fa-upload mr-1"></i> Import Data
                </a>
              @endif
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width:64px;">#</th>
                  <th>ISBN</th>
                  <th>Judul</th>
                  <th>Prodi</th>
                  <th>Penerbit</th>
                  <th>Terbit</th>
                  <th>Kota</th>
                  <th>Penulis</th>
                  <th style="width:120px;">File</th>
                  <th style="width:100px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($books as $i => $book)
                  @php
                    $rowNo = ($books->currentPage()-1)*$books->perPage() + $i + 1;

                    // Format bulan (aman untuk null/0)
                    $m = (int)($book->publish_month ?? 0);
                    $monthName = $m >= 1 && $m <= 12
                      ? \DateTime::createFromFormat('!m', $m)->format('F')
                      : '—';
                    $yearVal = $book->publish_year ?: '—';

                    // Ambil nama dosen langsung dari kolom lecturers.name (bukan users)
                    $lectNames = $book->lecturers
                      ->map(fn($lec) => $lec->name)
                      ->filter(fn($v) => !empty($v));

                    // Ambil nama mahasiswa (fallback ke NIM)
                    $studNames = $book->students
                      ->map(fn($s) => $s->name ?: $s->nim)
                      ->filter(fn($v) => !empty($v));

                    $allAuthors = $lectNames->merge($studNames)->values();
                  @endphp

                  <tr>
                    <td>{{ $rowNo }}</td>
                    <td class="text-break">{{ $book->isbn }}</td>
                    <td class="text-break">{{ $book->title }}</td>
                    <td>{{ $book->department->name }}</td>
                    <td class="text-break">{{ $book->publisher }}</td>
                    <td>{{ $monthName }} {{ $yearVal }}</td>
                    <td class="text-break">{{ $book->city ?: '—' }}</td>
                    <td class="text-break">
                      {{ $allAuthors->isNotEmpty() ? $allAuthors->implode(', ') : '—' }}
                    </td>
                    <td class="text-center">
                      @if($book->file)
                        <a href="{{ route('books.download', $book->file) }}" class="btn btn-outline-secondary btn-sm">
                          <i class="fa fa-download"></i> Unduh
                        </a>
                      @else
                        <span class="text-muted">Tidak ada</span>
                      @endif
                    </td>
                    <td>
                      @if(in_array(Auth::user()->role, ['admin','faculty_head','department_head'], true))
                        <div class="btn-group" role="group">
                          <a href="{{ route('books.edit', $book->id) }}" class="btn btn-warning btn-sm" title="Edit">
                            <i class="fa fa-edit"></i>
                          </a>
                          <form action="{{ route('books.destroy', $book->id) }}" method="POST"
                                onsubmit="return confirm('Yakin hapus buku ini?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm" title="Hapus"><i class="fa fa-trash"></i></button>
                          </form>
                        </div>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="9" class="text-center">Belum ada data.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if(method_exists($books,'links'))
            <div class="mt-3">{{ $books->withQueryString()->links() }}</div>
          @endif
        </div>
      </div>
    </div>

    @include('template.footer')
  </div>
</div>
@endsection
