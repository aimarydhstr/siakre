@extends ('layout.base')
@section('title','Artikel Peserta')
@section('nav')
<div class="d-flex " id="wrapper" >
    <!-- Sidebar -->
   @include('template.sidebar')
    <!-- /#sidebar-wrapper -->

  <!-- Page Content -->
    
    <div id="page-content-wrapper" class="site">
    {{ Breadcrumbs::render('add') }}

      @include('template.nav')

      <div class="content site-content">

        <h4 class="font-weight-bold my-3 mt-md-4">
          Seleksi Kategori 
        </h4>

        <div class="card rounded shadow mt-3 mb-5">
          <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

              <!-- <input type="radio" name="csr_selector" id="SelectorShow" value="1" />
              <label for="SelectorShow">Show</label>
              <input type="radio" name="csr_selector" id="csSelectorHideAll" value="2" checked="checked"/>
              <label for="csSelectorHideAll">Hide</label> -->

              <form action="{{ route('article-select-send')}}" method="post" enctype="multipart/form-data" class="add">
                {{ csrf_field() }}
                
               
                <div class="row" >    
                  <div class="col-md-6">
                      <div class="form-group input-group-sm ">
                          <label for="category">Kategori Masukan <span>&#42;</span></label>
                          <select class="form-control custom-select @error('category') is-invalid @enderror" 
                            id="category" name="category"  autocomplete="off"> 
                            <option value="dosen">Hanya Dosen</option>
                            <option  value="mahasiswa">Dosen | Mahasiswa</option>
                         
                          </select>
                          @error('category')<div class="invalid-feedback"> {{$message}} </div> @enderror
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group input-group-sm ">
                          <label for="valueDosen">Jumlah Dosen <span>&#42;</span></label>
                          <select class="form-control custom-select @error('valueDosen') is-invalid @enderror" 
                            id="valueDosen" name="valueDosen"  autocomplete="off"> 
                            <option  value="0" hidden>Masukan jumlah</option>
                            <option  value="1">1</option>
                            <option  value="2">2</option>
                            <option  value="3">3</option>
                            <option  value="4">4</option>
                            <option  value="5">5</option>
                          </select>
                          @error('valueDosen')<div class="invalid-feedback"> {{$message}} </div> @enderror
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group input-group-sm ">
                          <label for="valuePeserta">Jumlah Mahasiswa <span>&#42;</span></label>
                          <select class="form-control custom-select @error('valuePeserta') is-invalid @enderror" 
                            id="valuePeserta" name="valuePeserta"  autocomplete="off"> 
                            <option  value="0" hidden>Masukan jumlah</option>
                            <option  value="1">1</option>
                            <option  value="2">2</option>
                            <option  value="3">3</option>
                            <option  value="4">4</option>
                            <option  value="5">5</option>
                            <option  value="6">6</option>
                            <option  value="7">7</option>
                            <option  value="8">8</option>
                            <option  value="9">9</option>
                            <option  value="10">10</option>
                          </select>
                          @error('valuePeserta')<div class="invalid-feedback"> {{$message}} </div> @enderror
                      </div>
                    </div>
                </div>
               
                <div class="row mt-3">
                  <div class="col-md">
                    <div class="form-group">
                      <i>
                        <span>&#42;</span>
                        <small>Wajib diisi</small>
                      </i>
                    </div>
                  </div>
                  <div class="col-md">
                      <div class="form-group text-md-right">
                          <button type="submit" class="btn btn-md btn-primary">Pilih</button>
                          <!-- <a href=" {{url('user/login')}} "  class="btn btn-md btn-danger">Cancel</a> -->
                      </div>
                  </div>
                </div>  

              </form>
            
            
            </div>
          </div>
        </div>
        <!-- akhir container -->
      
        @include('template.footer')
        
      </div>
    </div>
  <!-- /#page-content-wrapper -->
  @endsection

  @section('js')
  <script>
    var mahasiswa =  document.getElementById("valuePeserta");
    var dosen =  document.getElementById("valueDosen");

    window.onload=check;
    function check() {
      mahasiswa.disabled = true;
      dosen.value = "1";
    }
    
    document.getElementById('category').onchange = function() {
        var mahasiswaSelected = (typeof this.selectedIndex === "undefined" ? window.event.srcElement : this);
        var value = mahasiswaSelected.value || mahasiswaSelected.options[mahasiswaSelected.selectedIndex].value;

        var dosenSelected = (typeof this.selectedIndex === "undefined" ? window.event.srcElement : this);
        var value = dosenSelected.value || dosenSelected.options[dosenSelected.selectedIndex].value;
        if ( value == "dosen" ) {
          mahasiswa.disabled = true;
          mahasiswa.value = "0";

          dosen.disabled = false;
          dosen.value = "1";
         
        } else {
          mahasiswa.disabled = false;
          mahasiswa.value = "1";

          dosen.value = "1";
          dosen.disabled = true;
        
        }
    }
  </script>
       
@endsection

