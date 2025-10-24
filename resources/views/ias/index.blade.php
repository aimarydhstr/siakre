@extends('layout.base')
@section('title','Implement Agreements')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <h4 class="font-weight-bold my-3 mt-md-4">
        Implement Agreements — Cooperation: <span class="text-monospace">{{ $cooperation->letter_number }}</span>
      </h4>

      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="mb-3">
        <a href="{{ route('cooperations.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="fa fa-arrow-left mr-1"></i> Back to Cooperations
        </a>
      </div>

      <div class="card rounded shadow mt-3 mb-5">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>Total : {{ method_exists($ias,'total') ? $ias->total() : $ias->count() }}</div>
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-add-ia">
              <i class="fa fa-plus mr-1"></i> Add IA
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width:64px;">#</th>
                  <th>MoU Name</th>
                  <th>IA Name</th>
                  <th>IA File</th>
                  <th>Proof</th>
                  <th style="width:160px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($ias as $i => $ia)
                  <tr>
                    <td>{{ ($ias->currentPage()-1)*$ias->perPage() + $i + 1 }}</td>
                    <td class="text-break">{{ $ia->mou_name }}</td>
                    <td class="text-break">{{ $ia->ia_name }}</td>
                    <td>
                      @if($ia->file)
                        <a href="{{ asset($ia->file) }}" target="_blank" class="btn btn-outline-secondary btn-sm">View PDF</a>
                      @else
                        -
                      @endif
                    </td>
                    <td>
                      @if($ia->proof)
                        <a href="{{ asset($ia->proof) }}" target="_blank" class="btn btn-outline-secondary btn-sm">View</a>
                      @else
                        -
                      @endif
                    </td>
                    <td>
                      <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modal-edit-ia-{{ $ia->id }}">
                        <i class="fa fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-delete-ia-{{ $ia->id }}">
                        <i class="fa fa-trash"></i>
                      </button>
                    </td>
                  </tr>

                  {{-- Edit Modal --}}
                  <div class="modal fade" id="modal-edit-ia-{{ $ia->id }}" tabindex="-1" role="dialog" aria-labelledby="label-edit-ia-{{ $ia->id }}" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                      <div class="modal-content">
                        <form action="{{ route('ias.update', [$cooperation->id, $ia->id]) }}" method="POST" enctype="multipart/form-data">
                          @csrf
                          @method('PUT')
                          <div class="modal-header">
                            <h5 class="modal-title" id="label-edit-ia-{{ $ia->id }}">Edit IA</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                          </div>
                          <div class="modal-body">
                            <div class="form-group">
                              <label for="mou-name-edit-{{ $ia->id }}">MoU Name <span class="text-danger">*</span></label>
                              <input type="text" id="mou-name-edit-{{ $ia->id }}" name="mou_name"
                                     value="{{ old('mou_name', $ia->mou_name) }}"
                                     class="form-control @error('mou_name') is-invalid @enderror" required>
                              @error('mou_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="ia-name-edit-{{ $ia->id }}">IA Name <span class="text-danger">*</span></label>
                              <input type="text" id="ia-name-edit-{{ $ia->id }}" name="ia_name"
                                     value="{{ old('ia_name', $ia->ia_name) }}"
                                     class="form-control @error('ia_name') is-invalid @enderror" required>
                              @error('ia_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="file-edit-{{ $ia->id }}">Replace IA PDF (optional)</label>
                              <input type="file" id="file-edit-{{ $ia->id }}" name="file" class="form-control-file @error('file') is-invalid @enderror" accept="application/pdf">
                              <small class="text-muted d-block">PDF only, max 10 MB.</small>
                              @error('file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="proof-edit-{{ $ia->id }}">Replace Proof (optional)</label>
                              <input type="file" id="proof-edit-{{ $ia->id }}" name="proof" class="form-control-file @error('proof') is-invalid @enderror" accept="application/pdf,image/png,image/jpeg">
                              <small class="text-muted d-block">PDF/JPG/PNG, max 10 MB.</small>
                              @error('proof') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">Save Changes</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>

                  {{-- Delete Modal --}}
                  <div class="modal fade" id="modal-delete-ia-{{ $ia->id }}" tabindex="-1" role="dialog" aria-labelledby="label-delete-ia-{{ $ia->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                      <div class="modal-content">
                        <form action="{{ route('ias.destroy', [$cooperation->id, $ia->id]) }}" method="POST">
                          @csrf
                          @method('DELETE')
                          <div class="modal-header">
                            <h5 class="modal-title text-danger" id="label-delete-ia-{{ $ia->id }}">Delete IA</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                          </div>
                          <div class="modal-body">
                            <p class="mb-0">Are you sure you want to delete <strong>{{ $ia->ia_name }}</strong>?</p>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>

                @empty
                  <tr>
                    <td colspan="6" class="text-center">No IA yet.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if(method_exists($ias,'links'))
            <div class="mt-3">{{ $ias->withQueryString()->links() }}</div>
          @endif
        </div>
      </div>
    </div>

    @include('template.footer')
  </div>
</div>

{{-- Add IA Modal --}}
<div class="modal fade" id="modal-add-ia" tabindex="-1" role="dialog" aria-labelledby="label-add-ia" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="{{ route('ias.store', $cooperation->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="label-add-ia">Add IA</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="mou-name-add">MoU Name <span class="text-danger">*</span></label>
            <input type="text" id="mou-name-add" name="mou_name" class="form-control @error('mou_name') is-invalid @enderror"
                   value="{{ old('mou_name', 'MoU '.$cooperation->letter_number) }}" required>
            @error('mou_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label for="ia-name-add">IA Name <span class="text-danger">*</span></label>
            <input type="text" id="ia-name-add" name="ia_name" class="form-control @error('ia_name') is-invalid @enderror"
                   value="{{ old('ia_name') }}" required placeholder="e.g., Research Collaboration 2025">
            @error('ia_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label for="file-add">IA PDF <span class="text-danger">*</span></label>
            <input type="file" id="file-add" name="file" class="form-control-file @error('file') is-invalid @enderror" accept="application/pdf" required>
            <small class="text-muted d-block">PDF only, max 10 MB.</small>
            @error('file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label for="proof-add">Proof (optional)</label>
            <input type="file" id="proof-add" name="proof" class="form-control-file @error('proof') is-invalid @enderror" accept="application/pdf,image/png,image/jpeg">
            <small class="text-muted d-block">PDF/JPG/PNG, max 10 MB.</small>
            @error('proof') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
