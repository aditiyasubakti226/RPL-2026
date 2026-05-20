<?php

namespace App\Http\Controllers\Editorial;

use App\Http\Controllers\Controller;
use App\Models\Discussion; // Sesuaikan dengan nama model Anda
use App\Models\Submission; // Sesuaikan dengan nama model Anda
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EditorialDiscussionController extends Controller
{
    /**
     * Menampilkan daftar diskusi berdasarkan submissionId
     */
    public function index($submissionId)
    {
        // Pastikan submission-nya ada
        $submission = Submission::findOrFail($submissionId);

        // Ambil data diskusi yang berelasi dengan submission ini beserta user pengirimnya
        $discussions = Discussion::where('submission_id', $submissionId)
            ->with('user:id,name') // Hanya mengambil id dan nama user untuk efisiensi
            ->orderBy('created_at', 'asc') // Urutkan dari pesan terlama ke terbaru (thread-style)
            ->get();

        return Inertia::render('Editorial/Desk/Discussion', [
            'submissionId' => (int) $submissionId,
            'discussions' => $discussions,
        ]);
    }

    /**
     * Menyimpan pesan/balasan diskusi baru
     */
    public function store(Request $request, $submissionId)
    {
        // Validasi input pesan
        $request->validate([
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        // Simpan data ke database
        Discussion::create([
            'submission_id' => $submissionId,
            'user_id' => Auth::id(), // ID Editor atau Author yang sedang login
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        // Redirect kembali ke halaman diskusi dengan membawa data terbaru (Inertia otomatis reload data)
        return redirect()->back()->with('success', 'Pesan diskusi berhasil dikirim.');
    }
}