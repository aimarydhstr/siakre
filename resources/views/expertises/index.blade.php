@extends('layout.base')
@section('title','Master Bidang Keilmuan')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <h4 class="font-weight-bold my-3 mt-md-4">Master Bidang Keilmuan</h4>

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
            <div>Total Bidang: {{ method_exists($expertises,'total') ? $expertises->total() : $expertises->count() }}</div>
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-add-expertise">
              <i class="fa fa-plus mr-1"></i> Tambah Bidang
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width:64px;">#</th>
                  <th>Nama Bidang</th>
                  <th style="width:140px;" class="text-center">Jumlah Sub</th>
                  <th style="width:240px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @forelse($expertises as $i => $ex)
                  <tr>
                    <td>{{ (method_exists($expertises,'currentPage') ? ($expertises->currentPage()-1)*$expertises->perPage() : 0) + $i + 1 }}</td>
                    <td class="text-break">{{ $ex->name }}</td>
                    <td class="text-center">
                      <span class="badge badge-info">{{ $ex->fields_count ?? $ex->fields_count }}</span>
                    </td>
                    <td>
                      <a class="btn btn-success btn-sm" href="{{ route('expertise-fields.index', $ex->id) }}">
                        <i class="fa fa-sitemap mr-1"></i> Kelola Sub
                      </a>
                      <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modal-edit-expertise-{{ $ex->id }}">
                        <i class="fa fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-delete-expertise-{{ $ex->id }}">
                        <i class="fa fa-trash"></i>
                      </button>
                    </td>
                  </tr>

                  {{-- Modal Edit --}}
                  <div class="modal fade" id="modal-edit-expertise-{{ $ex->id }}" tabindex="-1" role="dialog" aria-labelledby="label-edit-expertise-{{ $ex->id }}" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                      <div class="modal-content">
                        <form action="{{ route('expertises.update', $ex->id) }}" method="POST">
                          @csrf
                          @method('PUT')
                          <input type="hidden" name="_from" value="edit-{{ $ex->id }}">
                          <div class="modal-header">
                            <h5 class="modal-title" id="label-edit-expertise-{{ $ex->id }}">Edit Bidang</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <div class="form-group">
                              <label for="name-edit-{{ $ex->id }}">Nama <span class="text-danger">*</span></label>
                              <input type="text"
                                     id="name-edit-{{ $ex->id }}"
                                     name="name"
                                     class="form-control @error('name') is-invalid @enderror"
                                     value="{{ old('name', $ex->name) }}"
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
                  <div class="modal fade" id="modal-delete-expertise-{{ $ex->id }}" tabindex="-1" role="dialog" aria-labelledby="label-delete-expertise-{{ $ex->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                      <div class="modal-content">
                        <form action="{{ route('expertises.destroy', $ex->id) }}" method="POST">
                          @csrf
                          @method('DELETE')
                          <div class="modal-header">
                            <h5 class="modal-title text-danger" id="label-delete-expertise-{{ $ex->id }}">Hapus Bidang</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <p class="mb-1">Yakin ingin menghapus <strong>{{ $ex->name }}</strong>?</p>
                            <small class="text-muted">Tidak dapat dihapus jika masih memiliki Sub-bidang.</small>
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
                    <td colspan="4" class="text-center">Belum ada data.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if (method_exists($expertises,'links'))
            <div class="mt-3">
              {{ $expertises->withQueryString()->links() }}
            </div>
          @endif
        </div>
      </div>

    </div>

    @include('template.footer')
  </div>
</div>

{{-- Modal Add --}}
<div class="modal fade" id="modal-add-expertise" tabindex="-1" role="dialog" aria-labelledby="label-add-expertise" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="{{ route('expertises.store') }}" method="POST">
        @csrf
        <input type="hidden" name="_from" value="create">
        <div class="modal-header">
          <h5 class="modal-title" id="label-add-expertise">Tambah Bidang Keilmuan</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="name-add">Nama <span class="text-danger">*</span></label>
            <input type="text"
                   id="name-add"
                   name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}"
                   required
                   autocomplete="off"
                   placeholder="mis. Computer Science">
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
  $('#modal-add-expertise').on('shown.bs.modal', function () {
    $('#name-add').trigger('focus');
  });

  @if(isset($expertises) && count($expertises))
    @foreach($expertises as $ex)
      $('#modal-edit-expertise-{{ $ex->id }}').on('shown.bs.modal', function () {
        $('#name-edit-{{ $ex->id }}').trigger('focus');
      });
    @endforeach
  @endif

  // Auto open modal if validation error after submit
  @if ($errors->any() && old('_from'))
    const from = @json(old('_from'));
    if (from === 'create') {
      $('#modal-add-expertise').modal('show');
    } else if (from.startsWith('edit-')) {
      const id = from.split('-')[1];
      $('#modal-edit-expertise-' + id).modal('show');
    }
  @endif
</script>
@endsection
