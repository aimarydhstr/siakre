
@extends ('layout.base')
@section('title','Pendaftaran')
@section('nav')
<div id="auth">
  <div class="container" >
    <div class="row">
      <div class="col-sm-9 col-md-7 col-lg-5 mx-auto bg-white shadow border rounded my-sm-5 p-4">

        <div class="text-center mb-4">
          <img src="https://uhb.ac.id/wp-content/uploads/2024/03/logo_UHB_r-1.png" class="my-3" width="125" height="125" class="center-image" >
          <h5>Daftar Akun</h5>
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
        <form action="{{ url('/user/regs/send') }}" method="post">
          {{ csrf_field() }}
            <div class="form-group">
              <label for="name">Nama</label>
              <input type="name" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
              <label for="password">Kata Sandi</label>
              <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
              <label for="confirmation">Ulangi Kata Sandi</label>
              <input type="password" class="form-control" id="confirmation" name="confirmation" required>
            </div>
            
            <div class="form-group">
              <label for="role">Role</label>
              <select class="form-control" id="role" name="role" required>
                <option value="lecturer">Dosen</option>
                <option value="department head">Kaprodi</option>
              </select>
            </div>
            <div class="form-group">
              <button type="submit" class="btn btn-primary btn-md btn-block mt-4 p-2">Daftar</button>
            </div>
            <div class="form-group text-center mt-5">
              <p class="mb-1">Sudah memiliki akun?</p>
              Silakan <a class="text-decoration-none" href="{{url('user/login')}}">Masuk</a>
            </div>
        </form>

      </div>
    </div>
  </div>
</div>
@endsection