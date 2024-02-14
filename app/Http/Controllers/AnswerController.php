<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use Illuminate\Http\Request;
use AppHelper;
use App\Models\User;
use App\Models\Examinee;
use DB;
use Illuminate\Support\Facades\Log;
use App\Models\CompanyStatusUpdateCommon;
use App\Models\ExamineeStatusCommon;

class AnswerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
    * Store a newly created resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    public function store(Request $request, $myID)
    {
        $array = $request->validate(Answer::rules()->except('answers.*.examinee_id')->toArray());

        try {
            foreach ($array as $value) {
                foreach ($value as $key => $value1) {
                    $examinee = Examinee::where('user_id', $value1['user_id'])->orderBy('yearmm', 'DESC')->first();
                    $value1['examinee_id'] = $examinee->id;
                    $response = self::isInvalidFlag($value1);
                    if (!empty($response)) {
                        $answer = new Answer();
                        $answer->fill(array_merge($value1, ['examinee_id' => $examinee->id, 'invalid_flg' => $response['invalidflg']]));
                        $res = $answer->save();
                        if ($res) {
                            if ($answer->invalid_flg == true) {
                                $result1 = ExamineeStatusCommon::updateExamineeStatus($answer->examinee_id, 'MARKSHEET_INVALID_FLG_TRUE', 'MS');
                            } else {
                                $result2 = ExamineeStatusCommon::updateExamineeStatus($answer->examinee_id, 'MARKSHEET_INVALID_FLG_FALSE', 'MS');
                            }
                        }
                        self::calculateResultForIndividual($response['qType']);
                    }
                }
            }
            return response()->json(['result' => true], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => trans('validation.custom.Something_went_wrong_Please_try_again')], 400);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function isInvalidFlag($value1)
    {
        try {
            $invalidflg = false;
            $userQueType = Examinee::where('id', $value1['examinee_id'])->firstOrFail();
            if ($userQueType->questionnaire_type == config('constants.QUESTIONNAIRE_TYPE.MIRROR') || $userQueType->questionnaire_type == config('constants.QUESTIONNAIRE_TYPE.BOTH')) {
                for ($i = 1; $i <= 37; $i++) {
                    $var = "mirror_{$i}";
                    if (!isset($value1[$var]) || $value1[$var] == null) {
                        $invalidflg = true;
                    }
                    unset($var);
                }
            }
            if ($userQueType->questionnaire_type == config('constants.QUESTIONNAIRE_TYPE.BJSQ') || $userQueType->questionnaire_type == config('constants.QUESTIONNAIRE_TYPE.BOTH')) {
                for ($i = 1; $i <= 17; $i++) {
                    $var = "bjsq_a_{$i}";
                    if (!isset($value1[$var]) || $value1[$var] == null) {
                        $invalidflg = true;
                    }
                    unset($var);
                }
                for ($i = 1; $i <= 29; $i++) {
                    $var = "bjsq_b_{$i}";
                    if (!isset($value1[$var]) || $value1[$var] == null) {
                        $invalidflg = true;
                    }
                    unset($var);
                }
                for ($i = 1; $i <= 9; $i++) {
                    $var = "bjsq_c_{$i}";
                    if (!isset($value1[$var]) || $value1[$var] == null) {
                        $invalidflg = true;
                    }
                    unset($var);
                }
                for ($i = 1; $i <= 2; $i++) {
                    $var = "bjsq_d_{$i}";
                    if (!isset($value1[$var]) || $value1[$var] == null) {
                        $invalidflg = true;
                    }
                    unset($var);
                }
            }
            return ["invalidflg" => $invalidflg, 'qType' => $userQueType];
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => trans('validation.custom.Something_went_wrong_Please_try_again')], 400);
        }
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeMarksData(Request $request, $myID)
    {
        $data =  $request->all();
        try {
            $examinee = Examinee::where('user_id', $myID)->orderBy('yearmm', 'DESC')->firstOrFail();
            $awsId = self::saveData($data, $examinee->id);
            if ($awsId) {
                $res = self::calculateResultForIndividual($examinee);
            }
            
            if ($res) {
                $examinee->exam_complete_flg = 1;
                if ($examinee->question_method == config('constants.QUESTION_METHOD.WEB')) {
                    $examinee->status = config('constants.CALLED_API.EXAMINEE_MARKSHEET_STATUS');
                }
                $examinee->update();
                return response()->json(['result' => true], 200);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => trans('validation.custom.Something_went_wrong_Please_try_again')], 400);
        }
    }
    public function saveData($value, $examnieeId)
    {
        try {
            $id = AppHelper::randomIdHelper();
            $answer = Answer::where('examinee_id', $examnieeId)->first();
            if (empty($answer)) {
                $answer = new Answer();
                $answer->fill(array_merge($value, ['id' => 'A' . $id, 'examinee_id' => $examnieeId]));
                $result = $answer->save();
            } else {
                $answer->fill($value);
                $result = $answer->save();
            }

            return $result;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Answer  $my_user_id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $my_user_id)
    {
        $validated = $request->validate([
            'examinee_id' => 'required'
        ]);
        $exist = Examinee::findOrFail($request->examinee_id);
        $fields = ['answers.examinee_id', 'examinee.lastname', 'examinee.firstname', 'examinee.lastname_katakana', 'examinee.firstname_katakana', 'examinee.birth_day', 'examinee.gender', 'examinee.department_id', 'examinee.director_id', 'examinee.Interview_target_flg', 'examinee.question_method', 'examinee.Interview_request_flg', 'examinee.result_view_flg', 'examinee.result_view_created_at', 'answers.*'];
        $response = Answer::select($fields)->rightJoin('examinee', 'examinee.id', 'answers.examinee_id')->whereIn('answers.examinee_id', $request->examinee_id)->get();
        return response()->json($response, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Http\Response
     */
    public function edit(Answer $answer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $array = $request->validate(Answer::rulesAdminUpdate()->toArray());
        try {
            foreach ($array as $key => $value) {
                foreach ($value as $key1 => $value1) {
                    // DB::transaction(function () use ($value1, &$answer) {
                    if (isset($value1['update']) && $value1['update']) {
                        foreach ($value1['update'] as $updateValue) {
                            $answer = Answer::where('id', $updateValue['id'])->firstOrFail();
                            $response = self::isInvalidFlag($updateValue);
                            $answer->fill(array_merge($updateValue, ['invalid_flg' => $response['invalidflg']]));
                            if ($answer->save()) {
                                $examinee = Examinee::where('id', $updateValue['examinee_id'])->firstOrFail();
                                if ($updateValue['Interview_target_flg'] != 0) {
                                    unset($updateValue['status']);
                                    $updateValue = array_merge($updateValue, ['status' => config('constants.CALLED_API.EXAMINEE_ANSWER_CSV_UPLOAD_STATUS')]);
                                } elseif ($examinee->Interview_target_flg !=0) {
                                    unset($updateValue['status']);
                                    $updateValue = array_merge($updateValue, ['status' => config('constants.CALLED_API.EXAMINEE_ANSWER_CSV_UPLOAD_STATUS')]);
                                }
                                unset($updateValue['id']);
                                $examinee->fill($updateValue);
                                $examinee->update();
                                $res = self::calculateResultForIndividual($response['qType']);
                                $user_fixed_company_id = $examinee->user->fixed_company_id;
                                $yearmm = $examinee->yearmm;
                                $invalid_flg_Count = User::leftJoin('examinee', 'examinee.user_id', '=', 'users.id')->leftjoin('answers', 'answers.examinee_id', '=', 'examinee.id')->where([['users.fixed_company_id', $user_fixed_company_id],["examinee.yearmm", $yearmm],['answers.invalid_flg', 1]])->count();
                                if ($res && $invalid_flg_Count == 0) {
                                    $result = CompanyStatusUpdateCommon::updateStatus($user_fixed_company_id, 'ADMIN_ANSWERS_CSV_UPLOAD', $examinee->yearmm);
                                }
                                $interview_target_flg_count = User::leftJoin('examinee', 'examinee.user_id', '=', 'users.id')->where([['users.fixed_company_id', $user_fixed_company_id],["examinee.yearmm", $yearmm],['examinee.Interview_target_flg', '=', '0']])->count();
                                if ($res && $interview_target_flg_count == 0) {
                                    $result_flg = CompanyStatusUpdateCommon::updateStatus($user_fixed_company_id, 'ADMIN_ANSWERS_CSV_UPLOAD_TARGET_FLG', $examinee->yearmm);
                                } elseif ($res && $interview_target_flg_count > 0) {
                                    $result_flg1 = CompanyStatusUpdateCommon::updateStatus($user_fixed_company_id, 'ADMIN_ANSWERS_CSV_UPLOAD_TARGET_FLG_1', $examinee->yearmm);
                                }
                            }
                        }
                    }
                    if (isset($value1['delete']) && $value1['delete']) {
                        foreach ($value1['delete'] as $deleteValue) {
                            $answers = Answer::where('id', $deleteValue['id'])->delete();
                            $examinee = Examinee::where('id', $deleteValue['examinee_id'])->first();
                            $user_fixed_company_id = $examinee->user->fixed_company_id;
                            $invalid_flg_Count = User::where('fixed_company_id', $user_fixed_company_id)->leftJoin('examinee', 'examinee.user_id', '=', 'users.id')->leftjoin('answers', 'answers.examinee_id', '=', 'examinee.id')->where([['examinee.yearmm',$examinee->yearmm],['answers.invalid_flg',1]])->count();
                            if ($answers && $invalid_flg_Count == 0) {
                                $result = CompanyStatusUpdateCommon::updateStatus($user_fixed_company_id, 'ADMIN_ANSWERS_CSV_UPLOAD', $examinee->yearmm);
                            }
                            $interview_target_flg_count = User::leftJoin('examinee', 'examinee.user_id', '=', 'users.id')->where([['users.fixed_company_id', $user_fixed_company_id],["examinee.yearmm", $yearmm],['examinee.Interview_target_flg', '=', '0']])->count();
                            if ($res && $interview_target_flg_count == 0) {
                                $result_flg = CompanyStatusUpdateCommon::updateStatus($user_fixed_company_id, 'ADMIN_ANSWERS_CSV_UPLOAD_TARGET_FLG', $examinee->yearmm);
                            } elseif ($res && $interview_target_flg_count > 0) {
                                $result_flg1 = CompanyStatusUpdateCommon::updateStatus($user_fixed_company_id, 'ADMIN_ANSWERS_CSV_UPLOAD_TARGET_FLG_1', $examinee->yearmm);
                            }
                        }
                    }
                    // });
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => $e->getMessage()], 400);
        }
        return response()->json(['result' => true], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Answer $answer)
    {
        //
    }

    /**
     * Calculate the results using previously registered values from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Answer  $answer
     * @return \Illuminate\Http\Response
     */
    public function calculateResultAPI($myID)
    {
        $examinee = Examinee::where('user_id', $myID)->orderBy('yearmm', 'DESC')->firstOrFail();
        $res = self::calculateResultForIndividual($examinee);
        if ($res) {
            return response()->json(['result' => true], 200);
        }
    }

    public function calculateResultForIndividual($examinee)
    {
        try {
            // personal_cal_1
            $val = 15 - ($examinee->answers->bjsq_a_1 + $examinee->answers->bjsq_a_2 + $examinee->answers->bjsq_a_3);
            if ($examinee->gender == "Male") {
                $personal_cal_1 = ($val >= 12) ? 1 : (($val == 11 || $val == 10) ? 2 : (($val == 9 || $val == 8) ? 3 : (($val == 7 || $val == 6) ? 4 : 5)));
            } else {
                $personal_cal_1 = ($val >= 12) ? 1 : (($val == 11 || $val == 10) ? 2 : (($val == 9 || $val == 8 || $val == 7) ? 3 : (($val == 6 || $val == 5) ? 4 : 5)));
            }
            unset($val);

            // personal_cal_2
            $val = 15 - ($examinee->answers->bjsq_a_4 + $examinee->answers->bjsq_a_5 + $examinee->answers->bjsq_a_6);
            if ($examinee->gender == "Male") {
                $personal_cal_2 = (($val >= 12) ? 1 : (($val == 11 || $val == 10) ? 2 : (($val == 9 || $val == 8) ? 3 : (($val == 7 || $val == 6) ? 4 : 5))));
            } else {
                $personal_cal_2 = ($val >= 11) ? 1 : (($val == 10 || $val == 9) ? 2 : (($val == 8 || $val == 7) ? 3 : (($val == 6 || $val == 5) ? 4 : 5)));
            }
            unset($val);

            // personal_cal_3
            $val = 5 - $examinee->answers->bjsq_a_7;
            $personal_cal_3 = ($val == 4) ? 1 : (($val == 3) ? 2 : (($val == 2) ? 3 : 4));
            unset($val);

            // personal_cal_4
            $val = 10 - ($examinee->answers->bjsq_a_12 + $examinee->answers->bjsq_a_13) + $examinee->answers->bjsq_a_14;
            $personal_cal_4 = ($val > 9) ? 1 : (($val > 7) ? 2 : (($val > 5) ? 3 : (($val > 3) ? 4 : 5)));
            unset($val);

            // personal_cal_5
            $val = 5 - $examinee->answers->bjsq_a_15;
            $personal_cal_5 = ($val == 4) ? 1 : (($val == 3) ? 2 : (($val == 2) ? 3 : 4));
            unset($val);

            // personal_cal_6
            $val = 15 - ($examinee->answers->bjsq_a_8 + $examinee->answers->bjsq_a_9 + $examinee->answers->bjsq_a_10);
            if ($examinee->gender == "Male") {
                $personal_cal_6 = ($val == 3 || $val == 4) ? 1 : (($val == 5 || $val == 6) ? 2 : (($val == 7 || $val == 8) ? 3 : (($val == 9 || $val == 10) ? 4 : 5)));
            } else {
                $personal_cal_6 = ($val == 3) ? 1 : (($val == 4 || $val == 5) ? 2 : (($val == 6 || $val == 7 || $val == 8) ? 3 : (($val == 9 || $val == 10) ? 4 : 5)));
            }
            unset($val);

            // personal_cal_7
            $personal_cal_7 = $examinee->answers->bjsq_a_11;

            // personal_cal_8
            $val = 5 - $examinee->answers->bjsq_a_16;
            $personal_cal_8 = ($val == 4) ? 5 : $val;
            unset($val);

            // personal_cal_9
            $val = 5 - $examinee->answers->bjsq_a_17;
            $personal_cal_9 = ($val == 4) ? 5 : $val;
            unset($val);

            // personal_cal_10
            $val = $examinee->answers->bjsq_b_1 + $examinee->answers->bjsq_b_2 + $examinee->answers->bjsq_b_3;
            $personal_cal_10 = ($val >= 10) ? 5 : (($val == 9 || $val == 8) ? 4 : (($val == 7 || $val == 6) ? 3 : (($val == 5 || $val == 4) ? 2 : 1)));
            unset($val);

            // personal_cal_11
            $val = $examinee->answers->bjsq_b_4 + $examinee->answers->bjsq_b_5 + $examinee->answers->bjsq_b_6;
            if ($examinee->gender == "Male") {
                $personal_cal_11 = ($val == 12 || $val == 11 || $val == 10) ? 1 : (($val == 9 || $val == 8) ? 2 : (($val == 7 || $val == 6) ? 3 : (($val == 5 || $val == 4) ? 4 : 5)));
            } else {
                $personal_cal_11 = ($val >= 11) ? 1 : (($val == 9 || $val == 10) ? 2 : (($val == 8 || $val == 7 || $val == 6) ? 3 : (($val == 5 || $val == 4) ? 4 : 5)));
            }
            unset($val);

            // personal_cal_12
            $val = $examinee->answers->bjsq_b_7 + $examinee->answers->bjsq_b_8 + $examinee->answers->bjsq_b_9;
            if ($examinee->gender == "Male") {
                $personal_cal_12 = ($val == 12 || $val == 11) ? 1 : (($val == 10 || $val == 9 || $val == 8) ? 2 : (($val == 7 || $val == 6 || $val == 5) ? 3 : (($val == 4) ? 4 : 5)));
            } else {
                $personal_cal_12 = ($val == 12) ? 1 : (($val == 11 || $val == 10 || $val == 9) ? 2 : (($val == 8 || $val == 7 || $val == 6) ? 3 : (($val == 5 || $val == 4) ? 4 : 5)));
            }
            unset($val);

            // personal_cal_13
            $val = $examinee->answers->bjsq_b_10 + $examinee->answers->bjsq_b_11 + $examinee->answers->bjsq_b_12;
            if ($examinee->gender == "Male") {
                $personal_cal_13 = ($val == 12 || $val == 11 || $val == 10) ? 1 : (($val == 9 || $val == 8) ? 2 : (($val == 7 || $val == 6 || $val == 5) ? 3 : (($val == 4) ? 4 : 5)));
            } else {
                $personal_cal_13 = ($val >= 11) ? 1 : (($val == 10 || $val == 9 || $val == 8) ? 2 : (($val == 7 || $val == 6 || $val == 5) ? 3 : (($val == 4) ? 4 : 5)));
            }
            unset($val);

            // personal_cal_14
            $val = $examinee->answers->bjsq_b_13 + $examinee->answers->bjsq_b_14 + $examinee->answers->bjsq_b_15 + $examinee->answers->bjsq_b_16 + $examinee->answers->bjsq_b_17 + $examinee->answers->bjsq_b_18;
            if ($examinee->gender == "Male") {
                $personal_cal_14 = ($val > 16) ? 1 : (($val > 12) ? 2 : (($val > 8) ? 3 : (($val > 6) ? 4 : 5)));
            } else {
                $personal_cal_14 = ($val > 17) ? 1 : (($val > 12) ? 2 : (($val > 8) ? 3 : (($val > 6) ? 4 : 5)));
            }
            unset($val);

            // personal_cal_15
            $val = $examinee->answers->bjsq_b_19 + $examinee->answers->bjsq_b_20 + $examinee->answers->bjsq_b_21 + $examinee->answers->bjsq_b_22 + $examinee->answers->bjsq_b_23 + $examinee->answers->bjsq_b_24 + $examinee->answers->bjsq_b_25 + $examinee->answers->bjsq_b_26 + $examinee->answers->bjsq_b_27 + $examinee->answers->bjsq_b_28 + $examinee->answers->bjsq_b_29;
            if ($examinee->gender == "Male") {
                $personal_cal_15 = ($val > 26) ? 1 : (($val > 21) ? 2 : (($val > 15) ? 3 : (($val > 11) ? 4 : 5)));
            } else {
                $personal_cal_15 = ($val > 29) ? 1 : (($val > 23) ? 2 : (($val > 17) ? 3 : (($val > 13) ? 4 : 5)));
            }
            unset($val);

            // personal_cal_16
            $val = 15 - ($examinee->answers->bjsq_c_1 + $examinee->answers->bjsq_c_4  +  $examinee->answers->bjsq_c_7);
            if ($examinee->gender == "Male") {
                $personal_cal_16 = ($val == 3 || $val == 4) ? 1 : (($val == 5 || $val == 6) ? 2 : (($val == 7 || $val == 8) ? 3 : (($val == 9 || $val == 10) ? 4 : 5)));
            } else {
                $personal_cal_16 = ($val == 3) ? 1 : (($val == 4 || $val == 5) ? 2 : (($val == 6 || $val == 7) ? 3 : (($val == 8 || $val == 9) ? 4 : 5)));
            }
            unset($val);

            // personal_cal_17
            $val = 15 - ($examinee->answers->bjsq_c_2 + $examinee->answers->bjsq_c_5 + $examinee->answers->bjsq_c_8);
            $personal_cal_17 = ($val <= 5) ? 1 : (($val == 6 || $val == 7) ? 2 : (($val == 8 || $val == 9) ? 3 : (($val == 10 || $val == 11) ? 4 : 5)));
            unset($val);

            // personal_cal_18
            $val = 15 - ($examinee->answers->bjsq_c_3 + $examinee->answers->bjsq_c_6 + $examinee->answers->bjsq_c_9);
            $personal_cal_18 = ($val <= 6) ? 1 : (($val == 7 || $val == 8) ? 2 : (($val == 9) ? 3 : (($val == 10 || $val == 11) ? 4 : 5)));
            unset($val);

            // personal_cal_19
            $val = 10 - ($examinee->answers->bjsq_d_1 + $examinee->answers->bjsq_d_2);
            $personal_cal_19 = ($val <= 3) ? 1 : (($val == 4) ? 2 : (($val == 5 || $val == 6) ? 3 : (($val == 7) ? 4 : 5)));
            unset($val);

            // raw_stress_factor
            $raw_stress_factor = $personal_cal_1 + $personal_cal_2 + $personal_cal_3 + $personal_cal_4 + $personal_cal_5 + $personal_cal_6 + $personal_cal_7 + $personal_cal_8;

            // raw_stress_response
            $raw_stress_response = $personal_cal_9 + $personal_cal_10 + $personal_cal_11 + $personal_cal_12 + $personal_cal_13 + $personal_cal_14 + $personal_cal_15;

            // raw_support_factor
            $raw_support_factor = $personal_cal_16 + $personal_cal_17 + $personal_cal_18;

            // total_stress_factor
            $total_stress_factor = 0;
            for ($i = 1; $i <= 17; $i++) {
                $var = "bjsq_a_" . $i;
                $total_stress_factor = $total_stress_factor + $examinee->answers->$var;
            }

            // total_stress_response
            $total_stress_response = 0;
            for ($i = 1; $i <= 29; $i++) {
                $var = "bjsq_b_" . $i;
                $total_stress_response = $total_stress_factor + $examinee->answers->$var;
            }

            // total_support_factor
            $total_support_factor = 0;
            for ($i = 1; $i <= 9; $i++) {
                $var = "bjsq_c_" . $i;
                $total_support_factor = $total_support_factor + $examinee->answers->$var;
            }
            // stressor
            $stressor = ($personal_cal_1 == 1) ? 1 : 0;
            $stressor .= ($personal_cal_2 == 1) ? 1 : 0;
            $stressor .= ($personal_cal_3 == 1) ? 1 : 0;
            $stressor .= ($personal_cal_4 == 1) ? 1 : 0;
            $stressor .= ($personal_cal_6 == 1) ? 1 : 0;
            // stress_response
            $stress_response = ($personal_cal_10 == 1) ? 1 : 0;
            $stress_response .= ($personal_cal_11 == 1) ? 1 : 0;
            $stress_response .= ($personal_cal_12 == 1) ? 1 : 0;
            $stress_response .= ($personal_cal_13 == 1) ? 1 : 0;
            $stress_response .= ($personal_cal_14 == 1) ? 1 : 0;
            $stress_response .= ($personal_cal_15 == 1) ? 1 : 0;
            // stressor_stress_response
            $stressor_stress_response = (str_contains($stressor, "1") && str_contains($stress_response, "1")) ? 4 : ((str_contains($stressor, "1") && !str_contains($stress_response, "1")) ? 3 : ((!str_contains($stressor, "1") && str_contains($stress_response, "1")) ? 2 : 1));
            // high_stress_flg
            $high_stress_flg = (($total_stress_response >= 77) || ((($total_stress_factor) + ($total_support_factor) >= 76) && ($total_stress_response >= 63)) || ($raw_stress_response <= 12) || ((($raw_stress_factor) + ($raw_support_factor) <= 26) && ($raw_stress_response <= 17))) ? true : false;
            // judgment
            $judgment = ($high_stress_flg) ? 5 : $stressor_stress_response;
            $oneNull = false;
            $allNull = 0;
            for ($i = 1; $i <= 17; $i++) {
                $var = "bjsq_a_" . $i;
                if ($examinee->answers->$var == null) {
                    $oneNull = true;
                } else {
                    $allNull++;
                }
            }
            if (!$oneNull) {
                for ($i = 1; $i <= 29; $i++) {
                    $var = "bjsq_b_" . $i;
                    if ($examinee->answers->$var == null) {
                        $oneNull = true;
                    } else {
                        $allNull++;
                    }
                }
                if (!$oneNull) {
                    for ($i = 1; $i <= 9; $i++) {
                        $var = "bjsq_c_" . $i;
                        if ($examinee->answers->$var == null) {
                            $oneNull = true;
                        } else {
                            $allNull++;
                        }
                    }
                    if (!$oneNull) {
                        $oneNull = ($examinee->answers->bjsq_d_1 == null || $examinee->answers->bjsq_d_2 == null) ? true : false;
                        if ($examinee->answers->bjsq_d_1 != null && $examinee->answers->bjsq_d_2 != null) {
                            $allNull++;
                        }
                    }
                }
            }
            if ($oneNull) {
                $judgment = 9;
            } elseif ($allNull == 0) {
                $judgment = 0;
            }
            // weather_mark
            $weather_mark = ($judgment == 1) ? "晴" : (($judgment == 2 || $judgment == 3) ? "曇" : (($judgment == 4) ? "雨" : (($judgment == 5) ? "雷" : (($judgment == 9) ? "?" : null))));

            $fields = [
                'personal_cal_1' => $personal_cal_1,
                'personal_cal_2' => $personal_cal_2,
                'personal_cal_3' => $personal_cal_3,
                'personal_cal_4' => $personal_cal_4,
                'personal_cal_5' => $personal_cal_5,
                'personal_cal_6' => $personal_cal_6,
                'personal_cal_7' => $personal_cal_7,
                'personal_cal_8' => $personal_cal_8,
                'personal_cal_9' => $personal_cal_9,
                'personal_cal_10' => $personal_cal_10,
                'personal_cal_11' => $personal_cal_11,
                'personal_cal_12' => $personal_cal_12,
                'personal_cal_13' => $personal_cal_13,
                'personal_cal_14' => $personal_cal_14,
                'personal_cal_15' => $personal_cal_15,
                'personal_cal_16' => $personal_cal_16,
                'personal_cal_17' => $personal_cal_17,
                'personal_cal_18' => $personal_cal_18,
                'personal_cal_19' => $personal_cal_19,
                'raw_stress_factor' => $raw_stress_factor,
                'raw_stress_response' => $raw_stress_response,
                'raw_support_factor' => $raw_support_factor,
                'total_stress_factor' => $total_stress_factor,
                'total_stress_response' => $total_stress_response,
                'total_support_factor' => $total_support_factor,
                'stressor' => $stressor,
                'stress_response' => $stress_response,
                'stressor_stress_response' => $stressor_stress_response,
                'judgment' => $judgment,
                'weather_mark' => $weather_mark,
                'high_stress_flg' => $high_stress_flg
            ];
            $answer = Answer::where('examinee_id', $examinee->id)->first();
            $answer->fill($fields);
            $result = $answer->update();
            $examinee->high_stress_flg == $answer->high_stress_flg;
            $examinee->save();
            if ($answer->high_stress_flg == true) {
                $examinee->status = config('constants.CALLED_API.EXAMINEE_CALCULATION_PERSON_STATUS');
                $examinee->save();
            }
            return response()->json(['result' => true], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => trans('validation.custom.Something_went_wrong_Please_try_again')], 400);
        }
    }
}
