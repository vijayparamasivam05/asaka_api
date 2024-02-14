<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes;
    const UPDATED_AT = null;
    protected $primaryKey = 'id';
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'fixed_company_id',
        'yearmm',
        'name',
        'status',
        'status_message',
        'industry_standard',
        'industry_ascc',
        'employees_num',
        'exam_start',
        'exam_end',
        'criteria_type',
        'high_stress_1',
        'high_stress_2',
        'high_stress_3',
        'high_stress_4',
        'high_stress_5',
        'high_stress_6',
        'name_end',
        'answer_end',
        'result_day',
        'guidance_subject',
        'guidance_email',
        'remind_subject',
        'remind_email',
        'result_subject',
        'result_email',
        'result_remind_subject',
        'result_remind_email',
        'excel_report_url',
        'pdf_report_url',
        'schedule_excel_url',
        'generate_excel_report_url'
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
     * Get the departments associated with company (one to many relation)
     */
    public function departments()
    {
        return $this->hasMany(Department::class, 'company_id', 'id');
    }

    /**
     * Get the user that owns the company.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
    /**
     * Get the Collection containing user validation rules
     *
     * @return object Collection
     */
    protected function rules($id=0)
    {
        return collect([
            'companies' => 'required|array',
            'companies.*.new.*.id' => 'required|string|unique:companies,id',
            'companies.*.new.*.fixed_company_id' =>  'required|string',
            'companies.*.new.*.yearmm' => 'required|integer',
            'companies.*.new.*.name' => 'required|string',
            'companies.*.new.*.industry_standard' => 'required|integer',
            'companies.*.new.*.industry_ascc' => 'required|integer',
            'companies.*.new.*.employees_num' => 'required|integer',
            'companies.*.new.*.exam_start' => 'required|integer',
            'companies.*.new.*.exam_end' => 'required|integer',
            'companies.*.new.*.status' => 'nullable|string',
            'companies.*.new.*.status_message' => 'nullable|string',
            'companies.*.new.*.criteria_type' => ['required', Rule::in(['素点', '合計'])],
            'companies.*.new.*.high_stress_1' => 'nullable|integer',
            'companies.*.new.*.high_stress_2' => 'nullable|integer',
            'companies.*.new.*.high_stress_3' => 'nullable|integer',
            'companies.*.new.*.high_stress_4' => 'nullable|integer',
            'companies.*.new.*.high_stress_5' => 'nullable|integer',
            'companies.*.new.*.high_stress_6' => 'nullable|integer',
            'companies.*.new.*.name_end' => 'required|integer',
            'companies.*.new.*.answer_end' => 'required|integer',
            'companies.*.new.*.result_day' => 'required|integer',
            'companies.*.new.*.guidance_subject' => 'required|string',
            'companies.*.new.*.guidance_email' => 'required|string',
            'companies.*.new.*.remind_subject' => 'required|string',
            'companies.*.new.*.remind_email' => 'required|string',
            'companies.*.new.*.result_subject' => 'required|string',
            'companies.*.new.*.result_email' => 'required|string',
            'companies.*.new.*.result_remind_subject' => 'required|string',
            'companies.*.new.*.result_remind_email' => 'required|string',
            'companies.*.new.*.excel_report_url' => 'nullable|string',
            'companies.*.new.*.pdf_report_url' => 'nullable|string',
            'companies.*.new.*.schedule_excel_url' => 'nullable|string',
            'companies.*.new.*.generate_excel_report_url' => 'nullable|string'
        ]);
    }


    /*
        Name: AGTECHPRO - Asaka API Portal
        Date: 04/02/2022
        Desc: ASAKA-81- Excel write from Template read from local storage.
        Note: Previous used code committed now, Once all working fine, we will needs to remove the code
        Release: Asaka API Portal
    */


    public static function getCompanyIDBYYearMMFixedCompanyID($yearmm, $companyId, $myUserId)
    {
        $company=Company::where('fixed_company_id', $companyId)->where('yearmm', $yearmm)->select('id as companyID')->first();
        if ($company) {
            $companyID=$company->companyID;
        } else {
            $companyID="";
        }
        return $companyID;
    }

    public static function getCompanyBYyearmm($yearmm, $fixed_company_id)
    {
        $company = Company::where([['yearmm',$yearmm],['fixed_company_id', $fixed_company_id]])->first();
        return $company;
    }
}
