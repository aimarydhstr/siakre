@extends ('layout.base')
@section('title','Sunting Artikel')
@section('nav')
<div class="d-flex" id="wrapper">
    @include('template.sidebar')
    <div id="page-content-wrapper" class="site">
        {{ Breadcrumbs::render('add') }}
        @include('template.nav')

        <div class="content">
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <h4 class="font-weight-bold my-3 mt-md-4">Sunting Artikel</h4>

            <form action="{{ route('update_article', ['id'=> $data->id]) }}" method="post" enctype="multipart/form-data" class="add">
                {{ csrf_field() }}

                <div class="card rounded shadow mt-3 mb-5">
                    <div class="card-body">
                        <div class="row">
                            <!-- Judul -->
                            <div class="col-md-6">
                                <div class="form-group input-group-sm">
                                    <label for="title">Judul <span>&#42;</span></label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                                        id="title" name="title" value="{{ $data->title }}">
                                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <!-- Jenis Jurnal -->
                            <div class="col-md-6">
                                <div class="form-group input-group-sm">
                                    <label for="type_journal">Jenis Jurnal <span>&#42;</span></label>
                                    <select class="form-control @error('type_journal') is-invalid @enderror"
                                        id="type_journal" name="type_journal">
                                        <option disabled>Pilih Jenis Jurnal</option>
                                        @foreach([
                                            'Seminar Nasional',
                                            'Seminar Internasional',
                                            'Jurnal Bereputasi',
                                            'Jurnal Internasional',
                                            'Jurnal Internasional Bereputasi',
                                            'Jurnal Nasional Terakreditasi',
                                            'Jurnal Nasional Tidak Terakreditasi'
                                        ] as $type)
                                        <option value="{{ $type }}" @if($data->type_journal==$type) selected @endif>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                    @error('type_journal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <!-- URL -->
                            <div class="col-md-6">
                                <div class="form-group input-group-sm">
                                    <label for="url">Url <span>&#42;</span></label>
                                    <input type="text" class="form-control @error('url') is-invalid @enderror"
                                        id="url" name="url" value="{{ $data->url }}">
                                    @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <!-- Publisher -->
                            <div class="col-md-2">
                                <div class="form-group input-group-sm">
                                    <label for="publisher">Penerbit <span>&#42;</span></label>
                                    <input type="text" class="form-control @error('publisher') is-invalid @enderror"
                                        id="publisher" name="publisher" value="{{ $data->publisher }}">
                                    @error('publisher')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <!-- Volume & Number -->
                            <div class="col-md-2">
                                <div class="form-group input-group-sm">
                                    <label for="volume">Volume</label>
                                    <input type="text" class="form-control @error('volume') is-invalid @enderror"
                                        id="volume" name="volume" value="{{ $data->volume }}">
                                    @error('volume')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group input-group-sm">
                                    <label for="number">Nomer</label>
                                    <input type="text" class="form-control @error('number') is-invalid @enderror"
                                        id="number" name="number" value="{{ $data->number }}">
                                    @error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <!-- File PDF -->
                            <div class="col-md-6">
                                <div class="form-group input-group-sm">
                                    <label for="file">Dokumen <span>&#42;</span></label>
                                    <div class="custom-file @error('file') is-invalid @enderror">
                                        <input type="file" class="custom-file-input" id="file" name="file" accept="application/pdf" title="{{ $data->file }}">
                                        <label class="custom-file-label text-truncate" for="file"></label>
                                    </div>
                                    <small class="text-danger"><i>Hanya PDF - Maksimal 10 MB</i></small>
                                    @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <!-- Tanggal -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Periode <span>&#42;</span></label>
                                    <input type="text" class="form-control datepicker" name="date"
                                        value="{{ date('d-m-Y', strtotime($data->date)) }}">
                                </div>
                            </div>
                        </div>

                        <!-- Mahasiswa/Dosen -->
                        <div class="row">
                            @if($data->category === 'mahasiswa')
                                @foreach($data->students as $i => $student)
                                <div class="col-md-3">
                                    <div class="form-group input-group-sm">
                                        <label>Nama Mahasiswa {{ $i+1 }} <span>&#42;</span></label>
                                        <input type="text" class="form-control" name="name{{ $i+1 }}" value="{{ $student->name }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group input-group-sm">
                                        <label>NIM {{ $i+1 }} <span>&#42;</span></label>
                                        <input type="text" class="form-control" name="nim{{ $i+1 }}" value="{{ $student->nim }}">
                                    </div>
                                </div>
                                @endforeach
                            @endif
                            @foreach($data->lecturers as $i => $lecturer)
                            <div class="col-md-6">
                                <div class="form-group input-group-sm">
                                    <label>Nama Dosen {{ $i+1 }} <span>&#42;</span></label>
                                    <input type="text" class="form-control" name="dosen{{ $i+1 }}" value="{{ $lecturer->user->name }}">
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Submit -->
                        <div class="row mt-3">
                            <div class="col-md text-right">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </div>

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
$(document).ready(function(){
    $('.datepicker').datepicker({
        format: 'dd-mm-yyyy',
        todayHighlight: true,
        autoclose: true,
    });

    // Set initial file label
    $("#file+.custom-file-label").html($("#file").attr("title"));

    $("#file").on("change", function(ev) {
        var file = ev.target.files[0];
        var label = $(this).next();
        label.html(file.name);
    });
});
</script>
@endsection
