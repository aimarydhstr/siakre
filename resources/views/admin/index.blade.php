@extends ('layout.base')
@section('title','Beranda')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
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
                {{-- Section-specific department selector --}}
                @if(in_array($user->role ?? '', ['admin','faculty_head']))
                <div class="row col-md-12">
                    <select name="department_ach" class="form-control form-control-sm mr-2 w-100 mb-3">
                      <option value="">Semua Prodi</option>
                      @foreach($departments ?? [] as $dept)
                        <option value="{{ $dept->id }}"
                          @if((int)request()->get('department_ach') === (int)$dept->id) selected
                          @elseif(!request()->has('department_ach') && isset($selected_department_id) && (int)$selected_department_id === (int)$dept->id) selected @endif>
                          {{ $dept->name }}
                        </option>
                      @endforeach
                    </select>
                  @else
                    {{-- non-admin -> force department via hidden --}}
                    <input type="hidden" name="department_ach" value="{{ $selected_department_id }}">
                  @endif

                  {{-- preserve global dropdown param so UI remains consistent --}}
                  @if(request()->filled('department_id'))
                    <input type="hidden" name="department_id" value="{{ request()->get('department_id') }}">
                  @elseif(isset($selected_department_id))
                    <input type="hidden" name="department_id" value="{{ $selected_department_id }}">
                  @endif
                </div>

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
                {{-- Section-specific department selector --}}
                @if(in_array($user->role ?? '', ['admin','faculty_head']))
                <div class="row col-md-12">
                    <select name="department_art" class="form-control form-control-sm mr-2 w-100 mb-3">
                      <option value="">Semua Prodi</option>
                      @foreach($departments ?? [] as $dept)
                        <option value="{{ $dept->id }}"
                          @if((int)request()->get('department_art') === (int)$dept->id) selected
                          @elseif(!request()->has('department_art') && isset($selected_department_id) && (int)$selected_department_id === (int)$dept->id) selected @endif>
                          {{ $dept->name }}
                        </option>
                      @endforeach
                    </select>
                  @else
                    <input type="hidden" name="department_art" value="{{ $selected_department_id }}">
                  @endif

                  @if(request()->filled('department_id'))
                    <input type="hidden" name="department_id" value="{{ request()->get('department_id') }}">
                  @elseif(isset($selected_department_id))
                    <input type="hidden" name="department_id" value="{{ $selected_department_id }}">
                  @endif
                </div>

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

      {{-- ========================= --}}
      {{-- PRESTASI: TS summary + Tabel (baru) --}}
      {{-- ========================= --}}
      <div class="card rounded shadow mt-2 mb-4">
        <div class="card-header">
          <strong>Daftar Prestasi</strong>
          <small class="text-muted d-block">Filter Prodi / Periode (Between). Klik angka TS untuk melihat bucket (TS / TS-1 / TS-2).</small>
        </div>
        <div class="card-body">
          <form method="GET" action="{{ route('home') }}" class="form-inline mb-3">
            {{-- show department selector only for admin/faculty_head --}}
            @if(in_array($user->role ?? '', ['admin','faculty_head']))
              <select name="department_ach" class="form-control form-control-sm mr-2">
                <option value="">Semua Prodi</option>
                @foreach($departments ?? [] as $dept)
                  <option value="{{ $dept->id }}" {{ (string)request()->get('department_ach') === (string)$dept->id ? 'selected' : ((!request()->has('department_ach') && isset($selected_department_id) && (string)$selected_department_id === (string)$dept->id) ? 'selected' : '') }}>
                    {{ $dept->name }}
                  </option>
                @endforeach
              </select>
            @else
              <input type="hidden" name="department_ach" value="{{ $selected_department_id }}">
            @endif

            {{-- preserve global selector --}}
            @if(request()->filled('department_id'))
              <input type="hidden" name="department_id" value="{{ request()->get('department_id') }}">
            @elseif(isset($selected_department_id))
              <input type="hidden" name="department_id" value="{{ $selected_department_id }}">
            @endif

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

            <button class="btn btn-sm btn-primary">Filter</button>
            <a href="{{ route('home') }}" class="btn btn-sm btn-light ml-1">Reset</a>
          </form>

          {{-- TS buckets summary with links --}}
          <div class="mb-3">
            <h6>Ringkasan TS (Prestasi)</h6>
            <table class="table table-sm table-bordered" style="max-width:900px;">
              <thead class="table-light">
                <tr>
                  <th>Level</th>
                  <th>TS</th>
                  <th>TS-1</th>
                  <th>TS-2</th>
                </tr>
              </thead>
              <tbody>
                @foreach($ach_levels as $idx => $lvl)
                  <tr>
                    <td>{{ $lvl }}</td>
                    <td>
                      <a href="{{ route('stats.achievements.bucket', [$lvl, 'TS']) }}?{{ http_build_query(array_merge(request()->except(['page','ach_page']), ['department_ach' => request()->get('department_ach'), 'ach_start_month'=>request()->get('ach_start_month'), 'ach_start_year'=>request()->get('ach_start_year'), 'ach_end_month'=>request()->get('ach_end_month'), 'ach_end_year'=>request()->get('ach_end_year')])) }}">
                        {{ $ach_TS[$idx] ?? 0 }}
                      </a>
                    </td>
                    <td>
                      <a href="{{ route('stats.achievements.bucket', [$lvl, 'TS-1']) }}?{{ http_build_query(array_merge(request()->except(['page','ach_page']), ['department_ach' => request()->get('department_ach'), 'ach_start_month'=>request()->get('ach_start_month'), 'ach_start_year'=>request()->get('ach_start_year'), 'ach_end_month'=>request()->get('ach_end_month'), 'ach_end_year'=>request()->get('ach_end_year')])) }}">
                        {{ $ach_TS_1[$idx] ?? 0 }}
                      </a>
                    </td>
                    <td>
                      <a href="{{ route('stats.achievements.bucket', [$lvl, 'TS-2']) }}?{{ http_build_query(array_merge(request()->except(['page','ach_page']), ['department_ach' => request()->get('department_ach'), 'ach_start_month'=>request()->get('ach_start_month'), 'ach_start_year'=>request()->get('ach_start_year'), 'ach_end_month'=>request()->get('ach_end_month'), 'ach_end_year'=>request()->get('ach_end_year')])) }}">
                        {{ $ach_TS_2[$idx] ?? 0 }}
                      </a>

                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
            <small class="text-muted">Klik angka untuk membuka daftar prestasi pada bucket tersebut.</small>
          </div>

          {{-- achievements table (paginated) --}}
          <div class="table-responsive">
            <table class="table table-striped table-range">
              <thead class="bg-primary text-white">
                <tr class="text-center">
                  <th>#</th>
                  <th class="text-left">Tanggal</th>
                  <th class="text-left">Kompetisi</th>
                  <th class="text-left">Tim / Peserta</th>
                  <th>Level</th>
                  <th>Penyelenggara</th>
                  <th>Prodi</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($achievements_table as $ach)
                  <tr class="text-center">
                    <th scope="row">{{ $loop->iteration + (($achievements_table->currentPage()-1) * $achievements_table->perPage()) }}</th>
                    <td class="text-left">{{ sprintf('%02d', $ach->month ?? 0) }}/{{ $ach->year ?? '' }}</td>
                    <td class="text-left">{{ $ach->competition }}</td>
                    <td class="text-left">{{ $ach->team }}</td>
                    <td>{{ $ach->level }}</td>
                    <td class="text-left">{{ $ach->organizer }}</td>
                    <td>{{ $ach->department->name ?? '-' }}</td>
                    <td>
                      <a href="{{ url('/achievements/'.$ach->id) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="8" class="text-center">Tidak ada data prestasi.</td></tr>
                @endforelse
              </tbody>
            </table>

            {{-- pagination: menjaga semua query params --}}
            <div>
              {{ $achievements_table->appends(request()->except('page'))->links() }}
            </div>
          </div>
        </div>
      </div>

      {{-- ====== 5 Prestasi Terakhir ====== --}}
      <h4 class="font-weight-bold my-3 mt-md-4">5 Prestasi Terakhir</h4>
      <div class="card rounded shadow mt-2 mb-4">
        <div class="card-body">
          <form method="GET" action="{{ route('home') }}" class="form-inline mb-3">
            @if(in_array($user->role ?? '', ['admin','faculty_head']))
              <select name="department_last" class="form-control form-control-sm mr-2">
                <option value="">Semua Prodi</option>
                @foreach($departments ?? [] as $dept)
                  <option value="{{ $dept->id }}"
                    @if((int)request()->get('department_last') === (int)$dept->id) selected
                    @elseif(!request()->has('department_last') && isset($selected_department_id) && (int)$selected_department_id === (int)$dept->id) selected @endif>
                    {{ $dept->name }}
                  </option>
                @endforeach
              </select>
            @else
              <input type="hidden" name="department_last" value="{{ $selected_department_id }}">
            @endif

            @if(request()->filled('department_id'))
              <input type="hidden" name="department_id" value="{{ request()->get('department_id') }}">
            @elseif(isset($selected_department_id))
              <input type="hidden" name="department_id" value="{{ $selected_department_id }}">
            @endif

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

      {{-- ====== Tabel Artikel: MAHASISWA (category == 'mahasiswa') ====== --}}
      <div class="d-md-flex align-items-center justify-content-between mx-1 mt-1 mb-2">
        <h4 class="font-weight-bold my-2 mt-md-3">Artikel — Dosen & Mahasiswa </h4>
        <a href="{{ route('article-mahasiswa') }}" class="btn btn-outline-primary my-2 my-md-0">
          <i class="fas fa-download mr-1"></i> Unduh Excel
        </a>
      </div>
      <div class="card rounded shadow mt-1 mb-4">
        <div class="card-body">
          <form method="GET" action="{{ route('home') }}" class="form-inline mb-3">
            @if(in_array($user->role ?? '', ['admin','faculty_head']))
              <select name="department_mix" class="form-control form-control-sm mr-2">
                <option value="">Semua Prodi</option>
                @foreach($departments ?? [] as $dept)
                  <option value="{{ $dept->id }}"
                    @if((int)request()->get('department_mix') === (int)$dept->id) selected
                    @elseif(!request()->has('department_mix') && isset($selected_department_id) && (int)$selected_department_id === (int)$dept->id) selected @endif>
                    {{ $dept->name }}
                  </option>
                @endforeach
              </select>
            @else
              <input type="hidden" name="department_mix" value="{{ $selected_department_id }}">
            @endif

            @if(request()->filled('department_id'))
              <input type="hidden" name="department_id" value="{{ request()->get('department_id') }}">
            @elseif(isset($selected_department_id))
              <input type="hidden" name="department_id" value="{{ $selected_department_id }}">
            @endif

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
                  $ts2 = (int)($maha_TS_2_array[$idx] ?? 0);
                  $ts1 = (int)($maha_TS_1_array[$idx] ?? 0);
                  $ts  = (int)($maha_TS_array[$idx]    ?? 0);
                @endphp
                <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td class="text-left">{{ $type }}</td>
                  <td>
                    <a href="{{ route('stats.articles.bucket', ['category'=>'mahasiswa','bucket'=>'TS-2','type'=>$typeParam]) }}@if($s && $e)?start={{ $s }}&end={{ $e }}@endif">{{ $ts2 }}</a>
                  </td>
                  <td>
                    <a href="{{ route('stats.articles.bucket', ['category'=>'mahasiswa','bucket'=>'TS-1','type'=>$typeParam]) }}@if($s && $e)?start={{ $s }}&end={{ $e }}@endif">{{ $ts1 }}</a>
                  </td>
                  <td>
                    <a href="{{ route('stats.articles.bucket', ['category'=>'mahasiswa','bucket'=>'TS','type'=>$typeParam]) }}@if($s && $e)?start={{ $s }}&end={{ $e }}@endif">{{ $ts }}</a>
                  </td>
                  <td>{{ $ts2 + $ts1 + $ts }}</td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- ====== Tabel Artikel: DOSEN (category == 'dosen') ====== --}}
      <div class="d-md-flex align-items-center justify-content-between mx-1 mt-3 mb-2">
        <h4 class="font-weight-bold my-2 mt-md-3">Artikel — Dosen</h4>
        <a href="{{ route('article-dosen') }}" class="btn btn-outline-primary my-2 my-md-0">
          <i class="fas fa-download mr-1"></i> Unduh Excel
        </a>
      </div>
      <div class="card rounded shadow mt-1 mb-5">
        <div class="card-body">
          <form method="GET" action="{{ route('home') }}" class="form-inline mb-3">
            @if(in_array($user->role ?? '', ['admin','faculty_head']))
              <select name="department_lec" class="form-control form-control-sm mr-2">
                <option value="">Semua Prodi</option>
                @foreach($departments ?? [] as $dept)
                  <option value="{{ $dept->id }}"
                    @if((int)request()->get('department_lec') === (int)$dept->id) selected
                    @elseif(!request()->has('department_lec') && isset($selected_department_id) && (int)$selected_department_id === (int)$dept->id) selected @endif>
                    {{ $dept->name }}
                  </option>
                @endforeach
              </select>
            @else
              <input type="hidden" name="department_lec" value="{{ $selected_department_id }}">
            @endif

            @if(request()->filled('department_id'))
              <input type="hidden" name="department_id" value="{{ request()->get('department_id') }}">
            @elseif(isset($selected_department_id))
              <input type="hidden" name="department_id" value="{{ $selected_department_id }}">
            @endif

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
                  $ts2 = (int)($dosen_TS_2_array[$idx] ?? 0);
                  $ts1 = (int)($dosen_TS_1_array[$idx] ?? 0);
                  $ts  = (int)($dosen_TS_array[$idx]    ?? 0);
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
