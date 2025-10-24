@extends('layout.base')
@section('title','Rincian Prestasi')

@section('nav')
<div class="d-flex" id="wrapper">
  <!-- Sidebar -->
  @include('template.sidebar')
  <!-- /#sidebar-wrapper -->

  <!-- Page Content -->
  <div id="page-content-wrapper" class="site">

    {{-- Navbar --}}
    @include('template.nav')

    {{-- Breadcrumbs --}}
    @if ($keyroute == 1)
      @if (session('breadcrumb') == 'akademik_region')
        {{ Breadcrumbs::render('aka-detail-reg') }}
      @elseif (session('breadcrumb') == 'akademik_national')
        {{ Breadcrumbs::render('aka-detail-nat') }}
      @elseif (session('breadcrumb') == 'akademik_international')
        {{ Breadcrumbs::render('aka-detail-int') }}

      @elseif (session('breadcrumb') == 'nonAkademik_region')
        {{ Breadcrumbs::render('nonAka-detail-reg') }}
      @elseif (session('breadcrumb') == 'nonAkademik_national')
        {{ Breadcrumbs::render('nonAka-detail-nat') }}
      @elseif (session('breadcrumb') == 'nonAkademik_international')
        {{ Breadcrumbs::render('nonAka-detail-int') }}
      @endif
    @else
      {{ Breadcrumbs::render('detail-dash') }}
    @endif

    <div class="content">

      @if (session('status'))
        <div class="alert alert-primary">
          {{ session('status') }}
        </div>
      @endif

      <div class="card rounded shadow mt-3">
        <div class="card-body detail-page p-md-4">

          <div class="row">
            @auth
              <div class="col-12 mb-3 pb-3 text-center text-md-right px-3">
                <a class="btn btn-outline-primary" href="{{ route('edit', ['id' => $achievement->id]) }}">
                  Sunting
                </a>

                <form class="d-inline-block ml-2" action="{{ route('delete', ['id' => $achievement->id]) }}" method="post">
                  @method('delete')
                  @csrf
                  <button onclick="return confirm('Yakin data ingin dihapus?');" type="submit" class="btn btn-outline-danger">
                    Hapus
                  </button>
                </form>
              </div>
            @endauth

            {{-- Header: Info Achievement + Counter Tim --}}
            <div class="col-md-6 text-center text-md-left">

              <h5 class="mb-1">{{ $achievement->competition }}</h5>
              <div class="text-muted mb-2">
                {{ $achievement->organizer }} • {{ $achievement->month }} / {{ $achievement->year }}
              </div>

              @if(!empty($achievement->type_achievement))
                <span class="badge badge-primary text-uppercase mb-3">
                  {{ $achievement->type_achievement }}
                </span>
              @endif

              {{-- Department • Faculty --}}
              <div class="small text-muted">
                @if($achievement->department)
                  <span class="mr-2">
                    <i class="fas fa-building mr-1"></i>{{ $achievement->department->name }}
                  </span>
                  @if($achievement->department->faculty)
                    <span>
                      <i class="fas fa-university mr-1"></i>{{ $achievement->department->faculty->name }}
                    </span>
                  @endif
                @endif
              </div>
            </div>

            {{-- Counter total pencapaian tim --}}
            <div class="col-md-6">
              @php $count_all = $all_achievement->count(); @endphp
              <div class="card-counter success position-relative mb-3">
                <i class="fa fa-trophy"></i>
                <div class="count-trophy">
                  <span class="count-numbers">{{ $count_all }}</span>
                  <span class="count-name">Pencapaian Tim “{{ $achievement->team }}”</span>
                </div>
              </div>
            </div>
          </div>

          {{-- Detail + Tim & Sertifikat --}}
          <div class="row">
            <div class="col-md-6">
              <p class="font-weight-bold my-3 mt-md-4">Rincian Prestasi</p>
              <table class="table detail mb-4">
                <tbody>
                  <tr>
                    <td>Tim</td>
                    <td>{{ $achievement->team }}</td>
                  </tr>
                  <tr>
                    <td>Bidang</td>
                    <td>{{ $achievement->field }}</td>
                  </tr>
                  <tr>
                    <td>Tingkat</td>
                    <td>{{ $achievement->level }}</td>
                  </tr>
                  <tr>
                    <td>Pencapaian</td>
                    <td>{{ $achievement->rank }}</td>
                  </tr>
                  <tr>
                    <td>Kompetisi</td>
                    <td>{{ $achievement->competition }}</td>
                  </tr>
                  <tr>
                    <td>Periode</td>
                    <td>{{ $achievement->month }} / {{ $achievement->year }}</td>
                  </tr>
                  <tr>
                    <td>Penyelenggara</td>
                    <td>{{ $achievement->organizer }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            {{-- Anggota Tim + Sertifikat (pivot) --}}
            <div class="col-md-6">
              <p class="font-weight-bold my-3 mt-md-4">Anggota Tim & Sertifikat</p>

              @if($achievement->students->isEmpty())
                <div class="text-muted">Belum ada data anggota tim.</div>
              @else
                <div class="list-group mb-3">
                  @foreach($achievement->students as $stu)
                    <div class="list-group-item d-flex align-items-center">
                      {{-- Foto student --}}
                      @php
                        $fotoStu = !empty($stu->photo) ? asset('image-profile/'.$stu->photo) : asset('image/user.svg');
                      @endphp
                      <img src="{{ $fotoStu }}" alt="{{ $stu->name }}" width="40" height="40" class="rounded-circle mr-3" style="object-fit:cover" loading="lazy">

                      <div class="flex-fill">
                        <div class="font-weight-600">{{ $stu->name }}</div>
                        <div class="small text-muted">{{ $stu->nim }}</div>
                      </div>

                      {{-- Sertifikat dari pivot --}}
                      @if(!empty($stu->pivot->certificate))
                        <a href="{{ asset('image-certificate/'.$stu->pivot->certificate) }}"
                           target="_blank"
                           class="btn btn-sm btn-outline-primary">
                          Sertifikat
                        </a>
                      @else
                        <span class="badge badge-light">Tanpa Sertifikat</span>
                      @endif
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          </div>

          {{-- Dokumentasi (achievement_documentations) --}}
          <div class="row">
            <div class="col-12">
              <p class="font-weight-bold my-3 mt-md-4">Dokumentasi</p>
            </div>

            @forelse($achievement->documentations as $doc)
              <div class="col-md-6">
                <img
                  class="img-fluid rounded shadow mb-4"
                  src="{{ asset('image-documentations/'.$doc->image) }}"
                  alt="Dokumentasi {{ $achievement->competition }}"
                  loading="lazy"
                >
              </div>
            @empty
              <div class="col-12 text-muted">Belum ada dokumentasi.</div>
            @endforelse
          </div>

        </div>
      </div>
    </div>

    {{-- Footer --}}
    @include('template.footer')

  </div>
</div>
<!-- /#page-content-wrapper -->
@endsection
