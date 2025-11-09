@extends ('layout.base')
@section('title','Artikel - Bucket')
@section('nav')
  <div class="d-flex" id="wrapper">
    @include('template.sidebar')

    <div id="page-content-wrapper" class="site">
      {{ Breadcrumbs::render('article') }}
      @include('template.nav')

      <div class="content site-content">
        <div class="row mb-2">
          <div class="col-md-8">
            <h4 class="font-weight-bold mb-0">Artikel — {{ ucfirst($category) }} / {{ $bucket }} / {{ $type }}</h4>
            @if($start && $end)
              <div class="small text-muted mt-1">
                Rentang waktu: <strong>{{ \Carbon\Carbon::parse($start)->format('d-m-Y') }}</strong>
                sampai
                <strong>{{ \Carbon\Carbon::parse($end)->format('d-m-Y') }}</strong>
              </div>
            @endif
          </div>

          <div class="col-md-4 text-right">
            <a href="{{ route('home') }}" class="btn btn-outline-secondary">Kembali</a>
          </div>
        </div>

        @if (session('status'))
          <div class="my-3">
            <div class="alert alert-primary">{{ session('status') }}</div>
          </div>
        @endif

        @forelse($articles as $data_article)
          <div class="card rounded shadow mb-5">
            <div class="card-body">
              <a class="text-decoration-none" href="{{ $data_article->url }}" target="_blank" rel="noopener">
                <h4 class="text-capitalize mt-1 mb-3">{{ $data_article->title }}</h4>
              </a>

              <div class="row">
                {{-- Kolom kiri --}}
                <div class="col-sm-6 col-md-12 col-lg-6">
                  <table class="table mb-0">
                    <tbody>
                      {{-- Dosen: tampilkan dosen (sesuai permintaan) --}}
                      @php
                        $lectList = $data_article->lecturers
                          ->map(fn($l) => $l->name)
                          ->filter()
                          ->values();
                      @endphp
                      <tr>
                        <td class="pl-0 opacity-5">Dosen</td>
                        <td>{{ $lectList->isNotEmpty() ? $lectList->implode(', ') : '—' }}</td>
                      </tr>

                      <tr>
                        <td class="pl-0 opacity-5">ISSN</td>
                        <td>{{ $data_article->issn }}</td>
                      </tr>

                      <tr>
                        <td class="pl-0 opacity-5">Kategori</td>
                        <td>{{ $data_article->type_journal }}</td>
                      </tr>

                      <tr>
                        <td class="pl-0 opacity-5">Penerbit</td>
                        <td>{{ $data_article->publisher }}</td>
                      </tr>

                      <tr>
                        <td class="pl-0 opacity-5">Prodi</td>
                        <td>{{ optional($data_article->department)->name ?? '—' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                {{-- Kolom kanan --}}
                <div class="col-sm-6 col-md-12 col-lg-6">
                  <table class="table mb-0">
                    <tbody>
                      {{-- Mahasiswa (jika ada) --}}
                      @php
                        $studentList = $data_article->students
                          ->map(fn($s) => trim(($s->name ?: '') . ($s->nim ? " ({$s->nim})" : '')))
                          ->filter()
                          ->values();
                      @endphp
                      @if($studentList->isNotEmpty())
                        <tr>
                          <td width="120" class="pl-0 opacity-5">Mahasiswa</td>
                          <td>{{ $studentList->implode(', ') }}</td>
                        </tr>
                      @endif
                      <tr>
                        <td width="120" class="pl-0 opacity-5">Nomor</td>
                        <td>{{ $data_article->number ?: '—' }}</td>
                      </tr>
                      <tr>
                        <td class="pl-0 opacity-5">Volume</td>
                        <td>{{ $data_article->volume ?: '—' }}</td>
                      </tr>
                      <tr>
                        <td class="pl-0 opacity-5">Publikasi</td>
                        <td>{{ $data_article->date ? \Carbon\Carbon::parse($data_article->date)->format('d-m-Y') : '—' }}</td>
                      </tr>
                      <tr>
                        <td class="pl-0 opacity-5">DOI</td>
                        <td>{{ $data_article->doi ?: '—' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="row">
                <div class="col-sm-6">
                  <a class="btn btn-outline-primary d-block d-sm-inline-block mt-3"
                     href="{{ route('view', ['id' => $data_article->id]) }}">
                    <i class="fas fa-eye"></i> Lihat
                  </a>
                  @if($data_article->file)
                  <a class="btn btn-outline-success d-block d-sm-inline-block mt-3"
                     href="{{ route('download', ['file' => $data_article->file]) }}">
                    <i class="fas fa-download"></i> Unduh
                  </a>
                  @endif
                </div>
                <div class="col-sm-6 text-sm-right">
                  <a class="btn btn-primary d-block d-sm-inline-block mt-3"
                     href="{{ route('edit_article', ['id' => $data_article->id]) }}">
                    <i class="fas fa-pen"></i> Sunting
                  </a>
                  <form class="d-sm-inline-block"
                        action="{{ route('delete_article', ['id' => $data_article->id]) }}"
                        method="post">
                    @method('delete')
                    @csrf
                    <button onclick="return confirm('yakin data ingin di hapus?');"
                            type="submit"
                            class="btn btn-danger d-block d-sm-inline-block mt-3 w-100 w-sm-auto">
                      <i class="fas fa-trash"></i> Hapus
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="card rounded shadow mb-5">
            <div class="card-body text-center text-muted">
              Belum ada data untuk rentang tersebut.
            </div>
          </div>
        @endforelse

        {{-- Tidak ada search; pagination jika perlu (hapus appends search) --}}
        @if(method_exists($articles, 'links'))
          {{ $articles->links('pagination::bootstrap-4') }}
        @endif
      </div>

      @include('template.footer')
    </div>
  </div>
@endsection
