@extends ('layout.base')
@section('title','Beranda')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    {{ Breadcrumbs::render('home') }}
    @include('template.nav')

    <div class="content site-content">

      {{-- ====== Kartu Ringkas (Global) ====== --}}
      <div class="row">
        <div class="col-md-4">
          <div class="card-counter danger position-relative mb-3 shadow">
            <i class="fa fa-trophy"></i>
            <div class="count-trophy">
              <span class="count-numbers">{{ $region }}</span>
              <span class="count-name">Regional</span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card-counter success position-relative mb-3 shadow">
            <i class="fa fa-trophy"></i>
            <div class="count-trophy">
              <span class="count-numbers">{{ $national }}</span>
              <span class="count-name">Nasional</span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card-counter info position-relative mb-3 shadow">
            <i class="fa fa-trophy"></i>
            <div class="count-trophy">
              <span class="count-numbers">{{ $international }}</span>
              <span class="count-name">Internasional</span>
            </div>
          </div>
        </div>
      </div>

      {{-- ====== Dua Grafik Bersebelahan + Filter minimalis (Between) ====== --}}
      <h4 class="font-weight-bold my-3 mt-md-4">Grafik</h4>
      <div class="card rounded shadow mt-2 mb-4">
        <div class="card-body">

          <div class="row">
            {{-- Grafik Prestasi --}}
            <div class="col-md-6 mb-3">
              <form method="GET" action="{{ route('home') }}" class="form-inline mb-2">
                <select name="ach_start_month" class="form-control form-control-sm mr-1">
                  <option value="">MM</option>
                  @for($m=1;$m<=12;$m++)
                    <option value="{{ $m }}" @if(isset($ach_start_month) && (int)$ach_start_month===$m) selected @endif>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
                  @endfor
                </select>
                <select name="ach_start_year" class="form-control form-control-sm mr-2">
                  <option value="">YYYY</option>
                  @for($y=date('Y')+1;$y>=date('Y')-6;$y--)
                    <option value="{{ $y }}" @if(isset($ach_start_year) && (int)$ach_start_year===$y) selected @endif>{{ $y }}</option>
                  @endfor
                </select>
                <span class="text-muted small mr-2">to</span>
                <select name="ach_end_month" class="form-control form-control-sm mr-1">
                  <option value="">MM</option>
                  @for($m=1;$m<=12;$m++)
                    <option value="{{ $m }}" @if(isset($ach_end_month) && (int)$ach_end_month===$m) selected @endif>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
                  @endfor
                </select>
                <select name="ach_end_year" class="form-control form-control-sm mr-2">
                  <option value="">YYYY</option>
                  @for($y=date('Y')+1;$y>=date('Y')-6;$y--)
                    <option value="{{ $y }}" @if(isset($ach_end_year) && (int)$ach_end_year===$y) selected @endif>{{ $y }}</option>
                  @endfor
                </select>
                <button class="btn btn-sm btn-primary">Apply</button>
                <a href="{{ route('home') }}" class="btn btn-sm btn-light ml-1">Reset</a>
              </form>

              <div class="position-relative" style="height:360px">
                <canvas id="chart_ach"></canvas>
              </div>
            </div>

            {{-- Grafik Artikel --}}
            <div class="col-md-6 mb-3">
              <form method="GET" action="{{ route('home') }}" class="form-inline mb-2">
                <select name="art_start_month" class="form-control form-control-sm mr-1">
                  <option value="">MM</option>
                  @for($m=1;$m<=12;$m++)
                    <option value="{{ $m }}" @if(isset($art_start_month) && (int)$art_start_month===$m) selected @endif>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
                  @endfor
                </select>
                <select name="art_start_year" class="form-control form-control-sm mr-2">
                  <option value="">YYYY</option>
                  @for($y=date('Y')+1;$y>=date('Y')-6;$y--)
                    <option value="{{ $y }}" @if(isset($art_start_year) && (int)$art_start_year===$y) selected @endif>{{ $y }}</option>
                  @endfor
                </select>
                <span class="text-muted small mr-2">to</span>
                <select name="art_end_month" class="form-control form-control-sm mr-1">
                  <option value="">MM</option>
                  @for($m=1;$m<=12;$m++)
                    <option value="{{ $m }}" @if(isset($art_end_month) && (int)$art_end_month===$m) selected @endif>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
                  @endfor
                </select>
                <select name="art_end_year" class="form-control form-control-sm mr-2">
                  <option value="">YYYY</option>
                  @for($y=date('Y')+1;$y>=date('Y')-6;$y--)
                    <option value="{{ $y }}" @if(isset($art_end_year) && (int)$art_end_year===$y) selected @endif>{{ $y }}</option>
                  @endfor
                </select>
                <button class="btn btn-sm btn-primary">Apply</button>
                <a href="{{ route('home') }}" class="btn btn-sm btn-light ml-1">Reset</a>
              </form>

              <div class="position-relative" style="height:360px">
                <canvas id="chart_art"></canvas>
              </div>
            </div>
          </div>

          <p class="mb-0 mt-2 chart-notif text-muted small">
            KETERANGAN :
            [SN] : Seminar Nasional, [SI] : Seminar Internasional, [JI] : Jurnal Internasional,
            [JIB] : Jurnal Internasional Bereputasi, [JNT] : Jurnal Nasional Terakreditasi,
            [JNTT] : Jurnal Nasional Tidak Terakreditasi
          </p>
        </div>
      </div>

      {{-- ====== 5 Prestasi Terakhir ====== --}}
      <h4 class="font-weight-bold my-3 mt-md-4">5 Prestasi Terakhir</h4>
      <div class="card rounded shadow mt-2 mb-4">
        <div class="card-body">
          <form method="GET" action="{{ route('home') }}" class="form-inline mb-3">
            <select name="last_start_month" class="form-control form-control-sm mr-1">
              <option value="">MM</option>
              @for($m=1;$m<=12;$m++)
                <option value="{{ $m }}" @if(isset($last_start_month) && (int)$last_start_month===$m) selected @endif>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
              @endfor
            </select>
            <select name="last_start_year" class="form-control form-control-sm mr-2">
              <option value="">YYYY</option>
              @for($y=date('Y')+1;$y>=date('Y')-6;$y--)
                <option value="{{ $y }}" @if(isset($last_start_year) && (int)$last_start_year===$y) selected @endif>{{ $y }}</option>
              @endfor
            </select>
            <span class="text-muted small mr-2">to</span>
            <select name="last_end_month" class="form-control form-control-sm mr-1">
              <option value="">MM</option>
              @for($m=1;$m<=12;$m++)
                <option value="{{ $m }}" @if(isset($last_end_month) && (int)$last_end_month===$m) selected @endif>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
              @endfor
            </select>
            <select name="last_end_year" class="form-control form-control-sm mr-2">
              <option value="">YYYY</option>
              @for($y=date('Y')+1;$y>=date('Y')-6;$y--)
                <option value="{{ $y }}" @if(isset($last_end_year) && (int)$last_end_year===$y) selected @endif>{{ $y }}</option>
              @endfor
            </select>
            <button class="btn btn-sm btn-primary">Apply</button>
            <a href="{{ route('home') }}" class="btn btn-sm btn-light ml-1">Reset</a>
          </form>

          <div class="table-responsive">
            <table class="table table-striped table-range">
              <thead class="bg-primary text-white">
                <tr class="text-center">
                  <th>#</th>
                  <th class="text-left">Nama Mahasiswa</th>
                  <th>NIM</th>
                  <th class="text-left">Kompetisi</th>
                  <th class="text-left">Pencapaian</th>
                  <th>Tahun</th>
                  <th>Rincian</th>
                </tr>
              </thead>
              <tbody>
              @foreach($data as $data_all)
                <tr class="text-center">
                  <th scope="row">{{ $loop->iteration }}</th>
                  <td class="text-left">{{ $data_all->student->name }}</td>
                  <td>{{ $data_all->student->nim }}</td>
                  <td class="text-left">{{ $data_all->achievement->competition }}</td>
                  <td class="text-left">{{ $data_all->achievement->rank }}</td>
                  <td>{{ $data_all->achievement->year }}</td>
                  <td><a href="{{ route('detail-dash',['id'=>$data_all->achievement->id]) }}" class="badge badge-success">Lihat</a></td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- ====== Dosen & Mahasiswa (klik angka → list) ====== --}}
      <div class="d-md-flex align-items-center justify-content-between mx-1 mt-1 mb-2">
        <h4 class="font-weight-bold my-2 mt-md-3">Dosen dan Mahasiswa</h4>
        <a href="{{ route('article-mahasiswa') }}" class="btn btn-outline-primary my-2 my-md-0">
          <i class="fas fa-download mr-1"></i> Unduh Excel
        </a>
      </div>
      <div class="card rounded shadow mt-1 mb-4">
        <div class="card-body">
          <form method="GET" action="{{ route('home') }}" class="form-inline mb-3">
            <select name="mix_start_month" class="form-control form-control-sm mr-1">
              <option value="">MM</option>
              @for($m=1;$m<=12;$m++)
                <option value="{{ $m }}" @if(isset($mix_start_month) && (int)$mix_start_month===$m) selected @endif>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
              @endfor
            </select>
            <select name="mix_start_year" class="form-control form-control-sm mr-2">
              <option value="">YYYY</option>
              @for($y=date('Y')+1;$y>=date('Y')-6;$y--)
                <option value="{{ $y }}" @if(isset($mix_start_year) && (int)$mix_start_year===$y) selected @endif>{{ $y }}</option>
              @endfor
            </select>
            <span class="text-muted small mr-2">to</span>
            <select name="mix_end_month" class="form-control form-control-sm mr-1">
              <option value="">MM</option>
              @for($m=1;$m<=12;$m++)
                <option value="{{ $m }}" @if(isset($mix_end_month) && (int)$mix_end_month===$m) selected @endif>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
              @endfor
            </select>
            <select name="mix_end_year" class="form-control form-control-sm mr-2">
              <option value="">YYYY</option>
              @for($y=date('Y')+1;$y>=date('Y')-6;$y--)
                <option value="{{ $y }}" @if(isset($mix_end_year) && (int)$mix_end_year===$y) selected @endif>{{ $y }}</option>
              @endfor
            </select>
            <button class="btn btn-sm btn-primary">Apply</button>
            <a href="{{ route('home') }}" class="btn btn-sm btn-light ml-1">Reset</a>
          </form>

          <div class="table-responsive">
            <table class="table table-striped table-range text-center">
              <thead class="bg-primary text-white">
                <tr>
                  <th rowspan="2">#</th>
                  <th rowspan="2" class="text-left" style="min-width:200px">Jenis Publikasi</th>
                  <th colspan="3">Tahun</th>
                  <th rowspan="2">Total</th>
                </tr>
                <tr>
                  <th>TS-2</th>
                  <th>TS-1</th>
                  <th>TS</th>
                </tr>
              </thead>
              <tbody>
              @foreach($data_type_array as $idx => $type)
                @php
                  $typeParam = urlencode($type);
                  $s = $mix_date_from ?? '';
                  $e = $mix_date_to   ?? '';
                  $ts2 = (int)($mix_TS_2_array[$idx] ?? 0);
                  $ts1 = (int)($mix_TS_1_array[$idx] ?? 0);
                  $ts  = (int)($mix_TS_array[$idx]    ?? 0);
                @endphp
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td class="text-left">{{ $type }}</td>
                  <td>
                    <a href="{{ route('stats.articles.bucket', ['category'=>'mix','bucket'=>'TS-2','type'=>$typeParam]) }}@if($s && $e)?start={{ $s }}&end={{ $e }}@endif">{{ $ts2 }}</a>
                  </td>
                  <td>
                    <a href="{{ route('stats.articles.bucket', ['category'=>'mix','bucket'=>'TS-1','type'=>$typeParam]) }}@if($s && $e)?start={{ $s }}&end={{ $e }}@endif">{{ $ts1 }}</a>
                  </td>
                  <td>
                    <a href="{{ route('stats.articles.bucket', ['category'=>'mix','bucket'=>'TS','type'=>$typeParam]) }}@if($s && $e)?start={{ $s }}&end={{ $e }}@endif">{{ $ts }}</a>
                  </td>
                  <td>{{ $ts2 + $ts1 + $ts }}</td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- ====== Dosen (klik angka → list) ====== --}}
      <div class="d-md-flex align-items-center justify-content-between mx-1 mt-3 mb-2">
        <h4 class="font-weight-bold my-2 mt-md-3">Dosen</h4>
        <a href="{{ route('article-dosen') }}" class="btn btn-outline-primary my-2 my-md-0">
          <i class="fas fa-download mr-1"></i> Unduh Excel
        </a>
      </div>
      <div class="card rounded shadow mt-1 mb-5">
        <div class="card-body">
          <form method="GET" action="{{ route('home') }}" class="form-inline mb-3">
            <select name="lec_start_month" class="form-control form-control-sm mr-1">
              <option value="">MM</option>
              @for($m=1;$m<=12;$m++)
                <option value="{{ $m }}" @if(isset($lec_start_month) && (int)$lec_start_month===$m) selected @endif>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
              @endfor
            </select>
            <select name="lec_start_year" class="form-control form-control-sm mr-2">
              <option value="">YYYY</option>
              @for($y=date('Y')+1;$y>=date('Y')-6;$y--)
                <option value="{{ $y }}" @if(isset($lec_start_year) && (int)$lec_start_year===$y) selected @endif>{{ $y }}</option>
              @endfor
            </select>
            <span class="text-muted small mr-2">to</span>
            <select name="lec_end_month" class="form-control form-control-sm mr-1">
              <option value="">MM</option>
              @for($m=1;$m<=12;$m++)
                <option value="{{ $m }}" @if(isset($lec_end_month) && (int)$lec_end_month===$m) selected @endif>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
              @endfor
            </select>
            <select name="lec_end_year" class="form-control form-control-sm mr-2">
              <option value="">YYYY</option>
              @for($y=date('Y')+1;$y>=date('Y')-6;$y--)
                <option value="{{ $y }}" @if(isset($lec_end_year) && (int)$lec_end_year===$y) selected @endif>{{ $y }}</option>
              @endfor
            </select>
            <button class="btn btn-sm btn-primary">Apply</button>
            <a href="{{ route('home') }}" class="btn btn-sm btn-light ml-1">Reset</a>
          </form>

          <div class="table-responsive">
            <table class="table table-striped table-range text-center">
              <thead class="bg-primary text-white">
                <tr>
                  <th rowspan="2">#</th>
                  <th rowspan="2" class="text-left" style="min-width:200px">Jenis Publikasi</th>
                  <th colspan="3">Tahun</th>
                  <th rowspan="2">Total</th>
                </tr>
                <tr>
                  <th>TS-2</th>
                  <th>TS-1</th>
                  <th>TS</th>
                </tr>
              </thead>
              <tbody>
              @foreach($data_type_array as $idx => $type)
                @php
                  $typeParam = urlencode($type);
                  $s = $lec_date_from ?? '';
                  $e = $lec_date_to   ?? '';
                  $ts2 = (int)($lec_TS_2_array[$idx] ?? 0);
                  $ts1 = (int)($lec_TS_1_array[$idx] ?? 0);
                  $ts  = (int)($lec_TS_array[$idx]    ?? 0);
                @endphp
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td class="text-left">{{ $type }}</td>
                  <td>
                    <a href="{{ route('stats.articles.bucket', ['category'=>'dosen','bucket'=>'TS-2','type'=>$typeParam]) }}@if($s && $e)?start={{ $s }}&end={{ $e }}@endif">{{ $ts2 }}</a>
                  </td>
                  <td>
                    <a href="{{ route('stats.articles.bucket', ['category'=>'dosen','bucket'=>'TS-1','type'=>$typeParam]) }}@if($s && $e)?start={{ $s }}&end={{ $e }}@endif">{{ $ts1 }}</a>
                  </td>
                  <td>
                    <a href="{{ route('stats.articles.bucket', ['category'=>'dosen','bucket'=>'TS','type'=>$typeParam]) }}@if($s && $e)?start={{ $s }}&end={{ $e }}@endif">{{ $ts }}</a>
                  </td>
                  <td>{{ $ts2 + $ts1 + $ts }}</td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div> {{-- /.content --}}
    @include('template.footer')
  </div>
