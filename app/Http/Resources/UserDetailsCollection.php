<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Models\Company;
use App\Models\Examinee;

class UserDetailsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($item) use ($request) {
            $company = Company::where('fixed_company_id', $item->fixed_company_id)->orderBy('yearmm', 'DESC')->firstOrFail();
            $examinee = Examinee::with(['classifications'])->where([['examinee.user_id', $item->id],['examinee.yearmm', $item->yearmm]])->first();

            $classification = $examinee->classifications->map(function ($value, $key) {
                return ['id' => $value->id, 'name' => $value->name];
            });
            
            return [
                "id"=>  $item->id,
                "examinee_id" => $examinee->id,
                "fixed_company_id"=> $item->fixed_company_id,
                "company_name" => $company->name,
                "department_id"=> $examinee->department_id,
                "department_name"=> $examinee->department->name,
                "director_id"=> $examinee->director_id,
                "director_name"=> $examinee->director->name,
                "consultation_text"=> $examinee->consultation_text,
                "serial_number"=> $examinee->serial_number,
                "classification"=> $classification,
                "role"=> $item->role,
                "yearmm"=>  $examinee->yearmm,
                "password"=> $item->password,
                "status"=>  $examinee->status,
                "email"=>  $item->email,
                "lastname"=> $examinee->lastname,
                "firstname"=>  $examinee->firstname,
                "lastname_katakana"=> $examinee->lastname_katakana,
                "firstname_katakana"=> $examinee->firstname_katakana,
                "gender"=> $examinee->gender,
                "birth_day"=> $examinee->birth_day,
                "employment_num" => $examinee->employment_num,
                "question_method"=> $examinee->question_method,
                "questionnaire_type"=> $examinee->questionnaire_type,
                "notification_type"=> $examinee->notification_type,
                "question_output_method"=> $examinee->question_output_method,
                "language"=> $examinee->language,
                "employment_day"=> $examinee->employment_day,
                "job_status"=> $examinee->job_status,
                'result_view_flg' => $examinee->result_view_flg,
                'result_view_created_at' => $examinee->result_view_created_at,
                'high_stress_flg' => $examinee->high_stress_flg,
                'Interview_target_flg' => $examinee->Interview_target_flg,
                'Interview_request_flg' => $examinee->Interview_request_flg,
                'pdf_report_url' =>$examinee->pdf_report_url,
                'exam_complete_flg' => $examinee->exam_complete_flg
            ];
        });
    }
}
