@extends('layout.base')
@section('title','Edit HKI')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')
  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <h4 class="font-weight-bold my-3 mt-md-4">Edit HKI</h4>

      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif
      @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif

      <form action="{{ route('hki.update', $hki->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card rounded shadow mt-3 mb-4">
          <div class="card-body">
            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Nama HKI <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $hki->name) }}" class="form-control @error('name') is-invalid @enderror" required autocomplete="off">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="form-group col-md-6">
                <label>Nomor <span class="text-danger">*</span></label>
                <input type="text" name="number" value="{{ old('number', $hki->number) }}" class="form-control @error('number') is-invalid @enderror" required autocomplete="off">
                @error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Pemegang <span class="text-danger">*</span></label>
                <input type="text" name="holder" value="{{ old('holder', $hki->holder) }}" class="form-control @error('holder') is-invalid @enderror" required autocomplete="off">
                @error('holder')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              {{-- Program Studi --}}
              <div class="form-group col-md-6">
                <label>Program Studi <span class="text-danger">*</span></label>
                @php
                  $canChangeDept = in_array(optional($user)->role, ['admin','faculty_head'], true);
                  $selectedDept = old('department_id', $hki->department_id);
                @endphp

                @if(isset($departments) && $departments->count())
                  <select name="department_id" class="form-control @error('department_id') is-invalid @enderror"
                          {{ $canChangeDept ? '' : 'disabled' }} required>
                    <option value="">-- Pilih Program Studi --</option>
                    @foreach($departments as $dept)
                      <option value="{{ $dept->id }}" {{ (string)$selectedDept === (string)$dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                      </option>
                    @endforeach
                  </select>

                  {{-- kalau disabled, kirim hidden agar value tetap terkirim --}}
                  @unless($canChangeDept)
                    <input type="hidden" name="department_id" value="{{ $selectedDept }}">
                  @endunless

                  @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @else
                  <input type="hidden" name="department_id" value="{{ old('department_id', $hki->department_id) }}">
                  <div class="form-control-plaintext text-muted">
                    {{ optional(\App\Models\Department::find(old('department_id', $hki->department_id)))->name ?? 'Program Studi tidak tersedia — hubungi admin' }}
                  </div>
                  @error('department_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @endif
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label>File (PDF, max 10MB)</label>
                <div class="custom-file">
                  <input type="file" name="file" accept="application/pdf" id="file" class="custom-file-input @error('file') is-invalid @enderror">
                  <label class="custom-file-label" for="file">Pilih file…</label>
                </div>
                @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                @if($hki->file)
                  <div class="mt-2">
                    <a href="{{ route('hki.download', $hki->file) }}" class="btn btn-outline-secondary btn-sm">
                      <i class="fa fa-download"></i> Unduh file saat ini
                    </a>
                  </div>
                @endif
              </div>
              <div class="form-group col-md-6">
                <label>Tanggal <span class="text-danger">*</span></label>
                @php
                  try {
                    $dateVal = old('date', optional(\Carbon\Carbon::parse($hki->date))->format('Y-m-d'));
                  } catch (\Throwable $e) {
                    $dateVal = old('date', '');
                  }
                @endphp
                <input type="date" name="date" value="{{ $dateVal }}" class="form-control @error('date') is-invalid @enderror" required autocomplete="off">
                @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
        </div>

        {{-- Dosen --}}
        <div class="card rounded shadow mb-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0">Dosen</h6>
              <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-lect">
                <i class="fa fa-plus mr-1"></i> Tambah Dosen
              </button>
            </div>

            <div id="lecturers" class="form-row">
              @php
                $oldLect = old('lecturer_ids', $hki->lecturers->pluck('id')->values()->all());
              @endphp

              @if(is_array($oldLect) && count($oldLect))
                @foreach($oldLect as $idx => $lecId)
                  <div class="form-group col-md-12 lect-row">
                    <div class="border rounded p-2">
                      <p class="mb-2">Dosen ke-<span class="seq">{{ $loop->iteration }}</span></p>
                      <div class="input-group">
                        <select name="lecturer_ids[]" class="form-control custom-select">
                          <option value="">-- Pilih Dosen --</option>
                          @foreach($lecturers as $lec)
                            <option value="{{ $lec->id }}" {{ (string)$lecId === (string)$lec->id ? 'selected' : '' }}>
                              {{ $lec->name ?? ('Dosen #'.$lec->id) }}
                            </option>
                          @endforeach
                        </select>
                        <div class="input-group-append">
                          <button type="button" class="btn btn-outline-danger btn-remove-lect">&times;</button>
                        </div>
                      </div>
                    </div>
                  </div>
                @endforeach
              @else
                <div class="form-group col-md-12 lect-row">
                  <div class="border rounded p-2">
                    <p class="mb-2">Dosen ke-<span class="seq">1</span></p>
                    <div class="input-group">
                      <select name="lecturer_ids[]" class="form-control custom-select">
                        <option value="">-- Pilih Dosen --</option>
                        @foreach($lecturers as $lec)
                          <option value="{{ $lec->id }}">{{ $lec->name ?? ('Dosen #'.$lec->id) }}</option>
                        @endforeach
                      </select>
                      <div class="input-group-append">
                        <button type="button" class="btn btn-outline-danger btn-remove-lect">&times;</button>
                      </div>
                    </div>
                  </div>
                </div>
              @endif
            </div>

            <template id="tpl-lect-row">
              <div class="form-group col-md-12 lect-row">
                <div class="border rounded p-2">
                  <p class="mb-2">Dosen ke-<span class="seq">1</span></p>
                  <div class="input-group">
                    <select name="lecturer_ids[]" class="form-control custom-select">
                      <option value="">-- Pilih Dosen --</option>
                      @foreach($lecturers as $lec)
                        <option value="{{ $lec->id }}">{{ $lec->name ?? ('Dosen #'.$lec->id) }}</option>
                      @endforeach
                    </select>
                    <div class="input-group-append">
                      <button type="button" class="btn btn-outline-danger btn-remove-lect">&times;</button>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>

        {{-- Mahasiswa --}}
        <div class="card rounded shadow mb-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0">Mahasiswa</h6>
              <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-stud">
                <i class="fa fa-plus mr-1"></i> Tambah Mahasiswa
              </button>
            </div>

            <div id="students" class="form-row">
              @php
                $oldNames = old('student_names', $hki->students->pluck('name')->values()->all());
                $oldNims  = old('student_nims',  $hki->students->pluck('nim')->values()->all());
                $rows = max(count($oldNames), count($oldNims));
              @endphp

              @if($rows > 0)
                @for($i=0; $i<$rows; $i++)
                  <div class="form-group col-md-12 stud-row">
                    <div class="border rounded p-2">
                      <p class="mb-2">Mahasiswa ke-<span class="seq">{{ $i+1 }}</span></p>
                      <div class="form-row">
                        <div class="col-12 py-1">
                          <input type="text" name="student_names[]" class="form-control mb-2" placeholder="Nama" value="{{ $oldNames[$i] ?? '' }}">
                        </div>
                        <div class="col-12 py-1">
                          <input type="text" name="student_nims[]" class="form-control mb-2" placeholder="NIM" value="{{ $oldNims[$i] ?? '' }}">
                        </div>
                      </div>
                      <div class="text-right">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-stud">Hapus</button>
                      </div>
                    </div>
                  </div>
                @endfor
              @else
                <div class="form-group col-md-12 stud-row">
                  <div class="border rounded p-2">
                    <p class="mb-2">Mahasiswa ke-<span class="seq">1</span></p>
                    <div class="form-row">
                      <div class="col-12 py-1">
                        <input type="text" name="student_names[]" class="form-control mb-2" placeholder="Nama">
                      </div>
                      <div class="col-12 py-1">
                        <input type="text" name="student_nims[]" class="form-control mb-2" placeholder="NIM">
                      </div>
                    </div>
                    <div class="text-right">
                      <button type="button" class="btn btn-outline-danger btn-sm btn-remove-stud">Hapus</button>
                    </div>
                  </div>
                </div>
              @endif
            </div>

            <template id="tpl-stud-row">
              <div class="form-group col-md-12 stud-row">
                <div class="border rounded p-2">
                  <p class="mb-2">Mahasiswa ke-<span class="seq">1</span></p>
                  <div class="form-row">
                    <div class="col-12 py-1">
                      <input type="text" name="student_names[]" class="form-control mb-2" placeholder="Nama">
                    </div>
                    <div class="col-12 py-1">
                      <input type="text" name="student_nims[]" class="form-control mb-2" placeholder="NIM">
                    </div>
                  </div>
                  <div class="text-right">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-stud">Hapus</button>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>

        <div class="d-flex justify-content-between">
          <a href="{{ route('hki.index') }}" class="btn btn-outline-secondary">Kembali</a>
          <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
        </div>
      </form>
    </div>

    @include('template.footer')
  </div>
</div>
@endsection

@section('js')
<script>
(function(){
  // custom-file label
  (function(){
    var inputs = document.querySelectorAll('.custom-file-input');
    for (var i=0;i<inputs.length;i++){
      inputs[i].addEventListener('change', function(){
        var label = this.nextElementSibling;
        if (!label) return;
        var f = this.files && this.files[0] ? this.files[0].name : 'Pilih file…';
        label.textContent = f;
      });
    }
  })();

  function addFromTemplate(tplEl, container, afterAddCb){
    const node = tplEl.content.firstElementChild.cloneNode(true);
    container.appendChild(node);
    if (typeof afterAddCb === 'function') afterAddCb(node);
    renumber(container);
  }
  function attachRemoveBtn(node, selector, container){
    const btn = node.querySelector(selector);
    if (btn) btn.addEventListener('click', function(){ node.remove(); renumber(container); });
  }
  function renumber(scope){
    scope.querySelectorAll('.lect-row').forEach((row, i)=>{
      const seq = row.querySelector('.seq'); if (seq) seq.textContent = String(i+1);
    });
    scope.querySelectorAll('.stud-row').forEach((row, i)=>{
      const seq = row.querySelector('.seq'); if (seq) seq.textContent = String(i+1);
    });
  }

  const lectWrap = document.getElementById('lecturers');
  const lectTpl  = document.getElementById('tpl-lect-row');
  const btnLect  = document.getElementById('btn-add-lect');

  const studWrap = document.getElementById('students');
  const studTpl  = document.getElementById('tpl-stud-row');
  const btnStud  = document.getElementById('btn-add-stud');

  // attach remove to existing rows rendered by server
  lectWrap.querySelectorAll('.lect-row').forEach(row=>{
    attachRemoveBtn(row, '.btn-remove-lect', lectWrap);
  });
  studWrap.querySelectorAll('.stud-row').forEach(row=>{
    attachRemoveBtn(row, '.btn-remove-stud', studWrap);
  });
  renumber(document);

  // add handlers
  btnLect?.addEventListener('click', ()=> addFromTemplate(lectTpl, lectWrap, (node)=>attachRemoveBtn(node, '.btn-remove-lect', lectWrap)));
  btnStud?.addEventListener('click', ()=> addFromTemplate(studTpl, studWrap, (node)=>attachRemoveBtn(node, '.btn-remove-stud', studWrap)));
})();
</script>
@endsection
