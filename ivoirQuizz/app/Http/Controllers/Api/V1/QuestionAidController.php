<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Game\GameActionConflictException;
use App\Exceptions\Game\InsufficientCoinsException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Models\GameSession;
use App\Models\Question;
use App\Services\Game\QuestionAidService;
use Illuminate\Http\Request;
use Throwable;

class QuestionAidController extends Controller
{
    use ApiResponse;

    public function hint(Request $request, GameSession $session, Question $question, QuestionAidService $service)
    {
        return $this->execute(fn () => $service->hint($request->user(), $session, $question), 'Indice acheté.');
    }

    public function skip(Request $request, GameSession $session, Question $question, QuestionAidService $service)
    {
        return $this->execute(fn () => $service->skip($request->user(), $session, $question), 'Question passée.');
    }

    private function execute(callable $callback, string $message)
    {
        try { return $this->success($message, $callback()); }
        catch (InsufficientCoinsException $e) { return $this->error($e->getMessage(), [], 422); }
        catch (GameActionConflictException $e) { return $this->error($e->getMessage(), [], 409); }
        catch (Throwable $e) { return $this->businessError($e, 'question aid failed'); }
    }
}
