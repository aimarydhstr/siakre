
@extends ('layout.base')
@section('title','Login')
@section('nav')
<div id="auth">
  <div class="container">
    <div class="row">
      <div class="col-sm-9 col-md-7 col-lg-5 mx-auto bg-white shadow border rounded my-sm-5 p-4"> 
        <div class="text-center mb-4">
          <img src="https://uhb.ac.id/wp-content/uploads/2024/03/logo_UHB_r-1.png" class="my-3" width="125" height="125" class="center-image" >
          <h5>Login Akun</h5>
        </div>
            @if (session('alert'))
                <div class="alert alert-danger">
                    {{ session('alert') }}
                </div>
            @endif
            @if (session('alert-success'))
                <div class="alert alert-success">
                    {{ session('alert-success') }}
                </div>
            @endif
        <form action="{{route('login-send')  }}" method="post">
            {{ csrf_field() }}
            
            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
              <label for="password">Kata Sandi</label>
              <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
              <button type="submit" class="btn btn-primary btn-md btn-block mt-4 p-2">Masuk</button>
            </div>
            <div class="form-group text-center mt-5">
              <p class="mb-1">Belum memiliki akun? <a href="{{ route('registration') }}">Daftar Sekarang</a></p>
              <!-- Silakan hubungi <a class="text-decoration-none" href="https://wa.me/6281234567890?text=Permisi...">Admin</a> -->
            </div>
        </form>

      </div>
    </div>
  </div>
</div>
@endsection