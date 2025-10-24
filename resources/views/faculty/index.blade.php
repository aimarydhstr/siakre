@extends('layout.base')
@section('title','Daftar Fakultas')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <h4 class="font-weight-bold my-3 mt-md-4">Data Fakultas</h4>

      {{-- Flash message --}}
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      {{-- Error global (opsional) --}}
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="card rounded shadow mt-3 mb-5">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>Total Fakultas : {{ $faculties->count() }}</div>
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-add-faculty">
              <i class="fa fa-plus mr-1"></i> Tambah Fakultas
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width: 64px;">#</th>
                  <th>Nama Fakultas</th>
                  <th style="width: 160px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($faculties as $i => $faculty)
                  <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="text-break">{{ $faculty->name }}</td>
                    <td>
                      <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modal-edit-faculty-{{ $faculty->id }}">
                        <i class="fa fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-delete-faculty-{{ $faculty->id }}">
                        <i class="fa fa-trash"></i>
                      </button>
                    </td>
                  </tr>

                  {{-- Modal Edit (unik per baris) --}}
                  <div class="modal fade" id="modal-edit-faculty-{{ $faculty->id }}" tabindex="-1" role="dialog" aria-labelledby="label-edit-faculty-{{ $faculty->id }}" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                      <div class="modal-content">
                        <form action="{{ route('faculties.update', $faculty->id) }}" method="POST">
                          @csrf
                          @method('PUT')
                          <div class="modal-header">
                            <h5 class="modal-title" id="label-edit-faculty-{{ $faculty->id }}">Edit Fakultas</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <div class="form-group">
                              <label for="name-edit-{{ $faculty->id }}">Nama <span class="text-danger">*</span></label>
                              <input type="text"
                                     id="name-edit-{{ $faculty->id }}"
                                     name="name"
                                     class="form-control @error('name') is-invalid @enderror"
                                     value="{{ old('name', $faculty->name) }}"
                                     required
                                     autocomplete="off">
                              @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                              @enderror
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>

                  {{-- Modal Delete (unik per baris) --}}
                  <div class="modal fade" id="modal-delete-faculty-{{ $faculty->id }}" tabindex="-1" role="dialog" aria-labelledby="label-delete-faculty-{{ $faculty->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                      <div class="modal-content">
                        <form action="{{ route('faculties.destroy', $faculty->id) }}" method="POST">
                          @csrf
                          @method('DELETE')
                          <div class="modal-header">
                            <h5 class="modal-title text-danger" id="label-delete-faculty-{{ $faculty->id }}">Hapus Fakultas</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <p class="mb-0">Yakin ingin menghapus <strong>{{ $faculty->name }}</strong>? Tindakan ini tidak dapat dibatalkan.</p>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Hapus</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>

                @empty
                  <tr>
                    <td colspan="3" class="text-center">Belum ada data.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>

    @include('template.footer')
  </div>
</div>

{{-- Modal Add (tunggal) --}}
<div class="modal fade" id="modal-add-faculty" tabindex="-1" role="dialog" aria-labelledby="label-add-faculty" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="{{ route('faculties.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="label-add-faculty">Tambah Fakultas</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="name-add">Nama <span class="text-danger">*</span></label>
            <input type="text"
                   id="name-add"
                   name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}"
                   required
                   autocomplete="off"
                   placeholder="Masukkan nama fakultas">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('js')
<script>
  // Autofocus input ketika modal ditampilkan
  $('#modal-add-faculty').on('shown.bs.modal', function () {
    $('#name-add').trigger('focus');
  });

  @foreach($faculties as $faculty)
    $('#modal-edit-faculty-{{ $faculty->id }}').on('shown.bs.modal', function () {
      $('#name-edit-{{ $faculty->id }}').trigger('focus');
    });
  @endforeach

  // Jika ingin auto-buka modal Add saat ada error validasi dari create
  @if ($errors->any() && old('_from') === 'create')
    $('#modal-add-faculty').modal('show');
  @endif
</script>
@endsection
