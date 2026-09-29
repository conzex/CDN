<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('path')) {
            $query->where('path', 'like', '%' . $request->input('path') . '%');
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $activities = $query->paginate(20)->withQueryString();

        return view('admin.activity', compact('activities'));
    }

    public function api(Request $request)
    {
        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('path')) {
            $query->where('path', 'like', '%' . $request->input('path') . '%');
        }

        $activities = $query->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $activities,
        ]);
    }
}
