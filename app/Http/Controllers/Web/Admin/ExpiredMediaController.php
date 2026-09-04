<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostShareMedia;
use App\Services\Media\ExpiredPostShareMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ExpiredMediaController extends Controller
{
    public function index(ExpiredPostShareMediaService $expiredMedia): View
    {
        $items = $expiredMedia->query()->paginate(20);
        $items->getCollection()->transform(function (PostShareMedia $item) use ($expiredMedia): PostShareMedia {
            $item->setAttribute('file_status_label', $expiredMedia->fileStatus($item));
            $item->setAttribute('size_human', $this->formatBytes((int) ($item->size_bytes ?? 0)));

            return $item;
        });

        $summary = $expiredMedia->summary();
        $summary['bytes_human'] = $this->formatBytes($summary['bytes']);

        return view('admin.expired-media.index', [
            'items' => $items,
            'summary' => $summary,
        ]);
    }

    public function purge(Request $request, ExpiredPostShareMediaService $expiredMedia): RedirectResponse
    {
        $result = $expiredMedia->purge();

        Log::info('spoton.admin.expired_media.purged', [
            'admin_id' => $request->user()->id,
            ...$result,
        ]);

        return back()->with(
            'status',
            "Eliminati {$result['files']} file e {$result['records']} record scaduti.",
        );
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1, ',', '.').' KB';
        }

        if ($bytes < 1024 * 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1, ',', '.').' MB';
        }

        return number_format($bytes / (1024 * 1024 * 1024), 1, ',', '.').' GB';
    }
}
