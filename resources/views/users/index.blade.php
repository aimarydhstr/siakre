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
                  <th style="width:160px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($users as $i => $user)
                  @php
                    $deptName = '-';
                    if ($user->role !== 'admin') {
                      $deptName = optional(optional($user->department_head)->department)->name
                                  ?? optional(optional($user->lecturer)->department)->name
                                  ?? '-';
                    }
                  @endphp
                  <tr>
                    <td>{{ ($users->currentPage()-1)*$users->perPage() + $i + 1 }}</td>
                    <td class="text-break">{{ $user->name }}</td>
                    <td class="text-break">{{ $user->email }}</td>
                    <td class="text-capitalize">{{ str_replace('_',' ',$user->role) }}</td>
                    <td class="text-break">{{ $deptName }}</td>
                    <td>
                      <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modal-edit-user-{{ $user->id }}">
                        <i class="fa fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-delete-user-{{ $user->id }}">
                        <i class="fa fa-trash"></i>
                      </button>
                    </td>
                  </tr>

                  {{-- Modal Edit (unik per baris) --}}
                  <div class="modal fade" id="modal-edit-user-{{ $user->id }}" tabindex="-1" role="dialog" aria-labelledby="label-edit-user-{{ $user->id }}" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                      <div class="modal-content">
                        <form action="{{ route('users.update', $user->id) }}" method="POST">
                          @csrf
                          @method('PUT')
                          <input type="hidden" name="_from" value="edit-{{ $user->id }}">
                          <div class="modal-header">
                            <h5 class="modal-title" id="label-edit-user-{{ $user->id }}">Edit Pengguna</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <div class="form-group">
                              <label for="name-edit-{{ $user->id }}">Nama <span class="text-danger">*</span></label>
                              <input type="text"
                                     id="name-edit-{{ $user->id }}"
                                     name="name"
                                     class="form-control @error('name') is-invalid @enderror"
                                     value="{{ old('name', $user->name) }}"
                                     required autocomplete="off">
                              @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="email-edit-{{ $user->id }}">Email <span class="text-danger">*</span></label>
                              <input type="email"
                                     id="email-edit-{{ $user->id }}"
                                     name="email"
                                     class="form-control @error('email') is-invalid @enderror"
                                     value="{{ old('email', $user->email) }}"
                                     required autocomplete="off">
                              @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="role-edit-{{ $user->id }}">Role <span class="text-danger">*</span></label>
                              <select id="role-edit-{{ $user->id }}"
                                      name="role"
                                      class="form-control custom-select @error('role') is-invalid @enderror"
                                      required>
                                @foreach($roles as $r)
                                  <option value="{{ $r }}" {{ (string)old('role', $user->role) === (string)$r ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_',' ', $r)) }}
                                  </option>
                                @endforeach
                              </select>
                              @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group" id="wrap-dept-edit-{{ $user->id }}">
                              <label for="department-id-edit-{{ $user->id }}">Program Studi <span class="text-danger">*</span></label>
                              <select id="department-id-edit-{{ $user->id }}"
                                      name="department_id"
                                      class="form-control custom-select @error('department_id') is-invalid @enderror">
                                <option value="" disabled {{ old('department_id', ($user->departmentHead->department_id ?? $user->lecturer->department_id ?? '')) === '' ? 'selected' : '' }}>Pilih Prodi</option>
                                @foreach($departments as $dept)
                                  <option value="{{ $dept->id }}"
                                    @php
                                      $currentDept = old('department_id',
                                        $user->departmentHead->department_id ?? $user->lecturer->department_id ?? ''
                                      );
                                    @endphp
                                    {{ (string)$currentDept === (string)$dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                  </option>
                                @endforeach
                              </select>
                              @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="password-edit-{{ $user->id }}">Password (opsional)</label>
                              <input type="password"
                                     id="password-edit-{{ $user->id }}"
                                     name="password"
                                     class="form-control @error('password') is-invalid @enderror"
                                     autocomplete="new-password"
                                     placeholder="Biarkan kosong jika tidak diubah">
                              @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Ulangi Password (opsional) --}}
                            <div class="form-group">
                              <label for="password-confirm-edit-{{ $user->id }}">Ulangi Password (opsional)</label>
                              <input type="password"
                                     id="password-confirm-edit-{{ $user->id }}"
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
                  <div class="modal fade" id="modal-delete-user-{{ $user->id }}" tabindex="-1" role="dialog" aria-labelledby="label-delete-user-{{ $user->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                      <div class="modal-content">
                        <form action="{{ route('users.destroy', $user->id) }}" method="POST">
                          @csrf
                          @method('DELETE')
                          <div class="modal-header">
                            <h5 class="modal-title text-danger" id="label-delete-user-{{ $user->id }}">Hapus Pengguna</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <p class="mb-0">Yakin ingin menghapus <strong>{{ $user->name }}</strong>? Tindakan ini tidak dapat dibatalkan.</p>
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
                    <td colspan="6" class="text-center">Belum ada data.</td>
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
  // Helper: tampil/sembunyikan select Prodi berdasarkan role
  function bindRoleToggle(roleId, deptId, wrapperId) {
    const roleEl = document.getElementById(roleId);
    const deptEl = document.getElementById(deptId);
    const wrapEl = document.getElementById(wrapperId);
    if (!roleEl || !deptEl || !wrapEl) return;

    function apply() {
      const needDept = roleEl.value === 'department_head' || roleEl.value === 'lecturer';
      if (needDept) {
        wrapEl.classList.remove('d-none');
        deptEl.disabled = false;
      } else {
        wrapEl.classList.add('d-none');
        deptEl.disabled = true;
      }
    }

    roleEl.addEventListener('change', apply);
    apply();
  }

  // Modal Add
  bindRoleToggle('role-add','department-id-add','wrap-dept-add');

  // Modal Edit (per user)
  @foreach($users as $user)
    bindRoleToggle('role-edit-{{ $user->id }}','department-id-edit-{{ $user->id }}','wrap-dept-edit-{{ $user->id }}');
  @endforeach

  // Autofocus saat modal terbuka
  $('#modal-add-user').on('shown.bs.modal', function () {
    $('#name-add').trigger('focus');
  });
  @foreach($users as $user)
    $('#modal-edit-user-{{ $user->id }}').on('shown.bs.modal', function () {
      $('#name-edit-{{ $user->id }}').trigger('focus');
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
