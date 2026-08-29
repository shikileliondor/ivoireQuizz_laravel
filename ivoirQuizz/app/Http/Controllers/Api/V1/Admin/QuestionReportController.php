<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ResolveReportRequest;
use App\Http\Resources\Api\V1\Admin\AdminQuestionReportResource;
use App\Models\QuestionReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class QuestionReportController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $reports = QuestionReport::query()
            ->with(['user', 'question.answers', 'question.level.chapter', 'question.category'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('reason'), fn ($q) => $q->where('reason', $request->string('reason')))
            ->when($request->filled('question_id'), fn ($q) => $q->where('question_id', $request->integer('question_id')))
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return AdminQuestionReportResource::collection($reports)->response();
    }

    public function show(QuestionReport $report)
    {
        $report->load(['user', 'question.answers', 'question.level.chapter', 'question.category']);

        return $this->successResponse(new AdminQuestionReportResource($report));
    }

    /**
     * Resolving a report may also pull the question out of rotation: a wrong
     * answer key keeps costing players sessions until it is deactivated.
     */
    public function resolve(ResolveReportRequest $request, QuestionReport $report)
    {
        try {
            DB::transaction(function () use ($request, $report): void {
                $report->update([
                    'status' => $request->validated('status'),
                    'reviewed_at' => now(),
                ]);

                if ($request->boolean('deactivate_question')) {
                    $report->question()->update(['is_active' => false]);
                }
            });

            $report->load(['user', 'question.answers', 'question.level.chapter']);

            return $this->successResponse(new AdminQuestionReportResource($report), 'Signalement traité.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin report resolve failed');
        }
    }
}
