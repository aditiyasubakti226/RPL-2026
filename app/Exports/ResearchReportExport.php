<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResearchReportExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function view(): View
    {
        $query = \App\Models\Journal::with(['university', 'user']);

        // Menerapkan filter
        if (!empty($this->filters['tahun'])) {
            $query->whereIn('first_published_year', $this->filters['tahun']);
        }
        
        if (!empty($this->filters['status'])) {
            $query->whereIn('approval_status', $this->filters['status']);
        }

        $data = $query->get()->map(function ($journal) {
            return (object)[
                'id' => $journal->id,
                'judul' => $journal->title,
                'peneliti' => $journal->user ? $journal->user->name : '-',
                'universitas' => $journal->university ? $journal->university->name : '-',
                'tahun' => $journal->first_published_year ?: '-',
                'status' => $journal->approval_status_label
            ];
        });

        return view('exports.research_report', [
            'data' => $data
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],
        ];
    }
}
