@extends ('layout.base')
@section('title','Edit Prestasi')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    {{ Breadcrumbs::render('add') }}
    @include('template.nav')

    <div class="content">
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

      <h4 class="font-weight-bold my-3 mt-md-4">Edit Prestasi</h4>

      <form action="{{ url('/update/'.$achievement->id) }}" method="post" enctype="multipart/form-data" class="add">
        @csrf
        @method('PUT')

        <div class="card rounded shadow mt-3 mb-5">
          <div class="card-body">
            <div class="row">
              {{-- KIRI --}}
              <div class="col-md-6">
                <div class="form-group input-group-sm">
                  <label for="team">Nama Tim <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('team') is-invalid @enderror"
                         id="team" name="team"
                         value="{{ old('team', $achievement->team) }}"
                         placeholder="Masukkan Nama Tim">
                  @error('team')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group input-group-sm">
                  <label for="team_type">Tipe Tim <span class="text-danger">*</span></label>
                  <select id="team_type" name="team_type" class="form-control custom-select @error('team_type') is-invalid @enderror" required>
                    @php $teamType = old('team_type', ($teamType ?? 'Individu')); @endphp
                    <option value="" disabled {{ $teamType ? '' : 'selected' }}>Pilih tipe</option>
                    <option value="Individu" {{ $teamType==='Individu' ? 'selected':'' }}>Individu</option>
                    <option value="Kelompok" {{ $teamType==='Kelompok' ? 'selected':'' }}>Kelompok</option>
                  </select>
                  @error('team_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group input-group-sm">
                  <label for="field">Bidang <span class="text-danger">*</span></label>
                  @php $fieldVal = old('field', $achievement->field); @endphp
                  <select class="form-control custom-select @error('field') is-invalid @enderror" id="field" name="field">
                    <option value="" disabled {{ $fieldVal ? '' : 'selected' }}>Pilih Bidang</option>
                    <option value="Akademik"    {{ $fieldVal==='Akademik' ? 'selected':'' }}>Akademik</option>
                    <option value="NonAkademik" {{ $fieldVal==='NonAkademik' ? 'selected':'' }}>Non-Akademik</option>
                  </select>
                  @error('field')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group input-group-sm">
                  <label for="level">Tingkat <span class="text-danger">*</span></label>
                  @php $levelVal = old('level', $achievement->level); @endphp
                  <select class="form-control custom-select @error('level') is-invalid @enderror" id="level" name="level">
                    <option value="" disabled {{ $levelVal ? '' : 'selected' }}>Pilih Tingkat</option>
                    <option value="Region"        {{ $levelVal==='Region' ? 'selected':'' }}>Regional</option>
                    <option value="National"      {{ $levelVal==='National' ? 'selected':'' }}>Nasional</option>
                    <option value="International" {{ $levelVal==='International' ? 'selected':'' }}>Internasional</option>
                  </select>
                  @error('level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group input-group-sm">
                  <label for="organizer">Penyelenggara <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('organizer') is-invalid @enderror"
                         id="organizer" name="organizer"
                         value="{{ old('organizer', $achievement->organizer) }}"
                         placeholder="Nama penyelenggara">
                  @error('organizer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                @if(Auth::user()->role === 'admin' && isset($departments))
                  <div class="form-group input-group-sm">
                    <label for="department_id">Program Studi <span class="text-danger">*</span></label>
                    <select id="department_id" name="department_id" class="form-control custom-select @error('department_id') is-invalid @enderror" required>
                      <option value="" disabled {{ old('department_id', $achievement->department_id) ? '' : 'selected' }}>Pilih Program Studi</option>
                      @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ (string)old('department_id', $achievement->department_id)===(string)$dept->id ? 'selected':'' }}>
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
                <div class="form-group input-group-sm">
                  <label for="competition">Kompetisi <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('competition') is-invalid @enderror"
                         id="competition" name="competition"
                         value="{{ old('competition', $achievement->competition) }}"
                         placeholder="Nama kompetisi">
                  @error('competition')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group input-group-sm">
                  <label for="rank">Peringkat <span class="text-danger">*</span></label>
                  <input type="text" class="form-control @error('rank') is-invalid @enderror"
                         id="rank" name="rank"
                         value="{{ old('rank', $achievement->rank) }}"
                         placeholder="Contoh: Juara 1 / Harapan 1">
                  @error('rank')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                  <div class="col-md-6">
                    <div class="form-group input-group-sm">
                      <label for="month">Bulan <span class="text-danger">*</span></label>
                      <select class="form-control custom-select @error('month') is-invalid @enderror" id="month" name="month">
                        <option value="" disabled {{ old('month', $achievement->month) ? '' : 'selected' }}>Pilih Bulan</option>
                        @for($m=1;$m<=12;$m++)
                          @php $val = $m < 10 ? '0'.$m : (string)$m; @endphp
                          <option value="{{ $val }}" {{ (string)old('month', $achievement->month)===$val ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromDate(null,$m,1)->locale('id')->translatedFormat('F') }}
                          </option>
                        @endfor
                      </select>
                      @error('month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group input-group-sm">
                      <label for="year">Tahun <span class="text-danger">*</span></label>
                      <input type="number" class="form-control @error('year') is-invalid @enderror"
                             id="year" name="year"
                             value="{{ old('year', $achievement->year) }}"
                             placeholder="YYYY" min="1900" max="{{ date('Y') + 1 }}">
                      @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                  </div>
                </div>

                {{-- Dokumentasi: tambah baru --}}
                <div class="form-group input-group-sm">
                  <label for="documents">Tambah Dokumentasi (opsional)</label>
                  <div class="custom-file">
                    <input type="file" id="documents" name="documentations[]" class="custom-file-input @error('documentations.*') is-invalid @enderror" accept="image/png,image/jpeg" multiple>
                    <label class="custom-file-label text-truncate" for="documents">Pilih gambar...</label>
                  </div>
                  <small class="text-danger d-block"><i>JPG / JPEG / PNG - Maksimal 1 MB per file</i></small>
                  @error('documentations.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- DOKUMENTASI EXISITING --}}
        <div class="mb-3">
          <h5 class="mb-2">Dokumentasi Saat Ini</h5>
          <div class="row">
            @forelse($achievement->documentations as $doc)
              <div class="col-md-3 mb-3">
                <div class="border rounded p-2 h-100 d-flex flex-column">
                  <img src="{{ asset('image-documentations/'.$doc->image) }}" class="img-fluid mb-2 rounded" alt="Dokumentasi">
                  <div class="custom-control custom-checkbox mt-auto">
                    <input type="checkbox" class="custom-control-input" id="del_doc_{{ $doc->id }}" name="delete_docs[]" value="{{ $doc->id }}">
                    <label class="custom-control-label" for="del_doc_{{ $doc->id }}">Hapus</label>
                  </div>
                </div>
              </div>
            @empty
              <div class="col-12 text-muted">Belum ada dokumentasi.</div>
            @endforelse
          </div>
        </div>

        {{-- PESERTA --}}
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Peserta</h5>
          <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-participant">
            <i class="fa fa-plus mr-1"></i> Tambah Peserta
          </button>
        </div>

        <div id="participants" class="row">
          @php
            $students = old('names') ? collect(old('names'))->map(function($_, $i){
              return [
                'id'    => old('participant_ids')[$i] ?? null,
                'name'  => old('names')[$i] ?? '',
                'nim'   => old('nims')[$i] ?? '',
                'photo' => null,
                'cert'  => null,
              ];
            }) : $achievement->students->map(function($s){
              return [
                'id'    => $s->id,
                'name'  => $s->name,
                'nim'   => $s->nim,
                'photo' => $s->photo ? asset('image-profile/'.$s->photo) : null,
                'cert'  => $s->pivot->certificate ? asset('image-certificate/'.$s->pivot->certificate) : null,
              ];
            });
          @endphp

          @foreach($students as $idx => $stu)
            <div class="participant-col col-md-6">
              <div class="participant-row shadow bg-white border rounded p-3 mb-3">
                <input type="hidden" name="participant_ids[]" value="{{ $stu['id'] }}">

                <div class="form-group input-group-sm">
                  <label>Nama Mahasiswa <span class="text-danger">*</span></label>
                  <input type="text" name="names[]" class="form-control" placeholder="Nama lengkap" value="{{ $stu['name'] }}">
                </div>
                <div class="form-group input-group-sm">
                  <label>NIM <span class="text-danger">*</span></label>
                  <input type="text" name="nims[]" class="form-control" placeholder="Nomor Induk Mahasiswa" value="{{ $stu['nim'] }}">
                </div>

                <div class="form-group input-group-sm">
                  <label>Foto Profil (opsional)</label>
                  <div class="custom-file">
                    <input type="file" name="photos[]" class="custom-file-input" accept="image/png,image/jpeg">
                    <label class="custom-file-label text-truncate">Pilih foto...</label>
                  </div>
                  @if($stu['photo'])
                    <small class="text-muted d-block mt-1">Saat ini: <a href="{{ $stu['photo'] }}" target="_blank">Lihat foto</a></small>
                  @endif
                  <small class="text-danger d-block"><i>JPG / JPEG / PNG - Maksimal 1 MB</i></small>
                </div>

                <div class="form-group input-group-sm">
                  <label>Sertifikat {{ $stu['id'] ? '(kosongkan untuk tetap)' : '' }} <span class="text-danger">*</span></label>
                  <div class="custom-file">
                    <input type="file" name="certificates[]" class="custom-file-input" accept="image/png,image/jpeg" {{ $stu['id'] ? '' : 'required' }}>
                    <label class="custom-file-label text-truncate">Pilih sertifikat...</label>
                  </div>
                  @if($stu['cert'])
                    <small class="text-muted d-block mt-1">Saat ini: <a href="{{ $stu['cert'] }}" target="_blank">Lihat sertifikat</a></small>
                  @endif
                  <small class="text-danger d-block"><i>JPG / JPEG / PNG - Maksimal 1 MB</i></small>
                </div>

                <div class="d-flex align-items-center">
                  <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="rm_{{ $idx }}" name="remove_flags[{{ $idx }}]" value="1">
                    <label class="custom-control-label" for="rm_{{ $idx }}">Hapus baris ini</label>
                  </div>
                  <button type="button" class="btn btn-outline-danger btn-sm ml-auto btn-remove-row d-none">Hapus</button>
                </div>
              </div>
            </div>
          @endforeach
        </div>

        <small class="text-muted d-block mb-3">
          Sertifikat yang tidak diganti akan dipertahankan.
        </small>

        <div class="row mt-3">
          <div class="col-md"><i><span class="text-danger">*</span> <small>Wajib diisi</small></i></div>
          <div class="col-md text-md-right">
            <button type="submit" class="btn btn-md btn-primary">Perbarui</button>
          </div>
        </div>
      </form>
    </div>

    @include('template.footer')
  </div>
</div>

{{-- TEMPLATE KARTU PESERTA BARU --}}
<div id="participant-template" class="d-none">
  <div class="participant-col col-md-6">
    <div class="participant-row shadow bg-white border rounded p-3 mb-3">
      <input type="hidden" name="participant_ids[]" value="">

      <div class="form-group input-group-sm">
        <label>Nama Mahasiswa <span class="text-danger">*</span></label>
        <input type="text" name="names[]" class="form-control" placeholder="Nama lengkap">
      </div>
      <div class="form-group input-group-sm">
        <label>NIM <span class="text-danger">*</span></label>
        <input type="text" name="nims[]" class="form-control" placeholder="Nomor Induk Mahasiswa">
      </div>
      <div class="form-group input-group-sm">
        <label>Foto Profil (opsional)</label>
        <div class="custom-file">
          <input type="file" name="photos[]" class="custom-file-input" accept="image/png,image/jpeg">
          <label class="custom-file-label text-truncate">Pilih foto...</label>
        </div>
        <small class="text-danger d-block"><i>JPG / JPEG / PNG - Maksimal 1 MB</i></small>
      </div>
      <div class="form-group input-group-sm">
        <label>Sertifikat <span class="text-danger">*</span></label>
        <div class="custom-file">
          <input type="file" name="certificates[]" class="custom-file-input" accept="image/png,image/jpeg" required>
          <label class="custom-file-label text-truncate">Pilih sertifikat...</label>
        </div>
        <small class="text-danger d-block"><i>JPG / JPEG / PNG - Maksimal 1 MB</i></small>
      </div>
      <div class="d-flex">
        <button type="button" class="btn btn-outline-danger btn-sm ml-auto btn-remove-row">Hapus</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script>
  function bindFileLabel(scope) {
    var root = scope || document;
    var inputs = root.querySelectorAll('.custom-file-input');
    for (var i=0;i<inputs.length;i++){
      inputs[i].addEventListener('change', function(){
        var label = this.nextElementSibling;
        if (!label) return;
        label.textContent = this.files && this.files.length ? (this.files.length===1 ? this.files[0].name : this.files.length+' file dipilih') : 'Pilih file...';
      });
    }
  }

  var wrap   = document.getElementById('participants');
  var addBtn = document.getElementById('btn-add-participant');
  var tpl    = document.getElementById('participant-template').firstElementChild;
  var teamTypeEl = document.getElementById('team_type');

  function makeCard() { return tpl.cloneNode(true); }

  function setGrid(isGrid) {
    var cols = wrap.querySelectorAll('.participant-col');
    for (var i=0;i<cols.length;i++){
      cols[i].classList.remove('col-md-6','col-12');
      cols[i].classList.add(isGrid ? 'col-md-6' : 'col-12');
    }
  }

  function wireRemove(scope) {
    var root = scope || document;
    var btns = root.querySelectorAll('.btn-remove-row');
    for (var i=0;i<btns.length;i++){
      btns[i].onclick = function(){
        var type = teamTypeEl.value || 'Individu';
        var cols = wrap.querySelectorAll('.participant-col');
        if (type === 'Individu' && cols.length <= 1) return;
        if (type === 'Kelompok' && cols.length <= 2) return;
        var col = this.closest('.participant-col');
        if (col) col.remove();
      };
    }
  }

  function toggleControlsByType(type) {
    if (type === 'Individu') {
      addBtn.classList.add('d-none');
      var btns = wrap.querySelectorAll('.btn-remove-row');
      for (var i=0;i<btns.length;i++){ btns[i].classList.add('d-none'); }
    } else {
      addBtn.classList.remove('d-none');
      var btns = wrap.querySelectorAll('.btn-remove-row');
      for (var i=0;i<btns.length;i++){ btns[i].classList.remove('d-none'); }
    }
  }

  function ensureMinimum(type) {
    var minReq = (type === 'Kelompok') ? 2 : 1;
    var cols = wrap.querySelectorAll('.participant-col');
    if (cols.length < minReq) {
      for (var i=cols.length;i<minReq;i++){
        var card = makeCard();
        wrap.appendChild(card);
      }
    }
  }

  // Add button
  addBtn.addEventListener('click', function(){
    if ((teamTypeEl.value || 'Individu') !== 'Kelompok') return;
    var card = makeCard();
    wrap.appendChild(card);
    bindFileLabel(card);
    wireRemove(card);
  });

  // On change type: atur grid & tombol
  teamTypeEl.addEventListener('change', function(){
    var t = this.value;
    ensureMinimum(t);
    setGrid(t === 'Kelompok');
    toggleControlsByType(t);
  });

  // Init
  (function init(){
    bindFileLabel(document);
    wireRemove(document);
    var initialType = teamTypeEl.value || 'Individu';
    ensureMinimum(initialType);
    setGrid(initialType === 'Kelompok');
    toggleControlsByType(initialType);
  })();
</script>
@endsection
