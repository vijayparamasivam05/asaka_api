<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xlsm;
use Carbon\Carbon;
use Log;
use App\Models\Department;
use App\Models\Classification;
use DB;
use IntlDateFormatter;
use App\Models\ReportsCommon;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use quickchart;
use SendGrid\Mail\From;
use SendGrid\Mail\Mail;
use SendGrid\Mail\Personalization;
use SendGrid\Mail\Subject;
use SendGrid\Mail\To;
use App\Models\Examinee;
use App\Models\PersonalReportMessage;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use App\Models\CompanyStatusUpdateCommon;

class ReportController extends Controller
{
    /**
     * Create Excel Report.
     * @return \Illuminate\Http\Response
     *
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
        set_time_limit(0);

        $yearmm=$request->yearmm;

        /*
           Name: AGTECHPRO - Asaka API Portal
           Date: 23/02/2022
           Desc: ASAKA-81- Excel write from Template read from local storage.
           Note: Previous used six month and one year data code commented and added next values
           Release: Asaka API Portal
        */
        try {
            $companyFirst=Company::where('fixed_company_id', $companyId)->where('yearmm', '<>', $yearmm)->select('yearmm')->orderBy('yearmm', 'desc')->first();

            if ($companyFirst) {
                $companyFirstYearMM=$companyFirst->yearmm;

                $companySecond=Company::where('fixed_company_id', $companyId)->where('yearmm', '<', $companyFirstYearMM)->select('yearmm')->orderBy('yearmm', 'desc')->first();

                if ($companySecond) {
                    $companySecondYearMM=$companySecond->yearmm;
                } else {
                    $companySecondYearMM="";
                }
            } else {
                $companyFirstYearMM="";
            }

                    
            $yearmmLastSixMonth=$companyFirstYearMM;
            $yearmmLastTwelveMonth=$companySecondYearMM;
            
            //$yearmmLastSixMonth = Carbon::parse($yearmm.'01')->startOfMonth()->subMonths(6)->format('Ym');
            //$yearmmLastTwelveMonth = Carbon::parse($yearmm.'01')->startOfMonth()->subMonths(12)->format('Ym');
            
            // Examinee & Answers Sheet Data
            $forExportDataCurrentSelectedDate = ReportsCommon::prepareData($yearmm, $companyId, $myUserId);
            $forExportDataLastSixMonth = ReportsCommon::prepareData($yearmmLastSixMonth, $companyId, $myUserId);
            $forExportDataLastTwelveMonth = ReportsCommon::prepareData($yearmmLastTwelveMonth, $companyId, $myUserId);

            /*
                Name: AGTECHPRO - Asaka API Portal
                Date: 04/02/2022
                Desc: ASAKA-81- Excel write from Template read from local storage.
                Note: Previous used code committed now, Once all working fine, we will needs to remove the code
                Release: Asaka API Portal
            */

            $forExportDepartmentCurrentData = ReportsCommon::getDepartmentData($yearmm, $companyId, $myUserId);
            $forExportClassificationCurrentData = ReportsCommon::getClassificationData($yearmm, $companyId, $myUserId);
            $forExportDepartmentSixMonthData = ReportsCommon::getDepartmentData($yearmmLastSixMonth, $companyId, $myUserId);
            $forExportClassificationSixMonthData = ReportsCommon::getClassificationData($yearmmLastSixMonth, $companyId, $myUserId);
            $forExportDepartmentTwelveMonthData = ReportsCommon::getDepartmentData($yearmmLastTwelveMonth, $companyId, $myUserId);
            $forExportClassificationTwelveMonthData = ReportsCommon::getClassificationData($yearmmLastTwelveMonth, $companyId, $myUserId);

            if (count($forExportDataCurrentSelectedDate) == 0) {
                return response()->json(['result' => true], 200);
            }


            $response = json_encode(["success" => true]);

            echo $response;
            header('Connection: close');
            header("Content-Encoding: none");
            header("Content-Type: application/json");
            $size = ob_get_length();
            header("Content-Encoding: none");
            header("Content-Length: {$size}");
            header("Connection: close");
            ob_flush();
            flush();
            session_write_close();
            fastcgi_finish_request(); //this returns 200 to the user, and processing continues

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load(storage_path()."/company_excel_report_template.xlsx");
            $companySheet1=config('constants.REPORTS_SHEETNAMES.COMPANY_EXCEL_SHEET1');
            $companySheet2=config('constants.REPORTS_SHEETNAMES.COMPANY_EXCEL_SHEET2');
            $companySheet3=config('constants.REPORTS_SHEETNAMES.COMPANY_EXCEL_SHEET3');
            $companySheet4=config('constants.REPORTS_SHEETNAMES.COMPANY_EXCEL_SHEET4');
            $companySheet01=config('constants.REPORTS_SHEETNAMES.COMPANY_EXCEL_SHEET01');
            $companySheet02=config('constants.REPORTS_SHEETNAMES.COMPANY_EXCEL_SHEET02');
            $companySheet03=config('constants.REPORTS_SHEETNAMES.COMPANY_EXCEL_SHEET03');
            $companySheet04=config('constants.REPORTS_SHEETNAMES.COMPANY_EXCEL_SHEET04');
            $companySheet05=config('constants.REPORTS_SHEETNAMES.COMPANY_EXCEL_SHEET05');
            $companySheet06=config('constants.REPORTS_SHEETNAMES.COMPANY_EXCEL_SHEET06');



            // getDirectorsData Name

            $getDirectorsDataList = ReportsCommon::getDirectorsData($yearmm, $companyId, $myUserId);

            $sheet01=4;
            foreach ($getDirectorsDataList as $getDirectorsData) {
                $spreadsheet->getSheetByName($companySheet01)->setCellValue('D'.$sheet01, $getDirectorsData['directorsName']);
                $sheet01++;
            }

            // companySheet01 Remove Rows

            // $getDirectorsDataCount= count($getDirectorsDataList);
            // $companySheet01Range = $spreadsheet->getSheetByName($companySheet01)->rangeToArray('D2:D63', null, true, true, true);
            
            // $companySheet01Count=count($companySheet01Range)+1;

            // $startRow= 4+$getDirectorsDataCount;

            // for ($row=$startRow; $row <= $companySheet01Count; $row++) {
            //     $spreadsheet->getSheetByName($companySheet01)->setCellValue('D'.$row, '');
            //     $spreadsheet->getSheetByName($companySheet01)->setCellValue('E'.$row, '');
            //     $spreadsheet->getSheetByName($companySheet01)->setCellValue('F'.$row, '');
            //     $spreadsheet->getSheetByName($companySheet01)->setCellValue('G'.$row, '');
            //     $spreadsheet->getSheetByName($companySheet01)->setCellValue('H'.$row, '');
            //     $spreadsheet->getSheetByName($companySheet01)->setCellValue('I'.$row, '');
            //     $spreadsheet->getSheetByName($companySheet01)->setCellValue('J'.$row, '');
            //     $spreadsheet->getSheetByName($companySheet01)->setCellValue('K'.$row, '');
            //     $spreadsheet->getSheetByName($companySheet01)->setCellValue('L'.$row, '');
            //     $spreadsheet->getSheetByName($companySheet01)->setCellValue('M'.$row, '');
            //     $spreadsheet->getSheetByName($companySheet01)->setCellValue('N'.$row, '');
            //     $spreadsheet->getSheetByName($companySheet01)->setCellValue('O'.$row, '');
            //     $spreadsheet->getSheetByName($companySheet01)->setCellValue('P'.$row, '');
            // }
            // $spreadsheet->getSheetByName($companySheet01)->disconnectCells(); // remove cells of sheet1 from cache before move on to another sheet;
            // getClassification Name

            $getClassificationDataList = ReportsCommon::getClassificationData($yearmm, $companyId, $myUserId);

            $sheet02=4;
            foreach ($getClassificationDataList as $classificationData) {
                if ($classificationData['class_text'] == "分析対象") {
                    $spreadsheet->getSheetByName($companySheet02)->setCellValue('D'.$sheet02, $classificationData['classificationsName']);
                    $sheet02++;
                }
            }

            // companySheet02 Remove Rows

            // $getClassTextDataCount = Classification::where('class_text', '分析対象')->count();
            // $companySheet02Range = $spreadsheet->getSheetByName($companySheet02)->rangeToArray('D2:D63', null, true, true, true);
            // $companySheet02Count=count($companySheet02Range)+1;

            // $startRow02= 4+$getClassTextDataCount;

            // for ($row02=$startRow02; $row02 <= $companySheet02Count; $row02++) {
            //     $spreadsheet->getSheetByName($companySheet02)->setCellValue('D'.$row02, '');
            //     $spreadsheet->getSheetByName($companySheet02)->setCellValue('E'.$row02, '');
            //     $spreadsheet->getSheetByName($companySheet02)->setCellValue('F'.$row02, '');
            //     $spreadsheet->getSheetByName($companySheet02)->setCellValue('G'.$row02, '');
            //     $spreadsheet->getSheetByName($companySheet02)->setCellValue('H'.$row02, '');
            //     $spreadsheet->getSheetByName($companySheet02)->setCellValue('I'.$row02, '');
            //     $spreadsheet->getSheetByName($companySheet02)->setCellValue('J'.$row02, '');
            //     $spreadsheet->getSheetByName($companySheet02)->setCellValue('K'.$row02, '');
            //     $spreadsheet->getSheetByName($companySheet02)->setCellValue('L'.$row02, '');
            //     $spreadsheet->getSheetByName($companySheet02)->setCellValue('M'.$row02, '');
            //     $spreadsheet->getSheetByName($companySheet02)->setCellValue('N'.$row02, '');
            //     $spreadsheet->getSheetByName($companySheet02)->setCellValue('O'.$row02, '');
            //     $spreadsheet->getSheetByName($companySheet02)->setCellValue('P'.$row02, '');
            // }
            // $spreadsheet->getSheetByName($companySheet02)->disconnectCells();
            $sheet03=4;
            foreach ($getClassificationDataList as $classificationData) {
                if ($classificationData['class_text'] == "分類1") {
                    $spreadsheet->getSheetByName($companySheet03)->setCellValue('D'.$sheet03, $classificationData['classificationsName']);
                    $sheet03++;
                }
            }

            // companySheet03 Remove Rows

            // $getClassTextDataCount01 = Classification::where('class_text', '分類1')->count();
            // $companySheet03Range = $spreadsheet->getSheetByName($companySheet03)->rangeToArray('D2:D63', null, true, true, true);
            // $companySheet03Count=count($companySheet03Range)+1;

            // $startRow03= 4+$getClassTextDataCount01;

            // for ($row03=$startRow03; $row03 <= $companySheet03Count; $row03++) {
            //     $spreadsheet->getSheetByName($companySheet03)->setCellValue('D'.$row03, '');
            //     $spreadsheet->getSheetByName($companySheet03)->setCellValue('E'.$row03, '');
            //     $spreadsheet->getSheetByName($companySheet03)->setCellValue('F'.$row03, '');
            //     $spreadsheet->getSheetByName($companySheet03)->setCellValue('G'.$row03, '');
            //     $spreadsheet->getSheetByName($companySheet03)->setCellValue('H'.$row03, '');
            //     $spreadsheet->getSheetByName($companySheet03)->setCellValue('I'.$row03, '');
            //     $spreadsheet->getSheetByName($companySheet03)->setCellValue('J'.$row03, '');
            //     $spreadsheet->getSheetByName($companySheet03)->setCellValue('K'.$row03, '');
            //     $spreadsheet->getSheetByName($companySheet03)->setCellValue('L'.$row03, '');
            //     $spreadsheet->getSheetByName($companySheet03)->setCellValue('M'.$row03, '');
            //     $spreadsheet->getSheetByName($companySheet03)->setCellValue('N'.$row03, '');
            //     $spreadsheet->getSheetByName($companySheet03)->setCellValue('O'.$row03, '');
            //     $spreadsheet->getSheetByName($companySheet03)->setCellValue('P'.$row03, '');
            // }
            // $spreadsheet->getSheetByName($companySheet03)->disconnectCells();
            $sheet04=4;
            foreach ($getClassificationDataList as $classificationData) {
                if ($classificationData['class_text'] == "分類2") {
                    $spreadsheet->getSheetByName($companySheet04)->setCellValue('D'.$sheet04, $classificationData['classificationsName']);
                    $sheet04++;
                }
            }

            // companySheet04 Remove Rows

            // $getClassTextDataCount02 = Classification::where('class_text', '分類2')->count();
            // $companySheet04Range = $spreadsheet->getSheetByName($companySheet04)->rangeToArray('D2:D63', null, true, true, true);
            // $companySheet04Count=count($companySheet04Range)+1;

            // $startRow04= 4+$getClassTextDataCount02;

        
            // for ($row04=$startRow04; $row04 <= $companySheet04Count; $row04++) {
            //     $spreadsheet->getSheetByName($companySheet04)->setCellValue('D'.$row04, '');
            //     $spreadsheet->getSheetByName($companySheet04)->setCellValue('E'.$row04, '');
            //     $spreadsheet->getSheetByName($companySheet04)->setCellValue('F'.$row04, '');
            //     $spreadsheet->getSheetByName($companySheet04)->setCellValue('G'.$row04, '');
            //     $spreadsheet->getSheetByName($companySheet04)->setCellValue('H'.$row04, '');
            //     $spreadsheet->getSheetByName($companySheet04)->setCellValue('I'.$row04, '');
            //     $spreadsheet->getSheetByName($companySheet04)->setCellValue('J'.$row04, '');
            //     $spreadsheet->getSheetByName($companySheet04)->setCellValue('K'.$row04, '');
            //     $spreadsheet->getSheetByName($companySheet04)->setCellValue('L'.$row04, '');
            //     $spreadsheet->getSheetByName($companySheet04)->setCellValue('M'.$row04, '');
            //     $spreadsheet->getSheetByName($companySheet04)->setCellValue('N'.$row04, '');
            //     $spreadsheet->getSheetByName($companySheet04)->setCellValue('O'.$row04, '');
            //     $spreadsheet->getSheetByName($companySheet04)->setCellValue('P'.$row04, '');
            // }
            // $spreadsheet->getSheetByName($companySheet04)->disconnectCells();
            $sheet05=4;
            foreach ($getClassificationDataList as $classificationData) {
                if ($classificationData['class_text'] == "分類3") {
                    $spreadsheet->getSheetByName($companySheet05)->setCellValue('D'.$sheet05, $classificationData['classificationsName']);
                    $sheet05++;
                }
            }

            // companySheet05 Remove Rows

            // $getClassTextDataCount03 = Classification::where('class_text', '分類3')->count();
            // $companySheet05Range = $spreadsheet->getSheetByName($companySheet05)->rangeToArray('D2:D63', null, true, true, true);
            // $companySheet05Count=count($companySheet05Range)+1;

            // $startRow05= 4+$getClassTextDataCount03;
            
            // for ($row05=$startRow05; $row05 <= $companySheet05Count; $row05++) {
            //     $spreadsheet->getSheetByName($companySheet05)->setCellValue('D'.$row05, '');
            //     $spreadsheet->getSheetByName($companySheet05)->setCellValue('E'.$row05, '');
            //     $spreadsheet->getSheetByName($companySheet05)->setCellValue('F'.$row05, '');
            //     $spreadsheet->getSheetByName($companySheet05)->setCellValue('G'.$row05, '');
            //     $spreadsheet->getSheetByName($companySheet05)->setCellValue('H'.$row05, '');
            //     $spreadsheet->getSheetByName($companySheet05)->setCellValue('I'.$row05, '');
            //     $spreadsheet->getSheetByName($companySheet05)->setCellValue('J'.$row05, '');
            //     $spreadsheet->getSheetByName($companySheet05)->setCellValue('K'.$row05, '');
            //     $spreadsheet->getSheetByName($companySheet05)->setCellValue('L'.$row05, '');
            //     $spreadsheet->getSheetByName($companySheet05)->setCellValue('M'.$row05, '');
            //     $spreadsheet->getSheetByName($companySheet05)->setCellValue('N'.$row05, '');
            //     $spreadsheet->getSheetByName($companySheet05)->setCellValue('O'.$row05, '');
            //     $spreadsheet->getSheetByName($companySheet05)->setCellValue('P'.$row05, '');
            // }
            // $spreadsheet->getSheetByName($companySheet05)->disconnectCells();
            $sheet06=4;
            foreach ($getClassificationDataList as $classificationData) {
                if ($classificationData['class_text'] == "分類4") {
                    $spreadsheet->getSheetByName($companySheet06)->setCellValue('D'.$sheet06, $classificationData['classificationsName']);
                    $sheet06++;
                }
            }
            
            // companySheet06 Remove Rows

            // $getClassTextDataCount04 = Classification::where('class_text', '分類4')->count();
            // $companySheet06Range = $spreadsheet->getSheetByName($companySheet06)->rangeToArray('D2:D63', null, true, true, true);
            // $companySheet06Count=count($companySheet05Range)+1;

            // $startRow06= 4+$getClassTextDataCount04;

            // for ($row06=$startRow06; $row06 <= $companySheet06Count; $row06++) {
            //     $spreadsheet->getSheetByName($companySheet06)->setCellValue('D'.$row06, '');
            //     $spreadsheet->getSheetByName($companySheet06)->setCellValue('E'.$row06, '');
            //     $spreadsheet->getSheetByName($companySheet06)->setCellValue('F'.$row06, '');
            //     $spreadsheet->getSheetByName($companySheet06)->setCellValue('G'.$row06, '');
            //     $spreadsheet->getSheetByName($companySheet06)->setCellValue('H'.$row06, '');
            //     $spreadsheet->getSheetByName($companySheet06)->setCellValue('I'.$row06, '');
            //     $spreadsheet->getSheetByName($companySheet06)->setCellValue('J'.$row06, '');
            //     $spreadsheet->getSheetByName($companySheet06)->setCellValue('K'.$row06, '');
            //     $spreadsheet->getSheetByName($companySheet06)->setCellValue('L'.$row06, '');
            //     $spreadsheet->getSheetByName($companySheet06)->setCellValue('M'.$row06, '');
            //     $spreadsheet->getSheetByName($companySheet06)->setCellValue('N'.$row06, '');
            //     $spreadsheet->getSheetByName($companySheet06)->setCellValue('O'.$row06, '');
            //     $spreadsheet->getSheetByName($companySheet06)->setCellValue('P'.$row06, '');
            // }
            // $spreadsheet->getSheetByName($companySheet06)->disconnectCells();
            // Current Month

            $ii=3;
            foreach ($forExportDepartmentCurrentData as $currentDepartmentData) {
                $spreadsheet->getSheetByName($companySheet1)->setCellValue('B'.$ii, $currentDepartmentData['departmentsName']);
                $ii++;
            }

            $jj=3;
            foreach ($forExportClassificationCurrentData as $currentClassificationData) {
                $spreadsheet->getSheetByName($companySheet1)->setCellValue('F'.$jj, $currentClassificationData['classificationsName']);
                $jj++;
            }

            $i=3;
            foreach ($forExportDataCurrentSelectedDate as $currentData) {
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('A'.$i, $currentData['serial_number']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('B'.$i, $currentData['id']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('C'.$i, $currentData['password']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('D'.$i, $currentData['lastname']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('E'.$i, $currentData['firstname']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('F'.$i, $currentData['birth_day']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('G'.$i, $currentData['gender']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('H'.$i, $currentData['question_method']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('I'.$i, $currentData['employment_num']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('J'.$i, $currentData['employment_day']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('K'.$i, $currentData['departments_name']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('L'.$i, $currentData['directors_name']);

                $classifications = Classification::leftjoin('classifications_examinee_relation', 'classifications_examinee_relation.classification_id', '=', 'classifications.id')->where('classifications_examinee_relation.examinee_id', '=', $currentData['examinee_id'])->get()->toArray();

                foreach ($classifications as $classification) {
                    if ($classification['class_text'] == "分析対象") {
                        $spreadsheet->getSheetByName($companySheet2)->setCellValue('M'.$i, $classification['name']);
                    } elseif ($classification['class_text']=="分類1") {
                        $spreadsheet->getSheetByName($companySheet2)->setCellValue('N'.$i, $classification['name']);
                    } elseif ($classification['class_text']=="分類2") {
                        $spreadsheet->getSheetByName($companySheet2)->setCellValue('O'.$i, $classification['name']);
                    } elseif ($classification['class_text']=="分類3") {
                        $spreadsheet->getSheetByName($companySheet2)->setCellValue('P'.$i, $classification['name']);
                    } elseif ($classification['class_text']=="分類4") {
                        $spreadsheet->getSheetByName($companySheet2)->setCellValue('Q'.$i, $classification['name']);
                    }
                }
                // unset($classifications);

                $spreadsheet->getSheetByName($companySheet2)->setCellValue('R'.$i, $currentData['bjsq_a_1']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('S'.$i, $currentData['bjsq_a_2']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('T'.$i, $currentData['bjsq_a_3']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('U'.$i, $currentData['bjsq_a_4']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('V'.$i, $currentData['bjsq_a_5']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('W'.$i, $currentData['bjsq_a_6']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('X'.$i, $currentData['bjsq_a_7']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('Y'.$i, $currentData['bjsq_a_8']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('Z'.$i, $currentData['bjsq_a_9']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AA'.$i, $currentData['bjsq_a_10']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AB'.$i, $currentData['bjsq_a_11']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AC'.$i, $currentData['bjsq_a_12']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AD'.$i, $currentData['bjsq_a_13']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AE'.$i, $currentData['bjsq_a_14']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AF'.$i, $currentData['bjsq_a_15']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AG'.$i, $currentData['bjsq_a_16']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AH'.$i, $currentData['bjsq_a_17']);

                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AI'.$i, $currentData['bjsq_b_1']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AJ'.$i, $currentData['bjsq_b_2']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('Ak'.$i, $currentData['bjsq_b_3']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AL'.$i, $currentData['bjsq_b_4']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AM'.$i, $currentData['bjsq_b_5']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AN'.$i, $currentData['bjsq_b_6']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AO'.$i, $currentData['bjsq_b_7']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AP'.$i, $currentData['bjsq_b_8']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AQ'.$i, $currentData['bjsq_b_9']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AR'.$i, $currentData['bjsq_b_10']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AS'.$i, $currentData['bjsq_b_11']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AT'.$i, $currentData['bjsq_b_12']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AU'.$i, $currentData['bjsq_b_13']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AV'.$i, $currentData['bjsq_b_14']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AW'.$i, $currentData['bjsq_b_15']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AX'.$i, $currentData['bjsq_b_16']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AY'.$i, $currentData['bjsq_b_17']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('AZ'.$i, $currentData['bjsq_b_18']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BA'.$i, $currentData['bjsq_b_19']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BB'.$i, $currentData['bjsq_b_20']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BC'.$i, $currentData['bjsq_b_21']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BD'.$i, $currentData['bjsq_b_22']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BE'.$i, $currentData['bjsq_b_23']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BF'.$i, $currentData['bjsq_b_24']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BG'.$i, $currentData['bjsq_b_25']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BH'.$i, $currentData['bjsq_b_26']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BI'.$i, $currentData['bjsq_b_27']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BJ'.$i, $currentData['bjsq_b_28']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BK'.$i, $currentData['bjsq_b_29']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BL'.$i, $currentData['bjsq_c_1']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BM'.$i, $currentData['bjsq_c_2']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BN'.$i, $currentData['bjsq_c_3']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BO'.$i, $currentData['bjsq_c_4']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BP'.$i, $currentData['bjsq_c_5']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BQ'.$i, $currentData['bjsq_c_6']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BR'.$i, $currentData['bjsq_c_7']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BS'.$i, $currentData['bjsq_c_8']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BT'.$i, $currentData['bjsq_c_9']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BU'.$i, $currentData['bjsq_d_1']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BV'.$i, $currentData['bjsq_d_2']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BW'.$i, $currentData['mirror_1']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BX'.$i, $currentData['mirror_2']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BY'.$i, $currentData['mirror_3']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('BZ'.$i, $currentData['mirror_4']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CA'.$i, $currentData['mirror_5']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CB'.$i, $currentData['mirror_6']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CC'.$i, $currentData['mirror_7']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CD'.$i, $currentData['mirror_8']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CE'.$i, $currentData['mirror_9']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CF'.$i, $currentData['mirror_10']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CG'.$i, $currentData['mirror_11']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CH'.$i, $currentData['mirror_12']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CI'.$i, $currentData['mirror_13']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CJ'.$i, $currentData['mirror_14']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CK'.$i, $currentData['mirror_15']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CL'.$i, $currentData['mirror_16']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CM'.$i, $currentData['mirror_17']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CN'.$i, $currentData['mirror_18']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CO'.$i, $currentData['mirror_19']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CP'.$i, $currentData['mirror_20']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CQ'.$i, $currentData['mirror_21']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CR'.$i, $currentData['mirror_22']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CS'.$i, $currentData['mirror_23']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CT'.$i, $currentData['mirror_24']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CU'.$i, $currentData['mirror_25']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CV'.$i, $currentData['mirror_26']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CW'.$i, $currentData['mirror_27']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CX'.$i, $currentData['mirror_28']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CY'.$i, $currentData['mirror_29']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('CZ'.$i, $currentData['mirror_30']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DA'.$i, $currentData['mirror_31']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DB'.$i, $currentData['mirror_32']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DC'.$i, $currentData['mirror_33']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DD'.$i, $currentData['mirror_34']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DE'.$i, $currentData['mirror_35']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DF'.$i, $currentData['mirror_36']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DG'.$i, $currentData['mirror_37']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DH'.$i, $currentData['mirror_38']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DI'.$i, $currentData['mirror_39']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DJ'.$i, $currentData['mirror_40']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DK'.$i, $currentData['mirror_41']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DL'.$i, $currentData['mirror_42']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DM'.$i, $currentData['mirror_43']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DN'.$i, $currentData['mirror_44']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DO'.$i, $currentData['mirror_45']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DP'.$i, $currentData['personal_cal_1']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DQ'.$i, $currentData['personal_cal_2']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DR'.$i, $currentData['personal_cal_3']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DS'.$i, $currentData['personal_cal_4']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DT'.$i, $currentData['personal_cal_5']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DU'.$i, $currentData['personal_cal_6']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DV'.$i, $currentData['personal_cal_7']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DW'.$i, $currentData['personal_cal_8']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DX'.$i, $currentData['personal_cal_9']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DY'.$i, $currentData['personal_cal_10']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('DZ'.$i, $currentData['personal_cal_11']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EA'.$i, $currentData['personal_cal_12']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EB'.$i, $currentData['personal_cal_13']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EC'.$i, $currentData['personal_cal_14']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('ED'.$i, $currentData['personal_cal_15']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EE'.$i, $currentData['personal_cal_16']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EF'.$i, $currentData['personal_cal_17']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EG'.$i, $currentData['personal_cal_18']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EH'.$i, $currentData['personal_cal_19']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EI'.$i, $currentData['raw_stress_factor']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EJ'.$i, $currentData['raw_stress_response']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EK'.$i, $currentData['raw_support_factor']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EL'.$i, $currentData['total_stress_factor']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EM'.$i, $currentData['total_stress_response']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EN'.$i, $currentData['total_support_factor']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EO'.$i, $currentData['stressor']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EP'.$i, $currentData['stress_response']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EQ'.$i, $currentData['stressor_stress_response']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('ER'.$i, $currentData['judgment']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('ES'.$i, $currentData['weather_mark']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('ET'.$i, $currentData['high_stress_flg']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EU'.$i, $currentData['Interview_target_flg']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EV'.$i, $currentData['Interview_request_flg']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EW'.$i, $currentData['Interview_request_flg']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EX'.$i, $currentData['result_view_flg']);
                $spreadsheet->getSheetByName($companySheet2)->setCellValue('EY'.$i, $currentData['result_view_created_at']);
                $i++;
            }
            unset($forExportDataCurrentSelectedDate);
            // $spreadsheet->getSheetByName($companySheet2)->disconnectCells();
            // After Six Month

            $kk=3;
            foreach ($forExportDepartmentSixMonthData as $sixMonthDepartmentData) {
                $spreadsheet->getSheetByName($companySheet1)->setCellValue('I'.$kk, $sixMonthDepartmentData['departmentsName']);
                $kk++;
            }
            unset($forExportDepartmentSixMonthData);

            $l=3;
            foreach ($forExportClassificationSixMonthData as $sixMonthClassificationData) {
                $spreadsheet->getSheetByName($companySheet1)->setCellValue('M'.$l, $sixMonthClassificationData['classificationsName']);
                $l++;
            }
            unset($forExportClassificationSixMonthData);
            $j=3;

            foreach ($forExportDataLastSixMonth as $lastSixData) {
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('A'.$j, $lastSixData['serial_number']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('B'.$j, $lastSixData['id']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('C'.$j, $lastSixData['password']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('D'.$j, $lastSixData['lastname']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('E'.$j, $lastSixData['firstname']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('F'.$j, $lastSixData['birth_day']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('G'.$j, $lastSixData['gender']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('H'.$j, $lastSixData['question_method']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('I'.$j, $lastSixData['employment_num']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('J'.$j, $lastSixData['employment_day']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('K'.$j, $lastSixData['departments_name']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('L'.$j, $lastSixData['directors_name']);

                $classificationsSix = Classification::leftjoin('classifications_examinee_relation', 'classifications_examinee_relation.classification_id', '=', 'classifications.id')->where('classifications_examinee_relation.examinee_id', '=', $lastSixData['examinee_id'])->get()->toArray();
                
                foreach ($classificationsSix as $classificationSixdata) {
                    if ($classificationSixdata['class_text'] == "分析対象") {
                        $spreadsheet->getSheetByName($companySheet3)->setCellValue('M'.$j, $classificationSixdata['name']);
                    } elseif ($classificationSixdata['class_text']=="分類1") {
                        $spreadsheet->getSheetByName($companySheet3)->setCellValue('N'.$j, $classificationSixdata['name']);
                    } elseif ($classificationSixdata['class_text']=="分類2") {
                        $spreadsheet->getSheetByName($companySheet3)->setCellValue('O'.$j, $classificationSixdata['name']);
                    } elseif ($classificationSixdata['class_text']=="分類3") {
                        $spreadsheet->getSheetByName($companySheet3)->setCellValue('P'.$j, $classificationSixdata['name']);
                    } elseif ($classificationSixdata['class_text']=="分類4") {
                        $spreadsheet->getSheetByName($companySheet3)->setCellValue('Q'.$j, $classificationSixdata['name']);
                    }
                }
                // unset($classificationsSix);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('R'.$j, $lastSixData['bjsq_a_1']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('S'.$j, $lastSixData['bjsq_a_2']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('T'.$j, $lastSixData['bjsq_a_3']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('U'.$j, $lastSixData['bjsq_a_4']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('V'.$j, $lastSixData['bjsq_a_5']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('W'.$j, $lastSixData['bjsq_a_6']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('X'.$j, $lastSixData['bjsq_a_7']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('Y'.$j, $lastSixData['bjsq_a_8']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('Z'.$j, $lastSixData['bjsq_a_9']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AA'.$j, $lastSixData['bjsq_a_10']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AB'.$j, $lastSixData['bjsq_a_11']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AC'.$j, $lastSixData['bjsq_a_12']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AD'.$j, $lastSixData['bjsq_a_13']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AE'.$j, $lastSixData['bjsq_a_14']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AF'.$j, $lastSixData['bjsq_a_15']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AG'.$j, $lastSixData['bjsq_a_16']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AH'.$j, $lastSixData['bjsq_a_17']);

                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AI'.$j, $lastSixData['bjsq_b_1']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AJ'.$j, $lastSixData['bjsq_b_2']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('Ak'.$j, $lastSixData['bjsq_b_3']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AL'.$j, $lastSixData['bjsq_b_4']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AM'.$j, $lastSixData['bjsq_b_5']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AN'.$j, $lastSixData['bjsq_b_6']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AO'.$j, $lastSixData['bjsq_b_7']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AP'.$j, $lastSixData['bjsq_b_8']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AQ'.$j, $lastSixData['bjsq_b_9']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AR'.$j, $lastSixData['bjsq_b_10']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AS'.$j, $lastSixData['bjsq_b_11']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AT'.$j, $lastSixData['bjsq_b_12']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AU'.$j, $lastSixData['bjsq_b_13']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AV'.$j, $lastSixData['bjsq_b_14']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AW'.$j, $lastSixData['bjsq_b_15']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AX'.$j, $lastSixData['bjsq_b_16']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AY'.$j, $lastSixData['bjsq_b_17']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('AZ'.$j, $lastSixData['bjsq_b_18']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BA'.$j, $lastSixData['bjsq_b_19']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BB'.$j, $lastSixData['bjsq_b_20']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BC'.$j, $lastSixData['bjsq_b_21']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BD'.$j, $lastSixData['bjsq_b_22']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BE'.$j, $lastSixData['bjsq_b_23']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BF'.$j, $lastSixData['bjsq_b_24']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BG'.$j, $lastSixData['bjsq_b_25']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BH'.$j, $lastSixData['bjsq_b_26']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BI'.$j, $lastSixData['bjsq_b_27']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BJ'.$j, $lastSixData['bjsq_b_28']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BK'.$j, $lastSixData['bjsq_b_29']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BL'.$j, $lastSixData['bjsq_c_1']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BM'.$j, $lastSixData['bjsq_c_2']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BN'.$j, $lastSixData['bjsq_c_3']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BO'.$j, $lastSixData['bjsq_c_4']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BP'.$j, $lastSixData['bjsq_c_5']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BQ'.$j, $lastSixData['bjsq_c_6']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BR'.$j, $lastSixData['bjsq_c_7']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BS'.$j, $lastSixData['bjsq_c_8']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BT'.$j, $lastSixData['bjsq_c_9']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BU'.$j, $lastSixData['bjsq_d_1']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BV'.$j, $lastSixData['bjsq_d_2']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BW'.$j, $lastSixData['mirror_1']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BX'.$j, $lastSixData['mirror_2']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BY'.$j, $lastSixData['mirror_3']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('BZ'.$j, $lastSixData['mirror_4']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CA'.$j, $lastSixData['mirror_5']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CB'.$j, $lastSixData['mirror_6']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CC'.$j, $lastSixData['mirror_7']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CD'.$j, $lastSixData['mirror_8']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CE'.$j, $lastSixData['mirror_9']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CF'.$j, $lastSixData['mirror_10']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CG'.$j, $lastSixData['mirror_11']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CH'.$j, $lastSixData['mirror_12']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CI'.$j, $lastSixData['mirror_13']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CJ'.$j, $lastSixData['mirror_14']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CK'.$j, $lastSixData['mirror_15']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CL'.$j, $lastSixData['mirror_16']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CM'.$j, $lastSixData['mirror_17']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CN'.$j, $lastSixData['mirror_18']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CO'.$j, $lastSixData['mirror_19']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CP'.$j, $lastSixData['mirror_20']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CQ'.$j, $lastSixData['mirror_21']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CR'.$j, $lastSixData['mirror_22']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CS'.$j, $lastSixData['mirror_23']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CT'.$j, $lastSixData['mirror_24']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CU'.$j, $lastSixData['mirror_25']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CV'.$j, $lastSixData['mirror_26']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CW'.$j, $lastSixData['mirror_27']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CX'.$j, $lastSixData['mirror_28']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CY'.$j, $lastSixData['mirror_29']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('CZ'.$j, $lastSixData['mirror_30']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DA'.$j, $lastSixData['mirror_31']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DB'.$j, $lastSixData['mirror_32']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DC'.$j, $lastSixData['mirror_33']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DD'.$j, $lastSixData['mirror_34']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DE'.$j, $lastSixData['mirror_35']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DF'.$j, $lastSixData['mirror_36']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DG'.$j, $lastSixData['mirror_37']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DH'.$j, $lastSixData['mirror_38']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DI'.$j, $lastSixData['mirror_39']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DJ'.$j, $lastSixData['mirror_40']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DK'.$j, $lastSixData['mirror_41']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DL'.$j, $lastSixData['mirror_42']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DM'.$j, $lastSixData['mirror_43']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DN'.$j, $lastSixData['mirror_44']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DO'.$j, $lastSixData['mirror_45']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DP'.$j, $lastSixData['personal_cal_1']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DQ'.$j, $lastSixData['personal_cal_2']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DR'.$j, $lastSixData['personal_cal_3']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DS'.$j, $lastSixData['personal_cal_4']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DT'.$j, $lastSixData['personal_cal_5']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DU'.$j, $lastSixData['personal_cal_6']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DV'.$j, $lastSixData['personal_cal_7']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DW'.$j, $lastSixData['personal_cal_8']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DX'.$j, $lastSixData['personal_cal_9']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DY'.$j, $lastSixData['personal_cal_10']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('DZ'.$j, $lastSixData['personal_cal_11']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EA'.$j, $lastSixData['personal_cal_12']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EB'.$j, $lastSixData['personal_cal_13']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EC'.$j, $lastSixData['personal_cal_14']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('ED'.$j, $lastSixData['personal_cal_15']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EE'.$j, $lastSixData['personal_cal_16']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EF'.$j, $lastSixData['personal_cal_17']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EG'.$j, $lastSixData['personal_cal_18']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EH'.$j, $lastSixData['personal_cal_19']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EI'.$j, $lastSixData['raw_stress_factor']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EJ'.$j, $lastSixData['raw_stress_response']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EK'.$j, $lastSixData['raw_support_factor']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EL'.$j, $lastSixData['total_stress_factor']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EM'.$j, $lastSixData['total_stress_response']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EN'.$j, $lastSixData['total_support_factor']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EO'.$j, $lastSixData['stressor']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EP'.$j, $lastSixData['stress_response']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EQ'.$j, $lastSixData['stressor_stress_response']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('ER'.$j, $lastSixData['judgment']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('ES'.$j, $lastSixData['weather_mark']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('ET'.$j, $lastSixData['high_stress_flg']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EU'.$j, $lastSixData['Interview_target_flg']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EV'.$j, $lastSixData['Interview_request_flg']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EW'.$j, $lastSixData['Interview_request_flg']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EX'.$j, $lastSixData['result_view_flg']);
                $spreadsheet->getSheetByName($companySheet3)->setCellValue('EY'.$j, $lastSixData['result_view_created_at']);
                $j++;
            }
            unset($forExportDataLastSixMonth);

            // $spreadsheet->getSheetByName($companySheet3)->disconnectCells();
            // Twelve Month

            $m=3;
            foreach ($forExportDepartmentTwelveMonthData as $twelveMonthDepartmentData) {
                $spreadsheet->getSheetByName($companySheet1)->setCellValue('P'.$m, $twelveMonthDepartmentData['departmentsName']);
                $m++;
            }

            $n=3;
            foreach ($forExportClassificationTwelveMonthData as $twelveMonthClassificationData) {
                $spreadsheet->getSheetByName($companySheet1)->setCellValue('T'.$n, $twelveMonthClassificationData['classificationsName']);
                $n++;
            }


            $k=3;
            foreach ($forExportDataLastTwelveMonth as $lastTwelveMonthData) {
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('A'.$k, $lastTwelveMonthData['serial_number']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('B'.$k, $lastTwelveMonthData['id']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('C'.$k, $lastTwelveMonthData['password']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('D'.$k, $lastTwelveMonthData['lastname']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('E'.$k, $lastTwelveMonthData['firstname']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('F'.$k, $lastTwelveMonthData['birth_day']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('G'.$k, $lastTwelveMonthData['gender']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('H'.$k, $lastTwelveMonthData['question_method']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('I'.$k, $lastTwelveMonthData['employment_num']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('J'.$k, $lastTwelveMonthData['employment_day']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('K'.$k, $lastTwelveMonthData['departments_name']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('L'.$k, $lastTwelveMonthData['directors_name']);
                $classificationsTwelve = Classification::leftjoin('classifications_examinee_relation', 'classifications_examinee_relation.classification_id', '=', 'classifications.id')->where('classifications_examinee_relation.examinee_id', '=', $lastTwelveMonthData['examinee_id'])->get()->toArray();
                
                foreach ($classificationsTwelve as $classificationTwelvedata) {
                    if ($classificationTwelvedata['class_text'] == "分析対象") {
                        $spreadsheet->getSheetByName($companySheet4)->setCellValue('M'.$k, $classificationTwelvedata['name']);
                    } elseif ($classificationTwelvedata['class_text']=="分類1") {
                        $spreadsheet->getSheetByName($companySheet4)->setCellValue('N'.$k, $classificationTwelvedata['name']);
                    } elseif ($classificationTwelvedata['class_text']=="分類2") {
                        $spreadsheet->getSheetByName($companySheet4)->setCellValue('O'.$k, $classificationTwelvedata['name']);
                    } elseif ($classificationTwelvedata['class_text']=="分類3") {
                        $spreadsheet->getSheetByName($companySheet4)->setCellValue('P'.$k, $classificationTwelvedata['name']);
                    } elseif ($classificationTwelvedata['class_text']=="分類4") {
                        $spreadsheet->getSheetByName($companySheet4)->setCellValue('Q'.$k, $classificationTwelvedata['name']);
                    }
                }
                // unset($classificationsTwelve);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('R'.$k, $lastTwelveMonthData['bjsq_a_1']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('S'.$k, $lastTwelveMonthData['bjsq_a_2']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('T'.$k, $lastTwelveMonthData['bjsq_a_3']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('U'.$k, $lastTwelveMonthData['bjsq_a_4']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('V'.$k, $lastTwelveMonthData['bjsq_a_5']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('W'.$k, $lastTwelveMonthData['bjsq_a_6']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('X'.$k, $lastTwelveMonthData['bjsq_a_7']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('Y'.$k, $lastTwelveMonthData['bjsq_a_8']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('Z'.$k, $lastTwelveMonthData['bjsq_a_9']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AA'.$k, $lastTwelveMonthData['bjsq_a_10']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AB'.$k, $lastTwelveMonthData['bjsq_a_11']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AC'.$k, $lastTwelveMonthData['bjsq_a_12']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AD'.$k, $lastTwelveMonthData['bjsq_a_13']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AE'.$k, $lastTwelveMonthData['bjsq_a_14']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AF'.$k, $lastTwelveMonthData['bjsq_a_15']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AG'.$k, $lastTwelveMonthData['bjsq_a_16']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AH'.$k, $lastTwelveMonthData['bjsq_a_17']);

                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AI'.$k, $lastTwelveMonthData['bjsq_b_1']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AJ'.$k, $lastTwelveMonthData['bjsq_b_2']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('Ak'.$k, $lastTwelveMonthData['bjsq_b_3']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AL'.$k, $lastTwelveMonthData['bjsq_b_4']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AM'.$k, $lastTwelveMonthData['bjsq_b_5']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AN'.$k, $lastTwelveMonthData['bjsq_b_6']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AO'.$k, $lastTwelveMonthData['bjsq_b_7']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AP'.$k, $lastTwelveMonthData['bjsq_b_8']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AQ'.$k, $lastTwelveMonthData['bjsq_b_9']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AR'.$k, $lastTwelveMonthData['bjsq_b_10']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AS'.$k, $lastTwelveMonthData['bjsq_b_11']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AT'.$k, $lastTwelveMonthData['bjsq_b_12']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AU'.$k, $lastTwelveMonthData['bjsq_b_13']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AV'.$k, $lastTwelveMonthData['bjsq_b_14']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AW'.$k, $lastTwelveMonthData['bjsq_b_15']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AX'.$k, $lastTwelveMonthData['bjsq_b_16']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AY'.$k, $lastTwelveMonthData['bjsq_b_17']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('AZ'.$k, $lastTwelveMonthData['bjsq_b_18']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BA'.$k, $lastTwelveMonthData['bjsq_b_19']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BB'.$k, $lastTwelveMonthData['bjsq_b_20']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BC'.$k, $lastTwelveMonthData['bjsq_b_21']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BD'.$k, $lastTwelveMonthData['bjsq_b_22']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BE'.$k, $lastTwelveMonthData['bjsq_b_23']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BF'.$k, $lastTwelveMonthData['bjsq_b_24']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BG'.$k, $lastTwelveMonthData['bjsq_b_25']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BH'.$k, $lastTwelveMonthData['bjsq_b_26']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BI'.$k, $lastTwelveMonthData['bjsq_b_27']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BJ'.$k, $lastTwelveMonthData['bjsq_b_28']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BK'.$k, $lastTwelveMonthData['bjsq_b_29']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BL'.$k, $lastTwelveMonthData['bjsq_c_1']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BM'.$k, $lastTwelveMonthData['bjsq_c_2']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BN'.$k, $lastTwelveMonthData['bjsq_c_3']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BO'.$k, $lastTwelveMonthData['bjsq_c_4']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BP'.$k, $lastTwelveMonthData['bjsq_c_5']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BQ'.$k, $lastTwelveMonthData['bjsq_c_6']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BR'.$k, $lastTwelveMonthData['bjsq_c_7']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BS'.$k, $lastTwelveMonthData['bjsq_c_8']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BT'.$k, $lastTwelveMonthData['bjsq_c_9']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BU'.$k, $lastTwelveMonthData['bjsq_d_1']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BV'.$k, $lastTwelveMonthData['bjsq_d_2']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BW'.$k, $lastTwelveMonthData['mirror_1']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BX'.$k, $lastTwelveMonthData['mirror_2']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BY'.$k, $lastTwelveMonthData['mirror_3']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('BZ'.$k, $lastTwelveMonthData['mirror_4']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CA'.$k, $lastTwelveMonthData['mirror_5']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CB'.$k, $lastTwelveMonthData['mirror_6']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CC'.$k, $lastTwelveMonthData['mirror_7']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CD'.$k, $lastTwelveMonthData['mirror_8']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CE'.$k, $lastTwelveMonthData['mirror_9']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CF'.$k, $lastTwelveMonthData['mirror_10']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CG'.$k, $lastTwelveMonthData['mirror_11']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CH'.$k, $lastTwelveMonthData['mirror_12']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CI'.$k, $lastTwelveMonthData['mirror_13']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CJ'.$k, $lastTwelveMonthData['mirror_14']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CK'.$k, $lastTwelveMonthData['mirror_15']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CL'.$k, $lastTwelveMonthData['mirror_16']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CM'.$k, $lastTwelveMonthData['mirror_17']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CN'.$k, $lastTwelveMonthData['mirror_18']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CO'.$k, $lastTwelveMonthData['mirror_19']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CP'.$k, $lastTwelveMonthData['mirror_20']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CQ'.$k, $lastTwelveMonthData['mirror_21']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CR'.$k, $lastTwelveMonthData['mirror_22']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CS'.$k, $lastTwelveMonthData['mirror_23']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CT'.$k, $lastTwelveMonthData['mirror_24']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CU'.$k, $lastTwelveMonthData['mirror_25']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CV'.$k, $lastTwelveMonthData['mirror_26']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CW'.$k, $lastTwelveMonthData['mirror_27']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CX'.$k, $lastTwelveMonthData['mirror_28']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CY'.$k, $lastTwelveMonthData['mirror_29']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('CZ'.$k, $lastTwelveMonthData['mirror_30']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DA'.$k, $lastTwelveMonthData['mirror_31']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DB'.$k, $lastTwelveMonthData['mirror_32']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DC'.$k, $lastTwelveMonthData['mirror_33']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DD'.$k, $lastTwelveMonthData['mirror_34']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DE'.$k, $lastTwelveMonthData['mirror_35']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DF'.$k, $lastTwelveMonthData['mirror_36']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DG'.$k, $lastTwelveMonthData['mirror_37']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DH'.$k, $lastTwelveMonthData['mirror_38']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DI'.$k, $lastTwelveMonthData['mirror_39']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DJ'.$k, $lastTwelveMonthData['mirror_40']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DK'.$k, $lastTwelveMonthData['mirror_41']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DL'.$k, $lastTwelveMonthData['mirror_42']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DM'.$k, $lastTwelveMonthData['mirror_43']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DN'.$k, $lastTwelveMonthData['mirror_44']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DO'.$k, $lastTwelveMonthData['mirror_45']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DP'.$k, $lastTwelveMonthData['personal_cal_1']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DQ'.$k, $lastTwelveMonthData['personal_cal_2']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DR'.$k, $lastTwelveMonthData['personal_cal_3']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DS'.$k, $lastTwelveMonthData['personal_cal_4']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DT'.$k, $lastTwelveMonthData['personal_cal_5']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DU'.$k, $lastTwelveMonthData['personal_cal_6']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DV'.$k, $lastTwelveMonthData['personal_cal_7']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DW'.$k, $lastTwelveMonthData['personal_cal_8']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DX'.$k, $lastTwelveMonthData['personal_cal_9']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DY'.$k, $lastTwelveMonthData['personal_cal_10']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('DZ'.$k, $lastTwelveMonthData['personal_cal_11']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EA'.$k, $lastTwelveMonthData['personal_cal_12']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EB'.$k, $lastTwelveMonthData['personal_cal_13']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EC'.$k, $lastTwelveMonthData['personal_cal_14']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('ED'.$k, $lastTwelveMonthData['personal_cal_15']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EE'.$k, $lastTwelveMonthData['personal_cal_16']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EF'.$k, $lastTwelveMonthData['personal_cal_17']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EG'.$k, $lastTwelveMonthData['personal_cal_18']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EH'.$k, $lastTwelveMonthData['personal_cal_19']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EI'.$k, $lastTwelveMonthData['raw_stress_factor']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EJ'.$k, $lastTwelveMonthData['raw_stress_response']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EK'.$k, $lastTwelveMonthData['raw_support_factor']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EL'.$k, $lastTwelveMonthData['total_stress_factor']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EM'.$k, $lastTwelveMonthData['total_stress_response']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EN'.$k, $lastTwelveMonthData['total_support_factor']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EO'.$k, $lastTwelveMonthData['stressor']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EP'.$k, $lastTwelveMonthData['stress_response']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EQ'.$k, $lastTwelveMonthData['stressor_stress_response']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('ER'.$k, $lastTwelveMonthData['judgment']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('ES'.$k, $lastTwelveMonthData['weather_mark']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('ET'.$k, $lastTwelveMonthData['high_stress_flg']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EU'.$k, $lastTwelveMonthData['Interview_target_flg']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EV'.$k, $lastTwelveMonthData['Interview_request_flg']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EW'.$k, $lastTwelveMonthData['Interview_request_flg']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EX'.$k, $lastTwelveMonthData['result_view_flg']);
                $spreadsheet->getSheetByName($companySheet4)->setCellValue('EY'.$k, $lastTwelveMonthData['result_view_created_at']);
                
                $k++;
            }
            unset($forExportDataLastTwelveMonth);

            // $spreadsheet->getSheetByName($companySheet4)->disconnectCells();

            $fileName = $request->yearmm.'_集団分析_'.$companyId.'.xlsx';
            $writer = new Xlsx($spreadsheet);

            ob_start();
            $writer->save('php://output');
            $content = ob_get_contents();
            ob_end_clean();
            // $writer->save(storage_path()."/".$fileName);

            // $spreadsheet->disconnectWorksheets(); // erase all cache acquired by the workbook
            // unset($spreadsheet);
    
            Storage::disk('s3')->put($fileName, $content, array('ACL' => 'public-read'));
            // This will check if file already exists in S3 Bucket
            if (Storage::disk('s3')->exists($fileName)) {
                // This will return the file URL
                $excelReportUrl = Storage::url($fileName);
                $company = Company::where([['yearmm','=',$request->yearmm],['fixed_company_id','=',$request->fixed_company_id]])->firstOrFail();
                /* Desc: ASAKA-81- Excel write from Template read from local storage.
                Remove update query in company table excel_report_url column
                */
                $company->generate_excel_report_url = $excelReportUrl;
                $company->save();
            }
            
            return response()->json(['result' => true], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' =>$e->getMessage()], 400);
        }
    }
    
     
    
    /**
    * Create Marksheet Excel Report.
    *
    * @return \Illuminate\Http\Response
    */
    public function reportCompanyMarksheetExcel(Request $request, $myUserId)
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
      

        //Company details for sheet1 data
        $companyDetails = Company::select('exam_start', 'exam_end', 'name')->where('fixed_company_id', $companyId)->where('yearmm', $request->yearmm)->firstOrFail();
        
        $convertedDates = $this->dateConversion($companyDetails->exam_start, $companyDetails->exam_end);

        // Examinee & Answers Sheet Data
        $forExportDataCurrentSelectedDate = $this->getExaminedetails($request->yearmm, $companyId, $myUserId, $convertedDates, $companyDetails['name']);
        /*
            Name: AGTECHPRO - Asaka API Portal
            Date: 04/02/2022
            Desc: ASAKA-81- csv write from Template read from local storage.
            Note: Previous used code committed now, Once all working fine, we will needs to remove the code
            Release: Asaka API Portal
        */

        $inputFileName = storage_path()."/marksheet_excel_report_template.csv";
 
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

        $spreadsheet = $reader->load($inputFileName);
        $spreadsheet->getSheet(0)->fromArray($forExportDataCurrentSelectedDate, null, 'A2', true);

        $fileName = $request->yearmm.'_マークシート_'.$companyId.'.csv';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);

        ob_start();
        $writer->setUseBOM(false);
        $writer->setOutputEncoding('SJIS-WIN');
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
    public function getExaminedetails($yearmm, $companyId, $myUserId, $convertedDates, $company_name)
    {
        $searchQuery = array('users.role' => 5);
        if (!empty($companyId)) {
            $searchQuery = array('users.role' => 5, 'users.fixed_company_id' => $companyId,'examinee.question_method' =>config('constants.QUESTION_METHOD.MS'));
        }

        if (isset($yearmm) && !empty($yearmm)) {
            $searchQuery = array_merge($searchQuery, array('examinee.yearmm' => $yearmm));
        }
     
        $userDetails = User::select('users.id', 'examinee.lastname', 'examinee.firstname', 'examinee.employment_num', 'examinee.serial_number', 'departments.name', 'departments.ms_submission')
            ->where($searchQuery)
            ->leftjoin('examinee', 'examinee.user_id', '=', 'users.id')
            ->leftjoin('departments', 'departments.id', '=', 'examinee.department_id')
            ->groupBy('users.id')
            ->get()
            ->toArray();
        $forExportDataCurrentSelectedDate = [];
        foreach ($userDetails as $key=>$value) {
            $value['company_name'] = $company_name;
            $value['exam_end_date'] = $convertedDates['endDate'];
            $value['exam_end_day_name'] = $convertedDates['examEndDayName'];
            // $inserted = array($key+1);
            // array_splice( $value, 0, 0, $inserted);
            array_push($forExportDataCurrentSelectedDate, $value);
        }
        return $forExportDataCurrentSelectedDate;
    }
    
    /**
     * write user log info to the xlsm file.
     */
    public function createLogInfo(Request $request, $myId)
    {
        try {
            $userRole = User::findOrFail($myId);
            if ($userRole->role != 1) {
                $companyId = $userRole->fixed_company_id;
            } else {
                $validated = $request->validate([
                    'fixed_company_id' => 'required',
                ]);
                $companyId = $request->fixed_company_id;
            }
        
            $fields = [
            'users.id',
            'users.password',
            'users.fixed_company_id',
            'examinee.yearmm'
            ];
            $whereNull = [['users.role',5],['users.email',null],['examinee.question_method',config('constants.QUESTION_METHOD.WEB')]];
            $wherePost = [['users.role',5],['examinee.notification_type',config('constants.NOTIFICATION_TYPE.POST')],['examinee.question_method',config('constants.QUESTION_METHOD.WEB')]];

            if ($request->has('yearmm') && $request->yearmm != null) {
                $whereNull = array_merge($whereNull, [['examinee.yearmm', $request->yearmm]]);
                $wherePost = array_merge($wherePost, [['examinee.yearmm', $request->yearmm]]);
            }
           
            $userDetails =  User::select($fields)->where($whereNull)->orWhere($wherePost)->where('users.fixed_company_id', $companyId)->rightjoin('examinee', 'examinee.user_id', '=', 'users.id')->orderBy('examinee.yearmm', 'DESC')->get();
            $company = Company::where('fixed_company_id', $companyId)->where('yearmm', $request->yearmm)->firstOrFail();
            $convertedDates = $this->dateConversion($company->exam_start, $company->exam_end);
            $examinees = [];
            foreach ($userDetails as $key => $value) {
                $examinees[$key]['prg_no'] = $key+1;
                $examinees[$key]['id'] = $value->id;
                $examinees[$key]['password'] = $value->password;
                    
                foreach ($value->examinee as $keyx => $valuex) {
                    $examinees[$key]['ex_lastname'] = $valuex->lastname;
                    $examinees[$key]['ex_firstname'] = $valuex->firstname;
                    $examinees[$key]['ex_sr_no'] = $valuex->serial_number;
                    $examinees[$key]['departmant_name'] = $valuex->department->name;
                }
                $examinees[$key]['company_name'] = $company->name;
                $examinees[$key]['startDate'] = $convertedDates['startDate'];
                $examinees[$key]['examStartDayName'] = $convertedDates['examStartDayName'];
                $examinees[$key]['endDate'] = $convertedDates['endDate'];
                $examinees[$key]['examEndDayName'] = $convertedDates['examEndDayName'];
            }
            $inputFileName = storage_path()."/login_info_excel_report_template.csv";
        
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

            $spreadsheet = $reader->load($inputFileName);
            $spreadsheet->getSheet(0)->fromArray($examinees, null, 'A2', true);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
            $fileName = $request->yearmm.'_通知書_'.$companyId.'.csv';

            ob_start();
            $writer->setUseBOM(false);
            $writer->setOutputEncoding('SJIS-WIN');
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
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' =>$e->getMessage()], 400);
        }
    }

    public function dateConversion($exam_start, $exam_end)
    {
        $days = array('日', '月', '火', '水', '木', '金', '土');

        $timestampStartDay = strtotime($exam_start);
        $exam_start_date = date("Y年m月d日", $timestampStartDay);
        $exam_start_day = date('l', $timestampStartDay);
        $day_of_week1 = date('N', strtotime($exam_start_day));
        $exam_start_day_name = $days[$day_of_week1] . '曜日';

        $timestampEndDay = strtotime($exam_end);
        $exam_end_date = date("Y年m月d日", $timestampEndDay);
        $exam_end_day = date('l', $timestampEndDay);
        $day_of_week2 = date('N', strtotime($exam_end_day));
        $exam_end_day_name = $days[$day_of_week2] . '曜日';
        return [
            'startDate' => $exam_start_date,
            'endDate' => $exam_end_date,
            'examStartDayName' => $exam_start_day_name,
            'examEndDayName' => $exam_end_day_name
        ];
    }

    /**
     * Convert company excel file to pdf format
    */
    public function createCompanyPDF(Request $request, $myId)
    {
        $array = $request->validate([
            "yearmm"=>'required',
            "fixed_company_id"=>'required',
            "pdf_file"=> 'required|mimes:pdf,zip'
        ]);
        $file_ext = $request->file('pdf_file')->extension();
        $company = Company::where([['yearmm','=',$request->yearmm],['fixed_company_id','=',$request->fixed_company_id]])->firstOrFail();
        try {
            $pdfFileName = $request->yearmm.'_集団分析PDF_'.$company->fixed_company_id.'.'.$file_ext;
            Storage::disk('s3')->put($pdfFileName, $request->file('pdf_file')->get(), array('ACL' => 'public-read'));
            // This will check if file already exists in S3 Bucket
            if (Storage::disk('s3')->exists($pdfFileName)) {
                // This will return the file URL
                $excelReportUrl = Storage::url($pdfFileName);
            }
            $company->pdf_report_url = $excelReportUrl;
            $company->save();
            $companyCount = Company::whereNotNull('companies.pdf_report_url')->join('users', 'users.fixed_company_id', '=', 'companies.fixed_company_id')->join('examinee', 'examinee.user_id', '=', 'users.id')->where([['examinee.yearmm','=',$request->yearmm],['companies.yearmm', '=', $request->yearmm],['examinee.Interview_request_flg','=', '0']])->count();
            if ($companyCount > 0) {
                $result_flg = CompanyStatusUpdateCommon::updateStatus($request->fixed_company_id, 'COMPANY_PDF_UPLOAD', $request->yearmm);
            }
            return response()->json(['result' => true], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => trans('validation.custom.Something_went_wrong_Please_try_again')], 400);
        }
    }

    /**
     * Convert company excel file to pdf format
    */
    public function uploadScheduleReport(Request $request, $myId)
    {
        $array = $request->validate([
            "yearmm"=>'required',
            "fixed_company_id"=>'required',
            "excel_file"=> 'required|mimes:xlsx'
        ]);
        $company = Company::where([['yearmm','=',$request->yearmm],['fixed_company_id','=',$request->fixed_company_id]])->firstOrFail();
        try {
            $FileName = $request->yearmm.'_スケジュール_'.$company->fixed_company_id.'.xlsx';
            Storage::disk('s3')->put($FileName, $request->file('excel_file')->get(), array('ACL' => 'public-read'));
            // This will check if file already exists in S3 Bucket
            if (Storage::disk('s3')->exists($FileName)) {
                // This will return the file URL
                $excelReportUrl = Storage::url($FileName);
            }
            $company->schedule_excel_url = $excelReportUrl;
            $company->save();
            return response()->json(['result' => true], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => trans('validation.custom.Something_went_wrong_Please_try_again')], 400);
        }
    }

    /**
    * Prepare quick chart
    */
    public function quickchart(Request $request, $myId)
    {
        $qc = new QuickChart(array(
            'width' => 600,
            'height' => 300,
          ));
          
        $qc->setConfig('{
            "type": "line",
            "data": {
              "labels": [ "January", "February", "March", "April", "May", "June", "July"
              ],
              "datasets": [
                {
                  "label": "My data",
                  "fillColor": "rgba(220,220,220,0.5)",
                  "strokeColor": "rgba(220,220,220,1)",
                  "pointColor": "rgba(220,220,220,1)",
                  "pointStrokeColor": "#fff",
                  "data": [ 65, 59, 90, 81, 56, 55, 40 ],
                  "bezierCurve": false
                }
              ]
            }
          }');
        $QuickchartUrl = $qc->getUrl();
        $client = new Client();
        $qickchartImageName = '/quickchart/image'.rand().'.png';
        $client->request('GET', $QuickchartUrl, ['sink' => public_path($qickchartImageName)]);
        $getImg = $this->img_enc_base64(public_path($qickchartImageName));
        return response()->json(['result' => $getImg], 200);
    }

    /**
    * get the data uri for the file you want to.
    */
    public function img_enc_base64($filepath)
    {
        if (file_exists($filepath)) {
            $filetype = pathinfo($filepath, PATHINFO_EXTENSION);
            $get_img = file_get_contents($filepath);
            return 'data:image/' . $filetype . ';base64,' . base64_encode($get_img);
        }
    }


    /*
        Name: AGTECHPRO - Asaka API Portal
        Date: 23/03/2022
        Desc: ASAKA-143 API POST /report/personal/{examinee_id}
        Note: Call docurain api with json request and download pdf report
        Release: Asaka API Portal
    */
    public function docurainApi($examineeID)
    {
        $examineeA = Examinee::with('answers')->where('id', $examineeID)->orderBy('yearmm', 'DESC')->first();
        $examineeB = Examinee::with('answers')->where([['user_id',$examineeA->user_id], ['yearmm','<',$examineeA->yearmm]])->orderBy('yearmm', 'DESC')->first();

        $person_A_1 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_1) == true ? 0 : $examineeA->answers->personal_cal_1) : 0;
        $person_A_2 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_2) == true ? 0 : $examineeA->answers->personal_cal_2) : 0;
        $person_A_3 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_3) == true ? 0 : $examineeA->answers->personal_cal_3) : 0;
        $person_A_4 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_4) == true ? 0 : $examineeA->answers->personal_cal_4) : 0;
        $person_A_5 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_5) == true ? 0 : $examineeA->answers->personal_cal_5) : 0;
        $person_A_6 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_6) == true ? 0 : $examineeA->answers->personal_cal_6) : 0;
        $person_A_7 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_7) == true ? 0 : $examineeA->answers->personal_cal_7) : 0;
        $person_A_8 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_8) == true ? 0 : $examineeA->answers->personal_cal_8) : 0;
        $person_A_9 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_9) == true ? 0 : $examineeA->answers->personal_cal_9) : 0;
        $person_A_10 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_10) == true ? 0 : $examineeA->answers->personal_cal_10) : 0;
        $person_A_11 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_11) == true ? 0 : $examineeA->answers->personal_cal_11) : 0;
        $person_A_12 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_12) == true ? 0 : $examineeA->answers->personal_cal_12) : 0;
        $person_A_13 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_13) == true ? 0 : $examineeA->answers->personal_cal_13) : 0;
        $person_A_14 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_14) == true ? 0 : $examineeA->answers->personal_cal_14) : 0;
        $person_A_15 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_15) == true ? 0 : $examineeA->answers->personal_cal_15) : 0;
        $person_A_16 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_16) == true ? 0 : $examineeA->answers->personal_cal_16) : 0;
        $person_A_17 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_17) == true ? 0 : $examineeA->answers->personal_cal_17) : 0;
        $person_A_18 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_18) == true ? 0 : $examineeA->answers->personal_cal_18) : 0;
        $person_A_19 = !empty($examineeA->answers) == true ? (is_null($examineeA->answers->personal_cal_19) == true ? 0 : $examineeA->answers->personal_cal_19) : 0;

        $person_A_sum_1_9 = ($person_A_1 + $person_A_1 + $person_A_1 + $person_A_1 + $person_A_1 + $person_A_1 + $person_A_1 + $person_A_1 + $person_A_1);
        $person_A_sum_10_15 = ($person_A_10 + $person_A_11 + $person_A_12 + $person_A_13 + $person_A_14 + $person_A_15);
        $person_A_sum_16_19 = ($person_A_16 + $person_A_17 + $person_A_18 + $person_A_19);


        $person_B_1 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_1) == true ? 0 : $examineeB->answers->personal_cal_1) : 0) : 0;
        $person_B_2 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_2) == true ? 0 : $examineeB->answers->personal_cal_2) : 0) : 0;
        $person_B_3 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_3) == true ? 0 : $examineeB->answers->personal_cal_3) : 0) : 0;
        $person_B_4 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_4) == true ? 0 : $examineeB->answers->personal_cal_4) : 0) : 0;
        $person_B_5 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_5) == true ? 0 : $examineeB->answers->personal_cal_5) : 0) : 0;
        $person_B_6 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_6) == true ? 0 : $examineeB->answers->personal_cal_6) : 0) : 0;
        $person_B_7 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_7) == true ? 0 : $examineeB->answers->personal_cal_7) : 0) : 0;
        $person_B_8 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_8) == true ? 0 : $examineeB->answers->personal_cal_8) : 0) : 0;
        $person_B_9 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_9) == true ? 0 : $examineeB->answers->personal_cal_9) : 0) : 0;
        $person_B_10 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_10) == true ? 0 : $examineeB->answers->personal_cal_10) : 0) : 0;
        $person_B_11 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_11) == true ? 0 : $examineeB->answers->personal_cal_11) : 0) : 0;
        $person_B_12 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_12) == true ? 0 : $examineeB->answers->personal_cal_12) : 0) : 0;
        $person_B_13 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_13) == true ? 0 : $examineeB->answers->personal_cal_13) : 0) : 0;
        $person_B_14 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_14) == true ? 0 : $examineeB->answers->personal_cal_14) : 0) : 0;
        $person_B_15 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_15) == true ? 0 : $examineeB->answers->personal_cal_15) : 0) : 0;
        $person_B_16 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_16) == true ? 0 : $examineeB->answers->personal_cal_16) : 0) : 0;
        $person_B_17 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_17) == true ? 0 : $examineeB->answers->personal_cal_17) : 0) : 0;
        $person_B_18 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_18) == true ? 0 : $examineeB->answers->personal_cal_18) : 0) : 0;
        $person_B_19 = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? (is_null($examineeB->answers->personal_cal_19) == true ? 0 : $examineeB->answers->personal_cal_19) : 0) : 0;

        $person_B_sum_1_9 = ($person_B_1 + $person_B_1 + $person_B_1 + $person_B_1 + $person_B_1 + $person_B_1 + $person_B_1 + $person_B_1 + $person_B_1);
        $person_B_sum_10_15 = ($person_B_10 + $person_B_11 + $person_B_12 + $person_B_13 + $person_B_14 + $person_B_15);
        $person_B_sum_16_19 = ($person_B_16 + $person_B_17 + $person_B_18 + $person_B_19);
        
        $company_A = Company::getCompanyBYyearmm($examineeA->yearmm, $examineeA->user->fixed_company_id);
        $a_stress_factor = !empty($examineeA) == true ? (!empty($examineeA->answers) == true ? ($company_A->criteria_type == '素点' ? $examineeA->answers->raw_stress_factor : $examineeA->answers->total_stress_factor):0):0;
        $a_stress_response = !empty($examineeA) == true ? (!empty($examineeA->answers) == true ? ($company_A->criteria_type == '素点' ? $examineeA->answers->raw_stress_response : $examineeA->answers->total_stress_factor):0):0;
        $a_support_factor = !empty($examineeA) == true ? (!empty($examineeA->answers) == true ? ($company_A->criteria_type == '素点' ? $examineeA->answers->raw_support_factor : $examineeA->answers->total_support_factor):0):0;

        if (!empty($examineeB)) {
            $company_B = Company::getCompanyBYyearmm($examineeB->yearmm, $examineeB->user->fixed_company_id);
            $b_stress_factor = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? ($company_B->criteria_type == '素点' ? $examineeB->answers->raw_stress_factor : $examineeB->answers->total_stress_factor):0):0;
            $b_stress_response = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? ($company_B->criteria_type == '素点' ? $examineeB->answers->raw_stress_response : $examineeB->answers->total_stress_factor):0):0;
            $b_support_factor = !empty($examineeB) == true ? (!empty($examineeB->answers) == true ? ($company_B->criteria_type == '素点' ? $examineeB->answers->raw_support_factor : $examineeB->answers->total_support_factor):0):0;
        }

        $personal_message = PersonalReportMessage::getTitle($examineeA->answers->judgment, $examineeA->language);

        $setDoctorINterview = $examineeA->Interview_target_flg == true ? ($examineeA->language == 'JA' ? 'あなたは医師面接指導の対象となりました。' : $examineeA->department->problem_interview) : ($examineeA->language == 'JA' ? 'あなたは医師面接指導の対象となりませんでした。' : $examineeA->department->no_problem_interview);
        $intervireStatus = $examineeA->Interview_target_flg == true ? $examineeA->department->problem_interview : $examineeA->department->no_problem_interview;
        switch ($examineeA->answers->weather_mark) {
            case '晴':
                $water_mark1 = $this->img_enc_base64(public_path('/personal_report/晴.png'));
                break;
            case '曇':
                $water_mark1 = $this->img_enc_base64(public_path('/personal_report/雲.png'));
                break;
            case '雨':
                $water_mark1 = $this->img_enc_base64(public_path('/personal_report/雨.png'));
                break;
            case '雷':
                $water_mark1 = $this->img_enc_base64(public_path('/personal_report/雷.png'));
                break;
            case null:
                $water_mark1 = $this->img_enc_base64(public_path('/personal_report/はてな.png'));
                break;
            case '?':
                $water_mark1 = $this->img_enc_base64(public_path('/personal_report/はてな.png'));
        }
        switch ($examineeB->answers->weather_mark) {
            case '晴':
                $water_mark2 = $this->img_enc_base64(public_path('/personal_report/晴.png'));
                break;
            case '曇':
                $water_mark2 = $this->img_enc_base64(public_path('/personal_report/雲.png'));
                break;
            case '雨':
                $water_mark2 = $this->img_enc_base64(public_path('/personal_report/雨.png'));
                break;
            case '雷':
                $water_mark2 = $this->img_enc_base64(public_path('/personal_report/雷.png'));
                break;
            case null:
                $water_mark2 = $this->img_enc_base64(public_path('/personal_report/はてな.png'));
                break;
            case '?':
                $water_mark2 = $this->img_enc_base64(public_path('/personal_report/はてな.png'));
        }
        if ($examineeA->language == 'JA' && $company_A->criteria_type == '素点') {
            $stress_img = $this->img_enc_base64(public_path('/personal_report/結果の見方_素点・日本語.png'));
        } elseif ($examineeA->language == 'JA' && $company_A->criteria_type == '合計') {
            $stress_img = $this->img_enc_base64(public_path('/personal_report/結果の見方_合計・日本語.png'));
        } elseif ($examineeA->language == 'EN' && $company_A->criteria_type == '素点') {
            $stress_img = $this->img_enc_base64(public_path('/personal_report/結果の見方_素点・英語.png'));
        } elseif ($examineeA->language == 'EN' && $company_A->criteria_type == '合計') {
            $stress_img = $this->img_enc_base64(public_path('/personal_report/結果の見方_合計・英語.png'));
        }

        $self_care_img =  $examineeA->language == 'JA' ? $this->img_enc_base64(public_path('/personal_report/セルフケア資料_20190725リサイズ.png')) : $this->img_enc_base64(public_path('/personal_report/セルフケア資料en_20190725リサイズ.png'));
        $label_img =  $examineeA->language == 'JA' ? $this->img_enc_base64(public_path('/personal_report/ワンポイントアドバイス.png')) : $this->img_enc_base64(public_path('/personal_report/ワンポイントアドバイス_英語.png'));

        $data = [
            "番号" => $examineeA->user->id,
            "記号" => $examineeA->serial_number,
            "会社名" => $company_A->name,
            "部署名" => $examineeA->department->name,
            "名前" => $examineeA->user->lastname . ' ' . $examineeA->user->firstname,
            "実施年月日" => $examineeA->answers->created_at,
            "A_a_今回" => $person_A_1,
            "A_a_前回" => $person_B_1,
            "A_b_今回" => $person_A_2,
            "A_b_前回" => $person_B_2,
            "A_c_今回" => $person_A_3,
            "A_c_前回" => $person_B_3,
            "A_d_今回" => $person_A_4,
            "A_d_前回" => $person_B_4,
            "A_e_今回" => $person_A_5,
            "A_e_前回" => $person_B_5,
            "A_f_今回" => $person_A_6,
            "A_f_前回" => $person_B_6,
            "A_g_今回" => $person_A_7,
            "A_g_前回" => $person_B_7,
            "A_h_今回" => $person_A_8,
            "A_h_前回" => $person_B_8,
            "A_i_今回" => $person_A_9,
            "A_i_前回" => $person_B_9,
            "A_評価点_今回" => $person_A_sum_1_9,
            "A_評価点_前回" => $person_B_sum_1_9,
            "B_a_今回" => $person_A_10,
            "B_a_前回" => $person_B_10,
            "B_b_今回" => $person_A_11,
            "B_b_前回" => $person_B_11,
            "B_c_今回" => $person_A_12,
            "B_c_前回" => $person_B_12,
            "B_d_今回" => $person_A_13,
            "B_d_前回" => $person_B_13,
            "B_e_今回" => $person_A_14,
            "B_e_前回" => $person_B_14,
            "B_f_今回" => $person_A_15,
            "B_f_前回" => $person_B_15,
            "B_評価点_今回" => $person_A_sum_10_15,
            "B_評価点_前回" => $person_B_sum_10_15,
            "C_a_今回" => $person_A_16,
            "C_a_前回" => $person_B_16,
            "C_b_今回" => $person_A_17,
            "C_b_前回" => $person_B_17,
            "C_c_今回" => $person_A_18,
            "C_c_前回" => $person_B_18,
            "C_d_今回" => $person_A_19,
            "C_d_前回" => $person_B_19,
            "C_評価点_今回" => $person_A_sum_16_19,
            "C_評価点_前回" => $person_B_sum_16_19,
            "評価結果_Ａ_今回" => $a_stress_factor,
            "評価結果_Ａ_前回" => isset($b_stress_factor) == true ? $b_stress_factor : 0,
            "評価結果_Ｂ_今回" => $a_stress_response,
            "評価結果_Ｂ_前回" => isset($b_stress_response) == true ? $b_stress_response : 0,
            "評価結果_Ｃ_今回" => $a_support_factor,
            "評価結果_Ｃ_前回" => isset($b_support_factor) == true ? $b_support_factor : 0,
            "あなたのストレスの総合評価" => $personal_message->title,
            "あなたのストレスの総合評価説明" => $personal_message->sub_body,
            "ワンポイントアドバイス" => $personal_message->main_body,
            "面接指導の要否について" => $setDoctorINterview,
            "面接指導の要否について説明" => $intervireStatus,
            "相談窓口のご案内" => $examineeA->consultation_text,
            "A_レーダー画像" => null,
            "A_横棒グラフ画像" => null,
            "評価結果_A_画像" => null,
            "B_レーダー画像" => null,
            "B_横棒グラフ画像" => null,
            "評価結果_B_画像" => null,
            "C_レーダー画像" => null,
            "C_横棒グラフ画像" => null,
            "評価結果_C_画像" => null,
            "評価結果_画像" => null,
            "総合評価_画像1" => $water_mark1,
            "総合評価_画像2" => $water_mark2,
            "セルフケア_画像" => $self_care_img,
            "ストレスチェック結果報告書_画像" => $stress_img,
            "ラベル画像" => $label_img
        ];
        $postdata = json_encode($data, JSON_FORCE_OBJECT). PHP_EOL;
        $response = self::apiCall($postdata, $examineeA->user_id);
        if ($response['status'] == 200 && !empty($response['url'])) {
            $examineeA->pdf_report_url = $response['url'];
            $result = $examineeA->save();
            return response()->json(['result' => $result], $response['status']);
        } else {
            return response()->json(['result' => false], $response['status']);
        }
    }

    // Form rendering API call
    public function apiCall($postdata, $userID)
    {
        $token = env('DOCURAIN_TOKEN') ;    // Docurain API token
        $out_type = 'pdf';           // Output form
        $template_name = 'Personal_report'; // Saved template name
        $entity_json =  $postdata;     // Arbitrary data (JSON) to be applied to form templates
        
        $url = "https://api.docurain.jp/api/{$out_type}/{$template_name}";
        $headers['Authorization'] = "token {$token}";
        $headers['Content-Type'] = 'application/json';
        $options = [
            'http_errors' => false,
            'headers'     => $headers,
            'body'        => $entity_json
        ];
        $client = new Client();
        $res = $client->request('POST', $url, $options);
        return self::resHandle($res, $userID);
    }

    // Response handling (perform necessary handling as appropriate)
    public function resHandle($res, $userID)
    {
        if ($res->getStatusCode() === 200) { // On success, save the file in the current directory.

            $file_name = "ストレスチェック受検結果_".$userID.".pdf";
            
            Storage::disk('s3')->put($file_name, $res->getBody(), array('ACL' => 'public-read'));
            if (Storage::disk('s3')->exists($file_name)) {
                $excelReportUrl = Storage::url($file_name);
            }
            return ['url' => $excelReportUrl, 'status' =>$res->getStatusCode(), 'message'=> trans('validation.custom.success')];
        } else {
            Log::error($res->getBody());
            return ['message' => $res->getBody(), 'status'=> $res->getStatusCode()]; // Output error contents (JSON) when status code is other than 200.
        }
    }
}
