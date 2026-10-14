<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegistrationStatusController extends Controller
{
    public function index()
    {
        $stats = User::query()
            ->whereNotNull('graduation_year')
            ->select(
                'graduation_year',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved"),
                DB::raw("SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN role = 'year_admin' THEN 1 ELSE 0 END) as year_admins")
            )
            ->groupBy('graduation_year')
            ->get()
            ->keyBy('graduation_year');

        // 参照名簿に保管されている年代（「高校N回期」形式、卒業年度 = N + 1947）
        $rosterCounts = DB::table('reference_rosters')
            ->select('graduation_term', DB::raw('COUNT(*) as cnt'))
            ->groupBy('graduation_term')
            ->pluck('cnt', 'graduation_term');

        $rosterByYear = [];
        foreach ($rosterCounts as $term => $cnt) {
            if (preg_match('/高校\s*(\d+)\s*回期/u', (string) $term, $m)) {
                $year = (int) $m[1] + 1947;
                $rosterByYear[$year] = ($rosterByYear[$year] ?? 0) + $cnt;
            }
        }
        krsort($rosterByYear);

        $rows = collect();
        foreach ($rosterByYear as $year => $rosterCount) {
            $s = $stats->get($year);
            $rows->push((object) [
                'year' => $year,
                'term' => $year - 1947,
                'roster' => $rosterCount,
                'total' => (int) ($s->total ?? 0),
                'approved' => (int) ($s->approved ?? 0),
                'pending' => (int) ($s->pending ?? 0),
                'year_admins' => (int) ($s->year_admins ?? 0),
            ]);
        }

        $noRegistration = $rows->where('approved', 0)->values();
        $noYearAdmin = $rows->where('year_admins', 0)->values();

        return view('admin.registration_status.index', compact('rows', 'noRegistration', 'noYearAdmin'));
    }
}