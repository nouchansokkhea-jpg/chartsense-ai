<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminScanController extends Controller
{
    /**
     * Display list of all scans for monitoring.
     */
    public function index(Request $request): View
    {
        $query = Scan::with(['user', 'result', 'input'])->latest('id');

        if ($request->filled('symbol')) {
            $query->where('symbol', 'ilike', '%' . trim($request->symbol) . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('direction')) {
            $query->whereHas('result', fn($q) => $q->where('direction', $request->direction));
        }

        $scans = $query->paginate(20)->withQueryString();

        return view('admin.scans.index', compact('scans'));
    }
}
