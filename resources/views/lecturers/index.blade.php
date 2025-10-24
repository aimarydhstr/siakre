@extends('layout.base')
@section('title','Dosen Prodi Saya')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <h4 class="font-weight-bold my-3 mt-md-4">Dosen di Program Studi Saya</h4>

      {{-- Flash message --}}
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      {{-- Error global --}}
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
            <div>Total Dosen: {{ method_exists($lecturers,'total') ? $lecturers->total() : $lecturers->count() }}</div>
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-add-lecturer">
              <i class="fa fa-plus mr-1"></i> Tambah Dosen
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width:64px;">#</th>
                  <th>Nama</th>
                  <th>Email</th>
                  <th style="width:160px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($lecturers as $i => $lec)
                  <tr>
                    <td>{{ ($lecturers->currentPage()-1)*$lecturers->perPage() + $i + 1 }}</td>
                    <td class="text-break">{{ optional($lec->user)->name }}</td>
                    <td class="text-break">{{ optional($lec->user)->email }}</td>
                    <td>
                      <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modal-edit-lecturer-{{ $lec->id }}">
                        <i class="fa fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-delete-lecturer-{{ $lec->id }}">
                        <i class="fa fa-trash"></i>
                      </button>
                    </td>
                  </tr>

                  {{-- Modal Edit (unik per baris) --}}
                  <div class="modal fade" id="modal-edit-lecturer-{{ $lec->id }}" tabindex="-1" role="dialog" aria-labelledby="label-edit-lecturer-{{ $lec->id }}" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                      <div class="modal-content">
                        <form action="{{ route('lecturers.update', $lec->id) }}" method="POST">
                          @csrf
                          @method('PUT')
                          <input type="hidden" name="_from" value="edit-{{ $lec->id }}">
                          <div class="modal-header">
                            <h5 class="modal-title" id="label-edit-lecturer-{{ $lec->id }}">Edit Dosen</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <div class="form-group">
                              <label for="name-edit-{{ $lec->id }}">Nama <span class="text-danger">*</span></label>
                              <input type="text"
                                     id="name-edit-{{ $lec->id }}"
                                     name="name"
                                     class="form-control @error('name') is-invalid @enderror"
                                     value="{{ old('name', optional($lec->user)->name) }}"
                                     required autocomplete="off">
                              @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="email-edit-{{ $lec->id }}">Email <span class="text-danger">*</span></label>
                              <input type="email"
                                     id="email-edit-{{ $lec->id }}"
                                     name="email"
                                     class="form-control @error('email') is-invalid @enderror"
                                     value="{{ old('email', optional($lec->user)->email) }}"
                                     required autocomplete="off">
                              @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="password-edit-{{ $lec->id }}">Password (opsional)</label>
                              <input type="password"
                                     id="password-edit-{{ $lec->id }}"
                                     name="password"
                                     class="form-control @error('password') is-invalid @enderror"
                                     autocomplete="new-password"
                                     placeholder="Biarkan kosong jika tidak diubah">
                              @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="password-confirm-edit-{{ $lec->id }}">Ulangi Password (opsional)</label>
                              <input type="password"
                                     id="password-confirm-edit-{{ $lec->id }}"
                                     name="password_confirmation"
                                     class="form-control @error('password_confirmation') is-invalid @enderror"
                                     autocomplete="new-password"
                                     placeholder="Ulangi password jika mengubah">
                              @error('password_confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <small class="text-muted d-block">
                              * Program Studi dosen terkunci pada Prodi Anda.
                            </small>
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
                  <div class="modal fade" id="modal-delete-lecturer-{{ $lec->id }}" tabindex="-1" role="dialog" aria-labelledby="label-delete-lecturer-{{ $lec->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                      <div class="modal-content">
                        <form action="{{ route('lecturers.destroy', $lec->id) }}" method="POST">
                          @csrf
                          @method('DELETE')
                          <div class="modal-header">
                            <h5 class="modal-title text-danger" id="label-delete-lecturer-{{ $lec->id }}">Hapus Dosen</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <p class="mb-2">Yakin ingin menghapus dosen ini?</p>
                            <ul class="mb-0">
                              <li><strong>Nama:</strong> {{ optional($lec->user)->name }}</li>
                              <li><strong>Email:</strong> {{ optional($lec->user)->email }}</li>
                            </ul>
                            <small class="text-muted d-block mt-2">Tindakan ini juga akan menghapus akun pengguna dosen terkait.</small>
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
                    <td colspan="4" class="text-center">Belum ada dosen di prodi Anda.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          {{-- Pagination --}}
          @if(method_exists($lecturers,'links'))
            <div class="mt-3">
              {{ $lecturers->withQueryString()->links() }}
            </div>
          @endif

        </div>
      </div>

    </div>

    @include('template.footer')
  </div>
</div>

{{-- Modal Add (tunggal) --}}
<div class="modal fade" id="modal-add-lecturer" tabindex="-1" role="dialog" aria-labelledby="label-add-lecturer" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="{{ route('lecturers.store') }}" method="POST">
        @csrf
        <input type="hidden" name="_from" value="create">
        <div class="modal-header">
          <h5 class="modal-title" id="label-add-lecturer">Tambah Dosen</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          {{-- Nama --}}
          <div class="form-group">
            <label for="name-add">Nama <span class="text-danger">*</span></label>
            <input type="text"
                   id="name-add"
                   name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}"
                   required autocomplete="off"
                   placeholder="Masukkan nama dosen">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          {{-- Email --}}
          <div class="form-group">
            <label for="email-add">Email <span class="text-danger">*</span></label>
            <input type="email"
                   id="email-add"
                   name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}"
                   required autocomplete="off"
                   placeholder="nama@kampus.ac.id">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          {{-- Password --}}
          <div class="form-group">
            <label for="password-add">Password <span class="text-danger">*</span></label>
            <input type="password"
                   id="password-add"
                   name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="new-password"
                   placeholder="Minimal 6 karakter">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          {{-- Ulangi Password --}}
          <div class="form-group">
            <label for="password-confirm-add">Ulangi Password <span class="text-danger">*</span></label>
            <input type="password"
                   id="password-confirm-add"
                   name="password_confirmation"
                   class="form-control @error('password_confirmation') is-invalid @enderror"
                   required autocomplete="new-password"
                   placeholder="Ulangi password yang sama">
            @error('password_confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <small class="text-muted">
            * Dosen otomatis dimasukkan ke Program Studi Anda. Anda tidak perlu memilih Prodi.
          </small>
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
  // Autofocus saat modal tambah dibuka
  $('#modal-add-lecturer').on('shown.bs.modal', function () {
    $('#name-add').trigger('focus');
  });

  // Auto-buka modal yang error: create atau edit-{id}
  @if ($errors->any() && old('_from'))
    const from = @json(old('_from'));
    if (from === 'create') {
      $('#modal-add-lecturer').modal('show');
    } else if (from.startsWith('edit-')) {
      const id = from.split('-')[1];
      $('#modal-edit-lecturer-' + id).modal('show');
    }
  @endif
</script>
@endsection
