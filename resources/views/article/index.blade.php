@extends ('layout.base')
@section('title','Artikel')
@section('nav')
  <div class="d-flex " id="wrapper" >
    <!-- Sidebar -->
    @include('template.sidebar')
    <!-- /#sidebar-wrapper -->

    <!-- Page Content -->
    
    <div id="page-content-wrapper" class="site">
      {{ Breadcrumbs::render('article') }}
      <!-- navbar -->
      @include('template.nav')
      <!-- endnavbar -->
      <div class="content site-content">
          
        <div class="row mb-2">
          <div class="col-md-6">
            <h4 class="font-weight-bold mb-0">
              Artikel
            </h4>
          </div>
          <div class="col-md-6 mt-3 mt-md-0">
            <form action="" method="get" class="form-inline justify-content-end mb-4 mt-2 search">
              <input class="w-100 mr-3  search-article" type="search" placeholder="Pencarian Data ..." name="search">
              <button class="search" type="submit"><i  class="fas fa-search" aria-hidden="true"></i></button>
            </form>
          </div>
        </div>

        @if (session('status'))
            <div class="my-3">
                  <div class="alert alert-primary">
                      {{ session('status') }}
                  </div>
            </div>
        @endif

        @foreach($data as $data_article)
        <div class="card rounded shadow mb-5">
          <div class="card-body">
            <a class="text-decoration-none" href="{{$data_article['url']}}">
              <h4 class="text-capitalize mt-1 mb-3"> {{$data_article->title}}</h4>
            </a>
            <div class="row">
              <div class="col-sm-6 col-md-12 col-lg-6">
                <table class="table mb-0">
                  <tbody>
                    @if($data_article['category'] == 'mahasiswa')
                    <tr>
                      <td width="100" class="pl-0 opacity-5">Mahasiswa</td>
                      <td>
                        @foreach($data_article->students as $student)
                          {{$student['name']}} ({{$student['nim']}}),
                        @endforeach
                      </td>
                    </tr>
                    @endif
                    <tr>
                      <td width="100" class="pl-0 opacity-5">Dosen</td>
                      <td>
                        @foreach($data_article->lecturers as $lecturer)
                        {{$lecturer['user']['name']}}, 
                        @endforeach
                      </td>
                    </tr>
                    <tr>
                      <td class="pl-0 opacity-5">Kategori</td>
                      <td>
                        {{$data_article->type_journal}}
                      </td>
                    </tr>
                    <tr>
                      <td class="pl-0 opacity-5">Penerbit</td>
                      <td>
                        {{$data_article->publisher}}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="col-sm-6 col-md-12 col-lg-6">
                <table class="table mb-0">
                  <tbody>
                    <tr>
                      <td width="100" class="pl-0 opacity-5">Nomor</td>
                      <td>
                        {{$data_article->number}}
                      </td>
                    </tr>
                    <tr>
                      <td class="pl-0 opacity-5">Volume</td>
                      <td>
                        {{$data_article->volume}}
                      </td>
                    </tr>
                    <tr>
                      <td class="pl-0 opacity-5">Publikasi</td>
                      <td>
                        {{date('d-m-Y', strtotime($data_article->date))}}
                      </td>
                    </tr>
                    <tr>
                      <td class="pl-0 opacity-5">DOI</td>
                      <td>
                        {{$data_article->doi}}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-6">
                <a class="btn btn-outline-primary d-block d-sm-inline-block mt-3" href="{{route('view',['id'=>$data_article->id])}}" ><i class="fas fa-eye"></i> Lihat</a>
                <a class="btn btn-outline-success d-block d-sm-inline-block mt-3" href="{{route('download',['file'=>$data_article->file])}}" ><i class="fas fa-download"></i> Unduh</a>
              </div>
              <div class="col-sm-6 text-sm-right">
                <a class="btn btn-primary d-block d-sm-inline-block mt-3" href="{{route('edit_article',['id'=>$data_article->id]) }}">
                  <i class="fas fa-pen"></i> Sunting
                </a>
                <form class="d-sm-inline-block" action="{{route('delete_article',['id'=>$data_article->id])}} " method="post" >
                  @method('delete')
                  @csrf
                  <button onclick="return confirm('yakin data ingin di hapus?');" type="submit" class="btn btn-danger d-block d-sm-inline-block mt-3 w-100 w-sm-auto">
                    <i class="fas fa-trash"></i> Hapus
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
        
        @endforeach

        {{ $data->links('pagination::bootstrap-4') }}
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


