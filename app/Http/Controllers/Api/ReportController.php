<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreReportRequest;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request): JsonResponse
    {
        $data = $request->validated();
        $reportable = $this->resolveReportable($data['target_type'], $data['target_id']);

        $this->ensureCanReport($request->user(), $reportable);

        $report = Report::query()->firstOrCreate(
            [
                'reporter_id' => $request->user()->id,
                'reportable_type' => $data['target_type'],
                'reportable_id' => $reportable->getKey(),
                'status' => Report::STATUS_PENDING,
            ],
            [
                'reason' => $data['reason'],
                'details' => $data['details'] ?? null,
            ],
        );

        return response()->json([
            'message' => $report->wasRecentlyCreated ? 'Segnalazione ricevuta.' : 'Segnalazione gia in attesa.',
            'data' => [
                'id' => $report->id,
                'target_type' => $report->reportable_type,
                'target_id' => $report->reportable_id,
                'reason' => $report->reason,
                'status' => $report->status,
                'created_at' => $report->created_at?->toISOString(),
            ],
        ], $report->wasRecentlyCreated ? 201 : 200);
    }

    private function resolveReportable(string $type, string $id): Model
    {
        return match ($type) {
            'post' => Post::query()->findOrFail($id),
            'user' => User::query()->findOrFail($id),
        };
    }

    private function ensureCanReport(User $reporter, Model $reportable): void
    {
        $isOwnUser = $reportable instanceof User && $reportable->id === $reporter->id;
        $isOwnPost = $reportable instanceof Post && $reportable->author_id === $reporter->id;

        if ($isOwnUser || $isOwnPost) {
            throw ValidationException::withMessages([
                'target_id' => ['Non puoi segnalare te stesso o un tuo post.'],
            ]);
        }
    }
}
