@extends ('layout.base')
@section('title','Tambah Artikel')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    {{ Breadcrumbs::render('add') }}
    @include('template.nav')

    <div class="content">

      {{-- Alert Validasi --}}
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      @if (session('status'))
        <div class="alert alert-primary">{{ session('status') }}</div>
      @endif

      <h4 class="font-weight-bold my-3 mt-md-4">Tambah Artikel</h4>

      <form action="{{ route('article-add-send') }}" method="post" enctype="multipart/form-data" class="add">
        @csrf

        <div class="card rounded shadow mt-3 mb-5">
          <div class="card-body">
            @if (session('alert'))
              <div class="alert alert-danger">{{ session('alert') }}</div>
            @endif

            <div class="row">
              {{-- KIRI --}}
              <div class="col-md-6">
                <div class="form-group input-group-sm">
                  <label for="title">Judul <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('title') is-invalid @enderror"
                         id="title" name="title" placeholder="Masukkan judul artikel"
                         value="{{ old('title') }}">
                  @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group input-group-sm">
                  <label for="type_journal">Jenis Jurnal <span class="text-danger">*</span></label>
                  @php $tj = old('type_journal'); @endphp
                  <select class="form-control custom-select @error('type_journal') is-invalid @enderror"
                          id="type_journal" name="type_journal">
                    <option disabled {{ $tj ? '' : 'selected' }}>Pilih Jenis Jurnal</option>
                    <option value="Seminar Nasional" {{ $tj==='Seminar Nasional' ? 'selected':'' }}>Seminar Nasional</option>
                    <option value="Seminar Internasional" {{ $tj==='Seminar Internasional' ? 'selected':'' }}>Seminar Internasional</option>
                    <option value="Jurnal Internasional" {{ $tj==='Jurnal Internasional' ? 'selected':'' }}>Jurnal Internasional</option>
                    <option value="Jurnal Internasional Bereputasi" {{ $tj==='Jurnal Internasional Bereputasi' ? 'selected':'' }}>Jurnal Internasional Bereputasi</option>
                    <option value="Jurnal Nasional Terakreditasi" {{ $tj==='Jurnal Nasional Terakreditasi' ? 'selected':'' }}>Jurnal Nasional Terakreditasi</option>
                    <option value="Jurnal Nasional Tidak Terakreditasi" {{ $tj==='Jurnal Nasional Tidak Terakreditasi' ? 'selected':'' }}>Jurnal Nasional Tidak Terakreditasi</option>
                  </select>
                  @error('type_journal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group input-group-sm">
                  <label for="url">URL <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('url') is-invalid @enderror"
                         id="url" name="url" placeholder="https://contoh.com/artikel"
                         value="{{ old('url') }}">
                  @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- DOI --}}
                <div class="form-group input-group-sm">
                  <label for="doi">DOI <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('doi') is-invalid @enderror"
                         id="doi" name="doi" placeholder="Contoh: 10.1000/xyz123"
                         value="{{ old('doi') }}">
                  @error('doi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group input-group-sm">
                  <label for="publisher">Penerbit <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('publisher') is-invalid @enderror"
                         id="publisher" name="publisher" placeholder="Nama penerbit/jurnal"
                         value="{{ old('publisher') }}">
                  @error('publisher')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                @if(Auth::user()->role === 'admin' && isset($departments))
                  <div class="form-group input-group-sm">
                    <label for="department_id">Program Studi <span class="text-danger">*</span></label>
                    <select id="department_id" name="department_id" class="form-control custom-select @error('department_id') is-invalid @enderror" required>
                      <option value="" disabled {{ old('department_id') ? '' : 'selected' }}>Pilih Program Studi</option>
                      @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ (string)old('department_id')===(string)$dept->id ? 'selected':'' }}>
                          {{ $dept->name }}
                        </option>
                      @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                @endif
              </div>

              {{-- KANAN --}}
              <div class="col-md-6">
                <div class="form-row">
                  <div class="col-md-6">
                    <div class="form-group input-group-sm">
                      <label for="date">Periode (tanggal terbit) <span class="text-danger">*</span></label>
                      <div class="bootstrap-iso">
                        <div class="input-group date">
                          <div class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                          </div>
                          <input placeholder="dd-mm-yyyy"
                                 type="text"
                                 class="form-control datepicker @error('date') is-invalid @enderror"
                                 id="date" name="date" value="{{ old('date') }}">
                        </div>
                      </div>
                      @error('date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group input-group-sm">
                      <label for="volume">Volume</label>
                      <input type="text" class="form-control @error('volume') is-invalid @enderror"
                             id="volume" name="volume" value="{{ old('volume') }}" placeholder="Vol">
                      @error('volume')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group input-group-sm">
                      <label for="number">Nomor</label>
                      <input type="text" class="form-control @error('number') is-invalid @enderror"
                             id="number" name="number" value="{{ old('number') }}" placeholder="No">
                      @error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                  </div>
                </div>

                {{-- HANYA 2 kategori --}}
                <div class="form-group input-group-sm">
                  <label for="category">Kategori Penulis <span class="text-danger">*</span></label>
                  @php $cat = old('category'); @endphp
                  <select id="category" name="category" class="form-control custom-select @error('category') is-invalid @enderror">
                    <option value="" disabled {{ $cat ? '' : 'selected' }}>Pilih kategori</option>
                    <option value="dosen"     {{ $cat==='dosen' ? 'selected':'' }}>Dosen</option>
                    <option value="mahasiswa" {{ $cat==='mahasiswa' ? 'selected':'' }}>Mahasiswa</option>
                  </select>
                  @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group input-group-sm">
                  <label for="file">Dokumen (PDF) <span class="text-danger">*</span></label>
                  <div class="custom-file">
                    <input type="file" class="custom-file-input @error('file') is-invalid @enderror"
                           id="file" name="file" accept="application/pdf" title="Pilih PDF (maks 10MB)">
                    <label class="custom-file-label text-truncate" for="file">Pilih PDF…</label>
                  </div>
                  <small class="text-danger"><i>Hanya PDF - Maksimal 10 MB</i></small>
                  @error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- PENULIS DOSEN --}}
        <div id="section-lecturers">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Penulis Dosen</h5>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-lecturer">
              <i class="fa fa-plus mr-1"></i> Tambah Dosen
            </button>
          </div>
          <div id="lecturers" class="row"></div>
        </div>

        {{-- PENULIS MAHASISWA --}}
        <div id="section-students" class="mt-4">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Penulis Mahasiswa</h5>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-student">
              <i class="fa fa-plus mr-1"></i> Tambah Mahasiswa
            </button>
          </div>
          <div id="students" class="row"></div>
        </div>

        <div class="row mt-3">
          <div class="col-md">
            <i><span class="text-danger">*</span> <small>Wajib diisi</small></i>
          </div>
          <div class="col-md text-md-right">
            <button type="submit" class="btn btn-md btn-primary">Simpan</button>
          </div>
        </div>
      </form>
    </div>

    @include('template.footer')
  </div>
