@extends ('layout.base')
@section('title','Beranda')
@section('nav')
  <div class="d-flex " id="wrapper" >
    <!-- Sidebar -->
    @include('template.sidebar')
    <!-- /#sidebar-wrapper -->

    <!-- Page Content -->
    
    <div id="page-content-wrapper" class="site">
      {{ Breadcrumbs::render('search') }}
      <!-- navbar -->
      @include('template.nav')
        <!-- endnavbar -->
      <div class="content site-content">
 
        <h4 class="font-weight-bold my-3 mt-md-4">
         Pencarian
        </h4>
        <div class="card rounded shadow mt-3">
          <div class="card-body p-3">
            <form action="" method="get" class="form-inline justify-content-end mb-4 mt-2 search">
              <input class="mr-3" type="search" placeholder="Pencarian Nama / NIM / Tahun" name="search" value="{{ session('search')}}">
              <button class="search" type="submit"><i  class="fas fa-search" aria-hidden="true"></i></button>
            </form>

            <div class="table-responsive">
              <table class="table table-striped table-range">
                <thead class="bg-primary text-white">
                  <tr class="text-center">
                    <th scope="col">#</th>
                    <th scope="col" class="text-left">Nama Mahasiswa</th>
                    <th scope="col">NIM</th>
                    <th scope="col" class="text-left">Kompetisi</th>
                    <th scope="col" class="text-left">Pencapaian</th>
                    <th scope="col">Tahun</th>
                    <th scope="col">Rincian</th>
                  </tr>
                </thead>
                <tbody>
                @foreach($data as $data_all)
                  <tr class="text-center">
                    <th scope="row">{{  $loop->iteration }}</th>
                    <td class="text-left">{{$data_all->name}}</td>
                    <td>{{$data_all->nim}}</td>
                    <td class="text-left">{{$data_all->competition}}</td>
                    <td class="text-left">{{$data_all->achievement}}</td>
                    <td>{{$data_all->year}}</td>
                    <td ><a href="{{route('detail',['id' =>$data_all->id]) }}" class="badge badge-success text-center">Lihat</a> </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      
      </div>
        <!-- akhir container -->
      <!-- navbar -->
      @include('template.footer')
        <!-- endnavbar -->
      
    </div>
  </div>
  <!-- /#page-content-wrapper -->
@endsection


