@extends('layout.base')
@section('title','Edit Buku')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')
  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <h4 class="font-weight-bold my-3 mt-md-4">Edit Buku</h4>

      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif

      <form action="{{ route('books.update', $book->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card rounded shadow mt-3 mb-4">
          <div class="card-body">
            {{-- Baris 1 --}}
            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Judul <span class="text-danger">*</span></label>
                <input type="text" name="title" value="{{ old('title', $book->title) }}"
                       class="form-control @error('title') is-invalid @enderror" required autocomplete="off">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group col-md-6">
                <label>ISBN <span class="text-danger">*</span></label>
                <input type="text" name="isbn" value="{{ old('isbn', $book->isbn) }}"
                       class="form-control @error('isbn') is-invalid @enderror" required autocomplete="off">
                @error('isbn')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>

            {{-- Baris 2 --}}
            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Penerbit <span class="text-danger">*</span></label>
                <input type="text" name="publisher" value="{{ old('publisher', $book->publisher) }}"
                       class="form-control @error('publisher') is-invalid @enderror" required autocomplete="off">
                @error('publisher')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="form-group col-md-6">
                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label>Tahun Terbit <span class="text-danger">*</span></label>
                    <input type="number" name="publish_year" value="{{ old('publish_year', $book->publish_year) }}"
                           class="form-control @error('publish_year') is-invalid @enderror"
                           min="1900" max="2100" placeholder="mis. 2025" required>
                    @error('publish_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>

                  <div class="form-group col-md-6">
                    <label>Bulan Terbit <span class="text-danger">*</span></label>
                    <select name="publish_month" class="form-control @error('publish_month') is-invalid @enderror" required>
                      <option value="">Pilih bulan</option>
                      @for($m=1;$m<=12;$m++)
                        <option value="{{ $m }}" {{ (string)old('publish_month', $book->publish_month) === (string)$m ? 'selected' : '' }}>
                          {{ \DateTime::createFromFormat('!m',$m)->format('F') }}
                        </option>
                      @endfor
                    </select>
                    @error('publish_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
              </div>
            </div>

            {{-- Baris 3 --}}
            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Kota Terbit <span class="text-danger">*</span></label>
                <input type="text" name="city" value="{{ old('city', $book->city) }}"
                       class="form-control @error('city') is-invalid @enderror" required>
                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              {{-- Program Studi (select, disabled kalau tidak boleh diubah) --}}
              <div class="form-group col-md-6">
                <label>Program Studi <span class="text-danger">*</span></label>

                @php
                  // Perhitungan hak edit: biasanya admin & faculty_head boleh memilih; kaprodi tidak.
                  $canChangeDept = in_array(optional($user)->role, ['admin','faculty_head'], true);
                  // Pilihan yang dikirim (prioritaskan old())
                  $selectedDept = old('department_id', $book->department_id);
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

                  {{-- Jika select disabled, tambahkan hidden agar value tetap dikirim --}}
                  @unless($canChangeDept)
                    <input type="hidden" name="department_id" value="{{ $selectedDept }}">
                  @endunless

                  @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror

                @else
                  {{-- Fallback: bila controller tidak mengirim departments (sebaiknya controller kirim) --}}
                  <input type="hidden" name="department_id" value="{{ $selectedDept }}">
                  <div class="form-control-plaintext text-muted">
                    {{ optional(\App\Models\Department::find($selectedDept))->name ?? 'Program Studi terpilih' }}
                  </div>
                  @error('department_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @endif

              </div>
            </div>

            {{-- Baris 4: file --}}
            <div class="form-row">
              <div class="form-group col-md-12">
                <label>Ganti File (PDF)</label>
                <div class="custom-file">
                  <input type="file" name="file" accept="application/pdf"
                         class="custom-file-input @error('file') is-invalid @enderror" id="file">
                  <label class="custom-file-label" for="file">Pilih PDF…</label>
                </div>
                <small class="text-muted d-block">Maks 10 MB</small>
                @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                @if($book->file)
                  <div class="mt-2">
                    <a href="{{ route('books.download', $book->file) }}" class="btn btn-outline-secondary btn-sm">
                      <i class="fa fa-download"></i> Unduh file saat ini
                    </a>
                  </div>
                @endif
              </div>
            </div>

          </div>
        </div>

        {{-- Penulis Dosen --}}
        <div class="card rounded shadow mb-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0">Penulis Dosen</h6>
              <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-lect">
                <i class="fa fa-plus mr-1"></i> Tambah Dosen
              </button>
            </div>

            <div id="lecturers" class="form-row">
              @php
                $oldLect = old('lecturer_ids', $book->lecturers->pluck('id')->values()->all());
              @endphp

              @if(is_array($oldLect) && count($oldLect))
                @foreach($oldLect as $index => $lecId)
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

        {{-- Penulis Mahasiswa --}}
        <div class="card rounded shadow mb-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0">Penulis Mahasiswa</h6>
              <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-stud">
                <i class="fa fa-plus mr-1"></i> Tambah Mahasiswa
              </button>
            </div>

            <div id="students" class="form-row">
              @php
                $oldNames = old('student_names', $book->students->pluck('name')->values()->all());
                $oldNims  = old('student_nims',  $book->students->pluck('nim')->values()->all());
                $rows = max(count($oldNames), count($oldNims));
              @endphp

              @if($rows > 0)
                @for($i=0;$i<$rows;$i++)
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
          <a href="{{ route('books.index') }}" class="btn btn-outline-secondary">Kembali</a>
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

  // Pasang remove ke baris existing
  lectWrap.querySelectorAll('.lect-row').forEach(row=>{
    attachRemoveBtn(row, '.btn-remove-lect', lectWrap);
  });
  studWrap.querySelectorAll('.stud-row').forEach(row=>{
    attachRemoveBtn(row, '.btn-remove-stud', studWrap);
  });
  renumber(document);

  // Tambah baris
  btnLect?.addEventListener('click', ()=> addFromTemplate(lectTpl, lectWrap, (node)=>attachRemoveBtn(node,'.btn-remove-lect', lectWrap)));
  btnStud?.addEventListener('click', ()=> addFromTemplate(studTpl, studWrap, (node)=>attachRemoveBtn(node,'.btn-remove-stud', studWrap)));
})();
</script>
@endsection
