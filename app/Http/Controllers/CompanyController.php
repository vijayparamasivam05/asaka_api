<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Examinee;
use App\Models\User;
use App\Models\Answer;
use App\Models\Department;
use App\Models\Director;
use App\Models\Classification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;
use App\Models\CompanyStatusUpdateCommon;
use App\Models\ExamineeStatusCommon;

class CompanyController extends Controller
{
    /**
     * Store a newly created companies in db.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $my_user_id)
    {
        $array = $request->validate(Company::rules()->merge($request->validate([
            'companies.*.update.*.id' => 'required|exists:companies,id',
            'companies.*.update.*.fixed_company_id' => ['required','string','exists:companies,fixed_company_id'] ,
            'companies.*.update.*.name' => ['required','string'],
            'companies.*.update.*.yearmm' => ['required','integer'],
            'companies.*.update.*.industry_standard' => ['required','integer'],
            'companies.*.update.*.industry_ascc' => ['required','integer'],
            'companies.*.update.*.employees_num' => ['required','integer'],
            'companies.*.update.*.exam_start' => ['required','integer'],
            'companies.*.update.*.exam_end' => ['required','integer'],
            'companies.*.update.*.criteria_type' => ['required', Rule::in(['素点', '合計'])],
            'companies.*.update.*.high_stress_1' => ['nullable','integer'],
            'companies.*.update.*.high_stress_2' => ['nullable','integer'],
            'companies.*.update.*.high_stress_3' => ['nullable','integer'],
            'companies.*.update.*.high_stress_4' => ['nullable','integer'],
            'companies.*.update.*.high_stress_5' => ['nullable','integer'],
            'companies.*.update.*.high_stress_6' => ['nullable','integer'],
            'companies.*.update.*.name_end' => ['required','integer'],
            'companies.*.update.*.answer_end' => ['required','integer'],
            'companies.*.update.*.result_day' => ['required','integer'],
            'companies.*.update.*.guidance_subject' => ['required','string'],
            'companies.*.update.*.guidance_email' => ['required','string'],
            'companies.*.update.*.remind_subject' => ['required','string'],
            'companies.*.update.*.remind_email' => ['required','string'],
            'companies.*.update.*.result_subject' => ['required','string'],
            'companies.*.update.*.result_email' => ['required','string'],
            'companies.*.update.*.result_remind_subject' => ['required','string'],
            'companies.*.update.*.result_remind_email' => ['required','string'],
            'companies.*.update.*.excel_report_url' => "nullable|string",
            'companies.*.update.*.pdf_report_url' => "nullable|string",
            'companies.*.update.*.schedule_excel_url' => 'nullable|string',
            'companies.*.update.*.generate_excel_report_url' => 'nullable|string'
            ]))
            ->merge($request->validate([
            'companies.*.delete.*.id' => ['required','unique:directors,company_id','unique:departments,company_id']
        ], ['unique'=> trans('validation.custom.Can_not_delete_this_Company_is_already_referred')]))->toArray());
       
        try {
            foreach ($array as $value) {
                foreach ($value as $key => $value1) {
                    DB::transaction(function () use ($value1, &$user) {
                        if (isset($value1['new']) && $value1['new']) {
                            foreach ($value1['new'] as $key => $newValue) {
                                $company = new Company();
                                $company->fill(array_merge($newValue, ['status' =>  config('constants.CALLED_API.COMPANY_STATUS'), 'status_message'=> config('constants.CALLED_API.COMPANY_CSV_UPLOAD_STATUS_MESSAGE')]));
                                $company->save();
                            }
                        }
                        if (isset($value1['update']) && $value1['update']) {
                            foreach ($value1['update'] as $updateValue) {
                                $updateUser = Company::where('id', $updateValue['id'])->update($updateValue);
                            }
                        }
                        if (isset($value1['delete']) && $value1['delete']) {
                            foreach ($value1['delete'] as $deleteValue) {
                                $deleteuser = Company::where('id', $deleteValue['id'])->delete();
                            }
                        }
                    });
                }
            }
            return response()->json(['result' => true], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => trans('validation.custom.Something_went_wrong_Please_try_again')], 400);
        }
    }

    /**
     * Retrieve companies details .
     *
     * @param  \App\Models\Company  $companyIds,  $myId
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $myId)
    {
        $companies = [];
        $companyIds = $request->company_id_array;
        $companies = Company::whereIn('id', $companyIds)->get();
        return response()->json($companies, 200);
    }
    /**
     * Display the all resource.
     *
     * @param  \App\Models\Company  $myId
     * @return \Illuminate\Http\Response
     */
    public function getCompanies(Request $request, $myId)
    {
        $companies = Company::where('yearmm', '!=', '0')->orderBy('yearmm', 'DESC')->get();
        return response()->json($companies, 200);
    }

