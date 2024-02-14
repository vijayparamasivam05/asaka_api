<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;

class Examinee extends Model
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes;
    const UPDATED_AT = null;
    public $table = "examinee";
    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */
    protected $fillable = [
        'id',
        'user_id',
        'department_id',
        'director_id',
        'yearmm',
        'serial_number',
        'consultation_text',
        'firstname_katakana',
        'lastname_katakana',
        'firstname',
        'lastname',
        'status',
        'gender',
        'birth_day',
        'question_method',
        'questionnaire_type',
        'notification_type',
        'question_output_method',
        'language',
        'employment_day',
        'job_status',
        'result_view_flg',
        'result_view_created_at',
        'high_stress_flg',
        'Interview_target_flg',
        'Interview_request_flg',
        'pdf_report_url',
        'employment_num',
        'mismatch_flg',
        'exam_complete_flg'
    ];

    /**
    * The attributes that should be hidden for serialization.
    *
    * @var array
    */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Get the user that owns the examinee.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the user answers (one to one relation)
     */
    public function answers()
    {
        return $this->hasOne(Answer::class, 'examinee_id', 'id');
    }

    /**
     * Get the Director that owns the examinee.
     */
    public function director()
    {
        return $this->belongsTo(Director::class);
    }
   
    /**
    * Get the areas_questions associated with User (belong to many relation)
    */
    public function classifications()
    {
        return $this->belongsToMany(Classification::class, "classifications_examinee_relation", "examinee_id", "classification_id");
    }
    
    /**
     * Get the Department that owns the user.
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    /**
     * Get the Collection containing user validation rules
     *
     * @return object Collection
     */
    protected function rules($id=0)
    {
        return collect([
            // 'users.*.new.*.id' => 'unique:examinee,id,NULL,NULL,deleted_at,NULL',
            'users.*.new.*.department_id' => "required|string|exists:departments,id",
            'users.*.new.*.director_id' => "required|string|exists:directors,id",
            'users.*.new.*.yearmm' => "required|integer",
            'users.*.new.*.serial_number' => "required|integer",
            'users.*.new.*.status' => 'nullable|string',
            'users.*.new.*.firstname_katakana' => 'required|string',
            'users.*.new.*.lastname_katakana' => 'required|string',
            'users.*.new.*.firstname' => 'required|string',
            'users.*.new.*.lastname' => 'required|string',
            'users.*.new.*.gender' => ['required', Rule::in(['Not specified','Male','Female'])],
            'users.*.new.*.birth_day' => 'required|integer',
            'users.*.new.*.question_method' => ['required', Rule::in(['WEB', 'MS'])],
            'users.*.new.*.questionnaire_type' => ['required', Rule::in(['BJSQ', 'Mirror','Both'])],
            'users.*.new.*.notification_type' => ['nullable', Rule::in(['email', 'post'])],
            'users.*.new.*.question_output_method' => ['required', Rule::in(['email', 'post'])],
            'users.*.new.*.language' => ['required', Rule::in(['JA', 'EN'])],
            'users.*.new.*.employment_day' => 'nullable|integer',
            'users.*.new.*.job_status' => ['required', Rule::in(['currentjob', 'leave', 'retirement'])],
            'users.*.new.*.result_view_flg' => 'nullable|boolean',
            'users.*.new.*.result_view_created_at' => 'nullable|timestamp',
            'users.*.new.*.high_stress_flg' => 'nullable|boolean',
            'users.*.new.*.Interview_target_flg' => ['nullable', Rule::in([0,1,2])],
            'users.*.new.*.Interview_request_flg' => ['nullable', Rule::in([0,1,2])],
            'users.*.new.*.consultation_text' => 'required|string',
            'users.*.new.*.classification_id' => "nullable|array",
            'users.*.new.*.classification_id.*' => "nullable|string|exists:classifications,id",
            'users.*.new.*.pdf_report_url.*' => "nullable|string",
            'users.*.new.*.employment_num' => 'required|string',
            'users.*.new.*.exam_complete_flg' => 'nullable|boolean',

            // 'users.*.update.*.id' => "required|exists:examinee,id",
            'users.*.update.*.department_id' => "required|string|exists:departments,id",
            'users.*.update.*.director_id' => "required|string|exists:directors,id",
            'users.*.update.*.yearmm' => "required|integer",
            'users.*.update.*.serial_number' => "required|integer",
            'users.*.update.*.firstname_katakana' => 'required|string',
            'users.*.update.*.lastname_katakana' => 'required|string',
            'users.*.update.*.firstname' => 'required|string',
            'users.*.update.*.lastname' => 'required|string',
            'users.*.update.*.gender' => ['required', Rule::in(['Not specified','Male','Female'])],
            'users.*.update.*.birth_day' => 'required|integer',
            'users.*.update.*.question_method' => ['required', Rule::in(['WEB', 'MS'])],
            'users.*.update.*.questionnaire_type' => ['required', Rule::in(['BJSQ', 'Mirror','Both'])],
            'users.*.update.*.notification_type' => ['nullable', Rule::in(['email', 'post'])],
            'users.*.update.*.question_output_method' => ['required', Rule::in(['email', 'post'])],
            'users.*.update.*.language' => ['required', Rule::in(['JA', 'EN'])],
            'users.*.update.*.employment_day' => 'nullable|integer',
            'users.*.update.*.job_status' => ['required', Rule::in(['currentjob', 'leave', 'retirement'])],
            'users.*.update.*.result_view_flg' => 'nullable|boolean',
            'users.*.update.*.result_view_created_at' => 'nullable|timestamp',
            'users.*.update.*.high_stress_flg' => 'nullable|boolean',
            'users.*.update.*.Interview_target_flg' =>  ['nullable', Rule::in([0,1,2])],
            'users.*.update.*.Interview_request_flg' => ['nullable', Rule::in([0,1,2])],
            'users.*.update.*.consultation_text' => 'required|string',
            'users.*.update.*.classification_id' => "nullable|array",
            'users.*.update.*.classification_id.*' => "nullable|string|exists:classifications,id",
            'users.*.update.*.pdf_report_url.*' => "nullable|string",
            'users.*.update.*.employment_num' => 'required|string',
            'users.*.update.*.exam_complete_flg' => 'nullable|boolean',

            //delete user rules
            'users.*.delete.*.yearmm' => "required|integer",
        ]);
    }
}
