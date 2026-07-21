<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $reports = Report::query()
            ->with(['reporter', 'reportable', 'reviewer'])
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('target_type'), fn ($query, string $type) => $query->where('reportable_type', $type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'status' => $request->query('status', ''),
            'targetType' => $request->query('target_type', ''),
        ]);
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                Report::STATUS_REVIEWED,
                Report::STATUS_DISMISSED,
                Report::STATUS_ACTIONED,
            ])],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $report, $data): void {
            $report->loadMissing('reportable');

            if ($data['status'] === Report::STATUS_ACTIONED) {
                $this->applyModerationAction($report->reportable, $data['resolution_note'] ?? null);
            }

            $report->update([
                'status' => $data['status'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'resolution_note' => $data['resolution_note'] ?? null,
            ]);
        });

        return back()->with('status', 'Segnalazione aggiornata.');
    }

    private function applyModerationAction(Post|User|null $reportable, ?string $note): void
    {
        abort_if($reportable === null, 422, 'Il contenuto segnalato non e piu disponibile.');

        if ($reportable instanceof Post) {
            $reportable->update(['status' => 'removed']);

            return;
        }

        abort_if($reportable->is_admin, 422, 'Non puoi sospendere un amministratore da una segnalazione.');

        $reportable->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspension_reason' => $note ?: 'Sospensione da segnalazione admin.',
        ]);
        $reportable->tokens()->delete();
    }
}
