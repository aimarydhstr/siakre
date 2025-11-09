@extends('layout.base')
@section('title','Import Artikel (Excel)')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <div class="d-flex align-items-center mb-3 mt-md-4">
        <h4 class="font-weight-bold mb-0">Import Artikel (Excel)</h4>
      </div>

      {{-- Alert validasi & status --}}
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif

      {{-- Detail error per-baris (jika ada) --}}
      @if (session('import_errors') && is_array(session('import_errors')) && count(session('import_errors')))
        <div class="card border-danger mb-4">
          <div class="card-header bg-danger text-white py-2">
            Detail Error Import
          </div>
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
          <form action="{{ route('article.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- GRID 2 kolom agar ringkas --}}
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
                <label>Petunjuk Singkat</label>
                <div class="border rounded p-2 small bg-light">
                  <div>Header yang <strong>WAJIB</strong> ada:</div>
                  <code>department, title, issn, type_journal, url, doi, publisher, date, category, volume, number</code>
                  <div class="mt-2">Header <strong>opsional</strong>:</div>
                  <code>lecturers_nidn, students_nim</code>
                  <ul class="mb-0 mt-2 pl-3">
                    <li><em>department</em>: nama prodi (toleran salah ketik ringan).</li>
                    <li><em>date</em>: <code>dd-mm-yyyy</code> (boleh <code>yyyy-mm-dd</code> / <code>dd/mm/yyyy</code>).</li>
                    <li><em>category</em>: <code>dosen</code> / <code>mahasiswa</code>.</li>
                    <li><em>lecturers_nidn</em> & <em>students_nim</em> boleh banyak, pisahkan dengan koma/titik koma.</li>
                    <li>File PDF artikel <em>tidak</em> diimport dari Excel (kolom <code>file</code> diset null).</li>
                  </ul>
                </div>
              </div>
            </div>

            {{-- Contoh baris --}}
            <div class="mt-3">
              <label>Contoh Data (baris pertama setelah header)</label>
              <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>department</th>
                      <th>title</th>
                      <th>issn</th>
                      <th>type_journal</th>
                      <th>url</th>
                      <th>doi</th>
                      <th>publisher</th>
                      <th>date</th>
                      <th>category</th>
                      <th>volume</th>
                      <th>number</th>
                      <th>lecturers_nidn</th>
                      <th>students_nim</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>Informatika (S1)</td>
                      <td>Deteksi Anomali Jaringan...</td>
                      <td>1234-5678</td>
                      <td>Jurnal Nasional Terakreditasi</td>
                      <td>https://jurnal.example.com/vol1/issue2/123</td>
                      <td>10.1234/abcd.2025.001</td>
                      <td>Jurnal XYZ</td>
                      <td>31-10-2025</td>
                      <td>dosen</td>
                      <td>12</td>
                      <td>2</td>
                      <td>0012345678; 0098765432</td>
                      <td>Arya (21081010001); Dian (21081010002)</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
              <a href="{{ route('article') }}" class="btn btn-outline-secondary">Kembali</a>
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
  // tampilkan nama file di custom-file-label
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
