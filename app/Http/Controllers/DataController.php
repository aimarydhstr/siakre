<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Article;
use App\Models\Student;
use App\Models\Lecturer;
use App\Models\Achievement;
use App\Models\StudentAchievement;
use App\Models\Department;

use Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\dataExportArticle;
use App\Exports\listExportArticle;
use App\Exports\userExport;
use App\Exports\userExportField;

class ArticlesHelper
{
    public static function data_article(
        $department_id = null,
        $date_from = null,   // 'Y-m-d' (opsional, prioritas jika keduanya ada)
        $date_to = null,     // 'Y-m-d'
        $month = null,       // 1..12 (opsional)
        $year = null         // YYYY
    ) {
        $articles = Article::with(['students', 'lecturers'])
            ->when($department_id, fn($q) => $q->where('department_id', $department_id))
            // Range (prioritas jika diisi)
            ->when($date_from && $date_to, fn($q) => $q->whereBetween('date', [$date_from, $date_to]))
            // Bulanan
            ->when(!$date_from && !$date_to && $month && $year, fn($q) => $q
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
            )
            ->get();

        $yearTS  = date('Y');
        $yearTS1 = $yearTS - 1;
        $yearTS2 = $yearTS - 2;

        $data_type_array = [
            "Seminar Nasional",
            "Seminar Internasional",
            "Jurnal Internasional",
            "Jurnal Internasional Bereputasi",
            "Jurnal Nasional Terakreditasi",
            "Jurnal Nasional Tidak Terakreditasi"
        ];

        $TS_array = $TS_1_array = $TS_2_array = [];
        $TS_array_dosen = $TS_1_array_dosen = $TS_2_array_dosen = [];
        $TS_array_all = $TS_1_array_all = $TS_2_array_all = [];

        $getAcademicYear = function($year, $month) use ($yearTS, $yearTS1, $yearTS2) {
            $academic_year = ($month >= 9) ? $year + 1 : $year;
            if ($academic_year == $yearTS)  return 'TS';
            if ($academic_year == $yearTS1) return 'TS_1';
            if ($academic_year == $yearTS2) return 'TS_2';
            return null;
        };

        foreach ($data_type_array as $type) {
            $val_TS = $val_TS_1 = $val_TS_2 = 0;
            $val_TS_dosen = $val_TS_1_dosen = $val_TS_2_dosen = 0;
            $val_TS_all = $val_TS_1_all = $val_TS_2_all = 0;

            foreach ($articles as $article) {
                if ($article->type_journal !== $type) continue;

                $yearA  = (int) date('Y', strtotime($article->date));
                $monthA = (int) date('m', strtotime($article->date));

                $academic_year = $getAcademicYear($yearA, $monthA);
                if (!$academic_year) continue;

                // Mahasiswa
                if ($article->category === 'mahasiswa') {
                    if     ($academic_year === 'TS')   $val_TS++;
                    elseif ($academic_year === 'TS_1') $val_TS_1++;
                    elseif ($academic_year === 'TS_2') $val_TS_2++;
                }

                // Dosen
                if ($article->category === 'dosen') {
                    if     ($academic_year === 'TS')   $val_TS_dosen++;
                    elseif ($academic_year === 'TS_1') $val_TS_1_dosen++;
                    elseif ($academic_year === 'TS_2') $val_TS_2_dosen++;
                }

                // Semua
                if     ($academic_year === 'TS')   $val_TS_all++;
                elseif ($academic_year === 'TS_1') $val_TS_1_all++;
                elseif ($academic_year === 'TS_2') $val_TS_2_all++;
            }

            $TS_array[] = $val_TS;
            $TS_1_array[] = $val_TS_1;
            $TS_2_array[] = $val_TS_2;

            $TS_array_dosen[] = $val_TS_dosen;
            $TS_1_array_dosen[] = $val_TS_1_dosen;
            $TS_2_array_dosen[] = $val_TS_2_dosen;

            $TS_array_all[] = $val_TS_all;
            $TS_1_array_all[] = $val_TS_1_all;
            $TS_2_array_all[] = $val_TS_2_all;
        }

        return compact(
            'data_type_array',
            'TS_array','TS_1_array','TS_2_array',
            'TS_array_dosen','TS_1_array_dosen','TS_2_array_dosen',
            'TS_array_all','TS_1_array_all','TS_2_array_all'
        );
    }

}

class dataController extends Controller
{
    // ===================== Export Article ======================
    public function list_seminar_nasional(){
        $export = new listExportArticle();
        $export->setsn();
        return Excel::download($export, 'seminar_nasional.xlsx');
    }

    public function list_seminar_internasional(){
        $export = new listExportArticle();
        $export->setsi();
        return Excel::download($export, 'seminar_internasional.xlsx');
    }

    public function list_jurnal_internasional(){
        $export = new listExportArticle();
        $export->setji();
        return Excel::download($export, 'jurnal_internasional.xlsx');
    }

    public function list_jurnal_internasional_bereputasi(){
        $export = new listExportArticle();
        $export->setjib();
        return Excel::download($export, 'jurnal_internasional_bereputasi.xlsx');
    }

    public function list_jurnal_nasional_terakreditasi(){
        $export = new listExportArticle();
        $export->setjnt();
        return Excel::download($export, 'jurnal_nasional_terakreditasi.xlsx');
    }

    public function list_jurnal_nasional_tidak_terakreditasi(){
        $export = new listExportArticle();
        $export->setjntt();
        return Excel::download($export, 'jurnal_nasional_tidak_terakreditasi.xlsx');
    }

    // ===================== Export Mahasiswa / Dosen ======================
    public function article_mahasiswa($department_id = null){
        $export = new dataExportArticle();
        $export->setMahasiswa($department_id);
        return Excel::download($export, 'Mahasiswa.xlsx');
    }

    public function article_dosen($department_id = null){
        $export = new dataExportArticle();
        $export->setDosen($department_id);
        return Excel::download($export, 'Dosen.xlsx');
    }

    // ===================== Export Prestasi ======================
    public function ExportExcel_akademik(){
        $export = new userExport();
        $export->setAkademik();
        return Excel::download($export, 'Akademik.xlsx');
    }

    public function ExportExcel_akademik_region(){
        $export = new userExportField();
        $export->setAkademikRegion();
        return Excel::download($export, 'AkademikRegional.xlsx');
    }

    public function ExportExcel_akademik_national(){
        $export = new userExportField();
        $export->setAkademikNational();
        return Excel::download($export, 'AkademikNasional.xlsx');
    }

    public function ExportExcel_akademik_international(){
        $export = new userExportField();
        $export->setAkademikInternational();
        return Excel::download($export, 'AkademikInternasional.xlsx');
    }

    public function ExportExcel_nonAkademik(){
        $export = new userExport();
        $export->setNonAkademik();
        return Excel::download($export, 'NonAkademik.xlsx');
    }

    public function ExportExcel_nonAkademik_region(){
        $export = new userExportField();
        $export->setNonAkademikRegion();
        return Excel::download($export, 'NonAkademikRegional.xlsx');
    }

