@extends('layout.base')
@section('title', 'Daftar Prestasi - ' . ($level ?? 'Prestasi'))

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')
  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content site-content">
      <div class="card rounded shadow mb-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h4 class="mb-0">Prestasi — {{ $level }}</h4>
              <small class="text-muted">
                Bucket: <strong>{{ $bucket }}</strong>
                @if($start && $end) | Periode: {{ $start }} — {{ $end }} @else | Periode: Otomatis (TS logic) @endif
              </small>
            </div>
            <div>
              {{-- back to dashboard --}}
              <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-striped">
              <thead class="bg-primary text-white">
                <tr>
                  <th>#</th>
                  <th>Tanggal</th>
                  <th>Kompetisi</th>
                  <th>Tim / Peserta</th>
                  <th>Level</th>
                  <th>Penyelenggara</th>
                  <th>Prodi</th>
                  <th>Link</th>
                </tr>
              </thead>
              <tbody>
                @forelse($achievements as $a)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ sprintf('%02d', $a->month ?? 0) }}/{{ $a->year ?? '' }}</td>
                    <td class="text-left">{{ $a->competition }}</td>
                    <td class="text-left">
                      @if(isset($a->studentAchievements) && $a->studentAchievements->count())
                        <ul class="mb-0 ps-3 text-start">
                          @foreach($a->studentAchievements as $sa)
                            <li>
                              {{ isset($sa->student) ? $sa->student->name : ('ID:' . ($sa->student_id ?? '-')) }}
                              @if(isset($sa->student) && isset($sa->student->nim)) ({{ $sa->student->nim }}) @endif
                            </li>
                          @endforeach
                        </ul>
                      @else
                        {{ $a->team ?? '-' }}
                      @endif
                    </td>
                    <td>{{ $a->level }}</td>
                    <td class="text-left">{{ $a->organizer }}</td>
                    <td>{{ $a->department ? $a->department->name : '-' }}</td>
                    <td>
                      @if(!empty($a->link))
                        <a href="{{ $a->link }}" target="_blank" class="badge badge-info">Link</a>
                      @else
                        -
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center">Tidak ada data prestasi untuk bucket ini.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>

    @include('template.footer')
  </div>
</div>
@endsection
