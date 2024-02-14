<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use DB;

class CompanyStatusUpdateCommon extends Model
{
    public static function updateStatus($companyId, $status, $yearmm)
    {
        if (empty($yearmm)) {
            $company = Company::where('id', $companyId)->first();
        } else {
            $where = [['fixed_company_id', $companyId],['yearmm',$yearmm]];
            if ($status == 'ADMIN_ANSWERS_CSV_UPLOAD') {
                $where = [['fixed_company_id', $companyId],['yearmm', $yearmm], ['exam_end','<', date('Ymd')]];
            }
            $company = Company::where($where)->first();
        }
        try {
            if ($status == 'DEPARTMENT_CSV_UPLOAD' && $company->status_message == config('constants.CALLED_API.COMPANY_CSV_UPLOAD_STATUS_MESSAGE')) {
                $company->status = config('constants.CALLED_API.COMPANY_STATUS');
                $company->status_message = config('constants.CALLED_API.DEPARTMENT_CSV_UPLOAD_SATATUS_MESSAGE');
            } elseif ($status == 'DIRECTOR_CSV_UPLOAD' && $company->status_message == config('constants.CALLED_API.DEPARTMENT_CSV_UPLOAD_SATATUS_MESSAGE')) {
                $company->status = config('constants.CALLED_API.COMPANY_STATUS');
                $company->status_message = config('constants.CALLED_API.DIRECTOR_CSV_UPLOAD_STATUS_MESSAGE');
            } elseif ($status == 'CLASSIFICATION_CSV_UPLOAD' && $company->status_message == config('constants.CALLED_API.DIRECTOR_CSV_UPLOAD_STATUS_MESSAGE')) {
                $company->status = config('constants.CALLED_API.COMPANY_STATUS');
                $company->status_message = config('constants.CALLED_API.CLASSIFICATION_CSV_UPLOAD_STATUS_MESSAGE');
            } elseif ($status == 'USER_CSV_UPLOAD' && $company->status_message ==  config('constants.CALLED_API.CLASSIFICATION_CSV_UPLOAD_STATUS_MESSAGE')) {
                $company->status = config('constants.CALLED_API.COMPANY_STATUS');
                $company->status_message = config('constants.CALLED_API.USER_CSV_UPLOAD_STATUS_MESSAGE');
            } elseif ($status == 'MISMATCH' && $company->status_message ==  config('constants.CALLED_API.USER_CSV_UPLOAD_STATUS_MESSAGE')) {
                $company->status = config('constants.CALLED_API.COMPANY_STATUS');
                $company->status_message = config('constants.CALLED_API.MISSMATCH_FLAG_STATUS_MESSGAE');
            } elseif ($status == 'ADMIN_ANSWERS_CSV_UPLOAD') {
                $company->status = config('constants.CALLED_API.ADMIN_ANSWERS_UPDATE_STATUS');
                $company->status_message = config('constants.CALLED_API.ADMIN_ANSWERS_UPDATE_STATUS_MESSAGE');
            } elseif ($status == 'ADMIN_ANSWERS_CSV_UPLOAD_TARGET_FLG') {
                $company->status = config('constants.CALLED_API.ADMIN_ANSWERS_UPDATE_INTERVIEW_TARGET_FLG_STATUS');
                $company->status_message = config('constants.CALLED_API.ADMIN_ANSWERS_UPDATE_INTERVIEW_TARGET_FLG_STATUS_MESSAGE');
            } elseif ($status == 'CRONJOBTYPE2' && $company->status_message ==  config('constants.CALLED_API.MISSMATCH_FLAG_STATUS_MESSGAE')) {
                $company->status = config('constants.CALLED_API.CRON_JOB_TYPE_2_STATUS');
                $company->status_message = config('constants.CALLED_API.CRON_JOB_TYPE_2_STATUS_MESSAGE');
            } elseif ($status == 'CRONJOBTYPE3' && $company->status_message ==  config('constants.CALLED_API.CRON_JOB_TYPE_2_STATUS_MESSAGE')) {
                $company->status = config('constants.CALLED_API.CRON_JOB_TYPE_3_STATUS');
                $company->status_message = config('constants.CALLED_API.CRON_JOB_TYPE_3_STATUS_MESSAGE');
            } elseif ($status == 'ADMIN_ANSWERS_CSV_UPLOAD_TARGET_FLG_1') {
                $company->status = config('constants.CALLED_API.ADMIN_ANSWERS_UPDATE_INTERVIEW_TARGET_FLG_STATUS_1');
                $company->status_message = config('constants.CALLED_API.ADMIN_ANSWERS_UPDATE_INTERVIEW_TARGET_FLG_STATUS_1');
            } elseif ($status == 'COMPANY_PDF_UPLOAD') {
                $company->status = config('constants.CALLED_API.COMPANY_PDF_UPLOAD_STATUS');
                $company->status_message = config('constants.CALLED_API.COMPANY_PDF_UPLOAD_STATUS');
            } elseif ($status == 'SET_INTERVIEW_FLG') {
                $company->status = config('constants.CALLED_API.SET_INTERVIEW_FLG_STATUS');
                $company->status_message = config('constants.CALLED_API.SET_INTERVIEW_FLG_STATUS');
            }
           
            $er = $company->save();
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' =>'Something went wrong!, Please try again later.'], 400);
        }
        return $er;
    }
}