    public function ExportExcel_nonAkademik_national(){
        $export = new userExportField();
        $export->setNonAkademikNational();
        return Excel::download($export, 'NonAkademikNasional.xlsx');
    }

    public function ExportExcel_nonAkademik_international(){
        $export = new userExportField();
        $export->setNonAkademikInternational();
        return Excel::download($export, 'NonAkademikInternasional.xlsx');
    }

    // ===================== Search ======================
    public function search(Request $request, $department_id = null)
    {
        if(!Auth::check()){
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }
        $user = Auth::user();
        $search = $request->search;

        $data_limit = Achievement::query()
            ->when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('team','like',"%$search%")
            ->orWhere('year','like',"%$search%")
            ->orderBy('id','DESC')
            ->paginate();

        return view('admin/search', [
            'user' => $user,
            'data' => $data_limit,
        ]);
    }

    public function admin(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }
        $authUser = Auth::user();

        // Scope per department (untuk non-admin)
        $department_id = null;
        if ($authUser->role !== 'admin') {
            if ($authUser->role === 'department_head' && optional($authUser->department_head)->department_id) {
                $department_id = $authUser->department_head->department_id;
            } elseif ($authUser->role === 'lecturer' && optional($authUser->lecturer)->department_id) {
                $department_id = $authUser->lecturer->department_id;
            }
        }

        // ====== Kartu ringkas (Global, tanpa filter) ======
        $baseAchievements = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->get()
            ->sortBy('year');

        $array_year_global = $baseAchievements->pluck('year')->unique()->values()->toArray();
        $region = $national = $international = 0;
        foreach ($array_year_global as $idx => $year_data) {
            $seenR = $seenN = $seenI = [];
            foreach ($baseAchievements as $val) {
                $key = $val->competition.$val->team.$val->year.$val->organizer;
                if ($val->level === "Region" && !in_array($key, $seenR)) {
                    $seenR[] = $key; if ($idx === 0) $region++;
                } elseif ($val->level === "National" && !in_array($key, $seenN)) {
                    $seenN[] = $key; if ($idx === 0) $national++;
                } elseif ($val->level === "International" && !in_array($key, $seenI)) {
                    $seenI[] = $key; if ($idx === 0) $international++;
                }
            }
        }

        // ====== Helpers ======
        $types = [
            "Seminar Nasional",
            "Seminar Internasional",
            "Jurnal Internasional",
            "Jurnal Internasional Bereputasi",
            "Jurnal Nasional Terakreditasi",
            "Jurnal Nasional Tidak Terakreditasi",
        ];

        $academicMapper = function (int $y, int $m): ?string {
            $cur = (int)date('Y');
            $ay  = ($m >= 9) ? $y + 1 : $y; // Sep–Des ke tahun akademik berikutnya
            if ($ay === $cur)     return 'TS';
            if ($ay === $cur - 1) return 'TS_1';
            if ($ay === $cur - 2) return 'TS_2';
            return null;
        };

        $buildArticleRange = function (Request $r, string $prefix) {
            $sm = (int) $r->get("{$prefix}_start_month");
            $sy = (int) $r->get("{$prefix}_start_year");
            $em = (int) $r->get("{$prefix}_end_month");
            $ey = (int) $r->get("{$prefix}_end_year");
            if ($sm && $sy && $em && $ey) {
                $from = Carbon::createFromDate($sy, $sm, 1)->startOfMonth();
                $to   = Carbon::createFromDate($ey, $em, 1)->endOfMonth();
                if ($from->gt($to)) { [$from, $to] = [$to, $from]; }
                return [$from->toDateString(), $to->toDateString(), $sm, $sy, $em, $ey];
            }
            return [null, null, $sm, $sy, $em, $ey];
        };

        $buildAchRange = function (Request $r, string $prefix) {
            $sm = (int) $r->get("{$prefix}_start_month");
            $sy = (int) $r->get("{$prefix}_start_year");
            $em = (int) $r->get("{$prefix}_end_month");
            $ey = (int) $r->get("{$prefix}_end_year");
            if ($sm && $sy && $em && $ey) {
                $ym_from = ($sy * 100) + $sm;
                $ym_to   = ($ey * 100) + $em;
                if ($ym_from > $ym_to) { [$ym_from, $ym_to] = [$ym_to, $ym_from]; }
                return [$ym_from, $ym_to, $sm, $sy, $em, $ey];
            }
            return [null, null, $sm, $sy, $em, $ey];
        };

        $computeArticleTSArrays = function ($articles, array $types, ?string $categoryFilter) use ($academicMapper) {
            $TS = $TS1 = $TS2 = array_fill(0, count($types), 0);
            foreach ($types as $idx => $t) {
                foreach ($articles as $a) {
                    if ($a->type_journal !== $t) continue;
                    if ($categoryFilter && $a->category !== $categoryFilter) continue;
                    $y = (int) date('Y', strtotime($a->date));
                    $m = (int) date('m', strtotime($a->date));
                    $bucket = $academicMapper($y, $m);
                    if ($bucket === 'TS') $TS[$idx]++;
                    elseif ($bucket === 'TS_1') $TS1[$idx]++;
                    elseif ($bucket === 'TS_2') $TS2[$idx]++;
                }
            }
            return [$TS, $TS1, $TS2];
        };

        $computeAchievementSeries = function ($achievements) {
            $years = collect($achievements)->pluck('year')->unique()->values()->sort()->toArray();
            $region = $national = $international = [];
            foreach ($years as $y) {
                $seenR = $seenN = $seenI = [];
                $r = $n = $i = 0;
                foreach ($achievements as $a) {
                    if ((int)$a->year !== (int)$y) continue;
                    $key = $a->competition.$a->team.$a->year.$a->organizer;
                    if ($a->level === 'Region' && !in_array($key, $seenR)) { $seenR[] = $key; $r++; }
                    if ($a->level === 'National' && !in_array($key, $seenN)) { $seenN[] = $key; $n++; }
                    if ($a->level === 'International' && !in_array($key, $seenI)) { $seenI[] = $key; $i++; }
                }
                $region[] = $r; $national[] = $n; $international[] = $i;
            }
            return [$years, $region, $national, $international];
        };

        // ===== (1) GRAFIK PRESTASI (Between only) =====
        [$ach_ym_from, $ach_ym_to, $ach_sm, $ach_sy, $ach_em, $ach_ey] = $buildAchRange($request, 'ach');

        $achievementsForChart = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->when($ach_ym_from && $ach_ym_to, fn($q) =>
                $q->whereRaw('(year * 100 + month) between ? and ?', [$ach_ym_from, $ach_ym_to]))
            ->get();

        [$ach_year_array, $ach_region_array, $ach_national_array, $ach_international_array] =
            $computeAchievementSeries($achievementsForChart);

