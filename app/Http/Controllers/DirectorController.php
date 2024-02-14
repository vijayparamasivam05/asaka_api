<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Director;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use DB;
use App\Models\CompanyStatusUpdateCommon;

class DirectorController extends Controller
{
    /**
      * Store a newly created Directors in db.
      *
      * @param  \Illuminate\Http\Request  $request
      * @return \Illuminate\Http\Response
      */
    public function store(Request $request, $my_user_id)
    {
        $array = $request->validate(Director::rules()->merge($request->validate([
            'directors.*.update.*.id' => 'required|exists:directors,id',
            'directors.*.update.*.company_id' => ['required','string','exists:companies,id'] ,
            'directors.*.update.*.name' => ['required','string'],
            'directors.*.update.*.order_num' => ['required','integer']]))
            ->merge($request->validate([
                'directors.*.delete.*.id' => ['required','unique:examinee,director_id']
            ], ['unique'=>'Can not delete, this Director is already referred!']))->toArray());

        try {
            foreach ($array as $value) {
                foreach ($value as $key => $value1) {
                    DB::transaction(function () use ($value1, &$user) {
                        if (isset($value1['new']) && $value1['new']) {
                            foreach ($value1['new'] as $key => $newValue) {
                                $director = new Director();
                                $director->fill($newValue);
                                if ($director->save()) {
                                    $result = CompanyStatusUpdateCommon::updateStatus($newValue['company_id'], 'DIRECTOR_CSV_UPLOAD', null);
                                }
                            }
                        }
                        if (isset($value1['update']) && $value1['update']) {
                            foreach ($value1['update'] as $updateValue) {
                                $updateUser = Director::where('id', $updateValue['id'])->update($updateValue);
                            }
                        }
                        if (isset($value1['delete']) && $value1['delete']) {
                            foreach ($value1['delete'] as $deleteValue) {
                                $deleteuser = Director::where('id', $deleteValue['id'])->delete();
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
     * @param  \App\Models\Director  $directorIds,  $myId
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $myId)
    {
        $directors = [];
        $directorIdArray = $request->director_id_array;
        $directors = Director::whereIn('id', $directorIdArray)->get();
        return response()->json($directors, 200);
    }
    
    /**
     * Display the all resource of directors.
     *
     * @param  \App\Models\Director  $myId
     * @return \Illuminate\Http\Response
     */
    public function getDirectors(Request $request, $myId)
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
        $directors = Director::whereIn('company_id', $companyids)->orderBy('order_num', 'ASC')->get();

        return response()->json($directors, 200);
    }
}
