<?php

namespace App\Http\Controllers;

use App\Services\RecycleBinService;
use Illuminate\Http\Request;

class RecycleBinController extends Controller
{
    protected RecycleBinService $recycleBinService;

    public function __construct(RecycleBinService $recycleBinService)
    {
        $this->recycleBinService = $recycleBinService;
    }

    public function index()
    {
        $items = $this->recycleBinService->list();
        return view('admin.recycle-bin', compact('items'));
    }

    public function restore(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $result = $this->recycleBinService->restore($request->input('id'));

        return response()->json([
            'status' => 'success',
            'message' => 'Item restored successfully.',
            'data' => $result,
        ]);
    }

    public function purge(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $this->recycleBinService->purge($request->input('id'));

        return response()->json([
            'status' => 'success',
            'message' => 'Item permanently deleted.',
        ]);
    }

    public function empty(Request $request)
    {
        $count = $this->recycleBinService->emptyAll();

        return response()->json([
            'status' => 'success',
            'message' => "Recycle bin emptied ({$count} items purged).",
        ]);
    }
}