</div>
@endsection

@section('js')
<script>
  // ========= Data dari backend =========
  var ach_year          = @json($ach_year_array ?? []);
  var ach_region        = @json($ach_region_array ?? []);
  var ach_national      = @json($ach_national_array ?? []);
  var ach_international = @json($ach_international_array ?? []);

  var data_type_array    = @json($data_type_array ?? []);
  if (!Array.isArray(data_type_array) || data_type_array.length === 0) {
    data_type_array = ["S N","S I","J I","J I B","J N T","J N T T"];
  }
  var art_TS_array_all   = @json($art_TS_array_all ?? []);
  var art_TS_1_array_all = @json($art_TS_1_array_all ?? []);
  var art_TS_2_array_all = @json($art_TS_2_array_all ?? []);

  (function renderPrestasi() {
    if (typeof Chart === 'undefined') return;
    var el = document.getElementById('chart_ach');
    if (!el) return;

    var data = {
      labels: ach_year,
      datasets: [
        { label: 'Region',        id: "y-axis-0", backgroundColor: ['rgba(0,0,0,0)'], borderColor: "salmon",      data: ach_region },
        { label: 'National',      id: "y-axis-0", backgroundColor: ['rgba(0,0,0,0)'], borderColor: "lightGreen",  data: ach_national },
        { label: 'International', id: "y-axis-0", backgroundColor: ['rgba(0,0,0,0)'], borderColor: "lightblue",   data: ach_international }
      ]
    };
    var options = {
      title: { display: true, text: 'Data Prestasi', position: "top" },
      scales: { yAxes: [{ position: "left", ticks: { beginAtZero: true } }] },
      maintainAspectRatio: false
    };

    new Chart(el.getContext('2d'), { type: 'radar', data: data, options: options });
  })();

  (function renderArtikel() {
    if (typeof Chart === 'undefined') return;
    var el = document.getElementById('chart_art');
    if (!el) return;

    var data = {
      labels: data_type_array,
      datasets: [
        { label: 'TS',   id: "y-axis-0", backgroundColor: ['rgba(0,0,0,0)'], borderColor: "salmon",     data: art_TS_array_all },
        { label: 'TS 1', id: "y-axis-0", backgroundColor: ['rgba(0,0,0,0)'], borderColor: "lightGreen", data: art_TS_1_array_all },
        { label: 'TS 2', id: "y-axis-0", backgroundColor: ['rgba(0,0,0,0)'], borderColor: "lightblue",  data: art_TS_2_array_all }
      ]
    };
    var options = {
      title: { display: true, text: 'Data Artikel', position: "top" },
      scales: { yAxes: [{ position: "left", ticks: { beginAtZero: true } }] },
      maintainAspectRatio: false
    };

    new Chart(el.getContext('2d'), { type: 'radar', data: data, options: options });
  })();
</script>
@endsection
