<?php

namespace App\Http\Controllers;

use App\Models\Notice;

class NoticeController extends Controller
{
    /**
     * メインページ（全員）
     */
    public function index()
    {
        $notices = Notice::where('is_active', true)
            ->orderByDesc('published_at')
            ->get();

        return view('dashboard', compact('notices'));
    }
}
