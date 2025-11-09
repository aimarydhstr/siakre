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

        {{-- FORM UTAMA --}}
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

                {{-- Admin pilih Prodi --}}
                @if((Auth::user()->role === 'admin' || Auth::user()->role === 'faculty_head') && isset($departments))
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

                {{-- ISSN --}}
                <div class="form-group input-group-sm">
                  <label for="issn">ISSN <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('issn') is-invalid @enderror"
                         id="issn" name="issn" placeholder="Contoh: 0378-5955"
                         value="{{ old('issn') }}">
                  @error('issn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group input-group-sm">
                  <label for="publisher">Penerbit <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('publisher') is-invalid @enderror"
                         id="publisher" name="publisher" placeholder="Nama penerbit/jurnal"
                         value="{{ old('publisher') }}">
                  @error('publisher')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group input-group-sm">
                  <label for="file">Dokumen (PDF) <span class="text-danger">*</span></label>
                  <div class="custom-file">
                    <input type="file" class="custom-file-input @error('file') is-invalid @enderror"
                           id="file" name="file" accept="application/pdf" title="Pilih PDF (maks 10MB)" required>
                    <label class="custom-file-label text-truncate" for="file">Pilih PDF…</label>
                  </div>
                  <small class="text-danger"><i>Hanya PDF - Maksimal 10 MB</i></small>
                  @error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- PENULIS DOSEN (Gaya Book/HKI) --}}
        <div class="card rounded shadow mb-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0">Penulis Dosen</h6>
              <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-lect">
                <i class="fa fa-plus mr-1"></i> Tambah Dosen
              </button>
            </div>
            <div id="lecturers" class="form-row"></div>

            <template id="tpl-lect-row">
              <div class="form-group col-md-12 lect-row">
                <div class="border rounded p-2">
                  <p class="mb-2">Dosen ke-<span class="seq">1</span></p>
                  <div class="input-group">
                    <select name="lecturer_ids[]" class="form-control custom-select">
                      <option value="">-- Pilih Dosen --</option>
                      @if(isset($lecturers))
                        @foreach($lecturers as $lec)
                          <option value="{{ $lec->id }}">{{ $lec->name ?? ('Dosen #'.$lec->id) }}</option>
                        @endforeach
                      @endif
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

        {{-- PENULIS MAHASISWA (Gaya Book/HKI) --}}
        <div class="card rounded shadow mb-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0">Penulis Mahasiswa</h6>
              <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-stud">
                <i class="fa fa-plus mr-1"></i> Tambah Mahasiswa
              </button>
            </div>
            <div id="students" class="form-row"></div>

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

        <div class="row mt-3">
          <div class="col-md">
            <i><span class="text-danger">*</span> <small>Wajib diisi</small></i>
          </div>
          <div class="col-md text-md-right">
            <a href="{{ route('article') }}" class="btn btn-outline-secondary">Kembali</a>
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
  // Datepicker dd-mm-yyyy (pakai bootstrap-datepicker jika ada)
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

  (function(){
    // label file
    document.querySelectorAll('.custom-file-input').forEach(function(inp){
      inp.addEventListener('change', function(){
        var label = this.nextElementSibling;
        if (label) label.textContent = (this.files && this.files[0]) ? this.files[0].name : 'Pilih file…';
      });
    });

    // helpers
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

    // Dosen
    const lectWrap = document.getElementById('lecturers');
    const lectTpl  = document.getElementById('tpl-lect-row');
    const btnLect  = document.getElementById('btn-add-lect');

    const oldLect = @json(old('lecturer_ids', []));
    if (Array.isArray(oldLect) && oldLect.length){
      oldLect.forEach(function(val){
        addFromTemplate(lectTpl, lectWrap, function(node){
          const sel = node.querySelector('select[name="lecturer_ids[]"]');
          if (sel) sel.value = String(val);
          attachRemoveBtn(node, '.btn-remove-lect', lectWrap);
        });
      });
    } else {
      addFromTemplate(lectTpl, lectWrap, (node)=>attachRemoveBtn(node,'.btn-remove-lect', lectWrap));
    }
    btnLect?.addEventListener('click', ()=> addFromTemplate(lectTpl, lectWrap, (node)=>attachRemoveBtn(node,'.btn-remove-lect', lectWrap)));

    // Mahasiswa
    const studWrap = document.getElementById('students');
    const studTpl  = document.getElementById('tpl-stud-row');
    const btnStud  = document.getElementById('btn-add-stud');

    const oldNames = @json(old('student_names', []));
    const oldNims  = @json(old('student_nims',  []));
    const rows = Math.max(oldNames.length || 0, oldNims.length || 0);

    if (rows > 0){
      for (let i=0;i<rows;i++){
        addFromTemplate(studTpl, studWrap, function(node){
          node.querySelector('input[name="student_names[]"]').value = (oldNames[i] || '');
          node.querySelector('input[name="student_nims[]"]').value  = (oldNims[i]  || '');
          attachRemoveBtn(node, '.btn-remove-stud', studWrap);
        });
      }
    } else {
      addFromTemplate(studTpl, studWrap, (node)=>attachRemoveBtn(node,'.btn-remove-stud', studWrap));
    }
    btnStud?.addEventListener('click', ()=> addFromTemplate(studTpl, studWrap, (node)=>attachRemoveBtn(node,'.btn-remove-stud', studWrap)));
  })();
</script>
@endsection