</div>
@endsection

@section('js')
<script>
  // Datepicker dd-mm-yyyy
  (function(){
    var date_input = document.querySelector('input[name="date"]');
    if (window.jQuery && jQuery.fn.datepicker && date_input){
      var $ = window.jQuery;
      var container = $('.bootstrap-iso form').length>0 ? $('.bootstrap-iso form').parent() : "body";
      $(date_input).datepicker({
        format: 'dd-mm-yyyy',
        container: container,
        todayHighlight: true,
        autoclose: true,
      });
    }
  })();

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

  // ---------- Dinamis Dosen ----------
  var lecturersWrap = document.getElementById('lecturers');
  var addLectBtn    = document.getElementById('btn-add-lecturer');
  function addLecturerRow(selectedId){
    var col = document.createElement('div');
    col.className = 'col-md-6';
    var html = `
      <div class="shadow bg-white border rounded p-3 mb-3">
        <div class="form-group input-group-sm mb-2">
          <label>Dosen</label>
          <select name="lecturer_ids[]" class="form-control custom-select">
            <option value="">-- Pilih Dosen --</option>
            @if(isset($lecturers) && $lecturers->count())
              @foreach($lecturers as $lec)
                @php $lecName = optional($lec->user)->name ?? 'Tanpa Nama'; @endphp
                <option value="{{ $lec->id }}">{{ $lecName }}</option>
              @endforeach
            @endif
          </select>
        </div>
        <div class="d-flex">
          <button type="button" class="btn btn-outline-danger btn-sm ml-auto btn-remove-lect">Hapus</button>
        </div>
      </div>
    `;
    col.innerHTML = html;
    lecturersWrap.appendChild(col);

    if (selectedId){
      var sel = col.querySelector('select[name="lecturer_ids[]"]');
      if (sel) sel.value = selectedId;
    }

    col.querySelector('.btn-remove-lect').addEventListener('click', function(){
      col.remove();
    });
  }
  addLectBtn.addEventListener('click', function(){ addLecturerRow(null); });

  // ---------- Dinamis Mahasiswa ----------
  var studentsWrap = document.getElementById('students');
  var addStuBtn    = document.getElementById('btn-add-student');
  function addStudentRow(nameVal, nimVal){
    var col = document.createElement('div');
    col.className = 'col-md-6';
    var html = `
      <div class="shadow bg-white border rounded p-3 mb-3">
        <div class="form-group input-group-sm">
          <label>Nama Mahasiswa</label>
          <input type="text" name="student_names[]" class="form-control" placeholder="Nama lengkap" value="">
        </div>
        <div class="form-group input-group-sm">
          <label>NIM</label>
          <input type="text" name="student_nims[]" class="form-control" placeholder="Nomor Induk Mahasiswa" value="">
        </div>
        <div class="d-flex">
          <button type="button" class="btn btn-outline-danger btn-sm ml-auto btn-remove-stu">Hapus</button>
        </div>
      </div>
    `;
    col.innerHTML = html;
    studentsWrap.appendChild(col);

    if (typeof nameVal === 'string') col.querySelector('input[name="student_names[]"]').value = nameVal;
    if (typeof nimVal === 'string')  col.querySelector('input[name="student_nims[]"]').value  = nimVal;

    col.querySelector('.btn-remove-stu').addEventListener('click', function(){
      col.remove();
    });
  }
  addStuBtn.addEventListener('click', function(){ addStudentRow('', ''); });

  // ---------- Tampilkan/Sembunyikan Bagian berdasarkan kategori ----------
  var catEl           = document.getElementById('category');
  var sectionLect     = document.getElementById('section-lecturers');
  var sectionStud     = document.getElementById('section-students');

  function ensureAtLeastOneRow(container, addFunc){
    if (!container.querySelector('.shadow')) {
      addFunc(null, null); // kompatibel: addLecturerRow(null) atau addStudentRow('', '')
    }
  }

  function applyCategoryUI(catValue, isInit){
    if (catValue === 'dosen') {
      // dosen saja → sembunyikan mahasiswa
      sectionLect.classList.remove('d-none');
      sectionStud.classList.add('d-none');

      // pastikan minimal 1 baris dosen saat terlihat
      ensureAtLeastOneRow(lecturersWrap, addLecturerRow);

      // optional: kosongkan input mahasiswa jika ingin benar-benar diabaikan
      if (!isInit) {
        studentsWrap.innerHTML = '';
      }

    } else if (catValue === 'mahasiswa') {
      // mahasiswa → tampilkan dosen & mahasiswa
      sectionLect.classList.remove('d-none');
      sectionStud.classList.remove('d-none');

      ensureAtLeastOneRow(lecturersWrap, addLecturerRow);
      ensureAtLeastOneRow(studentsWrap, addStudentRow);

    } else {
      // belum dipilih → sembunyikan keduanya
      sectionLect.classList.add('d-none');
      sectionStud.classList.add('d-none');
    }
  }

  // Init rows dari old()
  (function initRowsFromOld(){
    // Lecturers
    @php $oldLect = old('lecturer_ids', []); @endphp
    @if(is_array($oldLect) && count($oldLect))
      @foreach($oldLect as $lid)
        addLecturerRow({{ (int)$lid }});
      @endforeach
    @endif

    // Students
    @php $oldNames = old('student_names', []); $oldNims = old('student_nims', []); @endphp
    @if(is_array($oldNames) && count($oldNames))
      @for($i=0;$i<count($oldNames);$i++)
        addStudentRow(@json($oldNames[$i] ?? ''), @json($oldNims[$i] ?? ''));
      @endfor
    @endif
  })();

  // Default baris jika belum ada old-value
  if (!lecturersWrap.querySelector('.shadow')) addLecturerRow(null);
  if (!studentsWrap.querySelector('.shadow')) addStudentRow('', '');

  // Apply kategori saat load
  applyCategoryUI(catEl.value || '', true);

  // On change kategori
  catEl.addEventListener('change', function(){
    applyCategoryUI(this.value, false);
  });
</script>
@endsection
