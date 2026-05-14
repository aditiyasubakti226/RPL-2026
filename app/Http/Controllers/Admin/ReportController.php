<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ResearchReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Tampilkan halaman Custom Report Generator.
     */
    public function generator()
    {
        $years = \App\Models\Journal::select('first_published_year')
            ->whereNotNull('first_published_year')
            ->distinct()
            ->orderBy('first_published_year', 'desc')
            ->pluck('first_published_year')
            ->toArray();

        $statuses = \App\Models\Journal::select('approval_status')
            ->whereNotNull('approval_status')
            ->distinct()
            ->pluck('approval_status')
            ->toArray();

        return Inertia::render('Admin/Report/Generator', [
            'filterOptions' => [
                'tahun' => $years,
                'status' => $statuses
            ]
        ]);
    }

    /**
     * Trigger Export Excel.
     */
    public function exportExcel(Request $request)
    {
        $request->validate([
            'filters' => 'nullable|array',
            'filters.tahun' => 'nullable|array',
            'filters.status' => 'nullable|array',
        ]);

        $filters = $request->input('filters', []);

        // Return file download via browser
        return Excel::download(new ResearchReportExport($filters), 'rekap_penelitian_'.date('Ymd_His').'.xlsx');
    }
}
