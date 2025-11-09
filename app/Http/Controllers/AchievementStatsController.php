<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Department;
use App\Models\DepartmentHead;
use App\Models\FacultyHead;
use App\Models\Lecturer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AchievementStatsController extends Controller
{
    /**
     * Tampilkan daftar prestasi untuk level (Region|National|International)
     * dan bucket (TS|TS-1|TS-2).
     */
    public function bucket(Request $request, string $level, string $bucket)
    {
        $allowedLevels = ['Region','National','International'];
        $allowedBuckets = ['TS','TS-1','TS-2'];

        // sanitize inputs
        $level = in_array($level, $allowedLevels, true) ? $level : 'National';
        $bucket = in_array($bucket, $allowedBuckets, true) ? $bucket : 'TS';

        // resolve date window (if start+end provided -> override)
        [$start, $end] = $this->resolveWindow($bucket, $request->query('start'), $request->query('end'));

        // Base query with eager loads
        $q = Achievement::query()
            ->with([
                // department relation
                'department' => function($qq){ $qq->select('id','name'); },

                // studentAchievements relation: select minimal fields and eager-load student
                'studentAchievements' => function($qq) {
                    $qq->select('id','achievement_id','student_id')
                       ->with(['student' => function($q2) {
                           $q2->select('id','name','nim');
                       }]);
                }
            ])
            ->where('level', $level);

        // department param: support both department_id and department_ach (UI may use department_ach)
        $reqDept = $request->query('department_id') ?? $request->query('department_ach');

        // role-based department scoping
        $auth = Auth::user();
        if ($auth && $auth->role !== 'admin') {
            if ($auth->role === 'department_head') {
                $deptId = DepartmentHead::where('user_id', $auth->id)->value('department_id');
                if ($deptId) $q->where('department_id', $deptId);
            } elseif ($auth->role === 'lecturer') {
                $deptId = Lecturer::where('user_id', $auth->id)->value('department_id');
                if ($deptId) $q->where('department_id', $deptId);
            } elseif ($auth->role === 'faculty_head') {
                $facultyId = FacultyHead::where('user_id', $auth->id)->value('faculty_id');
                if ($reqDept && ctype_digit((string)$reqDept) && $facultyId) {
                    $ok = Department::where('id', (int)$reqDept)->where('faculty_id', $facultyId)->exists();
                    if ($ok) $q->where('department_id', (int)$reqDept);
                } elseif ($facultyId) {
                    $q->whereIn('department_id', Department::where('faculty_id', $facultyId)->pluck('id')->toArray());
                }
            }
        } else {
            // admin: allow filter by department_id
            if ($reqDept && ctype_digit((string)$reqDept)) {
                $q->where('department_id', (int)$reqDept);
            }
        }

        // filter by date window (Achievement stores year+month)
        if ($start && $end) {
            $ymFrom = ((int)$start->year * 100) + (int)$start->month;
            $ymTo   = ((int)$end->year * 100)   + (int)$end->month;
            if ($ymFrom > $ymTo) { [$ymFrom, $ymTo] = [$ymTo, $ymFrom]; }

            $q->whereRaw('(year * 100 + month) between ? and ?', [$ymFrom, $ymTo]);
        }

        // retrieve results ordered newest first
        $achievements = $q->orderByDesc('year')->orderByDesc('month')->get([
            'id','department_id','competition','team','level','organizer','month','year','rank','link'
        ]);

        // Prepare some metadata for view
        $startStr = $start ? $start->toDateString() : null;
        $endStr   = $end   ? $end->toDateString()   : null;

        // For breadcrumb / back links keep the original query string
        $queryAll = $request->query();

        return view('achievement.bucket', [
            'user'         => $auth,
            'level'        => $level,
            'bucket'       => $bucket,
            'start'        => $startStr,
            'end'          => $endStr,
            'achievements' => $achievements,
            'queryAll'     => $queryAll,
        ]);
    }

    /**
     * Resolve window mengikuti aturan TS.
     */
    private function resolveWindow(string $bucket, ?string $start, ?string $end): array
    {
        if ($start && $end) {
            try {
                return [Carbon::parse($start), Carbon::parse($end)];
            } catch (\Throwable $e) {
                // fallback ke default
            }
        }

        $now = Carbon::today();
        $startYear = ($now->month >= 3) ? $now->year : $now->year - 1;

        $tsStart = Carbon::create($startYear, 3, 1)->startOfDay();
        $tsEnd   = Carbon::create($startYear + 1, 2, 1)->endOfMonth()->endOfDay();

        $ts1Start = $tsStart->copy()->subYear();
        $ts1End   = $tsEnd->copy()->subYear();
        $ts2Start = $tsStart->copy()->subYears(2);
        $ts2End   = $tsEnd->copy()->subYears(2);

        switch ($bucket) {
            case 'TS':  return [$tsStart, $tsEnd];
            case 'TS-1':return [$ts1Start, $ts1End];
            case 'TS-2':return [$ts2Start, $ts2End];
            default:    return [$tsStart, $tsEnd];
        }
    }
}
