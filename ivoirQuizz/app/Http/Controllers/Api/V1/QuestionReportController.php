<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Question\QuestionReportRequest;
use App\Http\Resources\Api\V1\QuestionReportResource;
use App\Models\Question;
use App\Services\Game\QuestionReportService;
use Throwable;

class QuestionReportController extends Controller
{
    use ApiResponse;

    public function store(QuestionReportRequest $request, Question $question, QuestionReportService $service)
    {
        try {
            $report = $service->report($request->user(), $question, $request->validated('reason'), $request->validated('message'));
            return $this->successResponse(new QuestionReportResource($report), 'Signalement enregistré.', 201);
        } catch (Throwable $e) {
            return $this->businessError($e, 'question report failed');
        }
    }
}
