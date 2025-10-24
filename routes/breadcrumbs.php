<?php
use Illuminate\Support\Facades\Session;

// ----------------------article-----------------
Breadcrumbs::for('article', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Artikel', route('article'));
});

//-----------------------base---------------------------
Breadcrumbs::for('home', function ($trail) {
    $trail->push('Beranda', route('home'));
});
Breadcrumbs::for('select-prestasi', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Pilih', route('select-prestasi'));
});
Breadcrumbs::for('add', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Tambah data', route('add'));
});


Breadcrumbs::for('search', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Hasil Pencarian');
   
});

Breadcrumbs::for('detail-dash', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Detail', route('detail-dash',['id'=>1]));
});


// --------akademik==================
Breadcrumbs::for('akademik', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Akademik', route('akademik'));
});
Breadcrumbs::for('akademik-region', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Akademik', route('akademik'));
   $trail->push('Regional', route('akademik-region',['year'=>session('akademikSESSION')]));
});
Breadcrumbs::for('akademik-national', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Akademik', route('akademik'));
   $trail->push('Nasional', route('akademik-national',['year'=>session('akademikSESSION')]));
});
Breadcrumbs::for('akademik-international', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Akademik', route('akademik'));
   $trail->push('Internasional', route('akademik-international',['year'=>session('akademikSESSION')]));
});


Breadcrumbs::for('aka-detail-reg', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Akademik', route('akademik'));
   $trail->push('Regional', route('akademik-region',['year'=>session('akademikSESSION')]));
   $trail->push('Detail', route('detail',['id'=>1]));
});
Breadcrumbs::for('aka-detail-nat', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Akademik', route('akademik'));
   $trail->push('Nasional', route('akademik-national',['year'=>session('akademikSESSION')]));
   $trail->push('Detail', route('detail',['id'=>1]));
});
Breadcrumbs::for('aka-detail-int', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Akademik', route('akademik'));
   $trail->push('Internasional', route('akademik-international',['year'=>session('akademikSESSION')]));
   $trail->push('Detail', route('detail',['id'=>1]));
});


//------------ non akademik==================

Breadcrumbs::for('non-akademik', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Non-Akademik', route('nonAkademik'));
});
Breadcrumbs::for('non-akademik-region', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Non-Akademik', route('nonAkademik'));
   $trail->push('Regional', route('nonAkademik-region',['year'=>session('nonAkademikSESSION')]));
});
Breadcrumbs::for('non-akademik-national', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Non-Akademik', route('nonAkademik'));
   $trail->push('Nasional', route('nonAkademik-national',['year'=>session('nonAkademikSESSION')]));
});
Breadcrumbs::for('non-akademik-international', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Non-Akademik', route('nonAkademik'));
   $trail->push('Internasional', route('nonAkademik-international',['year'=>session('nonAkademikSESSION')]));
});


Breadcrumbs::for('nonAka-detail-reg', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Non-Akademik', route('nonAkademik'));
   $trail->push('Regional', route('nonAkademik-region',['year'=>session('nonAkademikSESSION')]));
   $trail->push('Detail', route('detail',['id'=>1]));
});
Breadcrumbs::for('nonAka-detail-nat', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Non-Akademik', route('nonAkademik'));
   $trail->push('Nasional', route('nonAkademik-national',['year'=>session('nonAkademikSESSION')]));
   $trail->push('Detail', route('detail',['id'=>1]));
});
Breadcrumbs::for('nonAka-detail-int', function ($trail) {
   $trail->push('Beranda', route('home'));
   $trail->push('Non-Akademik', route('nonAkademik'));
   $trail->push('Internasional', route('nonAkademik-international',['year'=>session('nonAkademikSESSION')]));
   $trail->push('Detail', route('detail',['id'=>1]));
});

