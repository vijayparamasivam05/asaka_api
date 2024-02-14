<?php

namespace App\Http\Controllers\API\Reports;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Response;
use Carbon\Carbon;
use Log;
use App\Models\Department;
use App\Models\Classification;
use DB;
use Illuminate\Support\Facades\Validator;
use App\Models\ReportsCommon;

class CompanyReportController extends Controller
{
    /**
     * Create Excel Report.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $myUserId)
    {
        $validated = $request->validate([
            'yearmm' => 'required',
        ]);
        $userRole = User::findOrFail($myUserId);
        if ($userRole->role != 1) {
            $companyId = $userRole->fixed_company_id;
        } else {
            $validated = $request->validate([
                'fixed_company_id' => 'required',
            ]);
            $companyId = $request->fixed_company_id;
        }

        $forExportDataCurrentSelectedDate = $this->prepareRawData($request->yearmm, $companyId, $myUserId);
             
        if (count($forExportDataCurrentSelectedDate) == 0) {
            return response()->json(['result' => false], 200);
        }


        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load(storage_path()."/raw_excel_report_template.xlsx");

        $i=3;

        foreach ($forExportDataCurrentSelectedDate as $currentData) {
            $spreadsheet->getSheet(0)->setCellValue('A'.$i, $currentData['serial_number']);
            $spreadsheet->getSheet(0)->setCellValue('B'.$i, $currentData['id']);
            $spreadsheet->getSheet(0)->setCellValue('C'.$i, $currentData['password']);
            $spreadsheet->getSheet(0)->setCellValue('D'.$i, $currentData['lastname']);
            $spreadsheet->getSheet(0)->setCellValue('E'.$i, $currentData['firstname']);
            $spreadsheet->getSheet(0)->setCellValue('F'.$i, $currentData['birth_day']);
            $spreadsheet->getSheet(0)->setCellValue('G'.$i, $currentData['gender']);
            $spreadsheet->getSheet(0)->setCellValue('H'.$i, $currentData['question_method']);
            $spreadsheet->getSheet(0)->setCellValue('I'.$i, $currentData['employment_num']);
            $spreadsheet->getSheet(0)->setCellValue('J'.$i, $currentData['employment_day']);
            $spreadsheet->getSheet(0)->setCellValue('K'.$i, $currentData['departments_name']);
            $spreadsheet->getSheet(0)->setCellValue('L'.$i, $currentData['directors_name']);

            $classifications = Classification::leftjoin('classifications_examinee_relation', 'classifications_examinee_relation.classification_id', '=', 'classifications.id')->where('classifications_examinee_relation.examinee_id', '=', $currentData['examinee_id'])->get()->toArray();
            
            foreach ($classifications as $classification) {
                if ($classification['class_text'] == "分析対象") {
                    $spreadsheet->getSheet(0)->setCellValue('M'.$i, $classification['name']);
                } elseif ($classification['class_text']=="分類1") {
                    $spreadsheet->getSheet(0)->setCellValue('N'.$i, $classification['name']);
                } elseif ($classification['class_text']=="分類2") {
                    $spreadsheet->getSheet(0)->setCellValue('O'.$i, $classification['name']);
                } elseif ($classification['class_text']=="分類3") {
                    $spreadsheet->getSheet(0)->setCellValue('P'.$i, $classification['name']);
                } elseif ($classification['class_text']=="分類4") {
                    $spreadsheet->getSheet(0)->setCellValue('Q'.$i, $classification['name']);
                }
            }
            $spreadsheet->getSheet(0)->setCellValue('R'.$i, $currentData['bjsq_a_1']);
            $spreadsheet->getSheet(0)->setCellValue('S'.$i, $currentData['bjsq_a_2']);
            $spreadsheet->getSheet(0)->setCellValue('T'.$i, $currentData['bjsq_a_3']);
            $spreadsheet->getSheet(0)->setCellValue('U'.$i, $currentData['bjsq_a_4']);
            $spreadsheet->getSheet(0)->setCellValue('V'.$i, $currentData['bjsq_a_5']);
            $spreadsheet->getSheet(0)->setCellValue('W'.$i, $currentData['bjsq_a_6']);
            $spreadsheet->getSheet(0)->setCellValue('X'.$i, $currentData['bjsq_a_7']);
            $spreadsheet->getSheet(0)->setCellValue('Y'.$i, $currentData['bjsq_a_8']);
            $spreadsheet->getSheet(0)->setCellValue('Z'.$i, $currentData['bjsq_a_9']);
            $spreadsheet->getSheet(0)->setCellValue('AA'.$i, $currentData['bjsq_a_10']);
            $spreadsheet->getSheet(0)->setCellValue('AB'.$i, $currentData['bjsq_a_11']);
            $spreadsheet->getSheet(0)->setCellValue('AC'.$i, $currentData['bjsq_a_12']);
            $spreadsheet->getSheet(0)->setCellValue('AD'.$i, $currentData['bjsq_a_13']);
            $spreadsheet->getSheet(0)->setCellValue('AE'.$i, $currentData['bjsq_a_14']);
            $spreadsheet->getSheet(0)->setCellValue('AF'.$i, $currentData['bjsq_a_15']);
            $spreadsheet->getSheet(0)->setCellValue('AG'.$i, $currentData['bjsq_a_16']);
            $spreadsheet->getSheet(0)->setCellValue('AH'.$i, $currentData['bjsq_a_17']);

            $spreadsheet->getSheet(0)->setCellValue('AI'.$i, $currentData['bjsq_b_1']);
            $spreadsheet->getSheet(0)->setCellValue('AJ'.$i, $currentData['bjsq_b_2']);
            $spreadsheet->getSheet(0)->setCellValue('Ak'.$i, $currentData['bjsq_b_3']);
            $spreadsheet->getSheet(0)->setCellValue('AL'.$i, $currentData['bjsq_b_4']);
            $spreadsheet->getSheet(0)->setCellValue('AM'.$i, $currentData['bjsq_b_5']);
            $spreadsheet->getSheet(0)->setCellValue('AN'.$i, $currentData['bjsq_b_6']);
            $spreadsheet->getSheet(0)->setCellValue('AO'.$i, $currentData['bjsq_b_7']);
            $spreadsheet->getSheet(0)->setCellValue('AP'.$i, $currentData['bjsq_b_8']);
            $spreadsheet->getSheet(0)->setCellValue('AQ'.$i, $currentData['bjsq_b_9']);
            $spreadsheet->getSheet(0)->setCellValue('AR'.$i, $currentData['bjsq_b_10']);
            $spreadsheet->getSheet(0)->setCellValue('AS'.$i, $currentData['bjsq_b_11']);
            $spreadsheet->getSheet(0)->setCellValue('AT'.$i, $currentData['bjsq_b_12']);
            $spreadsheet->getSheet(0)->setCellValue('AU'.$i, $currentData['bjsq_b_13']);
            $spreadsheet->getSheet(0)->setCellValue('AV'.$i, $currentData['bjsq_b_14']);
            $spreadsheet->getSheet(0)->setCellValue('AW'.$i, $currentData['bjsq_b_15']);
            $spreadsheet->getSheet(0)->setCellValue('AX'.$i, $currentData['bjsq_b_16']);
            $spreadsheet->getSheet(0)->setCellValue('AY'.$i, $currentData['bjsq_b_17']);
            $spreadsheet->getSheet(0)->setCellValue('AZ'.$i, $currentData['bjsq_b_18']);
            $spreadsheet->getSheet(0)->setCellValue('BA'.$i, $currentData['bjsq_b_19']);
            $spreadsheet->getSheet(0)->setCellValue('BB'.$i, $currentData['bjsq_b_20']);
            $spreadsheet->getSheet(0)->setCellValue('BC'.$i, $currentData['bjsq_b_21']);
            $spreadsheet->getSheet(0)->setCellValue('BD'.$i, $currentData['bjsq_b_22']);
            $spreadsheet->getSheet(0)->setCellValue('BE'.$i, $currentData['bjsq_b_23']);
            $spreadsheet->getSheet(0)->setCellValue('BF'.$i, $currentData['bjsq_b_24']);
            $spreadsheet->getSheet(0)->setCellValue('BG'.$i, $currentData['bjsq_b_25']);
            $spreadsheet->getSheet(0)->setCellValue('BH'.$i, $currentData['bjsq_b_26']);
            $spreadsheet->getSheet(0)->setCellValue('BI'.$i, $currentData['bjsq_b_27']);
            $spreadsheet->getSheet(0)->setCellValue('BJ'.$i, $currentData['bjsq_b_28']);
            $spreadsheet->getSheet(0)->setCellValue('BK'.$i, $currentData['bjsq_b_29']);
            $spreadsheet->getSheet(0)->setCellValue('BL'.$i, $currentData['bjsq_c_1']);
            $spreadsheet->getSheet(0)->setCellValue('BM'.$i, $currentData['bjsq_c_2']);
            $spreadsheet->getSheet(0)->setCellValue('BN'.$i, $currentData['bjsq_c_3']);
            $spreadsheet->getSheet(0)->setCellValue('BO'.$i, $currentData['bjsq_c_4']);
            $spreadsheet->getSheet(0)->setCellValue('BP'.$i, $currentData['bjsq_c_5']);
            $spreadsheet->getSheet(0)->setCellValue('BQ'.$i, $currentData['bjsq_c_6']);
            $spreadsheet->getSheet(0)->setCellValue('BR'.$i, $currentData['bjsq_c_7']);
            $spreadsheet->getSheet(0)->setCellValue('BS'.$i, $currentData['bjsq_c_8']);
            $spreadsheet->getSheet(0)->setCellValue('BT'.$i, $currentData['bjsq_c_9']);
            $spreadsheet->getSheet(0)->setCellValue('BU'.$i, $currentData['bjsq_d_1']);
            $spreadsheet->getSheet(0)->setCellValue('BV'.$i, $currentData['bjsq_d_2']);
            $spreadsheet->getSheet(0)->setCellValue('BW'.$i, $currentData['mirror_1']);
            $spreadsheet->getSheet(0)->setCellValue('BX'.$i, $currentData['mirror_2']);
            $spreadsheet->getSheet(0)->setCellValue('BY'.$i, $currentData['mirror_3']);
            $spreadsheet->getSheet(0)->setCellValue('BZ'.$i, $currentData['mirror_4']);
            $spreadsheet->getSheet(0)->setCellValue('CA'.$i, $currentData['mirror_5']);
            $spreadsheet->getSheet(0)->setCellValue('CB'.$i, $currentData['mirror_6']);
            $spreadsheet->getSheet(0)->setCellValue('CC'.$i, $currentData['mirror_7']);
            $spreadsheet->getSheet(0)->setCellValue('CD'.$i, $currentData['mirror_8']);
            $spreadsheet->getSheet(0)->setCellValue('CE'.$i, $currentData['mirror_9']);
            $spreadsheet->getSheet(0)->setCellValue('CF'.$i, $currentData['mirror_10']);
            $spreadsheet->getSheet(0)->setCellValue('CG'.$i, $currentData['mirror_11']);
            $spreadsheet->getSheet(0)->setCellValue('CH'.$i, $currentData['mirror_12']);
            $spreadsheet->getSheet(0)->setCellValue('CI'.$i, $currentData['mirror_13']);
            $spreadsheet->getSheet(0)->setCellValue('CJ'.$i, $currentData['mirror_14']);
            $spreadsheet->getSheet(0)->setCellValue('CK'.$i, $currentData['mirror_15']);
            $spreadsheet->getSheet(0)->setCellValue('CL'.$i, $currentData['mirror_16']);
            $spreadsheet->getSheet(0)->setCellValue('CM'.$i, $currentData['mirror_17']);
            $spreadsheet->getSheet(0)->setCellValue('CN'.$i, $currentData['mirror_18']);
            $spreadsheet->getSheet(0)->setCellValue('CO'.$i, $currentData['mirror_19']);
            $spreadsheet->getSheet(0)->setCellValue('CP'.$i, $currentData['mirror_20']);
            $spreadsheet->getSheet(0)->setCellValue('CQ'.$i, $currentData['mirror_21']);
            $spreadsheet->getSheet(0)->setCellValue('CR'.$i, $currentData['mirror_22']);
            $spreadsheet->getSheet(0)->setCellValue('CS'.$i, $currentData['mirror_23']);
            $spreadsheet->getSheet(0)->setCellValue('CT'.$i, $currentData['mirror_24']);
            $spreadsheet->getSheet(0)->setCellValue('CU'.$i, $currentData['mirror_25']);
            $spreadsheet->getSheet(0)->setCellValue('CV'.$i, $currentData['mirror_26']);
            $spreadsheet->getSheet(0)->setCellValue('CW'.$i, $currentData['mirror_27']);
            $spreadsheet->getSheet(0)->setCellValue('CX'.$i, $currentData['mirror_28']);
            $spreadsheet->getSheet(0)->setCellValue('CY'.$i, $currentData['mirror_29']);
            $spreadsheet->getSheet(0)->setCellValue('CZ'.$i, $currentData['mirror_30']);
            $spreadsheet->getSheet(0)->setCellValue('DA'.$i, $currentData['mirror_31']);
            $spreadsheet->getSheet(0)->setCellValue('DB'.$i, $currentData['mirror_32']);
            $spreadsheet->getSheet(0)->setCellValue('DC'.$i, $currentData['mirror_33']);
            $spreadsheet->getSheet(0)->setCellValue('DD'.$i, $currentData['mirror_34']);
            $spreadsheet->getSheet(0)->setCellValue('DE'.$i, $currentData['mirror_35']);
            $spreadsheet->getSheet(0)->setCellValue('DF'.$i, $currentData['mirror_36']);
            $spreadsheet->getSheet(0)->setCellValue('DG'.$i, $currentData['mirror_37']);
            $spreadsheet->getSheet(0)->setCellValue('DH'.$i, $currentData['mirror_38']);
            $spreadsheet->getSheet(0)->setCellValue('DI'.$i, $currentData['mirror_39']);
            $spreadsheet->getSheet(0)->setCellValue('DJ'.$i, $currentData['mirror_40']);
            $spreadsheet->getSheet(0)->setCellValue('DK'.$i, $currentData['mirror_41']);
            $spreadsheet->getSheet(0)->setCellValue('DL'.$i, $currentData['mirror_42']);
            $spreadsheet->getSheet(0)->setCellValue('DM'.$i, $currentData['mirror_43']);
            $spreadsheet->getSheet(0)->setCellValue('DN'.$i, $currentData['mirror_44']);
            $spreadsheet->getSheet(0)->setCellValue('DO'.$i, $currentData['mirror_45']);
            $spreadsheet->getSheet(0)->setCellValue('DP'.$i, $currentData['personal_cal_1']);
            $spreadsheet->getSheet(0)->setCellValue('DQ'.$i, $currentData['personal_cal_2']);
            $spreadsheet->getSheet(0)->setCellValue('DR'.$i, $currentData['personal_cal_3']);
            $spreadsheet->getSheet(0)->setCellValue('DS'.$i, $currentData['personal_cal_4']);
            $spreadsheet->getSheet(0)->setCellValue('DT'.$i, $currentData['personal_cal_5']);
            $spreadsheet->getSheet(0)->setCellValue('DU'.$i, $currentData['personal_cal_6']);
            $spreadsheet->getSheet(0)->setCellValue('DV'.$i, $currentData['personal_cal_7']);
            $spreadsheet->getSheet(0)->setCellValue('DW'.$i, $currentData['personal_cal_8']);
            $spreadsheet->getSheet(0)->setCellValue('DX'.$i, $currentData['personal_cal_9']);
            $spreadsheet->getSheet(0)->setCellValue('DY'.$i, $currentData['personal_cal_10']);
            $spreadsheet->getSheet(0)->setCellValue('DZ'.$i, $currentData['personal_cal_11']);
            $spreadsheet->getSheet(0)->setCellValue('EA'.$i, $currentData['personal_cal_12']);
            $spreadsheet->getSheet(0)->setCellValue('EB'.$i, $currentData['personal_cal_13']);
            $spreadsheet->getSheet(0)->setCellValue('EC'.$i, $currentData['personal_cal_14']);
            $spreadsheet->getSheet(0)->setCellValue('ED'.$i, $currentData['personal_cal_15']);
            $spreadsheet->getSheet(0)->setCellValue('EE'.$i, $currentData['personal_cal_16']);
            $spreadsheet->getSheet(0)->setCellValue('EF'.$i, $currentData['personal_cal_17']);
            $spreadsheet->getSheet(0)->setCellValue('EG'.$i, $currentData['personal_cal_18']);
            $spreadsheet->getSheet(0)->setCellValue('EH'.$i, $currentData['personal_cal_19']);
            $spreadsheet->getSheet(0)->setCellValue('EI'.$i, $currentData['raw_stress_factor']);
            $spreadsheet->getSheet(0)->setCellValue('EJ'.$i, $currentData['raw_stress_response']);
            $spreadsheet->getSheet(0)->setCellValue('EK'.$i, $currentData['raw_support_factor']);
            $spreadsheet->getSheet(0)->setCellValue('EL'.$i, $currentData['total_stress_factor']);
            $spreadsheet->getSheet(0)->setCellValue('EM'.$i, $currentData['total_stress_response']);
            $spreadsheet->getSheet(0)->setCellValue('EN'.$i, $currentData['total_support_factor']);
            $spreadsheet->getSheet(0)->setCellValue('EO'.$i, $currentData['stressor']);
            $spreadsheet->getSheet(0)->setCellValue('EP'.$i, $currentData['stress_response']);
            $spreadsheet->getSheet(0)->setCellValue('EQ'.$i, $currentData['stressor_stress_response']);
            $spreadsheet->getSheet(0)->setCellValue('ER'.$i, $currentData['judgment']);
            $spreadsheet->getSheet(0)->setCellValue('ES'.$i, $currentData['weather_mark']);
            $spreadsheet->getSheet(0)->setCellValue('ET'.$i, $currentData['high_stress_flg']);
            $spreadsheet->getSheet(0)->setCellValue('EU'.$i, $currentData['Interview_target_flg']);
            $spreadsheet->getSheet(0)->setCellValue('EV'.$i, $currentData['Interview_request_flg']);
            $spreadsheet->getSheet(0)->setCellValue('EW'.$i, 0);
            $spreadsheet->getSheet(0)->setCellValue('EX'.$i, $currentData['result_view_flg']);
            $spreadsheet->getSheet(0)->setCellValue('EY'.$i, $currentData['result_view_created_at']);
            $i++;
        }
        
        $fileName = $request->yearmm.'_RAWデータ_'.$companyId.'.xlsx';
        $writer = new Xlsx($spreadsheet);
    
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();

        Storage::disk('s3')->put($fileName, $content, array('ACL' => 'public-read'));
        // This will check if file already exists in S3 Bucket
        if (!empty($companyId) && Storage::disk('s3')->exists($fileName)) {
            // This will return the file URL
            $excelReportUrl = Storage::url($fileName);
        }

        return response()->json(['url' => $excelReportUrl], 200);
    }

    

    public function prepareRawData($yearmm, $companyId, $myUserId)
    {
        $searchQuery = array('users.role' => 5);
        if (!empty($companyId)) {
            $searchQuery = array('users.role' => 5, 'users.fixed_company_id' => $companyId);
        }

        if (isset($yearmm) && !empty($yearmm)) {
            $searchQuery = array_merge($searchQuery, array('examinee.yearmm' => $yearmm));
        }

        $userDetails = User::select(ReportsCommon::commonfieldMapping())
            ->where($searchQuery)
            ->leftjoin('examinee', 'examinee.user_id', '=', 'users.id')
            ->join('answers', 'answers.examinee_id', '=', 'examinee.id')
            ->leftjoin('departments', 'departments.id', '=', 'examinee.department_id')
            ->leftjoin('directors', 'directors.id', '=', 'examinee.director_id')
            ->groupBy('users.id')
            ->get()->toArray();
              
        return $userDetails;
    }
    /*
           Name: AGTECHPRO - Asaka API Portal
           Date: 08/02/2022
           Desc: ASAKA-136- Excel report company upload.
           Note: New API
           Release: Asaka API Portal
       */

