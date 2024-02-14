<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use DB;
use Carbon\Carbon;

class ExamineeStatusCommon extends Model
{
    public static function updateExamineeStatus($examineeId, $status, $qMethod)
    {
        if ($qMethod == 'WEB') {
            $where = [['id', $examineeId],['question_method',config('constants.QUESTION_METHOD.WEB')]];
        } else {
            $where = [['id', $examineeId],['question_method',config('constants.QUESTION_METHOD.MS')]];
        }
        try {
            $examinee = examinee::where($where)->first();

            if ($status == 'USER_CSV_UPLOAD') {
                $examinee->status = config('constants.CALLED_API.EXAMINEE_STATUS');
            } elseif ($status == 'LOGIN_EXAMINEE') {
                $examinee->status = config('constants.CALLED_API.EXAMINEE_LOGIN_SUCCESS');
            } elseif ($status == 'CRONJOBTYPE4' && $examinee->status == config('constants.CALLED_API.EXAMINEE_LOGIN_SUCCESS')) {
                $examinee->status = config('constants.CALLED_API.EXAMINEE_CRONJOB_TYPE4_STATUS');
            } elseif ($status == 'USER_CSV_UPLOAD_MS') {
                $examinee->status = config('constants.CALLED_API.EXAMINEE_USER_CSV_UPLOAD_MS');
            } elseif ($status == 'CRONJOBTYPE5') {
                $examinee->status = config('constants.CALLED_API.EXAMINEE_CRONJOB_TYPE5_STATUS');
            } elseif ($status == 'MARKSHEET_INVALID_FLG_TRUE') {
                $examinee->status = config('constants.CALLED_API.EXAMINEE_INVALID_FLG_TRUE_MS_STATUS');
            } elseif ($status == 'MARKSHEET_INVALID_FLG_FALSE') {
                $examinee->status = config('constants.CALLED_API.EXAMINEE_INVALID_FLG_FALSE_MS_STATUS');
            }
           

            $er = $examinee->save();
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' =>'Something went wrong!, Please try again later.'], 400);
        }
        return $er;
    }
}
