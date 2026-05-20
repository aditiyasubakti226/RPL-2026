<?php

namespace App\Http\Controllers\Editorial;

use App\Http\Controllers\Controller;
use App\Models\Submission; // Import Model Submission
use Illuminate\Http\Request; // Import Request

class DeskController extends Controller
{
    // === METHOD UPDATE ROUND (TUGAS 4 KAMU) ===
    /**
     * Update round tracking submission (ronde ke-N)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Submission  $submission
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRound(Request $request, Submission $submission)
    {
        // 1. Validasi input ronde
        $validated = $request->validate([
            'current_round' => 'required|integer|min:1',
            'notes'         => 'nullable|string|max:1000'
        ]);

        try {
            // 2. Update kolom round tracking pada model Submission
            $submission->update([
                'current_round' => $validated['current_round'],
            ]);

            // 3. Kembali ke halaman sebelumnya dengan pesan sukses
            return redirect()->back()->with('success', 'Ronde tracking submission berhasil diperbarui ke ronde ' . $validated['current_round']);

        } catch (\Exception $e) {
            // Jika terjadi error sistem
            return redirect()->back()->with('error', 'Gagal memperbarui ronde tracking submission.');
        }
    }
} // <-- Kurung kurawal penutup kelas WAJIB di paling bawah sendiri