    public function upload(Request $request, $myUserId)
    {
        $rules = array('yearmm' => 'required|min:6|max:6|date_format:Ym',
            'excel_file' => 'required|mimes:xlsx,zip','fixed_company_id' => 'required');
        
        $validator = Validator::make($request->all(), $rules);
        $yearmm=$request->input('yearmm');
            
        if ($validator->fails()) {
            return Response::json(array(
                    'message' => "The given data was invalid",
                    'errors' => $validator->getMessageBag()->toArray()

                ), 400);
        } else {
            $userRole = User::findOrFail($myUserId);
            if ($userRole->role != 1) {
                $companyId = $userRole->fixed_company_id;
            } else {
                $validated = $request->validate([
                        'fixed_company_id' => 'required',
                    ]);
                $companyId = $request->fixed_company_id;
            }

            if ($request->hasfile('excel_file')) {
                $file = $request->file('excel_file');
                $file_ext = $request->file('excel_file')->extension();
                $fileName = $yearmm.'_集団分析Excel_'.$companyId.'.'.$file_ext;

                Storage::disk('s3')->put($fileName, file_get_contents($file), array('ACL' => 'public-read'));
                
                if (!empty($companyId) && Storage::disk('s3')->exists($fileName)) {
                    $excelReportUrl = Storage::url($fileName);
                    $company = Company::where([['fixed_company_id', $companyId],['yearmm', $yearmm]])->firstOrFail();
                    $company->update(['excel_report_url' => $excelReportUrl]);
                }

                return response()->json(['result' => true], 200);
            }
        }
    }
}
