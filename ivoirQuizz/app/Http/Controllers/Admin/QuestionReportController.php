<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuestionReportController extends Controller
{
    public const STATUSES = ['pending', 'reviewed', 'fixed', 'rejected'];

    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        $reports = QuestionReport::query()
            ->with(['user', 'question' => fn ($query) => $query->withTrashed()])
            ->when(in_array($status, self::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->when($request->filled('reason'), fn ($query) => $query->where('reason', $request->query('reason')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'status' => $status,
            'statuses' => self::STATUSES,
            'counts' => QuestionReport::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(QuestionReport $report): View
    {
        $report->load(['user', 'question' => fn ($query) => $query->withTrashed()->with(['answers' => fn ($answers) => $answers->orderBy('order'), 'level.city', 'category'])]);

        return view('admin.reports.show', [
            'report' => $report,
            'related' => QuestionReport::query()
                ->with('user')
                ->where('question_id', $report->question_id)
                ->whereKeyNot($report->id)
                ->latest()
                ->get(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, QuestionReport $report): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'apply_to_question' => ['boolean'],
        ]);

        $values = [
            'status' => $validated['status'],
            'reviewed_at' => $validated['status'] === 'pending' ? null : now(),
        ];

        $report->update($values);

        if ($request->boolean('apply_to_question')) {
            QuestionReport::query()
                ->where('question_id', $report->question_id)
                ->where('status', 'pending')
                ->update($values + ['updated_at' => now()]);
        }

        return redirect()->route('admin.reports.index')->with('success', 'Signalement mis à jour.');
    }
}
