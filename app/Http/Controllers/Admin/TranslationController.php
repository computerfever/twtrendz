<?php

namespace Acelle\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;
use Acelle\Library\Tool;
use Acelle\Model\TranslationPhrase;

class TranslationController extends Controller
{
    public function phrases(Request $request)
    {
        return view('admin.translation.phrases');
    }

    public function phrasesSave(Request $request)
    {
        // done | auto save | done & write tạm thời call chung cái này
        foreach ($request->phrases as $key => $ja) {
            $phrase = TranslationPhrase::findByKey($key);
            $phrase->ja = $ja;
            $phrase->save();
        }

        // success
        return response()->json([
            'status' => 'success',
            'message' => trans('messages.translation.update.success'),
        ]);
    }

    public function filter(Request $request)
    {
        $query = TranslationPhrase::search($request->keyword);

        return $query;
    }

    public function phrasesList(Request $request)
    {
        $query = $this->filter($request);

        if (!empty($request->sort_order)) {
            $query = $query->orderBy($request->sort_order, $request->sort_direction);
        }

        $phrases = $query->paginate($request->per_page);

        return view('admin.translation.phrasesList', [
            'phrases' => $phrases,
        ]);
    }

    public function phrasesFinishWrite(Request $request)
    {
        if ($request->select_tool == 'all_items') {
            $query = $this->filter($request);
        } else {
            $query = TranslationPhrase::whereIn(
                'uid',
                is_array($request->uids) ? $request->uids : explode(',', $request->uids)
            );
        }

        // Get phrases
        $phrases = $query->get();

        // execute
        foreach ($phrases as $phrase) {
            // finishWrite
            // $phrase->finishWrite();
        }

        // response
        return response()->json([
            'status' => 'success',
            'message' => trans('messages.translation.finish_write.success'),
        ]);
    }

    public function phrasesWrite(Request $request, $uid)
    {
        $phrase = TranslationPhrase::findByUid($uid);

        // write
        // $phrase->write();

        // response
        return response()->json([
            'status' => 'success',
            'message' => trans('messages.translation.write.success'),
        ]);
    }
}
