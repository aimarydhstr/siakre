
@extends ('layout.base')
@section('title','Sunting')
@section('nav')
<div id="auth">
  <div class="container" >
    <div class="row">
      <div class="col-sm-9 col-md-7 col-lg-5 mx-auto bg-white shadow border rounded my-sm-5 p-4">

        <div class="text-center mb-4">
          <img src="https://uhb.ac.id/wp-content/uploads/2024/03/logo_UHB_r-1.png" class="my-3" width="125" height="125" class="center-image" >
          <h5>Edit Profil</h5>
        </div>
        @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if (session('alert'))
                <div class="alert alert-danger">
                    {{ session('alert') }}
                </div>
            @endif
        <form action="{{ route('user-update') }}" method="post">
          {{ csrf_field() }}
            <div class="form-group">
              <label for="name">Nama</label>
              <input type="name" class="form-control" id="name" name="name" value="{{$user->name}}" require>
            </div>
            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" class="form-control" id="email" name="email" value="{{$user->email}}" require >
            </div>
            <div class="form-group">
              <label for="password">Kata Sandi Baru</label>
              <input type="password" class="form-control" id="password" name="password" >
            </div>
            <div class="form-group">
              <label for="confirmation">Ulangi Kata Sandi</label>
              <input type="password" class="form-control" id="confirmation" name="confirmation" >
            </div>
            <div class="form-group">
              <label for="password_confirm" class="text-danger font-weight-bold"><i>Konfirmasi perubahan dengan kata sandi lama</i></label>
              <input type="password" class="form-control" id="password_confirm" name="password_confirm" require>
            </div>
            <div class="form-group">
              <button type="button" class="btn btn-primary btn-md btn-block mt-4 p-2" data-toggle="modal" data-target="#confirm">Ubah</button>
            </div>
            <div class="form-group text-center mt-2">
            <a class="text-decoration-none" href="{{url('/')}}">Kembali</a>
            </div>

            <div class="modal fade" tabindex="-1" role="dialog" id="confirm">
              <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                  <div class="modal-body p-4">
                    <h5 class="modal-title font-weight-bold text-uppercase text-danger mb-2">
                      Perhatian !!!
                    </h5>
                    <p>Jika Anda melakukan perubahan, maka Anda harus login ulang.</p>
                    <div class="mt-3 text-right">
                      <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Kembali</button>
                      <button type="submit" class="btn btn-primary ml-2">Simpan Perubahan</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
        </form>

      </div>
    </div>
  </div>
</div>
@endsection
@section('js')
<script>
const input = document.querySelector('#password')
const confirm = document.querySelector('#confirmation')
confirm.setAttribute("disabled", true)

input.addEventListener('input', evt => {
  const value = input.value
  
  if (!value) {
    confirm.setAttribute("disabled", true);
    confirm.value="";
    return
  }
  else{
    confirm.removeAttribute("disabled");
   
  }
})
</script>
@endsection