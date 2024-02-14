<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use App\Models\Classification;
use App\Models\Director;

class ReportsCommon extends Model
{
    /*  
        Name: AGTECHPRO - Asaka API Portal
        Date: 09/02/2022
        Desc: ASAKA-81- Excel write from Template read from local storage.
        Note: Previous used code committed now, Once all working fine, we will needs to remove the code
        Release: Asaka API Portal
    */


    public static function prepareData($yearmm, $companyId, $myUserId)
    {
        $searchQuery = array('users.role' => 5);
        if(!empty($companyId)) {
            $searchQuery = array('users.role' => 5, 'users.fixed_company_id' => $companyId);
        }

        if(isset($yearmm) && !empty($yearmm)) {
            $searchQuery = array_merge($searchQuery, array('examinee.yearmm' => $yearmm));
        }

        $userDetails = User::select(ReportsCommon::commonfieldMapping())
            ->selectRaw('TIMESTAMPDIFF(YEAR, DATE(examinee.birth_day), current_date) AS age')
            ->where($searchQuery)
            ->leftjoin('examinee', 'examinee.user_id', '=', 'users.id')
            ->join('answers', 'answers.examinee_id', '=', 'examinee.id')
            ->leftjoin('classifications_examinee_relation','examinee.id', '=', 'classifications_examinee_relation.examinee_id')
            ->leftjoin('classifications', 'classifications.id', '=', 'classifications_examinee_relation.classification_id')
            ->leftjoin('departments', 'departments.id', '=', 'examinee.department_id')
            ->leftjoin('directors', 'directors.id', '=', 'examinee.director_id')
            ->groupBy('users.id')
            ->get();        

        return $userDetails;
    }


    public static function commonfieldMapping()
    {

        $fieldForQuery=array("examinee.id as examinee_id","examinee.serial_number","users.id","users.password","users.lastname as usersLastname","users.firstname as usersFirstname","examinee.lastname","examinee.firstname","examinee.birth_day","examinee.gender","examinee.question_method","examinee.employment_num","examinee.employment_day","departments.name as departments_name","directors.name as directors_name","answers.bjsq_a_1","answers.bjsq_a_2","answers.bjsq_a_3","answers.bjsq_a_4","answers.bjsq_a_5","answers.bjsq_a_6","answers.bjsq_a_7","answers.bjsq_a_8","answers.bjsq_a_9","answers.bjsq_a_10","answers.bjsq_a_11","answers.bjsq_a_12","answers.bjsq_a_13","answers.bjsq_a_14","answers.bjsq_a_15","answers.bjsq_a_16","answers.bjsq_a_17","answers.bjsq_b_1","answers.bjsq_b_2","answers.bjsq_b_3","answers.bjsq_b_4","answers.bjsq_b_5","answers.bjsq_b_6","answers.bjsq_b_7","answers.bjsq_b_8","answers.bjsq_b_9","answers.bjsq_b_10","answers.bjsq_b_11","answers.bjsq_b_12","answers.bjsq_b_13","answers.bjsq_b_14","answers.bjsq_b_15","answers.bjsq_b_16","answers.bjsq_b_17","answers.bjsq_b_18","answers.bjsq_b_19","answers.bjsq_b_20","answers.bjsq_b_21","answers.bjsq_b_22","answers.bjsq_b_23","answers.bjsq_b_24","answers.bjsq_b_25","answers.bjsq_b_26","answers.bjsq_b_27","answers.bjsq_b_28","answers.bjsq_b_29","answers.bjsq_c_1","answers.bjsq_c_2","answers.bjsq_c_3","answers.bjsq_c_4","answers.bjsq_c_5","answers.bjsq_c_6","answers.bjsq_c_7","answers.bjsq_c_8","answers.bjsq_c_9","answers.bjsq_d_1","answers.bjsq_d_2","answers.mirror_1","answers.mirror_2","answers.mirror_3","answers.mirror_4","answers.mirror_5","answers.mirror_6","answers.mirror_7","answers.mirror_8","answers.mirror_9","answers.mirror_10","answers.mirror_11","answers.mirror_12","answers.mirror_13","answers.mirror_14","answers.mirror_15","answers.mirror_16","answers.mirror_17","answers.mirror_18","answers.mirror_19","answers.mirror_20","answers.mirror_21","answers.mirror_22","answers.mirror_23","answers.mirror_24","answers.mirror_25","answers.mirror_26","answers.mirror_27","answers.mirror_28","answers.mirror_29","answers.mirror_30","answers.mirror_31","answers.mirror_32","answers.mirror_33","answers.mirror_34","answers.mirror_35","answers.mirror_36","answers.mirror_37","answers.mirror_38","answers.mirror_39","answers.mirror_40","answers.mirror_41","answers.mirror_42","answers.mirror_43","answers.mirror_44","answers.mirror_45","answers.personal_cal_1","answers.personal_cal_2","answers.personal_cal_3","answers.personal_cal_4","answers.personal_cal_5","answers.personal_cal_6","answers.personal_cal_7","answers.personal_cal_8","answers.personal_cal_9","answers.personal_cal_10","answers.personal_cal_11","answers.personal_cal_12","answers.personal_cal_13","answers.personal_cal_14","answers.personal_cal_15","answers.personal_cal_16","answers.personal_cal_17","answers.personal_cal_18","answers.personal_cal_19","answers.raw_stress_factor","answers.raw_stress_response","answers.raw_support_factor","answers.total_stress_factor","answers.total_stress_response","answers.total_support_factor","answers.stressor","answers.stress_response","answers.stress_response as SKIP","answers.stressor_stress_response","answers.judgment","answers.weather_mark","answers.high_stress_flg","examinee.Interview_target_flg","examinee.Interview_request_flg","examinee.result_view_flg","examinee.result_view_created_at");

        return $fieldForQuery;
    }

    public function getDirectorsData($yearmm, $companyId, $myUserId)
    {       
        $companyUniqueID=Company::getCompanyIDBYYearMMFixedCompanyID($yearmm, $companyId, $myUserId);
        $directorsList = Director::where('company_id',$companyUniqueID)->select('name as directorsName')
        ->orderBy('order_num', 'ASC')->get()->toArray();
        return $directorsList;
    }

    /*  
        Name: AGTECHPRO - Asaka API Portal
        Date: 04/02/2022
        Desc: ASAKA-81- Excel write from Template read from local storage.
        Note: Previous used code committed now, Once all working fine, we will needs to remove the code
        Release: Asaka API Portal
    */

    public function getDepartmentData($yearmm, $companyId, $myUserId)
    {       
        $companyUniqueID=Company::getCompanyIDBYYearMMFixedCompanyID($yearmm, $companyId, $myUserId);
        $depatmentsList = Department::where('company_id',$companyUniqueID)->select('name as departmentsName')->get()->toArray();
        return $depatmentsList;
    }

    public function getClassificationData($yearmm, $companyId, $myUserId)
    {
        $companyUniqueID=Company::getCompanyIDBYYearMMFixedCompanyID($yearmm, $companyId, $myUserId);
        $classificationList = Classification::where('company_id',$companyUniqueID)->select('name as classificationsName','class_text')->orderBy('order_num', 'ASC')->get()->toArray();
        return $classificationList;
    }  
    
}
