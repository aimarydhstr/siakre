@extends('layout.base')
@section('title','Daftar Pengguna')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <h4 class="font-weight-bold my-3 mt-md-4">Data Pengguna</h4>

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
            <div>Total Pengguna : {{ method_exists($users,'total') ? $users->total() : $users->count() }}</div>
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-add-user">
              <i class="fa fa-plus mr-1"></i> Tambah Pengguna
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width:64px;">#</th>
                  <th>Nama</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Prodi</th>
                  <th>Fakultas</th>
                  <th style="width:160px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($users as $i => $row)
                  @php
                    $deptName = optional(optional($row->department_head)->department)->name ?? '-';
                    $facName  = optional(optional($row->faculty_head)->faculty)->name;
                    $faculty = optional(optional($row->department_head)->department)->faculty->name ?? '-';
                  @endphp
                  <tr>
                    <td>{{ ($users->currentPage()-1)*$users->perPage() + $i + 1 }}</td>
                    <td class="text-break">{{ $row->name }}</td>
                    <td class="text-break">{{ $row->email }}</td>
                    <td class="text-capitalize">{{ str_replace('_',' ',$row->role) }}</td>
                    <td class="text-break">{{ $deptName }}</td>
                    <td class="text-break">{{ $facName ? $facName : $faculty }}</td>
                    <td>
                      <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modal-edit-user-{{ $row->id }}">
                        <i class="fa fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-delete-user-{{ $row->id }}">
                        <i class="fa fa-trash"></i>
                      </button>
                    </td>
                  </tr>

                  {{-- Modal Edit (unik per baris) --}}
                  <div class="modal fade" id="modal-edit-user-{{ $row->id }}" tabindex="-1" role="dialog" aria-labelledby="label-edit-user-{{ $row->id }}" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                      <div class="modal-content">
                        <form action="{{ route('users.update', $row->id) }}" method="POST">
                          @csrf
                          @method('PUT')
                          <input type="hidden" name="_from" value="edit-{{ $row->id }}">
                          <div class="modal-header">
                            <h5 class="modal-title" id="label-edit-user-{{ $row->id }}">Edit Pengguna</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <div class="form-group">
                              <label for="name-edit-{{ $row->id }}">Nama <span class="text-danger">*</span></label>
                              <input type="text"
                                     id="name-edit-{{ $row->id }}"
                                     name="name"
                                     class="form-control @error('name') is-invalid @enderror"
                                     value="{{ old('name', $row->name) }}"
                                     required autocomplete="off">
                              @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="email-edit-{{ $row->id }}">Email <span class="text-danger">*</span></label>
                              <input type="email"
                                     id="email-edit-{{ $row->id }}"
                                     name="email"
                                     class="form-control @error('email') is-invalid @enderror"
                                     value="{{ old('email', $row->email) }}"
                                     required autocomplete="off">
                              @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="role-edit-{{ $row->id }}">Role <span class="text-danger">*</span></label>
                              <select id="role-edit-{{ $row->id }}"
                                      name="role"
                                      class="form-control custom-select @error('role') is-invalid @enderror"
                                      required>
                                @foreach($roles as $r)
                                  <option value="{{ $r }}" {{ (string)old('role', $row->role) === (string)$r ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_',' ', $r)) }}
                                  </option>
                                @endforeach
                              </select>
                              @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Prodi (untuk department_head) --}}
                            @php
                              $currentDeptId = old('department_id', optional($row->department_head)->department_id ?? '');
                            @endphp
                            <div class="form-group" id="wrap-dept-edit-{{ $row->id }}">
                              <label for="department-id-edit-{{ $row->id }}">Program Studi <span class="text-danger">*</span></label>
                              <select id="department-id-edit-{{ $row->id }}"
                                      name="department_id"
                                      class="form-control custom-select @error('department_id') is-invalid @enderror">
                                <option value="" disabled {{ $currentDeptId === '' ? 'selected' : '' }}>Pilih Prodi</option>
                                @foreach($departments as $dept)
                                  <option value="{{ $dept->id }}" {{ (string)$currentDeptId === (string)$dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                  </option>
                                @endforeach
                              </select>
                              @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Fakultas (untuk faculty_head) --}}
                            @php
                              $currentFacId = old('faculty_id', optional($row->faculty_head)->faculty_id ?? '');
                            @endphp
                            <div class="form-group" id="wrap-fac-edit-{{ $row->id }}">
                              <label for="faculty-id-edit-{{ $row->id }}">Fakultas <span class="text-danger">*</span></label>
                              <select id="faculty-id-edit-{{ $row->id }}"
                                      name="faculty_id"
                                      class="form-control custom-select @error('faculty_id') is-invalid @enderror">
                                <option value="" disabled {{ $currentFacId === '' ? 'selected' : '' }}>Pilih Fakultas</option>
                                @foreach(($faculties ?? []) as $fac)
                                  <option value="{{ $fac->id }}" {{ (string)$currentFacId === (string)$fac->id ? 'selected' : '' }}>
                                    {{ $fac->name }}
                                  </option>
                                @endforeach
                              </select>
                              @error('faculty_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="password-edit-{{ $row->id }}">Password (opsional)</label>
                              <input type="password"
                                     id="password-edit-{{ $row->id }}"
                                     name="password"
                                     class="form-control @error('password') is-invalid @enderror"
                                     autocomplete="new-password"
                                     placeholder="Biarkan kosong jika tidak diubah">
                              @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="password-confirm-edit-{{ $row->id }}">Ulangi Password (opsional)</label>
                              <input type="password"
                                     id="password-confirm-edit-{{ $row->id }}"
                                     name="password_confirmation"
                                     class="form-control @error('password_confirmation') is-invalid @enderror"
                                     autocomplete="new-password"
                                     placeholder="Ulangi password jika mengubah">
                              @error('password_confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                  <div class="modal fade" id="modal-delete-user-{{ $row->id }}" tabindex="-1" role="dialog" aria-labelledby="label-delete-user-{{ $row->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                      <div class="modal-content">
                        <form action="{{ route('users.destroy', $row->id) }}" method="POST">
                          @csrf
                          @method('DELETE')
                          <div class="modal-header">
                            <h5 class="modal-title text-danger" id="label-delete-user-{{ $row->id }}">Hapus Pengguna</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <p class="mb-0">Yakin ingin menghapus <strong>{{ $row->name }}</strong>? Tindakan ini tidak dapat dibatalkan.</p>
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
                    <td colspan="7" class="text-center">Belum ada data.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          {{-- Pagination --}}
          @if(method_exists($users,'links'))
            <div class="mt-3">
              {{ $users->withQueryString()->links() }}
            </div>
          @endif

        </div>
      </div>

    </div>

    @include('template.footer')
  </div>
</div>

{{-- Modal Add (tunggal) --}}
<div class="modal fade" id="modal-add-user" tabindex="-1" role="dialog" aria-labelledby="label-add-user" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="{{ route('users.store') }}" method="POST">
        @csrf
        <input type="hidden" name="_from" value="create">
        <div class="modal-header">
          <h5 class="modal-title" id="label-add-user">Tambah Pengguna</h5>
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
                   required autocomplete="off"
                   placeholder="Masukkan nama">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

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

          <div class="form-group">
            <label for="role-add">Role <span class="text-danger">*</span></label>
            <select id="role-add"
                    name="role"
                    class="form-control custom-select @error('role') is-invalid @enderror"
                    required>
              <option value="" disabled {{ old('role') ? '' : 'selected' }}>Pilih Role</option>
              @foreach($roles as $r)
                <option value="{{ $r }}" {{ (string)old('role') === (string)$r ? 'selected' : '' }}>
                  {{ ucfirst(str_replace('_',' ', $r)) }}
                </option>
              @endforeach
            </select>
            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          {{-- Prodi (khusus department_head) --}}
          <div class="form-group" id="wrap-dept-add">
            <label for="department-id-add">Program Studi <span class="text-danger">*</span></label>
            <select id="department-id-add"
                    name="department_id"
                    class="form-control custom-select @error('department_id') is-invalid @enderror">
              <option value="" disabled {{ old('department_id') ? '' : 'selected' }}>Pilih Prodi</option>
              @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ (string)old('department_id') === (string)$dept->id ? 'selected' : '' }}>
                  {{ $dept->name }}
                </option>
              @endforeach
            </select>
            @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          {{-- Fakultas (khusus faculty_head) --}}
          <div class="form-group" id="wrap-fac-add">
            <label for="faculty-id-add">Fakultas <span class="text-danger">*</span></label>
            <select id="faculty-id-add"
                    name="faculty_id"
                    class="form-control custom-select @error('faculty_id') is-invalid @enderror">
              <option value="" disabled {{ old('faculty_id') ? '' : 'selected' }}>Pilih Fakultas</option>
              @foreach(($faculties ?? []) as $fac)
                <option value="{{ $fac->id }}" {{ (string)old('faculty_id') === (string)$fac->id ? 'selected' : '' }}>
                  {{ $fac->name }}
                </option>
              @endforeach
            </select>
            @error('faculty_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

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
  /**
   * Tampilkan/sembunyikan select Prodi/Fakultas berdasarkan role.
   * - department_head => tampilkan Prodi, sembunyikan Fakultas
   * - faculty_head    => tampilkan Fakultas, sembunyikan Prodi
   * - admin           => sembunyikan keduanya
   */
  function bindRoleToggles(roleId, deptWrapId, deptSelId, facWrapId, facSelId) {
    const roleEl = document.getElementById(roleId);
    const deptWrap = document.getElementById(deptWrapId);
    const deptSel  = document.getElementById(deptSelId);
    const facWrap  = document.getElementById(facWrapId);
    const facSel   = document.getElementById(facSelId);
    if (!roleEl) return;

    function hide(el){ if(el){ el.classList.add('d-none'); } }
    function show(el){ if(el){ el.classList.remove('d-none'); } }

    function apply(){
      const val = roleEl.value;
      if (val === 'department_head') {
        show(deptWrap); if (deptSel) deptSel.disabled = false;
        hide(facWrap);  if (facSel)  facSel.disabled  = true;
      } else if (val === 'faculty_head') {
        hide(deptWrap); if (deptSel) deptSel.disabled = true;
        show(facWrap);  if (facSel)  facSel.disabled  = false;
      } else {
        hide(deptWrap); if (deptSel) deptSel.disabled = true;
        hide(facWrap);  if (facSel)  facSel.disabled  = true;
      }
    }

    roleEl.addEventListener('change', apply);
    apply();
  }

  // Modal Add
  bindRoleToggles('role-add','wrap-dept-add','department-id-add','wrap-fac-add','faculty-id-add');

  // Modal Edit (per user)
  @foreach($users as $row)
    bindRoleToggles(
      'role-edit-{{ $row->id }}',
      'wrap-dept-edit-{{ $row->id }}', 'department-id-edit-{{ $row->id }}',
      'wrap-fac-edit-{{ $row->id }}',  'faculty-id-edit-{{ $row->id }}'
    );
  @endforeach

  // Autofocus saat modal terbuka
  $('#modal-add-user').on('shown.bs.modal', function () {
    $('#name-add').trigger('focus');
  });
  @foreach($users as $row)
    $('#modal-edit-user-{{ $row->id }}').on('shown.bs.modal', function () {
      $('#name-edit-{{ $row->id }}').trigger('focus');
    });
  @endforeach

  // Auto-buka modal sesuai sumber error (_from)
  @if ($errors->any() && old('_from'))
    const from = @json(old('_from'));
    if (from === 'create') {
      $('#modal-add-user').modal('show');
    } else if (from.startsWith('edit-')) {
      const id = from.split('-')[1];
      $('#modal-edit-user-' + id).modal('show');
    }
  @endif
</script>
@endsection
