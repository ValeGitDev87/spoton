<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups)
    {
    }

    public function index(): View
    {
        return view('admin.backups.index', [
            'backups' => $this->backups->list(),
            'backupPath' => $this->backups->path(),
        ]);
    }

    public function download(Request $request, string $filename): BinaryFileResponse
    {
        try {
            $path = $this->backups->resolve($filename);
        } catch (Throwable) {
            abort(404);
        }

        Log::info('Admin backup download', [
            'admin_id' => $request->user()->id,
            'filename' => basename($path),
        ]);

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $result = $this->backups->createManualBackup();

            Log::info('Admin manual backup requested', [
                'admin_id' => $request->user()->id,
                'output' => $result['output'] ?? null,
            ]);

            return back()->with('status', 'Backup manuale avviato/completato.');
        } catch (Throwable $exception) {
            Log::warning('Admin manual backup failed', [
                'admin_id' => $request->user()->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors(['backup' => $exception->getMessage()]);
        }
    }
}
