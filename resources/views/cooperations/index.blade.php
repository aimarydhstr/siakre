@extends('layout.base')
@section('title','Cooperations')

@section('nav')
<div class="d-flex" id="wrapper">
  @include('template.sidebar')

  <div id="page-content-wrapper" class="site">
    @include('template.nav')

    <div class="content">
      <h4 class="font-weight-bold my-3 mt-md-4">Cooperations (MoU)</h4>

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

      <div class="card rounded shadow mt-3 mb-5">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>Total : {{ method_exists($cooperations,'total') ? $cooperations->total() : $cooperations->count() }}</div>
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-add-cooperation">
              <i class="fa fa-plus mr-1"></i> Add Cooperation
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th style="width:64px;">#</th>
                  <th>Letter No.</th>
                  <th>Date</th>
                  <th>Partners</th>
                  <th>Type</th>
                  <th>Level</th>
                  <th>PIC</th>
                  <th>File</th>
                  <th style="width:200px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($cooperations as $i => $c)
                  <tr>
                    <td>{{ ($cooperations->currentPage()-1)*$cooperations->perPage() + $i + 1 }}</td>
                    <td class="text-break">{{ $c->letter_number }}</td>
                    <td>{{ \Carbon\Carbon::parse($c->letter_date)->format('d-m-Y') }}</td>
                    <td class="text-break">{{ $c->partner }}</td>
                    <td class="text-capitalize">{{ str_replace('_',' ',$c->type_coop) }}</td>
                    <td class="text-capitalize">{{ $c->level }}</td>
                    <td class="text-break">{{ optional($c->pic)->name ?? '-' }}</td>
                    <td>
                      @if($c->file)
                        <a href="{{ asset($c->file) }}" target="_blank" class="btn btn-outline-secondary btn-sm">View PDF</a>
                      @else
                        -
                      @endif
                    </td>
                    <td>
                      <a href="{{ route('ias.index', $c->id) }}" class="btn btn-info btn-sm">
                        <i class="fa fa-list mr-1"></i> IA
                      </a>
                      <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modal-edit-cooperation-{{ $c->id }}">
                        <i class="fa fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-delete-cooperation-{{ $c->id }}">
                        <i class="fa fa-trash"></i>
                      </button>
                    </td>
                  </tr>

                  {{-- Edit Modal --}}
                  <div class="modal fade" id="modal-edit-cooperation-{{ $c->id }}" tabindex="-1" role="dialog" aria-labelledby="label-edit-cooperation-{{ $c->id }}" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                      <div class="modal-content">
                        <form action="{{ route('cooperations.update', $c->id) }}" method="POST" enctype="multipart/form-data">
                          @csrf
                          @method('PUT')
                          <div class="modal-header">
                            <h5 class="modal-title" id="label-edit-cooperation-{{ $c->id }}">Edit Cooperation</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <div class="form-group">
                              <label for="letter-number-edit-{{ $c->id }}">Letter Number <span class="text-danger">*</span></label>
                              <input type="text" id="letter-number-edit-{{ $c->id }}" name="letter_number"
                                     class="form-control @error('letter_number') is-invalid @enderror"
                                     value="{{ old('letter_number', $c->letter_number) }}" required>
                              @error('letter_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="letter-date-edit-{{ $c->id }}">Letter Date <span class="text-danger">*</span></label>
                              <input type="date" id="letter-date-edit-{{ $c->id }}" name="letter_date"
                                     class="form-control @error('letter_date') is-invalid @enderror"
                                     value="{{ old('letter_date', $c->letter_date) }}" required>
                              @error('letter_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="partner-edit-{{ $c->id }}">Partners <span class="text-danger">*</span></label>
                              <textarea id="partner-edit-{{ $c->id }}" name="partner" rows="2"
                                        class="form-control @error('partner') is-invalid @enderror"
                                        required placeholder="e.g. UHB, UMP">{{ old('partner', $c->partner) }}</textarea>
                              @error('partner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="type-edit-{{ $c->id }}">Type <span class="text-danger">*</span></label>
                              <select id="type-edit-{{ $c->id }}" name="type_coop" class="form-control @error('type_coop') is-invalid @enderror" required>
                                @foreach(['research','community_service','education','other'] as $t)
                                  <option value="{{ $t }}" {{ old('type_coop', $c->type_coop) === $t ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_',' ', $t)) }}
                                  </option>
                                @endforeach
                              </select>
                              @error('type_coop') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="level-edit-{{ $c->id }}">Level <span class="text-danger">*</span></label>
                              <select id="level-edit-{{ $c->id }}" name="level" class="form-control @error('level') is-invalid @enderror" required>
                                @foreach(['national','international'] as $lv)
                                  <option value="{{ $lv }}" {{ old('level', $c->level) === $lv ? 'selected' : '' }}>
                                    {{ ucfirst($lv) }}
                                  </option>
                                @endforeach
                              </select>
                              @error('level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="user-id-edit-{{ $c->id }}">PIC (optional)</label>
                              <select id="user-id-edit-{{ $c->id }}" name="user_id" class="form-control @error('user_id') is-invalid @enderror">
                                <option value="">-- Select PIC --</option>
                                @foreach($users as $u)
                                  <option value="{{ $u->id }}" {{ (string)old('user_id', $c->user_id) === (string)$u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                              </select>
                              @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                              <label for="file-edit-{{ $c->id }}">Replace PDF (optional)</label>
                              <input type="file" id="file-edit-{{ $c->id }}" name="file" class="form-control-file @error('file') is-invalid @enderror" accept="application/pdf">
                              <small class="text-muted d-block">PDF only, max 10 MB.</small>
                              @error('file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
                  <div class="modal fade" id="modal-delete-cooperation-{{ $c->id }}" tabindex="-1" role="dialog" aria-labelledby="label-delete-cooperation-{{ $c->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                      <div class="modal-content">
                        <form action="{{ route('cooperations.destroy', $c->id) }}" method="POST">
                          @csrf
                          @method('DELETE')
                          <div class="modal-header">
                            <h5 class="modal-title text-danger" id="label-delete-cooperation-{{ $c->id }}">Delete Cooperation</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <p class="mb-0">Are you sure you want to delete <strong>{{ $c->letter_number }}</strong>? This will also remove its IAs and files.</p>
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
                    <td colspan="9" class="text-center">No data.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if(method_exists($cooperations,'links'))
            <div class="mt-3">{{ $cooperations->withQueryString()->links() }}</div>
          @endif
        </div>
      </div>
    </div>

    @include('template.footer')
  </div>
</div>

{{-- Add Modal --}}
<div class="modal fade" id="modal-add-cooperation" tabindex="-1" role="dialog" aria-labelledby="label-add-cooperation" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="{{ route('cooperations.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="label-add-cooperation">Add Cooperation</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="letter-number-add">Letter Number <span class="text-danger">*</span></label>
            <input type="text" id="letter-number-add" name="letter_number" class="form-control @error('letter_number') is-invalid @enderror" value="{{ old('letter_number') }}" required>
            @error('letter_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label for="letter-date-add">Letter Date <span class="text-danger">*</span></label>
            <input type="date" id="letter-date-add" name="letter_date" class="form-control @error('letter_date') is-invalid @enderror" value="{{ old('letter_date') }}" required>
            @error('letter_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label for="partner-add">Partners <span class="text-danger">*</span></label>
            <textarea id="partner-add" name="partner" rows="2" class="form-control @error('partner') is-invalid @enderror" placeholder="e.g. UHB, UMP" required>{{ old('partner') }}</textarea>
            @error('partner') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label for="type-add">Type <span class="text-danger">*</span></label>
            <select id="type-add" name="type_coop" class="form-control @error('type_coop') is-invalid @enderror" required>
              <option disabled {{ old('type_coop') ? '' : 'selected' }}>Select type</option>
              <option value="research" {{ old('type_coop') === 'research' ? 'selected' : '' }}>Research</option>
              <option value="community_service" {{ old('type_coop') === 'community_service' ? 'selected' : '' }}>Community Service</option>
              <option value="education" {{ old('type_coop') === 'education' ? 'selected' : '' }}>Education</option>
              <option value="other" {{ old('type_coop') === 'other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('type_coop') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label for="level-add">Level <span class="text-danger">*</span></label>
            <select id="level-add" name="level" class="form-control @error('level') is-invalid @enderror" required>
              <option disabled {{ old('level') ? '' : 'selected' }}>Select level</option>
              <option value="national" {{ old('level') === 'national' ? 'selected' : '' }}>National</option>
              <option value="international" {{ old('level') === 'international' ? 'selected' : '' }}>International</option>
            </select>
            @error('level') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label for="user-id-add">PIC (optional)</label>
            <select id="user-id-add" name="user_id" class="form-control @error('user_id') is-invalid @enderror">
              <option value="">-- Select PIC --</option>
              @foreach($users as $u)
                <option value="{{ $u->id }}" {{ (string)old('user_id') === (string)$u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
              @endforeach
            </select>
            @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label for="file-add">PDF File <span class="text-danger">*</span></label>
            <input type="file" id="file-add" name="file" class="form-control-file @error('file') is-invalid @enderror" accept="application/pdf" required>
            <small class="text-muted d-block">PDF only, max 10 MB.</small>
            @error('file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
