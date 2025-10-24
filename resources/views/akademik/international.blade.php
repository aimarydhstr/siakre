@extends ('layout.base')
@section('title','Internasional')
@section('nav')
<div class="d-flex " id="wrapper" >
    <!-- Sidebar -->
    @include('template.sidebar')
    <!-- /#sidebar-wrapper -->

    <!-- Page Content -->
    
    <div id="page-content-wrapper" class="site ">
    {{ Breadcrumbs::render('akademik-international') }}
      <!-- navbar -->
      @include('template.nav')
        <!-- endnavbar -->

      <div class="content site-content">

        <div class="row">
          <div class="col-md-4">
            <div class="card-counter danger position-relative mb-3 shadow">
              <i class="fa fa-trophy"></i>
              <div class="count-trophy">
                <span class="count-numbers">{{$region}}</span>
                <span class="count-name">Regional</span>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card-counter success position-relative mb-3 shadow">
              <i class="fa fa-trophy"></i>
              <div class="count-trophy">
                <span class="count-numbers">{{$national}}</span>
                <span class="count-name">National</span>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card-counter info position-relative mb-3 shadow">
              <i class="fa fa-trophy"></i>
              <div class="count-trophy">
                <span class="count-numbers">{{$international}}</span>
                <span class="count-name">international</span>
              </div>
            </div>
          </div>
        </div>

        <div class="d-md-flex align-items-center justify-content-between  mt-4 mb-3">
          <h4 class="font-weight-bold mb-0">
            Prestasi Internasional
          </h4>
          <a href="{{route('export-akademik-international')}}" class="btn btn-outline-primary my-3 my-md-0"><i class="fas fa-download mr-1"></i> Unduh Excel</a>
        </div>

        <div class="card rounded shadow mt-3">
          <div class="card-body p-3">
          <form action="" method="get" class="form-inline justify-content-end mb-4 mt-2 search">
            <input class="mr-3" type="search" placeholder="Pencarian Nama / NIM / Tahun" name="search">
            <button class="search" type="submit"><i  class="fas fa-search" aria-hidden="true"></i></button>
          </form>

            <div class="table-responsive">
              <table class="table table-striped table-range">
                <thead class="bg-primary text-white">
                  <tr class="text-center">
                    <th scope="col">#</th>
                    <th scope="col" class="text-left">Kompetisi</th>
                    <th scope="col" class="text-left">Tipe</th>
                    <th scope="col" class="text-left">Pencapaian</th>
                    <th scope="col" class="text-left">Bidang</th>
                    <th scope="col" class="text-left">Tingkat</th>
                    <th scope="col">Tahun</th>
                    <th scope="col">Rincian</th>
                  </tr>
                </thead>
                <tbody>
                @foreach($data as $data_all)
                  <tr class="text-center">
                    <th scope="row">{{  $loop->iteration }}</th>
                    <td class="text-left">{{$data_all->competition}}</td>
                    <td class="text-left">{{$data_all->type_achievement}}</td>
                    <td class="text-left">{{$data_all->rank}}</td>
                    <td class="text-left">{{$data_all->field}}</td>
                    <td class="text-left">{{$data_all->level}}</td>
                    <td>{{$data_all->year}}</td>
                    <td ><a href="{{route('detail',['id' =>$data_all->id])}}" class="badge badge-success text-center">Lihat</a> </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      
      </div>
        <!-- akhir container -->
        @include('template.footer')
      </div>
    </div>
  <!-- /#page-content-wrapper -->

  
@endsection


