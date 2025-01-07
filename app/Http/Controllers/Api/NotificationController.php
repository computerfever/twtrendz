<?php

namespace Acelle\Http\Controllers\Api;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;
use Acelle\Model\BounceLog;
use Acelle\Model\FeedbackLog;
use Response;
use Auth;
use Exception;

class NotificationController extends Controller
{
    public function bounce(Request $request)
    {
        $user = Auth::guard('api')->user();
        $params = $request->all();

        try {
            $bounceLog = BounceLog::recordHardBounce(
                $params['message_id'],
                $params['description'] ?? 'N/A',
                $logCallback = null,
                $throwMsgNotFoundException = true
            );

            return Response::json($bounceLog, 200);
        } catch (Exception $ex) {
            return Response::json(['error' => $ex->getMessage()], 400);
        }
    }

    public function feedback(Request $request)
    {
        $user = Auth::guard('api')->user();
        $params = $request->all();

        try {
            $feedbackLog = FeedbackLog::recordFeedback(
                $params['message_id'] ?? null,
                $params['type'] ?? null,
                $params['description'] ?? 'N/A',
                $logCallback = null,
                $throwMsgNotFoundException = true
            );

            return Response::json($feedbackLog, 200);
        } catch (Exception $ex) {
            return Response::json(['error' => $ex->getMessage()], 400);
        }
    }
}
