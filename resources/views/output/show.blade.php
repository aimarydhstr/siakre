@extends('layout.base')
@section('title','Luaran Dosen')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')
  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <div class="d-flex align-items-center justify-content-between">
        <h4 class="font-weight-bold my-3 mt-md-4">Detail Luaran Dosen</h4>
        <a href="{{ route('output.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="fa fa-arrow-left mr-1"></i> Kembali
        </a>
      </div>

      {{-- Profil singkat dosen --}}
      <div class="card rounded shadow mb-4">
        <div class="card-body d-md-flex align-items-center justify-content-between">
          <div>
            <div class="h5 mb-1">{{ $lecturer->name ?? 'Tanpa Nama' }}</div>
            <div class="text-muted">NIDN: {{ $lecturer->nidn ?? '-' }}</div>
            @if(optional($lecturer->department)->name)
              <div class="mt-1">
                <span class="badge badge-light" title="Program Studi">
                  {{ $lecturer->department->name }}
                </span>
              </div>
            @endif
          </div>
        </div>
      </div>

      {{-- ===================== ARTIKEL ===================== --}}
      <div class="card rounded shadow mb-4">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap">
            <h5 class="mb-3 mb-md-0">Artikel</h5>
            <form class="form-inline" method="GET" action="{{ route('output.show', $lecturer->id) }}">
              {{-- pertahankan filter seksi lain --}}
              <input type="hidden" name="book_yfrom" value="{{ $bookYFrom }}">
              <input type="hidden" name="book_mfrom" value="{{ $bookMFrom }}">
              <input type="hidden" name="book_yto"   value="{{ $bookYTo }}">
              <input type="hidden" name="book_mto"   value="{{ $bookMTo }}">
              <input type="hidden" name="hki_from"   value="{{ $hkiFrom }}">
              <input type="hidden" name="hki_to"     value="{{ $hkiTo }}">

              <div class="input-group input-group-sm mr-1 mb-2">
                <div class="input-group-prepend"><span class="input-group-text">Dari</span></div>
                <input type="date" class="form-control" name="art_from" value="{{ $artFrom }}">
              </div>
              <div class="input-group input-group-sm mr-2 mb-2">
                <div class="input-group-prepend"><span class="input-group-text">Sampai</span></div>
                <input type="date" class="form-control" name="art_to" value="{{ $artTo }}">
              </div>
              <button class="btn btn-sm btn-primary mb-2" type="submit"><i class="fa fa-filter mr-1"></i> Terapkan</button>
            </form>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width:70px;">#</th>
                  <th>Judul</th>
                  <th>ISSN</th>
                  <th>DOI</th>
                  <th>Jenis</th>
                  <th>Penerbit</th>
                  <th>Tanggal</th>
                  <th>File</th>
                </tr>
              </thead>
              <tbody>
                @forelse($articles as $i => $a)
                  <tr>
                    <td>{{ ($articles->currentPage()-1)*$articles->perPage() + $i + 1 }}</td>
                    <td class="text-break">{{ $a->title }}</td>
                    <td>{{ $a->issn ?? '-' }}</td>
                    <td>{{ $a->doi ?? '-' }}</td>
                    <td>{{ $a->type_journal ?? '-' }}</td>
                    <td class="text-break">{{ $a->publisher ?? '-' }}</td>
                    <td>{{ $a->date ?? '-' }}</td>
                    <td class="text-center">
                      @if($a->file)
                        {{-- ganti url jika punya route download khusus artikel --}}
                        <a href="{{ url('article/'.$a->file) }}" class="btn btn-outline-secondary btn-sm">
                          <i class="fa fa-download"></i>
                        </a>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="8" class="text-center text-muted">Tidak ada data.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="mt-3">
            {{ $articles->withQueryString()->links() }}
          </div>
        </div>
      </div>

      {{-- ===================== BUKU (Year + Month filter) ===================== --}}
      <div class="card rounded shadow mb-4">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap">
            <h5 class="mb-3 mb-md-0">Buku</h5>
            <form class="form-inline" method="GET" action="{{ route('output.show', $lecturer->id) }}">
              {{-- pertahankan filter seksi lain --}}
              <input type="hidden" name="art_from" value="{{ $artFrom }}">
              <input type="hidden" name="art_to"   value="{{ $artTo }}">
              <input type="hidden" name="hki_from" value="{{ $hkiFrom }}">
              <input type="hidden" name="hki_to"   value="{{ $hkiTo }}">

              <div class="input-group input-group-sm mr-1 mb-2">
                <div class="input-group-prepend"><span class="input-group-text">Dari Tahun</span></div>
                <input type="number" class="form-control" name="book_yfrom" value="{{ $bookYFrom }}" min="1900" max="2100" placeholder="mis. 2019">
              </div>
              <div class="input-group input-group-sm mr-1 mb-2">
                <div class="input-group-prepend"><span class="input-group-text">Bulan</span></div>
                <select class="form-control" name="book_mfrom">
                  <option value="">—</option>
                  @for($m=1;$m<=12;$m++)
                    <option value="{{ $m }}" {{ (string)$bookMFrom === (string)$m ? 'selected' : '' }}>
                      {{ \DateTime::createFromFormat('!m',$m)->format('F') }}
                    </option>
                  @endfor
                </select>
              </div>

              <div class="input-group input-group-sm mr-1 mb-2">
                <div class="input-group-prepend"><span class="input-group-text">Sampai Tahun</span></div>
                <input type="number" class="form-control" name="book_yto" value="{{ $bookYTo }}" min="1900" max="2100" placeholder="mis. 2025">
              </div>
              <div class="input-group input-group-sm mr-2 mb-2">
                <div class="input-group-prepend"><span class="input-group-text">Bulan</span></div>
                <select class="form-control" name="book_mto">
                  <option value="">—</option>
                  @for($m=1;$m<=12;$m++)
                    <option value="{{ $m }}" {{ (string)$bookMTo === (string)$m ? 'selected' : '' }}>
                      {{ \DateTime::createFromFormat('!m',$m)->format('F') }}
                    </option>
                  @endfor
                </select>
              </div>

              <button class="btn btn-sm btn-primary mb-2" type="submit"><i class="fa fa-filter mr-1"></i> Terapkan</button>
            </form>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width:70px;">#</th>
                  <th>ISBN</th>
                  <th>Judul</th>
                  <th>Penerbit</th>
                  <th>Tahun</th>
                  <th>Bulan</th>
                  <th>Kota</th>
                  <th>File</th>
                </tr>
              </thead>
              <tbody>
                @forelse($books as $i => $b)
                  <tr>
                    <td>{{ ($books->currentPage()-1)*$books->perPage() + $i + 1 }}</td>
                    <td class="text-break">{{ $b->isbn }}</td>
                    <td class="text-break">{{ $b->title }}</td>
                    <td class="text-break">{{ $b->publisher ?? '-' }}</td>
                    <td>{{ $b->publish_year ?? '-' }}</td>
                    <td>
                      @php
                        $mm = (int)($b->publish_month ?? 0);
                        echo $mm ? \DateTime::createFromFormat('!m',$mm)->format('F') : '—';
                      @endphp
                    </td>
                    <td>{{ $b->city ?? '-' }}</td>
                    <td class="text-center">
                      @if($b->file)
                        <a href="{{ route('books.download', $b->file) }}" class="btn btn-outline-secondary btn-sm">
                          <i class="fa fa-download"></i>
                        </a>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="8" class="text-center text-muted">Tidak ada data.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="mt-3">
            {{ $books->withQueryString()->links() }}
          </div>
        </div>
      </div>

      {{-- ===================== HKI ===================== --}}
      <div class="card rounded shadow mb-4">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap">
            <h5 class="mb-3 mb-md-0">HKI</h5>
            <form class="form-inline" method="GET" action="{{ route('output.show', $lecturer->id) }}">
              {{-- pertahankan filter seksi lain --}}
              <input type="hidden" name="art_from"   value="{{ $artFrom }}">
              <input type="hidden" name="art_to"     value="{{ $artTo }}">
              <input type="hidden" name="book_yfrom" value="{{ $bookYFrom }}">
              <input type="hidden" name="book_mfrom" value="{{ $bookMFrom }}">
              <input type="hidden" name="book_yto"   value="{{ $bookYTo }}">
              <input type="hidden" name="book_mto"   value="{{ $bookMTo }}">

              <div class="input-group input-group-sm mr-1 mb-2">
                <div class="input-group-prepend"><span class="input-group-text">Dari</span></div>
                <input type="date" class="form-control" name="hki_from" value="{{ $hkiFrom }}">
              </div>
              <div class="input-group input-group-sm mr-2 mb-2">
                <div class="input-group-prepend"><span class="input-group-text">Sampai</span></div>
                <input type="date" class="form-control" name="hki_to" value="{{ $hkiTo }}">
              </div>
              <button class="btn btn-sm btn-primary mb-2" type="submit"><i class="fa fa-filter mr-1"></i> Terapkan</button>
            </form>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width:70px;">#</th>
                  <th>Nama HKI</th>
                  <th>Nomor</th>
                  <th>Pemegang</th>
                  <th>Tanggal</th>
                  <th>File</th>
                </tr>
              </thead>
              <tbody>
                @forelse($hkis as $i => $h)
                  <tr>
                    <td>{{ ($hkis->currentPage()-1)*$hkis->perPage() + $i + 1 }}</td>
                    <td class="text-break">{{ $h->name }}</td>
                    <td class="text-break">{{ $h->number }}</td>
                    <td class="text-break">{{ $h->holder }}</td>
                    <td>{{ $h->date }}</td>
                    <td class="text-center">
                      @if($h->file)
                        <a href="{{ route('hki.download', $h->file) }}" class="btn btn-outline-secondary btn-sm">
                          <i class="fa fa-download"></i>
                        </a>
                      @else
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center text-muted">Tidak ada data.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="mt-3">
            {{ $hkis->withQueryString()->links() }}
          </div>
        </div>
      </div>

    </div>

    @include('template.footer')
  </div>
</div>
@endsection
