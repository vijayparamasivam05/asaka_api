<?php
namespace App\Http\Controllers;

use App\Models\Answer;
use Illuminate\Http\Request;
use AppHelper;
use DB;
use Illuminate\Support\Facades\Log;
use SendGrid\Mail\From;
use SendGrid\Mail\Mail;
use SendGrid\Mail\Personalization;
use SendGrid\Mail\Subject;
use SendGrid\Mail\To;
use App\Models\User;
use App\Models\Company;
use App\Models\Examinee;
use Carbon\Carbon;

class ManagementController extends Controller
{
    /**
     * Send email.
     *
     * @return \Illuminate\Http\Response
     */
    public function SendEmail(Request $request)
    {
        $type = $request->type;
        if ($type > 0 && $type < 7) {
            foreach ($request->ids as $item) {
                $user = User::findOrFail($item);
                $company = Company::where('fixed_company_id', $user->fixed_company_id)->orderBy('yearmm', 'DESC')->firstOrFail();
                $email = new \SendGrid\Mail\Mail();
                $email->setFrom(config('constants.MAIL_FROM_ADDRESS'), config('constants.MAIL_FROM_NAME'));
                $email->addTo($user->email, $user->firstname);
                $data = [];
                $templateId = null;
                
                // Set dynamic template data
                if ($type == 1 && $user->email != null) {
                    $data = [
                        '名前'=> $user->lastname,
                        'ID'     => $user->id,
                        'パスワード' => $user->password
                    ];
                    $templateId = config('constants.MAIL_TEMPLATE_ID.ID1');
                } elseif ($type == 2 && $user->email != null) {
                    $data = [
                        '会社名' => $company->name,
                        '名前' => $user->lastname,
                        '受検開始日' => $company->exam_start,
                        '受検終了日' => $company->exam_end,
                        'ユーザーID' => $user->id,
                        'パスワード' => $user->password
                    ];
                    $templateId = config('constants.MAIL_TEMPLATE_ID.ID2');
                } elseif ($type == 3 && $user->email != null) {
                    $data = [
                        '会社名' => $company->name,
                        '名前' => $user->lastname,
                        '受検終了日' => $company->exam_end,
                        'ユーザーID' => $user->id,
                        'パスワード' => $user->password
                    ];
                    $templateId = config('constants.MAIL_TEMPLATE_ID.ID3');
                } elseif ($type == 4 && $user->email != null) {
                    $data = [
                        '会社名' => $company->name,
                        '名前' => $user->lastname,
                        'ユーザーID' => $user->id,
                        'パスワード' => $user->password
                    ];
                    $templateId = config('constants.MAIL_TEMPLATE_ID.ID4');
                } elseif ($type == 5 && $user->email != null) {
                    $data = [
                        '会社名' => $company->name,
                        '名前' => $user->lastname,
                        'ユーザーID' => $user->id,
                        'パスワード' => $user->password
                    ];
                    $templateId = config('constants.MAIL_TEMPLATE_ID.ID5');
                } elseif ($type == 6 && $user->email != null) {
                    $data = [
                        '会社名' => $company->name,
                        '名前' => $user->lastname
                    ];
                    $templateId = config('constants.MAIL_TEMPLATE_ID.ID6');
                }
                if ($templateId != null && !empty($data)) {
                    $email->addDynamicTemplateDatas($data);
                    $email->setTemplateId(new \SendGrid\Mail\TemplateId($templateId));
                    $sendgrid = new \SendGrid(getenv('MAIL_PASSWORD'));
                    try {
                        $response = $sendgrid->send($email);
                        if ($type == 2 && $response->statusCode() == 202) {
                            if ($user->role == 5) {
                                $examinee = Examinee::where([['user_id', $user->id],['question_method',config('constants.QUESTION_METHOD.WEB')]])->orderBy('yearmm', 'DESC')->first();
                                $examinee->status = config('constants.CALLED_API.EXAMINEE_STATUS_SENDEMAIL');
                                $examinee->save();
                            }
                        }
                    } catch (Exception $e) {
                        Log::error($e);
                        return response()->json(['message' => trans('validation.custom.Something_went_wrong_Please_try_again')], 400);
                    }
                }
            }
            return response()->json(['result' => true], 200);
        } else {
            return response()->json(['message' =>'type required'], 400);
        }
    }

    /**
     * Cron job settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function ReslutDayCronJob($type)
    {
        $ids = [];
        if ($type == 4) {
            $date = Carbon::now()->format('Ymd');
            $companies = Company::select('fixed_company_id')->where('result_day', (int)$date)->distinct()->get()->toArray();
            $users = User::select('id')->whereIn('fixed_company_id', $companies)->get()->toArray();
            foreach ($users as $item) {
                array_push($ids, $item["id"]);
            }
            $emailRequest = new Request();
            $emailRequest->type = $type;
            $emailRequest->ids = $ids;
            $this->SendEmail($emailRequest);
        }
        return response()->json(["result" => true], 200);
    }
}
