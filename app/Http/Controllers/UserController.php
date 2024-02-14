<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Examinee;
use App\Models\Department;
use DB;
use App\Http\Resources\UserExamineeCollection;
use App\Http\Resources\UserDetailsCollection;
use App\Http\Controllers\ManagementController;
use App\Models\Company;
use Illuminate\Validation\Rule;
use App\Models\ClassificationExaminee;
use App\Models\Answer;
use App\Models\CompanyStatusUpdateCommon;
use App\Models\ExamineeStatusCommon;

// use Illuminate\Support\Collection;

class UserController extends Controller
{
    use AuthenticatesUsers;
    /**
     * Get the login id to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'id';
    }

    /**
     * Get user details.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $myUserId, $userId)
    {
        $userRole = User::findOrFail($myUserId);
        $fields = [
            'users.id',
            'users.fixed_company_id',
            'users.role',
            'users.email',
            'users.lastname',
            'users.firstname',
            'users.password',
            'examinee.yearmm'
        ];
        if ($request->has('yearmm') && $request->yearmm != null) {
            $where = [['users.role',5],['examinee.yearmm', $request->yearmm],['users.id', $userId]];
        } else {
            $where = [['users.role', 5],['users.id', $userId]];
        }
        try {
            if ($userRole->role == 1) {
                $userDetails = User::select($fields)->rightjoin('examinee', 'examinee.user_id', '=', 'users.id')->where($where)->orderBy('examinee.yearmm', 'DESC')->get();
            } elseif ($userRole->role == 2 || $userRole->role == 3 || $userRole->role == 4) {
                $userDetails = User::select($fields)->rightjoin('examinee', 'examinee.user_id', '=', 'users.id')->where('users.fixed_company_id', $userRole->fixed_company_id)->where($where)->orderBy('examinee.yearmm', 'DESC')->get();
            } elseif ($userRole->role == 5 && $myUserId == $userId) {
                $userDetails = User::select($fields)->rightjoin('examinee', 'examinee.user_id', '=', 'users.id')->where('users.id', $myUserId)->orderBy('examinee.yearmm', 'DESC')->get();
            }
            $userDetailsTemp = [];
            foreach ($userDetails as $item) {
                array_push($userDetailsTemp, $item);
                break;
            }
            $result = new UserDetailsCollection($userDetailsTemp); // call collection to format the result array
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => trans('validation.custom.Something_went_wrong_Please_try_again')], 400);
        }
        return response()->json($result, 200);
    }
    
    /**
     * Store user details into user table
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $my_user_id)
    {
        $array = $request->validate(User::rules()->merge(Examinee::rules()->toArray())->toArray());
      
        try {
            foreach ($array as $value) {
                foreach ($value as $key => $value1) {
                    if (isset($value1['new']) && $value1['new']) {
                        foreach ($value1['new'] as $key => $newValue) {
                            $user = User::firstOrNew(['id' => $newValue['id']]);
                            $user->fill($newValue);
                            try {
                                DB::beginTransaction();
                                $user->save();
                                $response = $this->saveExaminee($user, $newValue);
                           
                                if (!empty($response)) {
                                    $result = CompanyStatusUpdateCommon::updateStatus($newValue['fixed_company_id'], 'USER_CSV_UPLOAD', $newValue['yearmm']);
                                    if ($response->question_method == 'WEB') {
                                        $result1 = ExamineeStatusCommon::updateExamineeStatus($response->id, 'USER_CSV_UPLOAD', 'WEB');
                                    } else {
                                        $result2 = ExamineeStatusCommon::updateExamineeStatus($response->id, 'USER_CSV_UPLOAD_MS', 'MS');
                                    }
                                }
                            } catch (\Exception $e) {
                                DB::rollBack();
                                Log::error($e);
                                return response()->json(['message' => trans('validation.custom.Something_went_wrong_Please_try_again')], 400);
                            }
                            DB::commit();
                        }
                    }
                    if (isset($value1['update']) && $value1['update']) {
                        foreach ($value1['update'] as $updateValue) {
                            $user = User::where('id', $updateValue['id'])->first();
                            $user->fill($updateValue);
                            $user->save();
                            $this->saveExaminee($user, $updateValue);
                        }
                    }
                    if (isset($value1['delete']) && $value1['delete']) {
                        foreach ($value1['delete'] as $deleteValue) {
                            $user = User::where('id', $deleteValue['id'])->first();
                            $examinee = Examinee::where([['user_id','=',$user->id],['yearmm','=',$deleteValue['yearmm']]])->first();
                            $classification = ClassificationExaminee::where('examinee_id', $examinee->id)->delete();
                            $answers = Answer::where('examinee_id', $examinee->id)->delete();
                            $examinee->delete();
                            $examinee = Examinee::where('user_id', '=', $user->id)->first();
                            if (empty($examinee)) {
                                $user->delete();
                            }
                        }
                    }
                }
            }
            return response()->json(['result' => true], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => trans('validation.custom.Something_went_wrong_Please_try_again')], 400);
        }
    }

    public function saveExaminee($user, $data)
    {
        $chkAvailability = Examinee::where([['lastname_katakana', $data['lastname_katakana']],['firstname_katakana', $data['firstname_katakana']],['birth_day', $data['birth_day']],['employment_num', $data['employment_num']]])->exists();
        $examinee = Examinee::where([['user_id','=',$user->id],['yearmm','=',$data['yearmm']]])->first();
        
        if (empty($examinee)) {
            $examinee = new Examinee();
        }
        $examinee->fill([
            'user_id' => $user->id,
            'department_id' => $data['department_id'],
            'director_id' => $data['director_id'],
            'yearmm' => $data['yearmm'],
            'consultation_text' =>  $data['consultation_text'],
            'serial_number' =>  $data['serial_number'],
            'firstname_katakana' => $data['firstname_katakana'],
            'lastname_katakana' => $data['lastname_katakana'],
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'gender' => $data['gender'],
            'birth_day' => $data['birth_day'],
            'question_method' => $data['question_method'],
            'questionnaire_type' => $data['questionnaire_type'],
            'notification_type' => empty($data['notification_type']) ? 'post' : $data['notification_type'],
            'question_output_method' => $data['question_output_method'],
            'language' => $data['language'],
            'employment_day' => $data['employment_day'],
            'job_status' => $data['job_status'],
            'result_view_flg' => isset($data['result_view_flg'])==true ? $data['result_view_flg']: false,
            'result_view_created_at' => isset($data['result_view_created_at'])==true ? $data['result_view_created_at']: null,
            'high_stress_flg' => isset($data['high_stress_flg'])==true ? $data['high_stress_flg']: false,
            'Interview_target_flg' => isset($data['Interview_target_flg'])==true ? $data['Interview_target_flg'] : "0",
            'Interview_request_flg' => isset($data['Interview_request_flg'])==true ? $data['Interview_request_flg'] : "0",
            'pdf_report_url' => isset($data['pdf_report_url'])==true ? $data['pdf_report_url']: null,
            'employment_num' => $data['employment_num'],
            'mismatch_flg' => $chkAvailability,
            'exam_complete_flg' => isset($data['exam_complete_flg']) == true ? $data['exam_complete_flg'] : 0,
        ]);
        $classification_pivot_exist = $examinee->classifications()->detach();
        if ($examinee->save()) {
            $examinee->classifications()->attach($data['classification_id']);
        }
        return $examinee;
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return "store";
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Users  $users
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $myId)
    {
        $examinees = [];
    
        $examinees = Examinee::with(['user:id,fixed_company_id,role,password,email','classifications:id,name'])->whereIn('id', $request->examinee_id_array)->get();
        return response()->json(new UserExamineeCollection($examinees), 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Users  $users
     * @return \Illuminate\Http\Response
     */
    public function edit(Users $users)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Users  $users
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Users $users)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Users  $users
     * @return \Illuminate\Http\Response
     */
    public function destroy(Users $users)
    {
        //
    }

    /**
     * User login using sanctum.
     *
     * @param  \App\Models\Users  $users
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $validted = $request->validate([
            'id' => 'required',
            'password' => 'required'
        ]);
        $user = User::where([['id', $request['id']],['password',$request['password']]])->firstOrFail();
        $token = $user->createToken('admin', ['user:csvupload'])->plainTextToken;
        $examinee =  Examinee::where('user_id', $user->id)->orderBy('yearmm', 'DESC')->first();
        $department_id = ($user->role == 5) ? $examinee->department_id : null;
        $department_name = ($user->role == 5) ? $examinee->department->name : null;
        $company = Company::where('fixed_company_id', $user->fixed_company_id)->orderBy('yearmm', 'DESC')->firstOrFail();
        if ($user->role == 5) {
            if (!empty($examinee)) {
                $result1 = ExamineeStatusCommon::updateExamineeStatus($examinee->id, 'LOGIN_EXAMINEE', 'WEB');
            }
        }
        return response()->json([
                'access_token' => $token,
                'user_id' => $user->id,
                'user_name' => $user->lastname.' '.$user->firstname,
                'company_id' => $user->fixed_company_id,
                'company_name' => $company->name,
                'department_id' => $department_id,
                'department_name'=> $department_name,
                'role' => $user->role
        ]);
    }

    /**
     * Reset user password.
     *
     * @param  \App\Models\Users  $users
     * @return \Illuminate\Http\Response
     */
    public function resetPassword(Request $request)
    {
        $user = User::select('users.id')->leftjoin('examinee', 'users.id', '=', 'examinee.user_id')->where([['users.id', $request['user_id']],['examinee.birth_day', $request['birth_day']]])->orderBy('examinee.updated_at', 'DESC')->firstOrFail();
        $random = Str::random(8);
        $user->password = $random;
        $user->save();
        return response()->json(["password" => $random], 200);
    }
    
    /**
    * Reset users passwords by admin.
    *
    * @param  \App\Models\Users  $users
    * @return \Illuminate\Http\Response
    */
    public function resetAdminPassword(Request $request)
    {
        foreach ($request['admin_id'] as $key => $value) {
            $user = User::where('id', $value)->firstOrFail();
            $random = Str::random(8);
            $user->password = $random;
            $user->save();
        }
        $emailRequest = new Request();
        $emailRequest->type = 1;
        $emailRequest->ids = $request['admin_id'];
        $management = new ManagementController();
        $response = $management->SendEmail($emailRequest);
        return response()->json(["result" => true], 200);
    }

    /**
     * Get users details.
     *
     * @param  \App\Models\Users  $users, $myId, $yearmm
     * @return \Illuminate\Http\Response
     */
    public function getUserDetails(Request $request, $myId)
    {
        $results = [];
        $companyId = $request->fixed_company_id;
        $userRole = User::findOrFail($myId);
        if ($userRole->role != 1) {
            $companyId = $userRole->fixed_company_id;
        }
        if ($userRole->role == 3 || $userRole->role == 4) {
            $department_Id = User::select('department_id')->leftjoin('department_permission_users', 'users.id', 'department_permission_users.user_id')->where('user_id', $myId)->get();
        }
      
        $fields = [
        'users.id',
        'users.fixed_company_id',
        'users.role',
        'users.email',
        'users.lastname',
        'users.firstname',
        'users.password',
        'examinee.yearmm'
        ];
       
        if ($request->has('yearmm') && $request->yearmm != null) {
            $where = [['users.role',5],['examinee.yearmm', $request->yearmm]];
        } else {
            $where = [['users.role', 5]];
        }
        if (!empty($companyId)) {
            $query =  User::select($fields)->where($where)->where('users.fixed_company_id', $companyId);
        } else {
            $query = User::select($fields);
        }
        $finalQuery = $query;
       
        if ($userRole->role == 3 || $userRole->role == 4) {
            $finalQuery = $query->whereIn('examinee.department_id', $department_Id);
        }
        $userDetails =   $finalQuery->where($where)->rightjoin('examinee', 'examinee.user_id', '=', 'users.id')->where('examinee.mismatch_flg', '!=', 1)->whereNull('examinee.deleted_at')->orderBy('examinee.yearmm', 'DESC')->get();
      
        if (!empty($userDetails)) {
            $results = new UserDetailsCollection($userDetails);
        }
        return response()->json($results, 200);
    }
    
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Users  $users
     * @return \Illuminate\Http\Response
     */
    public function uploadAdminByCSV(Request $request, $myId)
    {
        $array = $request->validate(User::adminNewRules()->merge($request->validate([
            'admin.*.update.*.id' => 'required|exists:users,id',
            'admin.*.update.*.fixed_company_id' => "required|string|exists:companies,fixed_company_id",
            'admin.*.update.*.email' =>"required|email:filter",
            'admin.*.update.*.password' =>"required|string",
            'admin.*.update.*.role' => ['required', Rule::in([1,2,3,4])],
            'admin.*.update.*.firstname' => 'required|string',
            'admin.*.update.*.lastname' => 'required|string',
        ]))->merge($request->validate([
            'admin.*.delete.*.id' => ['required','exists:users,id']
        ]))->toArray());

        try {
            foreach ($array as $value) {
                foreach ($value as $key => $value1) {
                    DB::transaction(function () use ($value1, &$user) {
                        if (isset($value1['new']) && $value1['new']) {
                            foreach ($value1['new'] as $key => $newValue) {
                                $user = new User();
                                $user->fill($newValue);
                                $user->save();
                            }
                        }
                        if (isset($value1['update']) && $value1['update']) {
                            foreach ($value1['update'] as $updateValue) {
                                $updateUser = User::where('id', $updateValue['id'])->update($updateValue);
                            }
                        }
                        if (isset($value1['delete']) && $value1['delete']) {
                            foreach ($value1['delete'] as $deleteValue) {
                                $deleteuser = User::where('id', $deleteValue['id'])->delete();
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
      * Display the down resource.
      *
      * @param  \App\Models\Users  $users
      * @return \Illuminate\Http\Response
      */
    public function downloadAdminByCSV(Request $request, $myId)
    {
        $userIds = $request->admin_id_array;
        $fields = [
            'id',
            'fixed_company_id',
            'role',
            'password',
            'email',
            'lastname',
            'firstname',
            ];
        
        $users = User::select($fields)->whereIn('id', $userIds)->where('role', '!=', 5)->get();
       
        return response()->json($users, 200);
    }
    /**
     * Update user_view flag the down resource.
     *
     * @param  \App\Models\Users  $users
     * @return \Illuminate\Http\Response
     */
    public function usersResultView(Request $request, $myId)
    {
        $current_datetime =  date('Y-m-d h:i:s');
        Examinee::where('user_id', $myId)->update(['result_view_flg' => 1,'result_view_created_at'=>$current_datetime,'status'=> config('constants.CALLED_API.EXAMINEE_USER_RESULT_VIEW')]);
        return response()->json(['result' => true], 200);
    }

    /**
     * Get list of users whose company id is same as admin company.
     *
     * @param  \App\Models\Users  $users
     * @return \Illuminate\Http\Response
     */
    public function getAlladmins(Request $request, $myId)
    {
        $admin = User::findOrFail($myId);
        if ($admin->role == 1) {
            if (isset($request->fixed_company_id)) {
                $user = User::where([['role','!=',5],['fixed_company_id', $request->fixed_company_id]])->get();
            } else {
                $user = User::where([['role','!=',5],['fixed_company_id', $admin->fixed_company_id]])->get();
            }
        } else {
            $user = User::where([['role','!=',5],['fixed_company_id', $admin->fixed_company_id]])->get();
        }
        return response()->json($user, 200);
    }

    /**
    * Get specific admin details.
    *
    * @param  \App\Models\Users  $userId
    * @return \Illuminate\Http\Response
    */
    public function getSingleAdmin($myId, $adminId)
    {
        $admin = User::findOrFail($adminId);
        return response()->json($admin, 200);
    }

    /**
     * Store mismatch deta to examinee
     *
     * @param  \App\Models\Users  $my_user_id
     * @return \Illuminate\Http\Response
     */
    public function setMismatchData(Request $request, $myId)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'examinee_id'=>'required'
        ]);
        $examinee = Examinee::with('user')->where([['user_id', $request->user_id],['id', $request->examinee_id]])->firstOrFail();
       
        $examinee->fill([
            'firstname_katakana' => $request->firstname_katakana,
            'lastname_katakana' => $request->lastname_katakana,
            'birth_day' => $request->birth_day,
            'employment_num' => $request->employment_num,
            'mismatch_flg' => false
        ]);
        $res = $examinee->save();
        $user_fixed_company_id = $examinee->user->fixed_company_id;
        $missMatchCount = User::where('fixed_company_id', $user_fixed_company_id)->leftJoin('examinee', 'examinee.user_id', '=', 'users.id')->where([['examinee.yearmm',$examinee->yearmm],['examinee.mismatch_flg',1]])->count();
        if ($res && $missMatchCount == 0) {
            $result = CompanyStatusUpdateCommon::updateStatus($user_fixed_company_id, 'MISMATCH', $examinee->yearmm);
        }
        return response()->json(['result'=> true], 200);
    }

    /**
    * get mismatch deta of examinee
    *
    * @param  \App\Models\Users  $my_user_id
    * @return \Illuminate\Http\Response
    */
    public function getMismatchData(Request $request, $myId)
    {
        $validated = $request->validate([
            'yearmm' => 'required'
        ]);
        $userRole = User::findOrFail($myId);
        if ($userRole->role == 1) {
            $validated = $request->validate([
                'fixed_company_id' => 'required'
            ]);
        }
        if ($userRole->role == 3 || $userRole->role == 4) {
            $department_Id = User::select('department_id')->leftjoin('department_permission_users', 'users.id', 'department_permission_users.user_id')->where('user_id', $myId)->get();
        }
        try {
            $where = [['users.role',5],['examinee.yearmm', $request->yearmm], ['mismatch_flg', '=', 1], ['examinee.deleted_at', null]];
            
            $fields = [
                'users.id',
                'users.fixed_company_id',
                'users.role',
                'users.email',
                'users.lastname',
                'users.firstname',
                'users.password',
                'examinee.yearmm'
            ];
            if ($userRole->role == 1) {
                $userDetails = User::select($fields)->rightjoin('examinee', 'examinee.user_id', '=', 'users.id')->where('users.fixed_company_id', $request->fixed_company_id)->where($where)->orderBy('examinee.yearmm', 'DESC');
            } elseif ($userRole->role == 2 || $userRole->role == 3 || $userRole->role == 4) {
                $userDetails = User::select($fields)->rightjoin('examinee', 'examinee.user_id', '=', 'users.id')->where('users.fixed_company_id', $userRole->fixed_company_id)->where($where)->orderBy('examinee.yearmm', 'DESC');
            }
            
            if ($userRole->role == 3 || $userRole->role == 4) {
                $finalQuery = $userDetails->whereIn('examinee.department_id', $department_Id)->get();
            } else {
                $finalQuery = $userDetails->get();
            }

            $result = new UserDetailsCollection($finalQuery); // call collection to format the result array
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => trans('validation.custom.Something_went_wrong_Please_try_again')], 400);
        }
        return response()->json($result, 200);
    }

    /**
    * delete mismatch deta of examinee
    *
    * @param  \App\Models\Users  $my_user_id,examinee_id
    * @return \Illuminate\Http\Response
    */
    public function deleteMismatchData($myId, $examinee_id)
    {
        $examinee = Examinee::with(['user'])->where('id', $examinee_id)->firstOrFail();
        $user_fixed_company_id = $examinee->user->fixed_company_id;
        $yearmm = $examinee->yearmm;
        $examinee->classifications()->detach();
        $res = $examinee->delete();
        if ($res) {
            $missMatchCount = User::where('fixed_company_id', '=', $user_fixed_company_id)->leftJoin('examinee', 'examinee.user_id', '=', 'users.id')->where('examinee.yearmm', '=', $yearmm)->where('examinee.mismatch_flg', '=', 1)->where('examinee.deleted_at', null)->count();
       
            if ($missMatchCount == 0) {
                $result = CompanyStatusUpdateCommon::updateStatus($user_fixed_company_id, 'MISMATCH', $examinee->yearmm);
            }
        }
        return response()->json(['result' =>  $res], 200);
    }

    /**
    * update Interview request flg
    *
    * @param  \App\Models\Users  $my_user_id,examinee_id
    * @return \Illuminate\Http\Response
    */
    public function setInterviewFlg(Request $request, $myId, $examinee_id)
    {
        $isExists = Examinee::with(['user'])->findOrFail($examinee_id);
        $isExists->Interview_request_flg = (String)$request->value;
        if ($request->value != '0') {
            $isExists->status = config('constants.CALLED_API.EXAMINEE_SET_INTERVIEW_REQUEST_FLG_STATUS');
        }
        $isExists->update();
        if ($isExists) {
            $status = Examinee::leftJoin('users', 'users.id', '=', 'examinee.user_id')->where([['examinee.yearmm','=',$isExists->yearmm],['users.fixed_company_id','=',$isExists->user->fixed_company_id],['examinee.interview_request_flg','=','0']])->count();
            if ($status == 0) {
                $result_flg = CompanyStatusUpdateCommon::updateStatus($isExists->user->fixed_company_id, 'SET_INTERVIEW_FLG', $isExists->yearmm);
            }
        }
        return response()->json(['result' =>  true], 200);
    }
    /**
     * Get User history
     *
     * @param  \App\Models\Users  $my_user_id,user_id
     * @return \Illuminate\Http\Response
     */
    public function getUserHistory($myId, $user_id)
    {
        $array = [];
        $fields = ['users.id','users.fixed_company_id','users.firstname','users.lastname','history.birth_day','history.gender'];
        $response = User::select($fields)->rightjoin('examinee as history', 'users.id', '=', 'history.user_id')->where('users.id', $user_id)->first();
        $array = $response->history->groupBy('history.yearmm');
        foreach ($response->history as $key => $value) {
            $response->history[$key]->department_name = $response->examinee[$key]->department->name;
            $response->history[$key]['classification'] = $value->classifications->pluck('name');
            unset($response->history[$key]->classifications);
            unset($response->examinee);
        }
       
        return response()->json($response, 200);
    }
}
