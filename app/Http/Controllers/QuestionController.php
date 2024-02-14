<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use Illuminate\Support\Facades\Log;
use App\Models\Examinee;

class QuestionController extends Controller
{
    /**
      * Store a newly created Questions in db.
      *
      * @param  \Illuminate\Http\Request  $request
      * @return \Illuminate\Http\Response
      */
    public function store(Request $request, $my_user_id)
    {
        $array = $request->validate(Question::rules()->toArray());
        try {
            foreach ($array as $value) {
                foreach ($value as $key => $value1) {
                    $Question = new Question();
                    $Question->fill($value1);
                    $Question->save();
                }
            }
            return response()->json(['result' => true], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => trans('validation.custom.Something_went_wrong_Please_try_again')], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Question  $QuestionIds,  $myId
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $myId)
    {
        $QuestionIdArray =$request->questions_id_array;
        $Questions = Question::whereIn('id', $QuestionIdArray)->get();
        return response()->json($Questions, 200);
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Question  $QuestionIds,  $myId
     * @return \Illuminate\Http\Response
     */
    public function getQuestions(Request $request, $myId)
    {
        $Questions = [];
        $userRole = Examinee::where('user_id', $myId)->orderBy('yearmm', 'desc')->firstOrFail();
        if ($userRole->questionnaire_type == config('constants.QUESTIONNAIRE_TYPE.BJSQ') || $userRole->questionnaire_type == config('constants.QUESTIONNAIRE_TYPE.MIRROR')) {
            $Questions = Question::where('questionnaire_type', $userRole->questionnaire_type)->where('language', $userRole->language)->get();
        } else {
            $Questions = Question::where('language', $userRole->language)->get();
        }
        return response()->json($Questions, 200);
    }
}
