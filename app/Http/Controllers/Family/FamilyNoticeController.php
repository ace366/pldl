<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Request;

class FamilyNoticeController extends Controller
{
    public function index(Request $request)
    {
        // 例：公開中のみ（必要に応じて条件を合わせる）
        $notices = Notice::query()
            ->latest('id')
            ->paginate(20);

        return view('family.notices.index', compact('notices'));
    }
}
