@extends ('layout.base')
@section('title','NonAkademik')
@section('nav')
  <div class="d-flex " id="wrapper" >
    <!-- Sidebar -->
    
    @include('template.sidebar')
    <!-- /#sidebar-wrapper -->

    <!-- Page Content -->
    
    <div id="page-content-wrapper" class="site">
      {{ Breadcrumbs::render('non-akademik') }}
      <!-- navbar -->
      @include('template.nav')
      <!-- endnavbar -->
      <div class="content">

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
                <span class="count-name">Nasional</span>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card-counter info position-relative mb-3 shadow">
              <i class="fa fa-trophy"></i>
              <div class="count-trophy">
                <span class="count-numbers">{{$international}}</span>
                <span class="count-name">internasional</span>
              </div>
            </div>
          </div>
        </div>

        <div class="d-md-flex align-items-center justify-content-between  mt-4 mb-3">
          <h4 class="font-weight-bold mb-0">
            Prestasi Non-Akademik
          </h4>
          <a href="{{route('export-nonAkademik')}}" class="btn btn-outline-primary my-3 my-md-0"><i class="fas fa-download mr-1"></i> Unduh Excel</a>
        </div>

        <div class="card rounded shadow mt-3 mb-5">
          <div class="card-body">
            @if (session('status'))
                <div class="alert alert-primary">
                    {{ session('status') }}
                </div>
            @endif

            <div class="table-responsive">
              <table class="table table-striped table-range text-center">
                <thead class="bg-primary text-white">
                  <tr>
                    <th rowspan="2">#</th>
                    <th rowspan="2">Tahun</th>
                    <th colspan="3">Tingkat</th>
                    <th rowspan="2">Total</th>
                  </tr>
                  <tr>
                    <th>Regional</th>
                    <th>Nasional</th>
                    <th>Internasional</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($year_array as $year)
                  <tr>
                    <td>{{$loop->iteration}}</td>
                    <td>{{$year}}</td>
                    <td> <a href="{{route('nonAkademik-region',['year'=>$year]) }}"> {{$region_array[$loop->iteration-1]}}</a></td>
                    <td><a href="{{route('nonAkademik-national',['year'=>$year]) }}">{{$national_array[$loop->iteration-1]}}</a></td>
                    <td><a href="{{route('nonAkademik-international',['year'=>$year]) }}">{{$international_array[$loop->iteration-1]}}</a></td>
                    <td>{{$region_array[$loop->iteration-1] +
                          $national_array[$loop->iteration-1] +
                          $international_array[$loop->iteration-1]}}</td>
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





<!-- Modal -->
<div class="modal fade" id="SelectAdd" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-body p-5"> 
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem;">
          <span aria-hidden="true">&times;</span>
        </button>
        <div class="row">
          <div class="col-12 my-3">
              <a class="btn rounded-pill d-flex align-items-center justify-content-center py-3 px-4 btn-outline-info" href="{{route('select-prestasi')}}" >
                <h5 class="mb-0"><i class="fas fa-trophy"></i> Tambah Prestasi</h5>
              </a>
          </div>
          <div class="col-12 my-3">
            <a class="btn rounded-pill d-flex align-items-center justify-content-center py-3 px-4 btn-outline-success" href="{{route('article-select')}}" >
              <h5 class="mb-0"><i class="fas fa-book-open"></i> Tambah Artikel</h5>
            </a>
          </div>
        </div>
        
      </div>
      <!-- <div class="modal-footer"> -->
        <!-- <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button> -->
        <!-- <p></p> -->
      <!-- </div> -->
    </div>
  </div>
</div>


@endsection


