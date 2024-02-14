<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\User;
use App\Models\Company;
use App\Models\Examinee;
use Illuminate\Support\Facades\Log;
use DB;
use App\Models\CompanyStatusUpdateCommon;

class DepartmentController extends Controller
{
    /**
      * Store a newly created departments in db.
      *
      * @param  \Illuminate\Http\Request  $request
      * @return \Illuminate\Http\Response
      */
    public function store(Request $request, $my_user_id)
    {
        $array = $request->validate(Department::rules()->merge($request->validate([
            'departments.*.update.*.id' => 'required|exists:departments,id',
            'departments.*.update.*.problem_interview' => ['required','string'],
            'departments.*.update.*.name' => ['required','string'],
            'departments.*.update.*.company_id' => ['required','string','exists:companies,id'],
            'departments.*.update.*.order_num' => ['required','integer'],
            'departments.*.update.*.ms_submission' =>  ['nullable','string'],
            'departments.*.update.*.no_problem_interview' => ['required','string']]))
            ->merge($request->validate([
                'departments.*.delete.*.id' => ['required','unique:examinee,department_id']
            ], ['unique'=>'Can not delete, this Department is already referred!']))->toArray());

        try {
            foreach ($array as $value) {
                foreach ($value as $key => $value1) {
                    DB::transaction(function () use ($value1, &$user) {
                        if (isset($value1['new']) && $value1['new']) {
                            foreach ($value1['new'] as $key => $newValue) {
                                $department = new Department();
                                $department->fill($newValue);

                                if ($department->save()) {
                                    $department->departmentUser()->attach($newValue['users_id']);
                                    $result = CompanyStatusUpdateCommon::updateStatus($department->company_id, 'DEPARTMENT_CSV_UPLOAD', null);
                                }
                            }
                        }
                        if (isset($value1['update']) && $value1['update']) {
                            foreach ($value1['update'] as $updateValue) {
                                $updateDept = Department::where('id', $updateValue['id'])->first();
                                $updateDept->departmentUser()->sync($updateValue['users_id']);
                                $updateDept->update($updateValue);
                            }
                        }
                        if (isset($value1['delete']) && $value1['delete']) {
                            foreach ($value1['delete'] as $deleteValue) {
                                $deleteDept = Department::where('id', $deleteValue['id'])->first();
                                $deleteDept->departmentUser()->detach();
                                $deleteDept->delete();
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
     * Display the specified resource.
     *
     * @param  \App\Models\Department  $departmentIds,  $myId
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $myId)
    {
        $departments = [];
        $departmentIdArray = $request->department_id_array;
        $departments = Department::whereIn('id', $departmentIdArray)->get();
        foreach ($departments as $key => $value) {
            $departments[$key]['users_id'] = $value->departmentUser->pluck('id');
            unset($value['departmentUser']);
        }

        return response()->json($departments, 200);
    }
    /**
    * Display the all resource.
    *
    * @param  \App\Models\Director  $myId
    * @return \Illuminate\Http\Response
    */
    public function getDepartments(Request $request, $myId)
    {
        $userRole = User::findOrFail($myId);
        if ($userRole->role != 1) {
            $Company = Company::where('fixed_company_id', $userRole->fixed_company_id);
        } else {
            $validated = $request->validate([
                'fixed_company_id' => 'required',
             ]);
            $Company = Company::where('fixed_company_id', $request->fixed_company_id);
        }
        $queryClone = clone $Company;
        if (isset($request->yearmm)) {
            $company = $queryClone->where('yearmm', $request->yearmm)->get()->toArray();
        } else {
            $companyYearMm = $Company->orderBy('yearmm', 'DESC')->firstOrFail();
            $company = $queryClone->where('yearmm', $companyYearMm->yearmm)->get()->toArray();
        }
        
        //get all the company id
        $companyids  = array_column($company, 'id');
        $departments = Department::whereIn('company_id', $companyids)->orderBy('order_num', 'ASC')->get();
        foreach ($departments as $key => $value) {
            $departments[$key]['users_id'] = $value->departmentUser->pluck('id');
            unset($value['departmentUser']);
        }
        return response()->json($departments, 200);
    }

    /**
     * Get department by department id.
     *
     * @param  \App\Models\Director  $myuserId
     * @return \Illuminate\Http\Response
     */
    public function getDepartmentById(Request $request, $myuserId, $deptId)
    {
        $userRole = User::findOrFail($myuserId);
        $departmentDet = Department::findOrFail($deptId);
        $company = Company::findOrFail($departmentDet->company_id);
        if (($userRole->role != 1) && ($userRole->fixed_company_id != $company->fixed_company_id)) {
            $departmentDet = [];
        }
        return response()->json($departmentDet, 200);
    }
}
