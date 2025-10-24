@extends('layout.base')
@section('title','Daftar Program Studi')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <h4 class="font-weight-bold my-3 mt-md-4">Data Program Studi</h4>

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
            
            <div>Total Prodi : {{ method_exists($departments,'total') ? $departments->total() : $departments->count() }}</div>
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-add-department">
              <i class="fa fa-plus mr-1"></i> Tambah Prodi
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width: 64px;">#</th>
                  <th>Nama Program Studi</th>
                  <th>Fakultas</th>
                  <th style="width: 160px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($departments as $i => $department)
                  <tr>
                    <td>{{ ($departments->currentPage()-1)*$departments->perPage() + $i + 1 }}</td>
                    <td class="text-break">{{ $department->name }}</td>
                    <td class="text-break">{{ optional($department->faculty)->name ?? '-' }}</td>
                    <td>
                      <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modal-edit-department-{{ $department->id }}">
                        <i class="fa fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-delete-department-{{ $department->id }}">
                        <i class="fa fa-trash"></i>
                      </button>
                    </td>
                  </tr>

                  {{-- Modal Edit (unik per baris) --}}
                  <div class="modal fade" id="modal-edit-department-{{ $department->id }}" tabindex="-1" role="dialog" aria-labelledby="label-edit-department-{{ $department->id }}" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                      <div class="modal-content">
                        <form action="{{ route('departments.update', $department->id) }}" method="POST">
                          @csrf
                          @method('PUT')
                          <div class="modal-header">
                            <h5 class="modal-title" id="label-edit-department-{{ $department->id }}">Edit Program Studi</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <div class="form-group">
                              <label for="name-edit-{{ $department->id }}">Nama <span class="text-danger">*</span></label>
                              <input type="text"
                                     id="name-edit-{{ $department->id }}"
                                     name="name"
                                     class="form-control @error('name') is-invalid @enderror"
                                     value="{{ old('name', $department->name) }}"
                                     required
                                     autocomplete="off">
                              @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                              @enderror
                            </div>

                            <div class="form-group">
                              <label for="faculty-id-edit-{{ $department->id }}">Fakultas <span class="text-danger">*</span></label>
                              <select id="faculty-id-edit-{{ $department->id }}"
                                      name="faculty_id"
                                      class="form-control custom-select @error('faculty_id') is-invalid @enderror"
                                      required>
                                <option disabled value="">Pilih Fakultas</option>
                                @foreach($faculties as $faculty)
                                  <option value="{{ $faculty->id }}"
                                    {{ (string)old('faculty_id', $department->faculty_id) === (string)$faculty->id ? 'selected' : '' }}>
                                    {{ $faculty->name }}
                                  </option>
                                @endforeach
                              </select>
                              @error('faculty_id')
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
                  <div class="modal fade" id="modal-delete-department-{{ $department->id }}" tabindex="-1" role="dialog" aria-labelledby="label-delete-department-{{ $department->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                      <div class="modal-content">
                        <form action="{{ route('departments.destroy', $department->id) }}" method="POST">
                          @csrf
                          @method('DELETE')
                          <div class="modal-header">
                            <h5 class="modal-title text-danger" id="label-delete-department-{{ $department->id }}">Hapus Program Studi</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <p class="mb-0">Yakin ingin menghapus <strong>{{ $department->name }}</strong>? Tindakan ini tidak dapat dibatalkan.</p>
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
                    <td colspan="4" class="text-center">Belum ada data.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          {{-- Pagination --}}
          @if(method_exists($departments,'links'))
            <div class="mt-3">
              {{ $departments->withQueryString()->links() }}
            </div>
          @endif

        </div>
      </div>

    </div>

    @include('template.footer')
  </div>
</div>

{{-- Modal Add (tunggal) --}}
<div class="modal fade" id="modal-add-department" tabindex="-1" role="dialog" aria-labelledby="label-add-department" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="{{ route('departments.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="label-add-department">Tambah Program Studi</h5>
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
                   placeholder="Masukkan nama Program Studi">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <label for="faculty-id-add">Fakultas <span class="text-danger">*</span></label>
            <select id="faculty-id-add"
                    name="faculty_id"
                    class="form-control custom-select @error('faculty_id') is-invalid @enderror"
                    required>
              <option value="" disabled {{ old('faculty_id') ? '' : 'selected' }}>Pilih Fakultas</option>
              @foreach($faculties as $faculty)
                <option value="{{ $faculty->id }}" {{ (string)old('faculty_id') === (string)$faculty->id ? 'selected' : '' }}>
                  {{ $faculty->name }}
                </option>
              @endforeach
            </select>
            @error('faculty_id')
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
  $('#modal-add-department').on('shown.bs.modal', function () {
    $('#name-add').trigger('focus');
  });

  @foreach($departments as $department)
    $('#modal-edit-department-{{ $department->id }}').on('shown.bs.modal', function () {
      $('#name-edit-{{ $department->id }}').trigger('focus');
    });
  @endforeach

  // Auto-buka modal Add saat ada error validasi dari create (opsional)
  @if ($errors->any() && old('_from') === 'create')
    $('#modal-add-department').modal('show');
  @endif
</script>
@endsection