        // ===== (2) GRAFIK ARTIKEL (Between only) =====
        [$art_date_from, $art_date_to, $art_sm, $art_sy, $art_em, $art_ey] = $buildArticleRange($request, 'art');

        $articlesChart = Article::when($department_id, fn($q)=>$q->where('department_id',$department_id))
            ->when($art_date_from && $art_date_to, fn($q)=>$q->whereBetween('date', [$art_date_from,$art_date_to]))
            ->get();

        [$art_TS_array_all, $art_TS_1_array_all, $art_TS_2_array_all] =
            $computeArticleTSArrays($articlesChart, $types, null);

        // ===== (3) TABEL 5 PRESTASI TERAKHIR (Between only) =====
        [$last_ym_from, $last_ym_to, $last_sm, $last_sy, $last_em, $last_ey] = $buildAchRange($request, 'last');

        $data_limit = StudentAchievement::with(['student', 'achievement'])
            ->when($department_id, fn($q) => $q->whereHas('achievement', fn($q2) => $q2->where('department_id', $department_id)))
            ->when($last_ym_from && $last_ym_to, fn($q) =>
                $q->whereHas('achievement', fn($qq) => $qq->whereRaw('(year * 100 + month) between ? and ?', [$last_ym_from, $last_ym_to])))
            ->orderByDesc('id')
            ->take(5)
            ->get();

        // ===== (4) TABEL Dosen & Mahasiswa (Between only) =====
        [$mix_date_from, $mix_date_to, $mix_sm, $mix_sy, $mix_em, $mix_ey] = $buildArticleRange($request, 'mix');

        $articlesMix = Article::when($department_id, fn($q)=>$q->where('department_id',$department_id))
            ->when($mix_date_from && $mix_date_to, fn($q)=>$q->whereBetween('date', [$mix_date_from,$mix_date_to]))
            ->get();

        [$mix_TS_array, $mix_TS_1_array, $mix_TS_2_array] =
            $computeArticleTSArrays($articlesMix, $types, null);

        // ===== (5) TABEL Dosen saja (Between only) =====
        [$lec_date_from, $lec_date_to, $lec_sm, $lec_sy, $lec_em, $lec_ey] = $buildArticleRange($request, 'lec');

        $articlesLec = Article::when($department_id, fn($q)=>$q->where('department_id',$department_id))
            ->when($lec_date_from && $lec_date_to, fn($q)=>$q->whereBetween('date', [$lec_date_from,$lec_date_to]))
            ->get();

        [$lec_TS_array, $lec_TS_1_array, $lec_TS_2_array] =
            $computeArticleTSArrays($articlesLec, $types, 'dosen');