    /**
     * Get company by fixed_company_id.
     *
     * @param  \App\Models\Company  $myId, $fixed_company_id
     * @return \Illuminate\Http\Response company details
     */
    public function getCompanyDetail($myId, $fixed_company_id, Request $request)
    {
        $userRole = User::findOrFail($myId);
        $company = Company::where('fixed_company_id', $fixed_company_id)->orderBy('yearmm', 'DESC')->firstOrFail();
        if ($userRole->role != 1) {
            if ($userRole->fixed_company_id != $company->fixed_company_id) {
                $company = [];
            }
        } else {
            $validated = $request->validate([
                'yearmm' => 'required',
            ]);
            $yearmm = $request->yearmm;
            $company = Company::where('fixed_company_id', $fixed_company_id)->where('yearmm', $yearmm)->firstOrFail();
        }
        return response()->json($company, 200);
    }

    /**
     * Get company analysis data.
     *
     * @param  \App\Models\Company  $myId, $company_id, $year
     * @return \Illuminate\Http\Response company details
     */
    public function getCompanyAnalysis(Request $request, $myId)
    {
        $validated = $request->validate([
            'yearmm' => 'required',
        ]);
        $userrolefive = [];
        $userRole = User::findOrFail($myId);
        if ($userRole->role == '1') {
            if ($request->has('fixed_company_id') && $request->fixed_company_id !== null) {
                $userrolefive = User::leftJoin('examinee', 'examinee.user_id', '=', 'users.id')->where([['users.role', '=', 5],['users.fixed_company_id','=',$request->fixed_company_id],['examinee.yearmm','=',$request->yearmm]]);
            } else {
                $userrolefive = User::leftJoin('examinee', 'examinee.user_id', '=', 'users.id')->where([['users.role', '=', 5],['examinee.yearmm','=',$request->yearmm]]);
            }
        } else {
            $userrolefive = User::leftJoin('examinee', 'examinee.user_id', '=', 'users.id')->where([['users.role', '=', 5],['users.fixed_company_id','=',$userRole->fixed_company_id],['examinee.yearmm','=',$request->yearmm]]);
        }
        // $queryClone = clone $userrolefive;
        // no of target examinee
        $noOfTarget = $userrolefive->get()->count();
        $data_arr =  $userrolefive->get()->toArray();

        //get all the examinee id
        $user_ids  = array_column($data_arr, 'user_id');
        //get number of web tartget examinee
        $noOfWEBTarget = Examinee::SELECT('examinee.user_id')->where([['question_method', config('constants.QUESTION_METHOD.WEB')],['examinee.yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get();
        $noOfWEBTargetids = $noOfWEBTarget->toArray();
        $noOfWEBTargetids = array_column($noOfWEBTargetids, 'user_id');
        $noOfWEBTargetcount =  $noOfWEBTarget->count();

        $no_result_view = Examinee::SELECT('examinee.user_id')->where([['result_view_flg', '=', '1'],['examinee.yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
        $no_interview_target = Examinee::SELECT('examinee.user_id')->where([['Interview_target_flg', '=', '1'],['examinee.yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
        $no_interview_request = Examinee::SELECT('examinee.user_id')->where([['Interview_request_flg', '=', '1'],['examinee.yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
        //number of hightstress
        $noOfHighStress = Examinee::where([['high_stress_flg', '=', '1'],['examinee.yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
        //get number of MS tartget examinee
        $noOfMSTarget = Examinee::SELECT('examinee.user_id')->where([['question_method', config('constants.QUESTION_METHOD.MS')],['examinee.yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get();
                
        $noOfMSTargetids = $noOfMSTarget->toArray();
        $noOfMSTargetids = array_column($noOfMSTargetids, 'user_id');
        $noOfMSTargetcount =  $noOfMSTarget->count();

        //number of result of examinee
        $noOfResultExaminee = Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('examinee.user_id', $user_ids)->get()->count();
        $no_invalid =  Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('examinee.user_id', $user_ids)->where('invalid_flg', 1)->get()->count();
                
        //number of result of examinee web
        $noOfResultWEBExaminee = Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('examinee.user_id', $noOfWEBTargetids)->get()->count();
        //number of result of examinee MS
        $noOfResultMSExaminee = Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('examinee.user_id', $noOfMSTargetids)->get()->count();
        
        //check percentage
        $examination_rate = 0;
        $no_high_stress_rate = 0;
        if ($noOfTarget > 0 && $noOfResultExaminee > 0) {
            $examination_rate = round(($noOfResultExaminee/$noOfTarget) * 100, 2);
        }
        if ($noOfTarget > 0 && $noOfHighStress > 0) {
            $no_high_stress_rate = round(($noOfHighStress/$noOfTarget) * 100, 2);
        }
        
        // push the details to array to get final array
        $result= [
            "no_of_targets_examinee"=>  $noOfTarget,
            "no_of_web_targets_examinee"=> $noOfWEBTargetcount,
            "no_of_ms_targets_examinee"=> $noOfMSTargetcount,
            "no_of_result_examinee"=> $noOfResultExaminee,
            "no_of_web_result_examinee"=>  $noOfResultWEBExaminee,
            "no_of_ms_result_examinee"=>  $noOfResultMSExaminee,
            "no_of_web_non_result_examinee"=> $noOfWEBTargetcount -$noOfResultWEBExaminee ,
            "no_of_ms_non_result_examinee"=> $noOfMSTargetcount - $noOfResultMSExaminee,
            "examination_rate"=> $examination_rate,
            "no_invalid"=> $no_invalid,
            "no_result_view"=> $no_result_view,
            "no_high_stress"=> $noOfHighStress,
            "no_high_stress_rate"=>  $no_high_stress_rate,
            "no_interview_target"=> $no_interview_target,
            "no_interview_request"=>$no_interview_request
             ];
        return response()->json($result, 200);
    }
    /**
     * Get deparment company analysis data.
     *
     * @param  \App\Models\Company  $myId, $company_id, $year
     * @return \Illuminate\Http\Response deparment company details
     */
    public function getDepartmentAnalysis(Request $request, $myId)
    {
        $validated = $request->validate([
            'yearmm' => 'required',
        ]);
        $userrolefive = [];
        $result = [];
        $userRole = User::findOrFail($myId);
        //Based on role get all the deparetment in user table
        if ($userRole->role == '1') {
            if ($request->has('fixed_company_id') && $request->fixed_company_id !== null) {
                $departmentdet = User::select('departments.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->join('departments', 'examinee.department_id', '=', 'departments.id')->where([['role', '=', 5],['users.fixed_company_id','=',$request->fixed_company_id],['examinee.yearmm','=',$request->yearmm]]);
            } else {
                $departmentdet = User::select('departments.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->join('departments', 'examinee.department_id', '=', 'departments.id')->where([['role', '=', 5],['examinee.yearmm','=',$request->yearmm]]);
            }
        } else {
            $departmentdet = User::select('departments.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->join('departments', 'examinee.department_id', '=', 'departments.id')->where([['role', '=', 5],['users.fixed_company_id','=',$userRole->fixed_company_id],['examinee.yearmm','=',$request->yearmm]]);
        }
        $data = $departmentdet->groupBy('examinee.department_id')->get()->toArray();
        // loop department id to get all the details based on department
        foreach ($data as $dep) {
            //get user details based on department id
            if ($userRole->role == '1') {
                if ($request->has('fixed_company_id') && $request->fixed_company_id !== null) {
                    $userrolefive = User::select('users.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->where([['users.role', '=', 5],['users.fixed_company_id','=',$request->fixed_company_id],['examinee.yearmm','=',$request->yearmm],['examinee.department_id','=',$dep]]);
                } else {
                    $userrolefive = User::select('users.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->where([['users.role', '=', 5],['examinee.yearmm','=',$request->yearmm],['examinee.department_id','=',$dep]]);
                }
            } else {
                $userrolefive = User::select('users.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->where([['users.role', '=', 5],['users.fixed_company_id','=',$userRole->fixed_company_id],['examinee.yearmm','=',$request->yearmm],['examinee.department_id','=',$dep]]);
            }
            // no of target examinee
            $noOfTarget = $userrolefive->get()->count();
            $data_arr =  $userrolefive->get()->toArray();
            //get all the examinee id
            $user_ids  = array_column($data_arr, 'id');

            //get number of web tartget examinee
            $noOfWEBTarget = Examinee::SELECT('examinee.user_id')->where([['question_method', config('constants.QUESTION_METHOD.WEB')],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get();
            $noOfWEBTargetids = $noOfWEBTarget->toArray();
            $noOfWEBTargetids = array_column($noOfWEBTargetids, 'user_id');
            $noOfWEBTargetcount =  $noOfWEBTarget->count();
            $no_result_view = Examinee::SELECT('examinee.user_id')->where([['result_view_flg', '=', '1'],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
            $no_interview_target = Examinee::SELECT('examinee.user_id')->where([['Interview_target_flg', '=', '1'],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
            $no_interview_request = Examinee::SELECT('examinee.user_id')->where([['Interview_request_flg', '=', '1'],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
       
            //number of hightstress
            $noOfHighStress = Examinee::where([['high_stress_flg', '=', '1'],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
            //get number of MS tartget examinee
            $noOfMSTarget = Examinee::SELECT('examinee.user_id')->where([['question_method', config('constants.QUESTION_METHOD.MS')],['examinee.yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get();
       
            $noOfMSTargetids = $noOfMSTarget->toArray();
            $noOfMSTargetids = array_column($noOfMSTargetids, 'user_id');
            $noOfMSTargetcount =  $noOfMSTarget->count();

            //number of result of examinee
            $noOfResultExaminee = Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('user_id', $user_ids)->get()->count();
            $no_invalid =  Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->whereIn('user_id', $user_ids)->where('invalid_flg', 1)->where('examinee.yearmm', '=', $request->yearmm)->get()->count();
       
            //number of result of examinee web
            $noOfResultWEBExaminee = Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('user_id', $noOfWEBTargetids)->get()->count();
            //number of result of examinee MS
            $noOfResultMSExaminee = Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('user_id', $noOfMSTargetids)->get()->count();

            //check percentage
            $examination_rate = 0;
            $no_high_stress_rate = 0;
            if ($noOfTarget > 0 && $noOfResultExaminee > 0) {
                $examination_rate = round(($noOfResultExaminee/$noOfTarget) * 100, 2);
            }
            if ($noOfTarget > 0 && $noOfHighStress > 0) {
                $no_high_stress_rate = round(($noOfHighStress/$noOfTarget) * 100, 2);
            }
            //deparment name
            $department_name = Department::select('name')->where('id', $dep)->first();
            // push the details to array to get final array
            array_push($result, [
            "department"=> $department_name->name,
            "no_of_targets_examinee"=>  $noOfTarget,
            "no_of_web_targets_examinee"=> $noOfWEBTargetcount,
            "no_of_ms_targets_examinee"=> $noOfMSTargetcount,
            "no_of_result_examinee"=> $noOfResultExaminee,
            "no_of_web_result_examinee"=>  $noOfResultWEBExaminee,
            "no_of_ms_result_examinee"=>  $noOfResultMSExaminee,
            "no_of_web_non_result_examinee"=> $noOfWEBTargetcount -$noOfResultWEBExaminee ,
            "no_of_ms_non_result_examinee"=> $noOfMSTargetcount - $noOfResultMSExaminee,
            "examination_rate"=> $examination_rate,
            "no_invalid"=> $no_invalid,
            "no_result_view"=> $no_result_view,
            "no_high_stress"=> $noOfHighStress,
            "no_high_stress_rate"=>  $no_high_stress_rate,
            "no_interview_target"=> $no_interview_target,
            "no_interview_request"=>$no_interview_request
             ]);
        }
        return response()->json($result, 200);
    }
    
    /**
      * Get Director company analysis data.
      *
      * @param  \App\Models\Company  $myId, $fixed_company_id, $yearmm
      * @return \Illuminate\Http\Response director company details
      */
    public function getDirectorAnalysis(Request $request, $myId)
    {
        $validated = $request->validate([
            'yearmm' => 'required',
        ]);
        $userrolefive = [];
        $result = [];
        $userRole = User::findOrFail($myId);
        //Based on role get all the deparetment in user table
        if ($userRole->role == '1') {
            if ($request->has('fixed_company_id') && $request->fixed_company_id !== null) {
                $directorsdet = User::select('directors.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->join('directors', 'examinee.director_id', '=', 'directors.id')->where([['role', '=', 5],['users.fixed_company_id','=',$request->fixed_company_id],['examinee.yearmm','=',$request->yearmm]]);
            } else {
                $directorsdet = User::select('directors.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->join('directors', 'examinee.director_id', '=', 'directors.id')->where([['role', '=', 5],['examinee.yearmm','=',$request->yearmm]]);
            }
        } else {
            $directorsdet = User::select('directors.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->join('directors', 'examinee.director_id', '=', 'directors.id')->where([['role', '=', 5],['users.fixed_company_id','=',$userRole->fixed_company_id],['examinee.yearmm','=',$request->yearmm]]);
        }
        $data = $directorsdet->groupBy('examinee.director_id')->get()->toArray();
       
        // loop directors id to get all the details based on directors
        foreach ($data as $dep) {
            //get user details based on directors id
            if ($userRole->role == '1') {
                if ($request->has('fixed_company_id') && $request->fixed_company_id !== null) {
                    $userrolefive = User::select('users.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->where([['users.role', '=', 5],['users.fixed_company_id','=',$request->fixed_company_id],['examinee.yearmm','=',$request->yearmm],['examinee.director_id','=',$dep]]);
                } else {
                    $userrolefive = User::select('users.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->where([['users.role', '=', 5],['examinee.yearmm','=',$request->yearmm],['examinee.director_id','=',$dep]]);
                }
            } else {
                $userrolefive = User::select('users.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->where([['users.role', '=', 5],['users.fixed_company_id','=',$userRole->fixed_company_id],['examinee.yearmm','=',$request->yearmm],['examinee.director_id','=',$dep]]);
            }
            // no of target examinee
            $noOfTarget = $userrolefive->get()->count();
            $data_arr =  $userrolefive->get()->toArray();
            //get all the examinee id
            $user_ids  = array_column($data_arr, 'id');
      
            //get number of web tartget examinee
            $noOfWEBTarget = Examinee::SELECT('examinee.user_id')->where([['question_method', config('constants.QUESTION_METHOD.WEB')],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get();
            $noOfWEBTargetids = $noOfWEBTarget->toArray();
            $noOfWEBTargetids = array_column($noOfWEBTargetids, 'user_id');
            $noOfWEBTargetcount =  $noOfWEBTarget->count();
            $no_result_view = Examinee::SELECT('examinee.user_id')->where([['result_view_flg', '=', '1'],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
            $no_interview_target = Examinee::SELECT('examinee.user_id')->where([['Interview_target_flg', '=', '1'],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
            $no_interview_request = Examinee::SELECT('examinee.user_id')->where([['Interview_request_flg', '=', '1'],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
            //number of hightstress
            $noOfHighStress = Examinee::where([['high_stress_flg', '=', '1'],['yearmm','=',$request->yearmm]])->whereIn('user_id', $user_ids)->get()->count();
            //get number of MS tartget examinee
            $noOfMSTarget = Examinee::SELECT('examinee.user_id')->where([['question_method', config('constants.QUESTION_METHOD.MS')],['examinee.yearmm','=',$request->yearmm]])->whereIn('user_id', $user_ids)->get();
       
            $noOfMSTargetids = $noOfMSTarget->toArray();
            $noOfMSTargetids = array_column($noOfMSTargetids, 'user_id');
            $noOfMSTargetcount =  $noOfMSTarget->count();

            //number of result of examinee
            $noOfResultExaminee = Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('examinee.user_id', $user_ids)->get()->count();
            $no_invalid =  Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('examinee.user_id', $user_ids)->where('invalid_flg', 1)->get()->count();
       
            //number of result of examinee web
            $noOfResultWEBExaminee = Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('examinee.user_id', $noOfWEBTargetids)->get()->count();
            //number of result of examinee MS
            $noOfResultMSExaminee = Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('examinee.user_id', $noOfMSTargetids)->get()->count();

            //check percentage
            $examination_rate = 0;
            $no_high_stress_rate = 0;
            if ($noOfTarget > 0 && $noOfResultExaminee > 0) {
                $examination_rate = round(($noOfResultExaminee/$noOfTarget) * 100, 2);
            }
            if ($noOfTarget > 0 && $noOfHighStress > 0) {
                $no_high_stress_rate = round(($noOfHighStress/$noOfTarget) * 100, 2);
            }
            //deparment name
            $directors_name = Director::select('name')->where('id', $dep)->first();
            // push the details to array to get final array
            array_push($result, [
            "department"=> $directors_name->name,
            "no_of_targets_examinee"=>  $noOfTarget,
            "no_of_web_targets_examinee"=> $noOfWEBTargetcount,
            "no_of_ms_targets_examinee"=> $noOfMSTargetcount,
            "no_of_result_examinee"=> $noOfResultExaminee,
            "no_of_web_result_examinee"=>  $noOfResultWEBExaminee,
            "no_of_ms_result_examinee"=>  $noOfResultMSExaminee,
            "no_of_web_non_result_examinee"=> $noOfWEBTargetcount -$noOfResultWEBExaminee ,
            "no_of_ms_non_result_examinee"=> $noOfMSTargetcount - $noOfResultMSExaminee,
            "examination_rate"=> $examination_rate,
            "no_invalid"=> $no_invalid,
            "no_result_view"=> $no_result_view,
            "no_high_stress"=> $noOfHighStress,
            "no_high_stress_rate"=>  $no_high_stress_rate,
            "no_interview_target"=> $no_interview_target,
            "no_interview_request"=>$no_interview_request
             ]);
        }
        return response()->json($result, 200);
    }
    /**
     * Get deparment company analysis data.
     *
     * @param  \App\Models\Company  $myId, $company_id, $year
     * @return \Illuminate\Http\Response deparment company details
     */
    public function getCompanyAnalysisClassification(Request $request, $myId)
    {
        $validated = $request->validate([
            'yearmm' => 'required',
            'classification_title'=>'required'
        ]);
        
        $userrolefive = [];
        $result = [];
        $userRole = User::findOrFail($myId);
        $classification_arr = Classification::select('id', 'name')->where('subject', $request->classification_title)->get();
       
        // loop classification id to get all the details based on classification
        foreach ($classification_arr as $key=> $value) {
            $classi_id = $value['id'];
            $classi_name = $value['name'];

            //get user details based on classification id
            if ($userRole->role == '1') {
                if ($request->has('fixed_company_id') && $request->fixed_company_id !== null) {
                    $userrolefive = User::select('users.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->join('classifications_examinee_relation', 'examinee.id', '=', 'classifications_examinee_relation.examinee_id')->where([['users.role', '=', 5],['users.fixed_company_id','=',$request->fixed_company_id],['examinee.yearmm','=',$request->yearmm],['classifications_examinee_relation.classification_id','=',$classi_id]]);
                } else {
                    $userrolefive = User::select('users.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->join('classifications_examinee_relation', 'examinee.id', '=', 'classifications_examinee_relation.examinee_id')->where([['users.role', '=', 5],['examinee.yearmm','=',$request->yearmm],['classifications_examinee_relation.classification_id','=',$classi_id]]);
                }
            } else {
                $userrolefive = User::select('users.id')->join('examinee', 'users.id', '=', 'examinee.user_id')->join('classifications_examinee_relation', 'examinee.id', '=', 'classifications_examinee_relation.examinee_id')->where([['users.role', '=', 5],['users.fixed_company_id','=',$userRole->fixed_company_id],['examinee.yearmm','=',$request->yearmm],['classifications_examinee_relation.classification_id','=',$classi_id]]);
            }
        
            // no of target examinee
            $noOfTarget = $userrolefive->get()->count();
            $data_arr =  $userrolefive->get()->toArray();
            //get all the examinee id
            $user_ids  = array_column($data_arr, 'id');
       
            //get number of web tartget examinee
            $noOfWEBTarget = Examinee::SELECT('examinee.user_id')->where([['question_method', config('constants.QUESTION_METHOD.WEB')],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get();
            $noOfWEBTargetids = $noOfWEBTarget->toArray();
            $noOfWEBTargetids = array_column($noOfWEBTargetids, 'user_id');
            $noOfWEBTargetcount =  $noOfWEBTarget->count();
            $no_result_view = Examinee::SELECT('examinee.user_id')->where([['result_view_flg', '=', '1'],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
            $no_interview_target = Examinee::SELECT('examinee.user_id')->where([['Interview_target_flg', '=', '1'],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
            $no_interview_request = Examinee::SELECT('examinee.user_id')->where([['Interview_request_flg', '=', '1'],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
            //number of hightstress
            $noOfHighStress = Examinee::where([['high_stress_flg', '=', '1'],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get()->count();
            //get number of MS tartget examinee
            $noOfMSTarget = Examinee::SELECT('examinee.user_id')->where([['question_method', config('constants.QUESTION_METHOD.MS')],['yearmm','=',$request->yearmm]])->whereIn('examinee.user_id', $user_ids)->get();
        
            $noOfMSTargetids = $noOfMSTarget->toArray();
            $noOfMSTargetids = array_column($noOfMSTargetids, 'user_id');
            $noOfMSTargetcount =  $noOfMSTarget->count();
 
            //number of result of examinee
            $noOfResultExaminee = Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('examinee.user_id', $user_ids)->get()->count();
            $no_invalid =  Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('examinee.user_id', $user_ids)->where('answers.invalid_flg', 1)->get()->count();
        
            //number of result of examinee web
            $noOfResultWEBExaminee = Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('examinee.user_id', $noOfWEBTargetids)->get()->count();
            //number of result of examinee MS
            $noOfResultMSExaminee = Answer::leftjoin('examinee', 'examinee.id', '=', 'answers.examinee_id')->where('examinee.yearmm', '=', $request->yearmm)->whereIn('examinee.user_id', $noOfMSTargetids)->get()->count();
 
            //check percentage
            $examination_rate = 0;
            $no_high_stress_rate = 0;
            if ($noOfTarget > 0 && $noOfResultExaminee > 0) {
                $examination_rate = round(($noOfResultExaminee/$noOfTarget) * 100, 2);
            }
            if ($noOfTarget > 0 && $noOfHighStress > 0) {
                $no_high_stress_rate = round(($noOfHighStress/$noOfTarget) * 100, 2);
            }
       
            if (count($userrolefive->get()) > 0) {
        
        // push the details to array to get final array
                array_push($result, [
            "classification"=> $classi_name,
            "no_of_targets_examinee"=>  $noOfTarget,
            "no_of_web_targets_examinee"=> $noOfWEBTargetcount,
            "no_of_ms_targets_examinee"=> $noOfMSTargetcount,
            "no_of_result_examinee"=> $noOfResultExaminee,
            "no_of_web_result_examinee"=>  $noOfResultWEBExaminee,
            "no_of_ms_result_examinee"=>  $noOfResultMSExaminee,
            "no_of_web_non_result_examinee"=> $noOfWEBTargetcount -$noOfResultWEBExaminee ,
            "no_of_ms_non_result_examinee"=> $noOfMSTargetcount - $noOfResultMSExaminee,
            "examination_rate"=> $examination_rate,
            "no_invalid"=> $no_invalid,
            "no_result_view"=> $no_result_view,
            "no_high_stress"=> $noOfHighStress,
            "no_high_stress_rate"=>  $no_high_stress_rate,
            "no_interview_target"=> $no_interview_target,
            "no_interview_request"=>$no_interview_request
             ]);
            }
        }
        return response()->json($result, 200);
    }

    /**
     * Get company by company id.
     *
     * @param  \App\Models\Company  $myId, $company_id
     * @return \Illuminate\Http\Response company details
     */
    public function getCompanyDetailById($myId, $company_id)
    {
        $company = Company::where('id', '=', $company_id)->firstOrFail();
        return response()->json($company, 200);
    }

    /**
     * Get company history by fixed_company_id.
     *
     * @param  \App\Models\Company  $myId, $company_id
     * @return \Illuminate\Http\Response company details
     */
    public function getCompanyHistoryByFid($myId, $fixed_company_id)
    {
        $fields = ['id', 'yearmm','exam_start','exam_end','excel_report_url','pdf_report_url','schedule_excel_url'];
        $company = Company::select($fields)->where('fixed_company_id', '=', $fixed_company_id)->orderBy('yearmm', 'DESC')->get();
        
        return response()->json($company, 200);
    }
    
    /**
     * Get result history of role 5 users
     *
     * @param  \App\Models\Company  $myId
     * @return \Illuminate\Http\Response company details
     */
    public function getRoleFiveUserHistory($myId)
    {
        $response = Examinee::select(['examinee.yearmm','pdf_report_url','fixed_company_id'])
        ->leftjoin('users', 'users.id', '=', 'examinee.user_id')
        ->where('examinee.user_id', '=', $myId)
        ->orderBy('yearmm', 'DESC')->get()->toArray();

        foreach ($response as $key => $value) {
            $company = Company::select(['exam_start','exam_end'])->where([['fixed_company_id',$value['fixed_company_id']],['yearmm',$value['yearmm']]])->first()->toArray();
            $response[$key]['exam_start'] = $company['exam_start'];
            $response[$key]['exam_end'] = $company['exam_end'];
            unset($response[$key]['fixed_company_id']);
        }
        return response()->json($response, 200);
    }

    /**
     * Get companies by start date for cron job type 2
     *
     * @param  \App\Models\Company  $myId
     * @return \Illuminate\Http\Response company details
     */
    public function getCompanyByStartDate($type)
    {
        $result = Company::where('exam_start', '<=', date('Ymd'))->get();
       
        if ($type == 2) {
            foreach ($result as $key => $value) {
                $result_flg = CompanyStatusUpdateCommon::updateStatus($value['id'], 'CRONJOBTYPE2', null);
            }
        } elseif ($type == 4) {
            foreach ($result as $key => $value) {
                if ($value['yearmm'] != 0) {
                    $examinees = Examinee::select('examinee.*')->leftjoin('users', 'examinee.user_id', '=', 'users.id')->where([['users.fixed_company_id',$value['fixed_company_id']],['examinee.yearmm',$value['yearmm']]])->get()->toArray();
                    foreach ($examinees as $key1 => $examinee) {
                        $result_flg = ExamineeStatusCommon::updateExamineeStatus($examinee['id'], 'CRONJOBTYPE4', 'WEB');
                    }
                }
            }
        } elseif ($type == 5) {
            foreach ($result as $key => $value) {
                if ($value['yearmm'] != 0) {
                    $examinees = Examinee::select('examinee.*')->leftjoin('users', 'examinee.user_id', '=', 'users.id')->where([['users.fixed_company_id',$value['fixed_company_id']],['examinee.yearmm',$value['yearmm']]])->get()->toArray();
                    foreach ($examinees as $key1 => $examinee) {
                        $result_flg = ExamineeStatusCommon::updateExamineeStatus($examinee['id'], 'CRONJOBTYPE5', 'MS');
                    }
                }
            }
        }
        return response()->json(['result' => true], 200);
    }

    /**
     * Get companies by start date for cron job type 3
     *
     * @param  \App\Models\Company  $myId
     * @return \Illuminate\Http\Response company details
     */
    public function getCompanyByEndDate($type)
    {
        $result = Company::where('exam_end', '<', date('Ymd'))->get();
        foreach ($result as $key => $value) {
            if ($value['yearmm'] != 0) {
                $result_flg = CompanyStatusUpdateCommon::updateStatus($value['id'], 'CRONJOBTYPE3', null);
            }
        }
        return response()->json(['result' => true], 200);
    }
}
