@extends ('layout.base')
@section('title','Jumlah Peserta')
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
          Jumlah Peserta
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

              <form action="{{ route('select-send')}}" method="post" enctype="multipart/form-data" class="add">
                {{ csrf_field() }}
                
               
                <div class="row" >    
                    <div class="col-md">
                      <div class="form-group input-group-sm ">
                          <label for="selection">Individu / Tim <span>&#42;</span></label>
                          <select class="form-control custom-select @error('selection') is-invalid @enderror" 
                            id="selection" name="selection" selected="single" autocomplete="off">
                            <option  value="Individu">Individu</option>
                            <option  value="Tim">Tim</option>
                          </select>
                          @error('selection')<div class="invalid-feedback"> {{$message}} </div> @enderror
                      </div>
                    </div>
                    <div class="col-md">
                      <div class="form-group input-group-sm ">
                          <label for="valueTeam">Jumlah Peserta <span>&#42;</span></label>
                          <select class="form-control custom-select @error('valueTeam') is-invalid @enderror" 
                            id="valueTeam" name="valueTeam" selected="1" autocomplete="off"> 
                            <option id="hide" value="1">1</option>
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
                          @error('valueTeam')<div class="invalid-feedback"> {{$message}} </div> @enderror
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
    var tim =  document.getElementById("valueTeam");

    window.onload=check;
    function check() {
      tim.disabled = true;
    }
    
    document.getElementById('selection').onchange = function() {
        var timSelected = (typeof this.selectedIndex === "undefined" ? window.event.srcElement : this);
        var value = timSelected.value || timSelected.options[timSelected.selectedIndex].value;
        if ( value == "Individu" ) {
          tim.disabled = true;
          tim.value = "1";
          document.getElementById("hide").hidden = false;
        } else {
          tim.disabled = false;
          tim.value = "2";
          document.getElementById("hide").hidden = true;
        }
    }
  </script>
       
@endsection