        // ===== Kirim ke view =====
        return view('admin.index', [
            'user' => $authUser,

            // Kartu global
            'region' => $region,
            'national' => $national,
            'international' => $international,

            // Grafik Prestasi
            'ach_start_month' => $ach_sm, 'ach_start_year' => $ach_sy,
            'ach_end_month'   => $ach_em, 'ach_end_year'   => $ach_ey,
            'ach_year_array'  => $ach_year_array,
            'ach_region_array' => $ach_region_array,
            'ach_national_array' => $ach_national_array,
            'ach_international_array' => $ach_international_array,

            // Grafik Artikel
            'art_start_month' => $art_sm, 'art_start_year' => $art_sy,
            'art_end_month'   => $art_em, 'art_end_year'   => $art_ey,
            'data_type_array'    => $types,
            'art_TS_array_all'   => $art_TS_array_all,
            'art_TS_1_array_all' => $art_TS_1_array_all,
            'art_TS_2_array_all' => $art_TS_2_array_all,

            // 5 Prestasi Terakhir
            'last_start_month' => $last_sm, 'last_start_year' => $last_sy,
            'last_end_month'   => $last_em, 'last_end_year'   => $last_ey,
            'data' => $data_limit,

            // Dosen & Mahasiswa
            'mix_start_month' => $mix_sm, 'mix_start_year' => $mix_sy,
            'mix_end_month'   => $mix_em, 'mix_end_year'   => $mix_ey,
            'mix_TS_array'    => $mix_TS_array,
            'mix_TS_1_array'  => $mix_TS_1_array,
            'mix_TS_2_array'  => $mix_TS_2_array,

            // Dosen
            'lec_start_month' => $lec_sm, 'lec_start_year' => $lec_sy,
            'lec_end_month'   => $lec_em, 'lec_end_year'   => $lec_ey,
            'lec_TS_array'    => $lec_TS_array,
            'lec_TS_1_array'  => $lec_TS_1_array,
            'lec_TS_2_array'  => $lec_TS_2_array,
        ]);
    }



    public function akademik(Request $request)
    {
        if(!Auth::check()){
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }
        $user = Auth::user();

        $department_id = null;
        if($user->role != 'admin') {
            if($user->role == 'department_head') $department_id = $user->department_head->department_id;
            elseif($user->role == 'lecturer') $department_id = $user->lecturer->department_id;
        }

        $all = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('field', 'Akademik')
            ->orderByDesc('year')
            ->get();

        $array_year = $all->pluck('year')->unique()->values()->toArray();
        $region = $national = $international = 0;
        $region_val_array = $national_val_array = $international_val_array = [];

        foreach($array_year as $count_year => $year_data){
            $array_competition_region = [];
            $array_competition_national = [];
            $array_competition_international = [];
            $region_val = $national_val = $international_val = 0;

            foreach($all as $val){
                $level = $val->level;
                $key = $val->competition.$val->team.$val->year.$val->organizer;

                if($level == "Region" && !in_array($key, $array_competition_region)){
                    $array_competition_region[] = $key;
                    if($count_year == 0) $region++;
                    if($year_data == $val->year) $region_val++;
                } elseif($level == "National" && !in_array($key, $array_competition_national)){
                    $array_competition_national[] = $key;
                    if($count_year == 0) $national++;
                    if($year_data == $val->year) $national_val++;
                } elseif($level == "International" && !in_array($key, $array_competition_international)){
                    $array_competition_international[] = $key;
                    if($count_year == 0) $international++;
                    if($year_data == $val->year) $international_val++;
                }
            }

            $region_val_array[] = $region_val;
            $national_val_array[] = $national_val;
            $international_val_array[] = $international_val;
        }

        return view('akademik/akademik', [
            'user' => $user,
            'region' => $region,
            'national' => $national,
            'international' => $international,
            'year_array' => $array_year,
            'region_array' => $region_val_array,
            'national_array' => $national_val_array,
            'international_array' => $international_val_array,
        ]);
    }
    public function akademik_region(Request $request, $req_year)
    {
        if(!Auth::check()){
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }
        $user = Auth::user();

        // Ambil department_id jika bukan admin
        $department_id = null;
        if($user->role != 'admin') {
            if($user->role == 'department_head') $department_id = $user->department_head->department_id;
            elseif($user->role == 'lecturer') $department_id = $user->lecturer->department_id;
        }

        session(['akademikSESSION' => $req_year]);
        session(['breadcrumb' => 'akademik_region']);
        $search = $request->search;

        $data = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('field', 'Akademik')
            ->where('level', 'Region')
            ->where('year', $req_year)
            ->where(function($q) use ($search){
                $q->where('team', 'like', "%$search%");
            })
            ->orderBy('id','DESC')
            ->paginate();

        // Statistik keseluruhan (Region, National, International)
        $all = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('field', 'Akademik')
            ->get();

        $region = $national = $international = 0;
        $array_competition_region = [];
        $array_competition_national = [];
        $array_competition_international = [];

        foreach($all as $val){
            $key = $val->competition.$val->team.$val->year.$val->organizer;
            switch($val->level){
                case "Region":
                    if(!in_array($key, $array_competition_region)){
                        $array_competition_region[] = $key;
                        $region++;
                    }
                    break;
                case "National":
                    if(!in_array($key, $array_competition_national)){
                        $array_competition_national[] = $key;
                        $national++;
                    }
                    break;
                case "International":
                    if(!in_array($key, $array_competition_international)){
                        $array_competition_international[] = $key;
                        $international++;
                    }
                    break;
            }
        }

        return view('akademik/region', [
            'user' => $user,
            'data' => $data,
            'region' => $region,
            'national' => $national,
            'international' => $international,
        ]);
    }

    public function akademik_national(Request $request, $req_year)
    {
        if(!Auth::check()){
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }
        $user = Auth::user();

        // Ambil department_id jika bukan admin
        $department_id = null;
        if($user->role != 'admin') {
            if($user->role == 'department_head') $department_id = $user->department_head->department_id;
            elseif($user->role == 'lecturer') $department_id = $user->lecturer->department_id;
        }

        session(['akademikSESSION' => $req_year]);
        session(['breadcrumb' => 'akademik_national']);
        $search = $request->search;

        $data = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('field', 'Akademik')
            ->where('level', 'National')
            ->where('year', $req_year)
            ->where(function($q) use ($search){
                $q->where('team', 'like', "%$search%");
            })
            ->orderBy('id','DESC')
            ->paginate();

        // Statistik keseluruhan (Region, National, International)
        $all = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('field', 'Akademik')
            ->get();

        $region = $national = $international = 0;
        $array_competition_region = [];
        $array_competition_national = [];
        $array_competition_international = [];

        foreach($all as $val){
            $key = $val->competition.$val->team.$val->year.$val->organizer;
            switch($val->level){
                case "Region":
                    if(!in_array($key, $array_competition_region)) $region++;
                    break;
                case "National":
                    if(!in_array($key, $array_competition_national)) $national++;
                    break;
                case "International":
                    if(!in_array($key, $array_competition_international)) $international++;
                    break;
            }
        }

        return view('akademik/national', [
            'user' => $user,
            'data' => $data,
            'region' => $region,
            'national' => $national,
            'international' => $international,
        ]);
    }
    public function akademik_international(Request $request, $req_year)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }
        $user = Auth::user();

        // Ambil department_id jika bukan admin
        $department_id = null;
        if($user->role != 'admin') {
            if($user->role == 'department_head') $department_id = $user->department_head->department_id;
            elseif($user->role == 'lecturer') $department_id = $user->lecturer->department_id;
        }

        session(['akademikSESSION' => $req_year]);
        session(['breadcrumb' => 'akademik_international']);
        $search = $request->search;

        $data = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('field','Akademik')
            ->where('level','International')
            ->where('year', $req_year)
            ->where(function($q) use ($search){
                $q->where('team','like',"%$search%");
            })
            ->orderBy('id','DESC')
            ->paginate();

        $all = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('field','Akademik')
            ->get();

        $region = $national = $international = 0;
        $array_competition_region = [];
        $array_competition_national = [];
        $array_competition_international = [];

        foreach($all as $val){
            $key = $val->competition.$val->team.$val->year.$val->organizer;
            switch($val->level){
                case "Region":
                    if(!in_array($key,$array_competition_region)) $region++;
                    break;
                case "National":
                    if(!in_array($key,$array_competition_national)) $national++;
                    break;
                case "International":
                    if(!in_array($key,$array_competition_international)) $international++;
                    break;
            }
        }

        return view('akademik/international', [
            'user'=>$user,
            'data'=>$data,
            'region'=>$region,
            'national'=>$national,
            'international'=>$international,
        ]);
    }

    public function nonAkademik(Request $request)
    {
        if(!Auth::check()){
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }
        $user = Auth::user();

        $department_id = null;
        if($user->role != 'admin') {
            if($user->role == 'department_head') $department_id = $user->department_head->department_id;
            elseif($user->role == 'lecturer') $department_id = $user->lecturer->department_id;
        }

        $all = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('field','NonAkademik')
            ->orderBy('year','DESC')->get();
        $array_year = $all->pluck('year')->unique()->values()->all();

        $region_val_array = [];
        $national_val_array = [];
        $international_val_array = [];

        $region = $national = $international = 0;

        foreach($array_year as $year_data){
            $region_val = $national_val = $international_val = 0;
            $array_competition_region = [];
            $array_competition_national = [];
            $array_competition_international = [];

            foreach($all as $val){
                if($val->year != $year_data) continue;

                $key = $val->competition.$val->team.$val->year.$val->organizer;

                switch($val->level){
                    case "Region":
                        if(!in_array($key,$array_competition_region)){
                            $array_competition_region[] = $key;
                            $region_val++;
                            if($year_data == $array_year[0]) $region++;
                        }
                        break;
                    case "National":
                        if(!in_array($key,$array_competition_national)){
                            $array_competition_national[] = $key;
                            $national_val++;
                            if($year_data == $array_year[0]) $national++;
                        }
                        break;
                    case "International":
                        if(!in_array($key,$array_competition_international)){
                            $array_competition_international[] = $key;
                            $international_val++;
                            if($year_data == $array_year[0]) $international++;
                        }
                        break;
                }
            }

            $region_val_array[] = $region_val;
            $national_val_array[] = $national_val;
            $international_val_array[] = $international_val;
        }

        return view('nonAkademik/nonAkademik',[
            'user'=>$user,
            'region'=>$region,
            'national'=>$national,
            'international'=>$international,
            'year_array'=>$array_year,
            'region_array'=>$region_val_array,
            'national_array'=>$national_val_array,
            'international_array'=>$international_val_array,
        ]);
    }

    public function nonAkademik_region(Request $request, $req_year)
    {
        if(!Auth::check()){
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }
        $user = Auth::user();

        session(['nonAkademikSESSION' => $req_year]);
        session(['breadcrumb' => 'nonAkademik_region']);
        $search = $request->search;

        // Ambil department_id jika bukan admin
        $department_id = null;
        if($user->role != 'admin') {
            if($user->role == 'department_head') $department_id = $user->department_head->department_id;
            elseif($user->role == 'lecturer') $department_id = $user->lecturer->department_id;
        }

        $data = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('field','NonAkademik')
            ->where('level','Region')
            ->where('year',$req_year)
            ->where(function($q) use ($search){
                $q->where('team','like',"%$search%");
            })
            ->orderBy('id','DESC')
            ->paginate();

        $all = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('field','NonAkademik')
            ->get();

        $region = $national = $international = 0;
        $array_competition_region = [];
        $array_competition_national = [];
        $array_competition_international = [];

        foreach($all as $val){
            $key = $val->competition.$val->team.$val->year.$val->organizer;
            switch($val->level){
                case "Region":
                    if(!in_array($key,$array_competition_region)) $region++;
                    break;
                case "National":
                    if(!in_array($key,$array_competition_national)) $national++;
                    break;
                case "International":
                    if(!in_array($key,$array_competition_international)) $international++;
                    break;
            }
        }

        return view('nonAkademik/region',[
            'user'=>$user,
            'data'=>$data,
            'region'=>$region,
            'national'=>$national,
            'international'=>$international,
        ]);
    }

    public function nonAkademik_national(Request $request, $req_year)
    {
        if(!Auth::check()){
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }
        $user = Auth::user();

        session(['nonAkademikSESSION' => $req_year]);
        session(['breadcrumb' => 'nonAkademik_national']);
        $search = $request->search;

        // Ambil department_id jika bukan admin
        $department_id = null;
        if($user->role != 'admin') {
            if($user->role == 'department_head') $department_id = $user->department_head->department_id;
            elseif($user->role == 'lecturer') $department_id = $user->lecturer->department_id;
        }

        $data = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('field','NonAkademik')
            ->where('level','National')
            ->where('year',$req_year)
            ->where(function($q) use ($search){
                $q->where('team','like',"%$search%");
            })
            ->orderBy('id','DESC')
            ->paginate();

        $all = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('field','NonAkademik')
            ->get();

        $region = $national = $international = 0;
        $array_competition_region = [];
        $array_competition_national = [];
        $array_competition_international = [];

        foreach($all as $val){
            $key = $val->competition.$val->team.$val->year.$val->organizer;
            switch($val->level){
                case "Region":
                    if(!in_array($key,$array_competition_region)) $region++;
                    break;
                case "National":
                    if(!in_array($key,$array_competition_national)) $national++;
                    break;
                case "International":
                    if(!in_array($key,$array_competition_international)) $international++;
                    break;
            }
        }

        return view('nonAkademik/national',[
            'user'=>$user,
            'data'=>$data,
            'region'=>$region,
            'national'=>$national,
            'international'=>$international,
        ]);
    }


    public function nonAkademik_international(Request $request, $req_year)
    {
        if(!Auth::check()){
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }
        $user = Auth::user();

        session(['nonAkademikSESSION' => $req_year]);
        session(['breadcrumb' => 'nonAkademik_international']);
        $search = $request->search;

        // Ambil department_id jika bukan admin
        $department_id = null;
        if($user->role != 'admin') {
            if($user->role == 'department_head') $department_id = $user->department_head->department_id;
            elseif($user->role == 'lecturer') $department_id = $user->lecturer->department_id;
        }

        $data = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('field','NonAkademik')
            ->where('level','International')
            ->where('year',$req_year)
            ->where(function($q) use ($search){
                $q->where('team','like',"%$search%");
            })
            ->orderBy('id','DESC')
            ->paginate();

        $all = Achievement::when($department_id, fn($q) => $q->where('department_id', $department_id))
            ->where('field','NonAkademik')
            ->get();

        $region = $national = $international = 0;
        $array_competition_region = [];
        $array_competition_national = [];
        $array_competition_international = [];

        foreach($all as $val){
            $key = $val->competition.$val->team.$val->year.$val->organizer;
            switch($val->level){
                case "Region":
                    if(!in_array($key,$array_competition_region)) $region++;
                    break;
                case "National":
                    if(!in_array($key,$array_competition_national)) $national++;
                    break;
                case "International":
                    if(!in_array($key,$array_competition_international)) $international++;
                    break;
            }
        }

        return view('nonAkademik/international',[
            'user' => $user,
            'data' => $data,
            'region' => $region,
            'national' => $national,
            'international' => $international,
        ]);
    }


    // ======================= BASIC CRUD START =========================
    public function index()
    {
        return view('index');
    }

    public function select(Request $request)
    {
        if(!Auth::check()){
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $user = Auth::user();
        return view('admin/select', compact('user'));
    }

    public function selectPost(Request $request)
    {
        if(!Auth::check()){
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $request->validate([
            'selection' => 'required',
        ]);

        $valueTeam = $request->valueTeam ?? 1;
        $selection = $request->selection;

        session(['val' => $valueTeam]);
        session(['selection' => $selection]);

        return redirect('/add');
    }

    // ================= CREATE =================
    public function create()
    {
        if(!Auth::check()){
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $user = Auth::user();
        $departments = null;

        if($user->role == 'admin'){
            $departments = Department::orderBy('name', 'asc')->get();
        }

        return view('admin/add', compact('user', 'departments'));
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $auth = Auth::user();

        // Tentukan department_id
        $departmentId = null;
        if ($auth->role === 'admin') {
            $request->validate([
                'department_id' => ['required','exists:departments,id'],
            ]);
            $departmentId = (int) $request->department_id;
        } elseif ($auth->role === 'department_head') {
            $departmentId = optional($auth->department_head)->department_id;
        } elseif ($auth->role === 'lecturer') {
            $departmentId = optional($auth->lecturer)->department_id;
        }
        if (!$departmentId) {
            return back()->withErrors(['general' => 'Akun belum terhubung ke Program Studi.'])->withInput();
        }

        // Minimal peserta dinamis: Individu = 1, Kelompok = 2
        $minParticipants = $request->team_type === 'Kelompok' ? 2 : 1;

        // Validasi input utama & array peserta
        $request->validate([
            'team'        => 'required|string|max:255',
            'team_type'   => 'required|in:Individu,Kelompok',
            'level'       => 'required|in:Region,National,International',
            'field'       => 'required|in:Akademik,NonAkademik',
            'organizer'   => 'required|string|max:255',
            'month'       => 'required|digits:2',
            'year'        => 'required|digits:4|numeric',
            'competition' => 'required|string|max:255',
            'rank'        => 'nullable|string|max:255',

            'names'          => "required|array|min:$minParticipants",
            'names.*'        => 'required|string|max:255',
            'nims'           => "required|array|min:$minParticipants",
            'nims.*'         => 'required|string|max:50',
            'photos'         => 'nullable|array',
            'photos.*'       => 'nullable|mimes:jpeg,jpg,png|max:1100',
            'certificates'   => "required|array|min:$minParticipants",
            'certificates.*' => 'required|mimes:jpeg,jpg,png|max:1100',

            'documentations'   => 'nullable|array',
            'documentations.*' => 'nullable|mimes:jpeg,jpg,png|max:1100',
        ]);

        // Pre-check alignment: pastikan setiap baris peserta punya certificate
        $names = $request->input('names', []);
        $nims  = $request->input('nims', []);
        $certs = $request->file('certificates', []);

        $rows = max(count($names), count($nims));
        $alignErrors = [];
        for ($i = 0; $i < $rows; $i++) {
            $name = isset($names[$i]) ? trim($names[$i]) : '';
            $nim  = isset($nims[$i])  ? trim($nims[$i])  : '';
            if ($name === '' && $nim === '') {
                continue; // skip baris kosong
            }
            if (!isset($certs[$i])) {
                $alignErrors["certificates.$i"] = 'Sertifikat wajib diisi untuk setiap peserta.';
            }
        }
        // Enforcement minimal baris sesuai tipe
        $effectiveRows = 0;
        for ($i = 0; $i < $rows; $i++) {
            if (!empty(trim($names[$i] ?? '')) || !empty(trim($nims[$i] ?? ''))) {
                $effectiveRows++;
            }
        }
        if ($effectiveRows < $minParticipants) {
            $alignErrors['names'] = 'Jumlah peserta kurang dari ketentuan tipe tim.';
        }
        if (!empty($alignErrors)) {
            return back()->withErrors($alignErrors)->withInput();
        }

        // Buat Achievement (catatan: simpan team_type jika kolom tersedia)
        $achievement = \App\Models\Achievement::create([
            'department_id'    => $departmentId,
            'team'             => $request->team,
            'type_achievement' => $request->team_type,   // mengikuti struktur tabel yang ada
            'field'            => $request->field,
            'level'            => $request->level,
            'competition'      => $request->competition,
            'rank'             => $request->rank ?: null,
            'organizer'        => $request->organizer,
            'month'            => $request->month,
            'year'             => $request->year,
            // 'team_type'      => $request->team_type, // aktifkan jika kolom sudah ada
        ]);

        // Proses peserta
        $photos = $request->file('photos', []);
        for ($i = 0; $i < $rows; $i++) {
            $name = isset($names[$i]) ? trim($names[$i]) : '';
            $nim  = isset($nims[$i])  ? trim($nims[$i])  : '';
            if ($name === '' && $nim === '') {
                continue;
            }

            // Cari/buat Student berdasarkan NIM & Prodi
            $student = \App\Models\Student::where('nim', $nim)
                ->where('department_id', $departmentId)
                ->first();

            // Upload foto profil (opsional)
            $photoFileName = null;
            if (isset($photos[$i]) && $photos[$i]) {
                $p = $photos[$i];
                $photoFileName = time() . '_' . $p->getClientOriginalName();
                $p->move(public_path('image-profile'), $photoFileName);
            }

            if ($student) {
                $student->name = $name;
                if ($photoFileName) {
                    $student->photo = $photoFileName;
                }
                $student->save();
            } else {
                $student = \App\Models\Student::create([
                    'nim'           => $nim,
                    'name'          => $name,
                    'photo'         => $photoFileName,
                    'department_id' => $departmentId,
                ]);
            }

            // Upload sertifikat (wajib)
            $c = $certs[$i];
            $certificateFileName = $student->id . "_" . time() . "_" . $c->getClientOriginalName();
            $c->move(public_path('image-certificate'), $certificateFileName);

            // Attach ke pivot student_achievements + sertifikat
            $achievement->students()->attach($student->id, [
                'certificate' => $certificateFileName,
            ]);
        }

        // Dokumentasi (opsional, multiple)
        if ($request->hasFile('documentations')) {
            foreach ($request->file('documentations') as $doc) {
                if (!$doc) continue;
                $docName = $achievement->id . "_" . time() . "_" . $doc->getClientOriginalName();
                $doc->move(public_path('image-documentations'), $docName);

                \App\Models\AchievementDocumentation::create([
                    'achievement_id' => $achievement->id,
                    'image'          => $docName,
                ]);
            }
        }

        return redirect()->route('detail', $achievement->id)->with('status','Prestasi berhasil ditambahkan');
    }


    public function detail($id)
    {
        // Wajib login
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert', 'Kamu harus login dulu');
        }

        $user = Auth::user();

        // Ambil department_id user (untuk filter non-admin)
        $userDeptId = null;
        if ($user->role === 'department_head') {
            $userDeptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
        } elseif ($user->role === 'lecturer') {
            $userDeptId = Lecturer::where('user_id', $user->id)->value('department_id');
        }

        // Ambil 1 achievement + relasi terkait
        $achievement = Achievement::with([
            'department:id,name,faculty_id',
            'department.faculty:id,name',
            // students via pivot student_achievements (certificate ada di pivot -> relasi model harus withPivot('certificate'))
            'students' => function ($q) {
                $q->select('students.id','students.nim','students.name','students.photo','students.department_id');
            },
            'documentations:id,image,achievement_id',
        ])->findOrFail($id);

        
        // (Opsional) Batasi akses lintas-departemen untuk non-admin
        if ($user->role !== 'admin' && $userDeptId && (int)$achievement->department_id !== (int)$userDeptId) {
            abort(403, 'Anda tidak memiliki akses ke data departemen ini.');
        }

        // Semua pencapaian tim yang sama (untuk counter) + filter departemen jika non-admin
        $all_achievement = Achievement::where('team', $achievement->team)
            ->when($user->role !== 'admin' && $userDeptId, function ($q) use ($userDeptId) {
                $q->where('department_id', $userDeptId);
            })
            ->get();

        // Kirim ke view — pastikan Blade pakai variabel $achievement (bukan $mahasiswa)
        return view('admin/detail', [
            'achievement'     => $achievement,
            'all_achievement' => $all_achievement,
            'user'            => $user,
            'keyroute'        => 0,
        ]);
    }

    public function show($id)
    {
        // Wajib login
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert', 'Kamu harus login dulu');
        }

        $user = Auth::user();

        // Ambil department_id user (untuk filter non-admin)
        $userDeptId = null;
        if ($user->role === 'department_head') {
            $userDeptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
        } elseif ($user->role === 'lecturer') {
            $userDeptId = Lecturer::where('user_id', $user->id)->value('department_id');
        }

        // Ambil 1 achievement + relasi terkait
        $achievement = Achievement::with([
            'department:id,name,faculty_id',
            'department.faculty:id,name',
            // students via pivot student_achievements (certificate ada di pivot -> relasi model harus withPivot('certificate'))
            'students' => function ($q) {
                $q->select('students.id','students.nim','students.name','students.photo','students.department_id');
            },
            'documentations:id,image,achievement_id',
        ])->findOrFail($id);

        
        // (Opsional) Batasi akses lintas-departemen untuk non-admin
        if ($user->role !== 'admin' && $userDeptId && (int)$achievement->department_id !== (int)$userDeptId) {
            abort(403, 'Anda tidak memiliki akses ke data departemen ini.');
        }

        // Semua pencapaian tim yang sama (untuk counter) + filter departemen jika non-admin
        $all_achievement = Achievement::where('team', $achievement->team)
            ->when($user->role !== 'admin' && $userDeptId, function ($q) use ($userDeptId) {
                $q->where('department_id', $userDeptId);
            })
            ->get();

        // Kirim ke view — pastikan Blade pakai variabel $achievement (bukan $mahasiswa)
        return view('admin/detail', [
            'achievement'     => $achievement,
            'all_achievement' => $all_achievement,
            'user'            => $user,
            'keyroute'        => 0,
        ]);
    }

    public function edit($id)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert', 'Kamu harus login dulu');
        }

        $auth = Auth::user();

        // department user untuk akses non-admin
        $departmentId = null;
        if ($auth->role === 'department_head') {
            $departmentId = DepartmentHead::where('user_id', $auth->id)->value('department_id');
        } elseif ($auth->role === 'lecturer') {
            $departmentId = Lecturer::where('user_id', $auth->id)->value('department_id');
        }

        // Ambil achievement + relasi (hindari id ambiguous)
        $achievement = Achievement::with([
            'department:id,name,faculty_id',
            'department.faculty:id,name',
            'students' => function ($q) {
                $q->select('students.*'); // penting: hindari 'id' ambiguous
            },
            'documentations:id,image,achievement_id',
        ])->findOrFail($id);

        // Batasi akses lintas prodi utk non-admin
        if ($auth->role !== 'admin' && $departmentId && (int) $achievement->department_id !== (int) $departmentId) {
            abort(403, 'Anda tidak memiliki akses ke data departemen ini.');
        }

        // Untuk admin: kirim daftar prodi
        $departments = null;
        if ($auth->role === 'admin') {
            $departments = Department::select('id', 'name')->orderBy('name')->get();
        }

        // derive tipe tim dari kolom type_achievement
        $teamType = $achievement->type_achievement === 'Kelompok' ? 'Kelompok' : 'Individu';

        return view('admin/update', [
            'user'        => $auth,
            'achievement' => $achievement,
            'departments' => $departments,
            'teamType'    => $teamType,
        ]);
    }

    /**
     * Proses update Prestasi
     */
    public function update(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert', 'Kamu harus login dulu');
        }

        $auth = Auth::user();

        // Tentukan department_id (admin pilih, non-admin ikut account)
        $departmentId = null;
        if ($auth->role === 'admin') {
            $request->validate([
                'department_id' => ['required', 'exists:departments,id'],
            ]);
            $departmentId = (int) $request->department_id;
        } elseif ($auth->role === 'department_head') {
            $departmentId = DepartmentHead::where('user_id', $auth->id)->value('department_id');
        } elseif ($auth->role === 'lecturer') {
            $departmentId = Lecturer::where('user_id', $auth->id)->value('department_id');
        }
        if (!$departmentId) {
            return back()->withErrors(['general' => 'Akun belum terhubung ke Program Studi.'])->withInput();
        }

        // Ambil data lama + relasi (hindari id ambiguous)
        $achievement = Achievement::with([
            'students' => function ($q) {
                $q->select('students.*');
            },
            'documentations:id,image,achievement_id',
        ])->findOrFail($id);

        // Batasi akses lintas prodi utk non-admin
        if ($auth->role !== 'admin' && (int) $achievement->department_id !== (int) $departmentId) {
            abort(403, 'Anda tidak memiliki akses ke data departemen ini.');
        }

        // Validasi umum
        $teamType = $request->team_type === 'Kelompok' ? 'Kelompok' : 'Individu';
        $minParticipants = $teamType === 'Kelompok' ? 2 : 1;

        $request->validate([
            'team'        => 'required|string|max:255',
            'team_type'   => 'required|in:Individu,Kelompok',
            'level'       => 'required|in:Region,National,International',
            'field'       => 'required|in:Akademik,NonAkademik',
            'organizer'   => 'required|string|max:255',
            'month'       => 'required|digits:2',
            'year'        => 'required|digits:4|numeric',
            'rank'        => 'required|string|max:255',
            'competition' => 'required|string|max:255',
            'rank'        => 'nullable|string|max:255',

            // peserta (edit)
            'participant_ids'   => 'nullable|array',   // id student lama per baris (boleh kosong utk baris baru)
            'remove_flags'      => 'nullable|array',   // flag hapus baris
            'names'             => "required|array|min:$minParticipants",
            'names.*'           => 'nullable|string|max:255',
            'nims'              => "required|array|min:$minParticipants",
            'nims.*'            => 'nullable|string|max:50',
            'photos'            => 'nullable|array',
            'photos.*'          => 'nullable|mimes:jpeg,jpg,png|max:1100',
            'certificates'      => 'nullable|array',
            'certificates.*'    => 'nullable|mimes:jpeg,jpg,png|max:1100',

            // dokumentasi
            'documentations'    => 'nullable|array',
            'documentations.*'  => 'nullable|mimes:jpeg,jpg,png|max:1100',
            'delete_docs'       => 'nullable|array',
        ]);

        // Pastikan jumlah baris efektif (nama atau nim terisi)
        $names = $request->input('names', []);
        $nims  = $request->input('nims', []);
        $rows  = max(count($names), count($nims));
        $effective = 0;
        for ($i = 0; $i < $rows; $i++) {
            if (trim(isset($names[$i]) ? $names[$i] : '') !== '' || trim(isset($nims[$i]) ? $nims[$i] : '') !== '') {
                $effective++;
            }
        }
        if ($effective < $minParticipants) {
            return back()->withErrors(['names' => 'Jumlah peserta kurang dari ketentuan tipe tim.'])->withInput();
        }

        // Update kolom Achievement
        $achievement->department_id    = $departmentId;
        $achievement->team             = $request->team;
        $achievement->type_achievement = $teamType; // disimpan sesuai skema
        $achievement->field            = $request->field;
        $achievement->level            = $request->level;
        $achievement->competition      = $request->competition;
        $achievement->rank             = $request->rank ? $request->rank : null;
        $achievement->organizer        = $request->organizer;
        $achievement->month            = $request->month;
        $achievement->year             = $request->year;
        $achievement->save();

        // ----- Rekonsiliasi Peserta -----
        $participantIds = $request->input('participant_ids', []); // student id per baris (bisa null)
        $removeFlags    = $request->input('remove_flags', []);    // '1' untuk hapus baris
        $photos         = $request->file('photos', []);
        $certs          = $request->file('certificates', []);

        $keepStudentIds = [];

        for ($i = 0; $i < $rows; $i++) {
            $name = trim(isset($names[$i]) ? $names[$i] : '');
            $nim  = trim(isset($nims[$i]) ? $nims[$i] : '');
            $remove = isset($removeFlags[$i]) && $removeFlags[$i] == '1';
            $existingStudentId = !empty($participantIds[$i]) ? (int) $participantIds[$i] : null;

            // Jika baris kosong total & tidak refer student lama → skip
            if ($name === '' && $nim === '' && !$existingStudentId) {
                continue;
            }

            // Jika diminta hapus & ada student id lama → detach dan lanjut
            if ($remove && $existingStudentId) {
                $achievement->students()->detach($existingStudentId);
                continue;
            }

            // Cari / buat Student
            $student = null;
            if ($existingStudentId) {
                $student = Student::find($existingStudentId);
                if ($student) {
                    if ($name !== '') $student->name = $name;
                    if ($nim  !== '') $student->nim  = $nim;
                    $student->department_id = $departmentId;
                }
            } else {
                if ($nim !== '') {
                    $student = Student::where('nim', $nim)->where('department_id', $departmentId)->first();
                }
                if (!$student) {
                    $student = new Student();
                    $student->nim           = $nim;
                    if ($name !== '') $student->name = $name;
                    $student->department_id = $departmentId;
                }
            }

            // Foto profil opsional (replace jika upload)
            if (isset($photos[$i]) && $photos[$i]) {
                $p = $photos[$i];
                $photoFileName = time() . '_' . $p->getClientOriginalName();
                $p->move(public_path('image-profile'), $photoFileName);
                $student->photo = $photoFileName;
            }

            $student->save();

            // Sertifikat: optional di edit (replace jika upload baru, keep jika kosong)
            $existingOnPivot = $achievement->students()->where('students.id', $student->id)->first();
            $newCertFile = null;
            if (isset($certs[$i]) && $certs[$i]) {
                $c = $certs[$i];
                $newCertFile = $student->id . "_" . time() . "_" . $c->getClientOriginalName();
                $c->move(public_path('image-certificate'), $newCertFile);
            }

            if ($existingOnPivot) {
                // sudah terhubung → update pivot (certificate replace jika ada baru)
                $achievement->students()->updateExistingPivot($student->id, [
                    'certificate' => $newCertFile ? $newCertFile : $existingOnPivot->pivot->certificate,
                ]);
            } else {
                // peserta baru → wajib sertifikat baru
                if (!$newCertFile) {
                    return back()->withErrors(["certificates.$i" => 'Sertifikat wajib diisi untuk peserta baru.'])->withInput();
                }
                $achievement->students()->attach($student->id, [
                    'certificate' => $newCertFile,
                ]);
            }

            $keepStudentIds[] = $student->id;
        }

        // Detach peserta yg tidak di-keep
        $existingIds = $achievement->students()->pluck('students.id')->toArray();
        $toDetach = array_diff($existingIds, $keepStudentIds);
        if (!empty($toDetach)) {
            $achievement->students()->detach($toDetach);
        }

        // ----- Dokumentasi -----
        // Hapus dokumentasi yang ditandai
        $deleteDocs = $request->input('delete_docs', []);
        if (is_array($deleteDocs) && count($deleteDocs)) {
            AchievementDocumentation::where('achievement_id', $achievement->id)
                ->whereIn('id', array_map('intval', $deleteDocs))
                ->delete();
            // (opsional) hapus file fisik di /public/image-documentations jika diperlukan
        }

        // Tambah dokumentasi baru
        if ($request->hasFile('documentations')) {
            foreach ($request->file('documentations') as $doc) {
                if (!$doc) continue;
                $docName = $achievement->id . "_" . time() . "_" . $doc->getClientOriginalName();
                $doc->move(public_path('image-documentations'), $docName);

                AchievementDocumentation::create([
                    'achievement_id' => $achievement->id,
                    'image'          => $docName,
                ]);
            }
        }

        // Redirect sesuai field
        if ($request->field === 'NonAkademik') {
            return redirect()->route('nonAkademik')->with('status', 'Prestasi berhasil diperbarui');
        }
        return redirect()->route('detail', $id)->with('status', 'Prestasi berhasil diperbarui');
    }

    public function destroy($id)
    {
        if (!Auth::check()) {
            return redirect('/user/login')->with('alert','Kamu harus login dulu');
        }

        $user = Auth::user();

        // Ambil achievement + relasi yang diperlukan (hindari ambiguous id)
        $achievement = Achievement::with([
            'documentations:id,image,achievement_id',
            'students' => function ($q) {
                $q->select('students.*'); // penting agar tidak ambiguous di SELECT id
            },
        ])->findOrFail($id);

        // Tentukan department_id user untuk non-admin
        $userDeptId = null;
        if ($user->role === 'department_head') {
            $userDeptId = DepartmentHead::where('user_id', $user->id)->value('department_id');
        } elseif ($user->role === 'lecturer') {
            $userDeptId = Lecturer::where('user_id', $user->id)->value('department_id');
        }

        // Otorisasi: admin bebas; non-admin hanya boleh hapus di departemennya
        if ($user->role !== 'admin') {
            if (!$userDeptId || (int)$achievement->department_id !== (int)$userDeptId) {
                abort(403, 'Unauthorized');
            }
        }

        DB::beginTransaction();
        try {
            // 1) Hapus file sertifikat milik pivot student_achievements (jangan hapus mahasiswa)
            foreach ($achievement->students as $stu) {
                $cert = $stu->pivot->certificate ?? null;
                if ($cert) {
                    $path = public_path('image-certificate/'.$cert);
                    if (File::exists($path)) {
                        File::delete($path);
                    }
                }
            }

            // 2) Lepas semua relasi peserta pada pivot
            $achievement->students()->detach();

            // 3) Hapus semua file dokumentasi + record-nya
            foreach ($achievement->documentations as $doc) {
                $docPath = public_path('image-documentations/'.$doc->image);
                if (File::exists($docPath)) {
                    File::delete($docPath);
                }
                $doc->delete();
            }

            // 4) Hapus record achievement
            $achievement->delete();

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            // Bisa diarahkan ke halaman sebelumnya dengan pesan error yang jelas
            return back()->withErrors(['general' => 'Gagal menghapus data: '.$e->getMessage()]);
        }

        // Redirect sesuai breadcrumb session (seperti logika awalmu)
        $breadcrumb = session('breadcrumb');

        switch ($breadcrumb) {
            case 'akademik_region':
                return redirect(route('akademik-region', ['year' => session('akademikSESSION')]))
                    ->with('status', 'Data berhasil dihapus');
            case 'akademik_national':
                return redirect(route('akademik-national', ['year' => session('akademikSESSION')]))
                    ->with('status', 'Data berhasil dihapus');
            case 'akademik_international':
                return redirect(route('akademik-international', ['year' => session('akademikSESSION')]))
                    ->with('status', 'Data berhasil dihapus');
            case 'nonAkademik_region':
                return redirect(route('nonAkademik-region', ['year' => session('nonAkademikSESSION')]))
                    ->with('status', 'Data berhasil dihapus');
            case 'nonAkademik_national':
                return redirect(route('nonAkademik-national', ['year' => session('nonAkademikSESSION')]))
                    ->with('status', 'Data berhasil dihapus');
            case 'nonAkademik_international':
                return redirect(route('nonAkademik-international', ['year' => session('nonAkademikSESSION')]))
                    ->with('status', 'Data berhasil dihapus');
            default:
                return redirect('/')->with('status','Data berhasil dihapus');
        }
    }
}