{{-- resources/views/lecturers/import.blade.php --}}
@extends('layout.base')
@section('title','Import Dosen (Excel)')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <div class="d-flex align-items-center mb-3 mt-md-4">
        <h4 class="font-weight-bold mb-0">Import Dosen (Excel)</h4>
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

      {{-- Error per-baris --}}
      @php $rowErrors = session('import_errors'); @endphp
      @if (is_array($rowErrors) && count($rowErrors))
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
                  @foreach($rowErrors as $err)
                    <tr>
                      <td>{{ is_array($err) ? ($err['row'] ?? '-') : '-' }}</td>
                      <td>{{ is_array($err) ? ($err['message'] ?? '-') : $err }}</td>
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
          <form action="{{ route('lecturers.import.store') }}" method="POST" enctype="multipart/form-data">
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
                  <div>Header <strong>wajib</strong>:</div>
                  <code>nidn, name, department</code>
                  <div class="mt-2">Header <strong>opsional</strong>:</div>
                  <code>nik, birth_place, birth_date, address, position, marital_status, expertise_field</code>
                  <ul class="mb-0 mt-2 pl-3">
                    <li><em>department</em> dicocokkan berdasarkan <strong>nama prodi</strong> (ada toleransi typo ringan).</li>
                    <li><em>birth_date</em> fleksibel (disarankan YYYY-MM-DD).</li>
                    <li><em>position</em>: Asisten Ahli, Lektor, Lektor Kepala, Profesor.</li>
                    <li><em>marital_status</em>: Menikah, Belum Menikah.</li>
                    <li>Baris dengan <strong>NIDN sudah ada</strong> akan di-<strong>update</strong>.</li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="mt-3">
              <label>Contoh Data</label>
              <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th>nidn</th>
                      <th>name</th>
                      <th>department</th>
                      <th>nik</th>
                      <th>birth_place</th>
                      <th>birth_date</th>
                      <th>address</th>
                      <th>position</th>
                      <th>marital_status</th>
                      <th>expertise_field</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>0012345678</td>
                      <td>Dr. Contoh</td>
                      <td>Informatika (S1)</td>
                      <td>3578XXXXXXXX0001</td>
                      <td>Bandung</td>
                      <td>1985-02-14</td>
                      <td>Jl. Mawar No. 1</td>
                      <td>Lektor</td>
                      <td>Menikah</td>
                      <td>Data Science</td>
                    </tr>
                    <tr>
                      <td>0098765432</td>
                      <td>Prof. Satu Dua</td>
                      <td>Sistem Informasi (S1)</td>
                      <td></td>
                      <td>Surabaya</td>
                      <td>1979-10-01</td>
                      <td>Jl. Melati No. 2</td>
                      <td>Profesor</td>
                      <td>Belum Menikah</td>
                      <td>Operations Research</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
              <a href="{{ route('lecturers.index') }}" class="btn btn-outline-secondary">Kembali</a>
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
