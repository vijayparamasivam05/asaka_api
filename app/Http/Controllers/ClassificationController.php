<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classification;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use DB;
use App\Models\CompanyStatusUpdateCommon;

class ClassificationController extends Controller
{
   
    /**
     * Store a newly created Classifications in storage.
     *
     * @param  \Illuminate\Http\Request  $request $my_user_id
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $my_user_id)
    {
        $array = $request->validate(Classification::rules()->merge($request->validate(
            [
            'classifications.*.update.*.id' => 'required|exists:classifications,id',
            'classifications.*.update.*.company_id' => ['required','string','exists:companies,id'] ,
            'classifications.*.update.*.subject' => ['required','string'],
            'classifications.*.update.*.name' => ['required','string'],
            'classifications.*.update.*.order_num' => ['required','integer'],
            'classifications.*.update.*.class_text' => ['required',Rule::in(['分析対象', '分類1','分類2','分類3','分類4'])]]
        ))
            ->merge($request->validate([
                'classifications.*.delete.*.id' => ['required','unique:classifications_examinee_relation,classification_id']
            ], ['unique'=>trans('validation.custom.Can_not_delete_this_Director_is_already_referred')]))->toArray());

        try {
            foreach ($array as $value) {
                foreach ($value as $key => $value1) {
                    DB::transaction(function () use ($value1, &$user) {
                        if (isset($value1['new']) && $value1['new']) {
                            foreach ($value1['new'] as $key => $newValue) {
                                $classification = new Classification();
                                $classification->fill($newValue);
                                if ($classification->save()) {
                                    $result = CompanyStatusUpdateCommon::updateStatus($classification->company_id, 'CLASSIFICATION_CSV_UPLOAD', null);
                                }
                            }
                        }
                        if (isset($value1['update']) && $value1['update']) {
                            foreach ($value1['update'] as $updateValue) {
                                $updateUser = Classification::where('id', $updateValue['id'])->update($updateValue);
                            }
                        }
                        if (isset($value1['delete']) && $value1['delete']) {
                            foreach ($value1['delete'] as $deleteValue) {
                                $deleteuser = Classification::where('id', $deleteValue['id'])->delete();
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
    * Retrieve Classifications details .
    *
    * @param  \App\Models\CLassification  $myId,  $classificationIds
    * @return \Illuminate\Http\Response
    */
    public function show(Request $request, $myId)
    {
        $classificationIdArray = $request->classification_id_array;
        $classifications = Classification::whereIn('id', $classificationIdArray)->get();
        return response()->json($classifications, 200);
    }

    /**
     * Retrieve all classifications details .
     *
     * @param  \App\Models\CLassification  $myId
     * @return \Illuminate\Http\Response
     */
    public function getClassifications(Request $request, $myId)
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
        $classifications = Classification::whereIn('company_id', $companyids)->orderBy('order_num', 'ASC')->get();
        return response()->json($classifications, 200);
    }

    
    /**
     * Retrieve all classifications subject .
     *
     * @param  \App\Models\CLassification  $myId
     * @return \Illuminate\Http\Response
     */
    public function getAllClassificationsTitle($myId)
    {
        $fields = [
            'classifications.subject'
        ];
        $classificationsSubject = [];

        $userRole = User::findOrFail($myId);
        if ($userRole->role != 1) {
            $Company = Company::where('fixed_company_id', $userRole->fixed_company_id);
            $queryClone = clone $Company;
            $companyYearMm = $Company->orderBy('yearmm', 'DESC')->firstOrFail();
            $company = $queryClone->where('yearmm', $companyYearMm->yearmm)->get()->toArray();
            //get all the company id
            $companyids  = array_column($company, 'id');
            $classifications = Classification::select($fields)->whereIn('company_id', $companyids)->distinct()->get()->toArray();
        } else {
            $classifications = Classification::select($fields)->distinct()->get()->toArray();
        }

        foreach ($classifications as $value) {
            array_push($classificationsSubject, $value["subject"]);
        }

        return response()->json($classificationsSubject, 200);
    }
}
