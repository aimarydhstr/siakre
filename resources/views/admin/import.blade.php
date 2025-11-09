@extends('layout.base')
@section('title','Import Achievement (Excel)')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <div class="d-flex align-items-center mb-3 mt-md-4">
        <h4 class="font-weight-bold mb-0">Import Achievement (Excel)</h4>
      </div>

      {{-- Alerts --}}
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
      @endif
      @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif

      {{-- Detail error per baris --}}
      @if (session('import_errors') && is_array(session('import_errors')) && count(session('import_errors')))
        <div class="card border-danger mb-4">
          <div class="card-header bg-danger text-white py-2">Detail Error Import</div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-striped mb-0">
                <thead class="thead-light">
                  <tr>
                    <th style="width:100px;">Row</th>
                    <th>Pesan</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach(session('import_errors') as $err)
                    <tr>
                      <td>{{ $err['row'] ?? '-' }}</td>
                      <td>{{ $err['message'] ?? '-' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif

      <div class="card rounded shadow">
        <div class="card-body">
          <form action="{{ route('achievements.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="excel">File Excel <span class="text-danger">*</span></label>
                <div class="custom-file">
                  <input type="file"
                         class="custom-file-input @error('excel') is-invalid @enderror"
                         id="excel"
                         name="excel"
                         accept=".xlsx,.xls,.csv"
                         required>
                  <label class="custom-file-label" for="excel">Pilih file…</label>
                  @error('excel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <small class="text-muted d-block mt-1">Format: .xlsx / .xls / .csv (maks 20 MB)</small>
              </div>

              <div class="form-group col-md-6">
                <label>Petunjuk</label>
                <div class="border rounded p-2 small bg-light">
                  <div>Header <strong>wajib</strong> (case-insensitive):</div>
                  <code>team, team_type, level, field, organizer, month, year, competition, department, students</code>
                  <div class="mt-2">Header <strong>opsional</strong>:</div>
                  <code>rank</code>
                  <ul class="mb-0 mt-2 pl-3">
                    <li><strong>team_type</strong>: <em>Individu</em> atau <em>Kelompok</em>. (Jika Kelompok, jumlah peserta minimal 2 — validasi akhir tetap di server.)</li>
                    <li><strong>level</strong>: contoh — <em>Region</em>, <em>National</em>, <em>International</em>.</li>
                    <li><strong>field</strong>: contoh — <em>Akademik</em>, <em>NonAkademik</em>.</li>
                    <li><strong>month</strong>: format 2 digit (mis. <code>03</code> untuk Maret) atau angka numeric; <strong>year</strong>: 4 digit (mis. <code>2025</code>).</li>
                    <li><strong>department</strong>: bisa nama prodi (mis. "Informatika (S1)") atau department_id jika ingin memakai id.</li>
                    <li><strong>students</strong>: kolom fleksibel untuk peserta — dukung format:
                      <ul>
                        <li><code>Name (NIM)</code> — contoh: <code>Arya (21081010001); Dian (21081010002)</code></li>
                        <li><code>NIM</code> saja, dipisah dengan <code>;</code> atau <code>,</code></li>
                        <li><code>Name</code> saja — akan dibuat student dengan NIM placeholder.</li>
                      </ul>
                    </li>
                    <li>File foto / sertifikat <strong>tidak</strong> diimport lewat Excel — harus diunggah terpisah setelah import jika diperlukan.</li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="mt-3">
              <label>Contoh Data (satu baris):</label>
              <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>team</th>
                      <th>team_type</th>
                      <th>level</th>
                      <th>field</th>
                      <th>organizer</th>
                      <th>month</th>
                      <th>year</th>
                      <th>competition</th>
                      <th>rank</th>
                      <th>department</th>
                      <th>students</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>Tim Robotika A</td>
                      <td>Kelompok</td>
                      <td>National</td>
                      <td>Akademik</td>
                      <td>Universitas ABC</td>
                      <td>10</td>
                      <td>2025</td>
                      <td>Kompetisi Robot Nasional</td>
                      <td>Juara 1</td>
                      <td>Informatika (S1)</td>
                      <td>Arya (21081010001); Dian (21081010002)</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
              <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Kembali</a>
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-upload mr-1"></i> Import
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>

    @include('template.footer')
  </div>
</div>
@endsection

@section('js')
<script>
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
</script>
@endsection
