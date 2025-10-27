@extends('layout.base')
@section('title','Sub Bidang: '.$expertise->name)

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <h4 class="font-weight-bold my-3 mt-md-4">
        Sub Bidang Keilmuan — <span class="text-primary">{{ $expertise->name }}</span>
      </h4>

      {{-- Flash --}}
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      {{-- Error --}}
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
            <div>Total Sub-bidang: {{ method_exists($fields,'total') ? $fields->total() : $fields->count() }}</div>
            <div>
              <a href="{{ route('expertises.index') }}" class="btn btn-light btn-sm mr-2">
                <i class="fa fa-arrow-left mr-1"></i> Kembali
              </a>
              <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-add-field">
                <i class="fa fa-plus mr-1"></i> Tambah Sub-bidang
              </button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width:64px;">#</th>
                  <th>Nama Sub-bidang</th>
                  <th style="width:240px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($fields as $i => $f)
                  <tr>
                    <td>{{ (method_exists($fields,'currentPage') ? ($fields->currentPage()-1)*$fields->perPage() : 0) + $i + 1 }}</td>
                    <td class="text-break">{{ $f->name }}</td>
                    <td>
                      <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modal-edit-field-{{ $f->id }}">
                        <i class="fa fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-delete-field-{{ $f->id }}">
                        <i class="fa fa-trash"></i>
                      </button>
                    </td>
                  </tr>

                  {{-- Modal Edit --}}
                  <div class="modal fade" id="modal-edit-field-{{ $f->id }}" tabindex="-1" role="dialog" aria-labelledby="label-edit-field-{{ $f->id }}" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                      <div class="modal-content">
                        <form action="{{ route('expertise-fields.update', $f->id) }}" method="POST">
                          @csrf
                          @method('PUT')
                          <input type="hidden" name="_from" value="edit-{{ $f->id }}">
                          <div class="modal-header">
                            <h5 class="modal-title" id="label-edit-field-{{ $f->id }}">Edit Sub-bidang</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <div class="form-group">
                              <label>Parent</label>
                              <select name="expertise_id" class="form-control" required>
                                @foreach($allExpertises as $opt)
                                  <option value="{{ $opt->id }}" {{ $opt->id == $f->expertise_id ? 'selected' : '' }}>
                                    {{ $opt->name }}
                                  </option>
                                @endforeach
                              </select>
                            </div>
                            <div class="form-group">
                              <label for="name-edit-{{ $f->id }}">Nama <span class="text-danger">*</span></label>
                              <input type="text"
                                     id="name-edit-{{ $f->id }}"
                                     name="name"
                                     class="form-control @error('name') is-invalid @enderror"
                                     value="{{ old('name', $f->name) }}"
                                     required
                                     autocomplete="off">
                              @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                              @enderror
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

                  {{-- Modal Delete --}}
                  <div class="modal fade" id="modal-delete-field-{{ $f->id }}" tabindex="-1" role="dialog" aria-labelledby="label-delete-field-{{ $f->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                      <div class="modal-content">
                        <form action="{{ route('expertise-fields.destroy', $f->id) }}" method="POST">
                          @csrf
                          @method('DELETE')
                          <div class="modal-header">
                            <h5 class="modal-title text-danger" id="label-delete-field-{{ $f->id }}">Hapus Sub-bidang</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <p class="mb-0">Yakin ingin menghapus <strong>{{ $f->name }}</strong>? Tidak dapat dihapus bila sedang dipakai dosen.</p>
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
                    <td colspan="3" class="text-center">Belum ada data.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if (method_exists($fields,'links'))
            <div class="mt-3">
              {{ $fields->withQueryString()->links() }}
            </div>
          @endif
        </div>
      </div>

    </div>

    @include('template.footer')
  </div>
</div>

{{-- Modal Add --}}
<div class="modal fade" id="modal-add-field" tabindex="-1" role="dialog" aria-labelledby="label-add-field" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="{{ route('expertise-fields.store') }}" method="POST">
        @csrf
        <input type="hidden" name="_from" value="create">
        <div class="modal-header">
          <h5 class="modal-title" id="label-add-field">Tambah Sub-bidang</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="expertise_id" value="{{ $expertise->id }}">
          <div class="form-group">
            <label for="name-add">Nama <span class="text-danger">*</span></label>
            <input type="text"
                   id="name-add"
                   name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}"
                   required
                   autocomplete="off"
                   placeholder="mis. Biomedical">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
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
  // Autofocus
  $('#modal-add-field').on('shown.bs.modal', function () {
    $('#name-add').trigger('focus');
  });

  @if(isset($fields) && count($fields))
    @foreach($fields as $f)
      $('#modal-edit-field-{{ $f->id }}').on('shown.bs.modal', function () {
        $('#name-edit-{{ $f->id }}').trigger('focus');
      });
    @endforeach
  @endif

  // Auto open modal if validation error after submit
  @if ($errors->any() && old('_from'))
    const from = @json(old('_from'));
    if (from === 'create') {
      $('#modal-add-field').modal('show');
    } else if (from.startsWith('edit-')) {
      const id = from.split('-')[1];
      $('#modal-edit-field-' + id).modal('show');
    }
  @endif
</script>
@endsection
