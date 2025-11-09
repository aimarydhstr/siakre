@extends('layout.base')
@section('title','Dosen')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <h4 class="font-weight-bold my-3 mt-md-4">Daftar Dosen</h4>

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
            <div class="d-flex gap-2">
              <button class="btn btn-primary btn-sm mr-2" data-toggle="modal" data-target="#modal-add-lecturer">
                <i class="fa fa-plus mr-1"></i> Tambah Dosen
              </button>
              <a href="{{ route('lecturers.import.form') }}" class="btn btn-success btn-sm">
                <i class="fa fa-upload mr-1"></i> Import Data
              </a>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width:64px;">#</th>
                  <th>NIDN</th>
                  <th>Nama</th>
                  @if(in_array(Auth::user()->role, ['admin','faculty_head']))
                    <th>Program Studi</th>
                  @endif
                  <th>Jabatan</th>
                  <th>Bidang Keilmuan</th>
                  <th style="width:160px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($lecturers as $i => $lec)
                  @php
                    $field  = $lec->expertiseField;
                    $parent = optional($field)->expertise;
                  @endphp
                  <tr>
                    <td>{{ ($lecturers->currentPage()-1)*$lecturers->perPage() + $i + 1 }}</td>
                    <td class="text-break">{{ $lec->nidn ?? '—' }}</td>
                    <td class="text-break">{{ $lec->name ?? '—' }}</td>
                    @if(in_array(Auth::user()->role, ['admin','faculty_head']))
                      <td class="text-break">{{ optional($lec->department)->name ?? '—' }}</td>
                    @endif
                    <td>{{ $lec->position ?? '—' }}</td>
                    <td>
                      @if($parent || $field)
                        <span class="badge badge-light">{{ $parent->name ?? '—' }}</span>
                        <i class="fa fa-arrow-right mx-1 text-muted"></i>
                        <span class="badge badge-info">{{ $field->name ?? '—' }}</span>
                      @else
                        —
                      @endif
                    </td>
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
                    <div class="modal-dialog modal-lg" role="document">
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
                            <div class="row">
                              <div class="col-md-6">
                                {{-- Program Studi (hanya admin & faculty_head) --}}
                                @if(in_array(Auth::user()->role, ['admin','faculty_head']))
                                  <div class="form-group">
                                    <label for="department_id-edit-{{ $lec->id }}">Program Studi <span class="text-danger">*</span></label>
                                    <select id="department_id-edit-{{ $lec->id }}"
                                            name="department_id"
                                            class="form-control @error('department_id') is-invalid @enderror"
                                            required>
                                      <option value="">— Pilih Prodi —</option>
                                      @foreach(($departments ?? []) as $d)
                                        <option value="{{ $d->id }}"
                                          {{ (old('department_id') ?? $lec->department_id) == $d->id ? 'selected' : '' }}>
                                          {{ $d->name }}
                                        </option>
                                      @endforeach
                                    </select>
                                    @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                  </div>
                                @endif

                                {{-- Nama --}}
                                <div class="form-group">
                                  <label for="name-edit-{{ $lec->id }}">Nama <span class="text-danger">*</span></label>
                                  <input type="text"
                                         id="name-edit-{{ $lec->id }}"
                                         name="name"
                                         class="form-control @error('name') is-invalid @enderror"
                                         value="{{ old('name', $lec->name) }}"
                                         required autocomplete="off">
                                  @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                {{-- NIK --}}
                                <div class="form-group">
                                  <label for="nik-edit-{{ $lec->id }}">NIK</label>
                                  <input type="text"
                                         id="nik-edit-{{ $lec->id }}"
                                         name="nik"
                                         class="form-control @error('nik') is-invalid @enderror"
                                         value="{{ old('nik', $lec->nik) }}"
                                         autocomplete="off">
                                  @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                {{-- NIDN --}}
                                <div class="form-group">
                                  <label for="nidn-edit-{{ $lec->id }}">NIDN</label>
                                  <input type="text"
                                         id="nidn-edit-{{ $lec->id }}"
                                         name="nidn"
                                         class="form-control @error('nidn') is-invalid @enderror"
                                         value="{{ old('nidn', $lec->nidn) }}"
                                         autocomplete="off">
                                  @error('nidn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                {{-- Alamat --}}
                                <div class="form-group">
                                  <label for="address-edit-{{ $lec->id }}">Alamat</label>
                                  <textarea id="address-edit-{{ $lec->id }}"
                                            name="address"
                                            class="form-control @error('address') is-invalid @enderror"
                                            rows="2">{{ old('address', $lec->address) }}</textarea>
                                  @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                              </div>

                              <div class="col-md-6">
                                {{-- Tempat / Tanggal Lahir --}}
                                <div class="form-row">
                                  <div class="form-group col-md-6">
                                    <label for="birth_place-edit-{{ $lec->id }}">Tempat Lahir</label>
                                    <input type="text"
                                           id="birth_place-edit-{{ $lec->id }}"
                                           name="birth_place"
                                           class="form-control @error('birth_place') is-invalid @enderror"
                                           value="{{ old('birth_place', $lec->birth_place) }}">
                                    @error('birth_place') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                  </div>
                                  <div class="form-group col-md-6">
                                    <label for="birth_date-edit-{{ $lec->id }}">Tanggal Lahir</label>
                                    <input type="date"
                                           id="birth_date-edit-{{ $lec->id }}"
                                           name="birth_date"
                                           class="form-control @error('birth_date') is-invalid @enderror"
                                           value="{{ old('birth_date', $lec->birth_date) }}">
                                    @error('birth_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                  </div>
                                </div>

                                {{-- Jabatan & Status --}}
                                <div class="form-group">
                                  <label for="position-edit-{{ $lec->id }}">Jabatan Fungsional</label>
                                  <select id="position-edit-{{ $lec->id }}" name="position" class="form-control @error('position') is-invalid @enderror">
                                    <option value="">— Pilih —</option>
                                    @foreach(($positions ?? []) as $pos)
                                      <option value="{{ $pos }}" {{ old('position', $lec->position) === $pos ? 'selected' : '' }}>{{ $pos }}</option>
                                    @endforeach
                                  </select>
                                  @error('position') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-group">
                                  <label for="marital-edit-{{ $lec->id }}">Status Keluarga</label>
                                  <select id="marital-edit-{{ $lec->id }}" name="marital_status" class="form-control @error('marital_status') is-invalid @enderror">
                                    <option value="">— Pilih —</option>
                                    @foreach(($maritals ?? []) as $m)
                                      <option value="{{ $m }}" {{ old('marital_status', $lec->marital_status) === $m ? 'selected' : '' }}>{{ $m }}</option>
                                    @endforeach
                                  </select>
                                  @error('marital_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                {{-- Bidang Keilmuan (Parent → Child) --}}
                                @php
                                  $selectedParent = optional($lec->expertiseField)->expertise_id;
                                  $selectedField  = $lec->expertise_field_id;
                                @endphp
                                <div class="form-row">
                                  <div class="form-group col-md-6">
                                    <label for="exp-parent-edit-{{ $lec->id }}">Bidang Keilmuan</label>
                                    <select id="exp-parent-edit-{{ $lec->id }}" class="form-control exp-parent" data-target="#exp-field-edit-{{ $lec->id }}">
                                      <option value="">— Pilih —</option>
                                      @foreach(($expertises ?? []) as $ex)
                                        <option value="{{ $ex->id }}" {{ (old('exp_parent_'.$lec->id, $selectedParent)==$ex->id) ? 'selected':'' }}>
                                          {{ $ex->name }}
                                        </option>
                                      @endforeach
                                    </select>
                                    <input type="hidden" name="exp_parent_{{ $lec->id }}" value="{{ old('exp_parent_'.$lec->id, $selectedParent) }}">
                                  </div>
                                  <div class="form-group col-md-6">
                                    <label for="exp-field-edit-{{ $lec->id }}">Sub Bidang</label>
                                    <select id="exp-field-edit-{{ $lec->id }}" name="expertise_field_id" class="form-control">
                                      {{-- Diisi via JS saat modal dibuka / parent berubah --}}
                                    </select>
                                  </div>
                                </div>
                              </div>
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
                              <li><strong>Nama:</strong> {{ $lec->name }}</li>
                              <li><strong>NIDN:</strong> {{ $lec->nidn ?? '—' }}</li>
                            </ul>
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
                    <td colspan="{{ in_array(Auth::user()->role, ['admin','faculty_head']) ? 7 : 6 }}" class="text-center">Belum ada dosen.</td>
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

{{-- Modal Add --}}
<div class="modal fade" id="modal-add-lecturer" tabindex="-1" role="dialog" aria-labelledby="label-add-lecturer" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
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
          <div class="row">
            <div class="col-md-6">

              {{-- Program Studi (hanya admin & faculty_head) --}}
              @if(in_array(Auth::user()->role, ['admin','faculty_head']))
                <div class="form-group">
                  <label for="department_id-add">Program Studi <span class="text-danger">*</span></label>
                  <select id="department_id-add"
                          name="department_id"
                          class="form-control @error('department_id') is-invalid @enderror"
                          required>
                    <option value="">— Pilih Prodi —</option>
                    @foreach(($departments ?? []) as $d)
                      <option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected' : '' }}>
                        {{ $d->name }}
                      </option>
                    @endforeach
                  </select>
                  @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              @endif

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

              {{-- NIK / NIDN --}}
              <div class="form-group">
                <label for="nik-add">NIK</label>
                <input type="text" id="nik-add" name="nik" class="form-control @error('nik') is-invalid @enderror" value="{{ old('nik') }}">
                @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-group">
                <label for="nidn-add">NIDN</label>
                <input type="text" id="nidn-add" name="nidn" class="form-control @error('nidn') is-invalid @enderror" value="{{ old('nidn') }}">
                @error('nidn') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Alamat --}}
              <div class="form-group">
                <label for="address-add">Alamat</label>
                <textarea id="address-add" name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

            </div>

            <div class="col-md-6">
              {{-- Tempat/Tanggal Lahir --}}
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="birth_place-add">Tempat Lahir</label>
                  <input type="text" id="birth_place-add" name="birth_place" class="form-control @error('birth_place') is-invalid @enderror" value="{{ old('birth_place') }}">
                  @error('birth_place') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-group col-md-6">
                  <label for="birth_date-add">Tanggal Lahir</label>
                  <input type="date" id="birth_date-add" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror" value="{{ old('birth_date') }}">
                  @error('birth_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
              </div>

              {{-- Jabatan & Status --}}
              <div class="form-group">
                <label for="position-add">Jabatan Fungsional</label>
                <select id="position-add" name="position" class="form-control @error('position') is-invalid @enderror">
                  <option value="">— Pilih —</option>
                  @foreach(($positions ?? []) as $pos)
                    <option value="{{ $pos }}" {{ old('position')===$pos ? 'selected' : '' }}>{{ $pos }}</option>
                  @endforeach
                </select>
                @error('position') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-group">
                <label for="marital-add">Status Keluarga</label>
                <select id="marital-add" name="marital_status" class="form-control @error('marital_status') is-invalid @enderror">
                  <option value="">— Pilih —</option>
                  @foreach(($maritals ?? []) as $m)
                    <option value="{{ $m }}" {{ old('marital_status')===$m ? 'selected' : '' }}>{{ $m }}</option>
                  @endforeach
                </select>
                @error('marital_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Bidang Keilmuan (Parent → Child) --}}
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="exp-parent-add">Bidang Keilmuan</label>
                  <select id="exp-parent-add" class="form-control exp-parent" data-target="#exp-field-add">
                    <option value="">— Pilih —</option>
                    @foreach(($expertises ?? []) as $ex)
                      <option value="{{ $ex->id }}" {{ old('exp_parent')==$ex->id ? 'selected' : '' }}>{{ $ex->name }}</option>
                    @endforeach
                  </select>
                  <input type="hidden" name="exp_parent" value="{{ old('exp_parent') }}">
                </div>
                <div class="form-group col-md-6">
                  <label for="exp-field-add">Sub Bidang</label>
                  <select id="exp-field-add" name="expertise_field_id" class="form-control">
                    {{-- diisi via JS --}}
                  </select>
                </div>
              </div>

            </div>
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
  // ===== Helper AJAX fetch sub-bidang berdasarkan parent =====
  function fillChildOptions(parentSelect, childSelector, selectedChildId) {
    var parentId = parentSelect.value || '';
    var child = document.querySelector(childSelector);
    if (!child) return;

    child.innerHTML = '<option value="">Memuat...</option>';

    if (!parentId) {
      child.innerHTML = '<option value="">— Pilih —</option>';
      return;
    }

    // GET /expertises/{id}/fields  ->  [{id,name},...]
    var urlTmpl = @json(route('expertises.fields-json', ['id' => ':id']));

    var url = urlTmpl.replace(':id', encodeURIComponent(parentId));
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(list => {
        var opts = '<option value="">— Pilih —</option>';
        (list || []).forEach(function(it){
          var sel = (String(selectedChildId || '') === String(it.id)) ? 'selected' : '';
          opts += '<option value="'+ it.id +'" '+ sel +'>'+ it.name +'</option>';
        });
        child.innerHTML = opts;
      })
      .catch((err) => {
        console.error('Gagal memuat sub-bidang:', err, 'URL:', url);
        child.innerHTML = '<option value="">(Gagal memuat)</option>';
      });
  }

  // ===== Wiring untuk semua select parent (add/edit) =====
  function wireParentChange() {
    document.querySelectorAll('.exp-parent').forEach(function(sel){
      sel.addEventListener('change', function(){
        // simpan hidden parent jika ada
        var nearestForm = sel.closest('form');
        if (nearestForm) {
          var hiddenName = sel.id.includes('edit-') ? ('exp_parent_' + sel.id.split('edit-')[1]) : 'exp_parent';
          var hidden = nearestForm.querySelector('input[name="'+ hiddenName +'"]');
          if (hidden) hidden.value = sel.value || '';
        }
        fillChildOptions(sel, sel.dataset.target, null);
      });
    });
  }

  // ===== Saat modal ADD dibuka, prefill child (jika ada old input) =====
  $('#modal-add-lecturer').on('shown.bs.modal', function () {
    $('#name-add').trigger('focus');
    var parent = document.getElementById('exp-parent-add');
    var selectedChild = @json(old('expertise_field_id'));
    fillChildOptions(parent, '#exp-field-add', selectedChild);
  });

  // ===== Saat modal EDIT dibuka, prefill child sesuai data existing =====
  @foreach($lecturers as $lec)
  $('#modal-edit-lecturer-{{ $lec->id }}').on('shown.bs.modal', function () {
    var parentSel = document.getElementById('exp-parent-edit-{{ $lec->id }}');
    var selectedChild = @json(old('expertise_field_id')) || @json($lec->expertise_field_id);
    fillChildOptions(parentSel, '#exp-field-edit-{{ $lec->id }}', selectedChild);
  });
  @endforeach

  // Auto-buka modal yang error: create atau edit-{id}
  @if ($errors->any() && old('_from'))
    (function(){
      const from = @json(old('_from'));
      if (from === 'create') {
        $('#modal-add-lecturer').modal('show');
        setTimeout(function(){
          var parent = document.getElementById('exp-parent-add');
          var selectedChild = @json(old('expertise_field_id'));
          fillChildOptions(parent, '#exp-field-add', selectedChild);
        }, 100);
      } else if (from.startsWith('edit-')) {
        const id = from.split('-')[1];
        const modalId = '#modal-edit-lecturer-' + id;
        $(modalId).modal('show');
        setTimeout(function(){
          var parentSel = document.getElementById('exp-parent-edit-' + id);
          var selectedChild = @json(old('expertise_field_id'));
          fillChildOptions(parentSel, '#exp-field-edit-' + id, selectedChild || null);
        }, 100);
      }
    })();
  @endif

  wireParentChange();
</script>
@endsection
