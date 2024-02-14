<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserExamineeCollection extends ResourceCollection
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
            return [
                "id"=>  $item->user->id,
                "fixed_company_id"=>$item->user->fixed_company_id,
                "department_id"=> $item->department_id,
                "director_id"=> $item->director_id,
                "consultation_text"=> $item->consultation_text,
                "serial_number"=> $item->serial_number,
                "classification_id"=> $item->classifications->pluck('name'),
                "role"=> $item->user->role,
                "yearmm"=>  $item->yearmm,
                "password"=> $item->user->password,
                "status"=>  $item->status,
                "email"=> $item->user->email,
                "lastname"=> $item->lastname,
                "firstname"=>  $item->firstname,
                "lastname_katkana"=> $item->lastname_katakana,
                "firstname_katakana"=> $item->firstname_katakana,
                "gender"=> $item->gender,
                "birth_day"=> $item->birth_day,
                "employment_num" => $item->employment_num,
                "question_method"=> $item->question_method,
                "questionnaire_type"=> $item->questionnaire_type,
                "notification_type"=> $item->notification_type,
                "question_output_method"=> $item->notification_type,
                "language"=> $item->language,
                "employment_day"=> $item->employment_day,
                "job_status"=> $item->job_status,
                'result_view_flg' => $item->result_view_flg,
                'result_view_created_at' => $item->result_view_created_at,
                'high_stress_flg' => $item->high_stress_flg,
                'Interview_target_flg' => $item->Interview_target_flg,
                'Interview_request_flg' => $item->Interview_request_flg,
                'pdf_report_url' =>$item->pdf_report_url,
                'exam_complete_flg' => $item->exam_complete_flg
            ];
        });
    }
}
