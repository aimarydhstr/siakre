<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\CooperationController;
use App\Http\Controllers\IaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// --------------------------user----------------------------

Route::get('/user/login',[AuthController::class, 'index'])->name('login');
Route::post('/user/login/send',[AuthController::class, 'login'])->name("login-send");
Route::get('/user/logout',[AuthController::class, 'logout'])->name('logout');

Route::group(['middleware' => ['auth']], function() {
    Route::get('/user/edit',[AuthController::class, 'edit'])->name('user-edit');
    Route::post('/user/update/send',[AuthController::class, 'update'])->name('user-update');
    Route::get('/user/regs',[AuthController::class, 'create'])->name('registration');
    Route::post('/user/regs/send',[AuthController::class, 'store']);

    Route::get('/',[DataController::class, 'admin'])->name('home');
    Route::get('/add',[DataController::class, 'create'])->name('add');
    Route::post('/add/send',[DataController::class, 'store']);
    Route::get('/select-prestasi',[DataController::class, 'select'])->name('select-prestasi');
    Route::post('/select/send',[DataController::class, 'selectPost'])->name('select-send');

    Route::get('/akademik',[DataController::class, 'akademik'])->name('akademik');
    Route::get('/akademik/region/{year}',[DataController::class, 'akademik_region'],function($year){})->name('akademik-region');
    Route::get('/akademik/national/{year}',[DataController::class, 'akademik_national'],function($year){})->name('akademik-national');
    Route::get('/akademik/international/{year}',[DataController::class, 'akademik_international'],function($year){})->name('akademik-international');

    Route::get('/non-akademik',[DataController::class, 'nonAkademik'])->name('nonAkademik');
    Route::get('/non-akademik/region/{year}',[DataController::class, 'nonAkademik_region'],function($year){})->name('nonAkademik-region');
    Route::get('/non-akademik/national/{year}',[DataController::class, 'nonAkademik_national'],function($year){})->name('nonAkademik-national');
    Route::get('/non-akademik/international/{year}',[DataController::class, 'nonAkademik_international'],function($year){})->name('nonAkademik-international');

    Route::get('/det/{id}',[DataController::class, 'detail'],function($id){})->name('detail-dash');
    Route::get('/detail/{id}',[DataController::class, 'show'],function($id){})->name('detail');
    Route::get('/edit/{id}',[DataController::class, 'edit'],function($id){})->name('edit');
    Route::put('/update/{id}',[DataController::class, 'update'],function($id){})->name('update');
    Route::delete('/delete/{id}',[DataController::class, 'destroy'],function($id){})->name('delete');

    Route::get('/search',[DataController::class, 'search'])->name('search');

    // ----------------------export-------------------------
    Route::get('/data/export-akademik',[DataController::class,'ExportExcel_akademik'])->name('export-akademik');
    Route::get('/data/export-akademik/region',[DataController::class,'ExportExcel_akademik_region'])->name('export-akademik-region');
    Route::get('/data/export-akademik/national',[DataController::class,'ExportExcel_akademik_national'])->name('export-akademik-national');
    Route::get('/data/export-akademik/international',[DataController::class,'ExportExcel_akademik_international'])->name('export-akademik-international');

    Route::get('/data/export-nonAkademik',[DataController::class,'ExportExcel_nonAkademik'])->name('export-nonAkademik');
    Route::get('/data/export-nonAkademik/region',[DataController::class,'ExportExcel_nonAkademik_region'])->name('export-nonAkademik-region');
    Route::get('/data/export-nonAkademik/national',[DataController::class,'ExportExcel_nonAkademik_national'])->name('export-nonAkademik-national');
    Route::get('/data/export-nonAkademik/international',[DataController::class,'ExportExcel_nonAkademik_international'])->name('export-nonAkademik-international');

    Route::get('/data/export-article-dosen',[DataController::class,'article_dosen'])->name('article-dosen');
    Route::get('/data/export-article-mahasiswa',[DataController::class,'article_mahasiswa'])->name('article-mahasiswa');

    // ["Seminar Nasional","Seminar Internasional","Jurnal Internasional",
    // "Jurnal Internasional Bereputasi","Jurnal Nasional Terakreditasi","Jurnal Nasional Tidak Terakreditasi"];

    Route::get('/data/list/sn',[DataController::class,'list_seminar_nasional'])->name('export-list-sn');
    Route::get('/data/list/si',[DataController::class,'list_seminar_internasional'])->name('export-list-si');
    Route::get('/data/list/ji',[DataController::class,'list_jurnal_internasional'])->name('export-list-ji');
    Route::get('/data/list/jib',[DataController::class,'list_jurnal_internasional_bereputasi'])->name('export-list-jib');
    Route::get('/data/list/jnt',[DataController::class,'list_jurnal_nasional_terakreditasi'])->name('export-list-jnt');
    Route::get('/data/list/jntt',[DataController::class,'list_jurnal_nasional_tidak_terakreditasi'])->name('export-list-jntt');

    // ----------------------article----------------------
    Route::get('/articles',[ArticleController::class,'index'])->name('article');
    Route::get('/article/select',[ArticleController::class, 'select'])->name('article-select');
    Route::post('/article/select/send',[ArticleController::class, 'selectPost'])->name('article-select-send');
    Route::get('/article/add',[ArticleController::class,'create'])->name('article-add');
    Route::post('/article/add/send',[ArticleController::class,'store'])->name('article-add-send');
    Route::get('/article/view/{id}',[ArticleController::class,'show'],function($id){})->name('view');
    Route::get('/article/download/{file}',[ArticleController::class,'download'],function($id){})->name('download');
    Route::get('/article/edit/{id}',[ArticleController::class,'edit'],function($id){})->name('edit_article');
    Route::post('/article/update/{id}',[ArticleController::class,'update'],function($id){})->name('update_article');
    Route::delete('/deleteArticle/{id}',[ArticleController::class, 'destroy'],function($id){})->name('delete_article');

    // ----------------------prodi----------------------
    Route::get('/prodi',[DepartmentController::class,'index'])->name('departments.index');
    Route::post('/prodi/add/send',[DepartmentController::class,'store'])->name('departments.store');
    Route::put('/prodi/update/{id}',[DepartmentController::class,'update'],function($id){})->name('departments.update');
    Route::delete('/prodi/delete/{id}',[DepartmentController::class, 'destroy'],function($id){})->name('departments.destroy');

    // ----------------------fakultas----------------------
    Route::get('/fakultas',[FacultyController::class,'index'])->name('faculties.index');
    Route::post('/fakultas/add/send',[FacultyController::class,'store'])->name('faculties.store');
    Route::put('/fakultas/update/{id}',[FacultyController::class,'update'],function($id){})->name('faculties.update');
    Route::delete('/fakultas/delete/{id}',[FacultyController::class, 'destroy'],function($id){})->name('faculties.destroy');


    Route::get('/lecturers',[LecturerController::class,'index'])->name('lecturers.index');
    Route::post('/lecturers/add/send',[LecturerController::class,'store'])->name('lecturers.store');
    Route::put('/lecturers/update/{id}',[LecturerController::class,'update'],function($id){})->name('lecturers.update');
    Route::delete('/lecturers/delete/{id}',[LecturerController::class, 'destroy'],function($id){})->name('lecturers.destroy');


    Route::get('/users',[UserController::class,'index'])->name('users.index');
    Route::post('/users/add/send',[UserController::class,'store'])->name('users.store');
    Route::put('/users/update/{id}',[UserController::class,'update'],function($id){})->name('users.update');
    Route::delete('/users/delete/{id}',[UserController::class, 'destroy'],function($id){})->name('users.destroy');

    Route::get('/cooperations',[CooperationController::class,'index'])->name('cooperations.index');
    Route::post('/cooperations/add/send',[CooperationController::class,'store'])->name('cooperations.store');
    Route::put('/cooperations/update/{id}',[CooperationController::class,'update'],function($id){})->name('cooperations.update');
    Route::delete('/cooperations/delete/{id}',[CooperationController::class, 'destroy'],function($id){})->name('cooperations.destroy');

    Route::get('/ia/{id}',[IaController::class,'index'],function($id){})->name('ias.index');
    Route::post('/ia/add/send',[IaController::class,'store'])->name('ias.store');
    Route::put('/ia/update/{id}',[IaController::class,'update'],function($id){})->name('ias.update');
    Route::delete('/ia/delete/{id}',[IaController::class, 'destroy'],function($id){})->name('ias.destroy');

});
