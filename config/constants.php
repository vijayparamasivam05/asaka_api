<?php

return [
    'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS'),
    'MAIL_SUBJECT' => "Asaka subject",
    'MAIL_FROM_NAME' => env('MAIL_FROM_NAME'),
    'QUESTIONNAIRE_TYPE'=> [
        'BJSQ' => 'BJSQ',
        'MIRROR' => 'Mirror',
        'BOTH' => 'Both'
    ],
    'QUESTION_METHOD' => [
        'WEB' =>'WEB',
        'MS' => 'MS'
    ],
    'NOTIFICATION_TYPE' => [
        'EMAIL' =>'email',
        'POST' => 'post'
    ],
    'MAIL_TEMPLATE_ID' => [
        'ID1' => 'd-368142bdc4404430adc3e007ca711429',
        'ID2' => 'd-4bdf1ec0108741fbbc1d47ff820f433c',
        'ID3' => 'd-61350f78add14270a7385bfe9ca1eb7e',
        'ID4' => 'd-be5cc3ec4d5d4ff595595dbed643091c',
        'ID5' => 'd-b08003503ec143158384a0b38e86643b',
        'ID6' => 'd-ca87418e8a08443ea0cb93b30150b80d'
    ],
    'REPORTS_SHEETNAMES' => [
        'COMPANY_EXCEL_SHEET1' =>'1部署設定',
        'COMPANY_EXCEL_SHEET2' => '2今回',
        'COMPANY_EXCEL_SHEET3' => '3前回',
        'COMPANY_EXCEL_SHEET4' => '4前々回',
        'COMPANY_EXCEL_SHEET01' => '役職',
        'COMPANY_EXCEL_SHEET02' => '分析対象',
        'COMPANY_EXCEL_SHEET03' => '分類①',
        'COMPANY_EXCEL_SHEET04' => '分類②',
        'COMPANY_EXCEL_SHEET05' => '分類③',
        'COMPANY_EXCEL_SHEET06' => '分類④'
    ],
    'CALLED_API' => [
        'COMPANY_STATUS' => '受検準備中',
        'COMPANY_CSV_UPLOAD_STATUS_MESSAGE' => '所属一覧の登録を行ってください。',
        'DEPARTMENT_CSV_UPLOAD_SATATUS_MESSAGE' => '役職一覧の登録を行ってください。',
        'DIRECTOR_CSV_UPLOAD_STATUS_MESSAGE' => '分類一覧の登録を行ってください。',
        'CLASSIFICATION_CSV_UPLOAD_STATUS_MESSAGE' => '受検者の登録を行ってください。',
        'USER_CSV_UPLOAD_STATUS_MESSAGE' => '受検者のミスマッチ対応を行ってください。',
        'MISSMATCH_FLAG_STATUS_MESSGAE' => '受検開始までお待ちください。',
        'ADMIN_ANSWERS_UPDATE_STATUS' => '実施者確認中',
        'ADMIN_ANSWERS_UPDATE_STATUS_MESSAGE' => '要否判定開始',
        'ADMIN_ANSWERS_UPDATE_INTERVIEW_TARGET_FLG_STATUS' => '開示準備中',
        'ADMIN_ANSWERS_UPDATE_INTERVIEW_TARGET_FLG_STATUS_MESSAGE' => '要否判定終了',
        'CRON_JOB_TYPE_2_STATUS' => '受検中',
        'CRON_JOB_TYPE_2_STATUS_MESSAGE' => '受検中',
        'CRON_JOB_TYPE_3_STATUS' => '集計中',
        'CRON_JOB_TYPE_3_STATUS_MESSAGE' => '無効回答の修正をしてください。',
        'EXAMINEE_STATUS' => 'ログインメール未送信',
        'EXAMINEE_STATUS_SENDEMAIL' => 'メール送信済',
        'EXAMINEE_LOGIN_SUCCESS' => 'ログイン済',
        'EXAMINEE_CRONJOB_TYPE4_STATUS' => '受検中',
        'EXAMINEE_MARKSHEET_STATUS' => '受検完了',
        'EXAMINEE_CALCULATION_PERSON_STATUS' => '高ストレス者',
        'EXAMINEE_ANSWER_CSV_UPLOAD_STATUS' => '目検チェック済',
        'EXAMINEE_USER_RESULT_VIEW' => '個人レポート確認済',
        'EXAMINEE_SET_INTERVIEW_REQUEST_FLG_STATUS' => '医師面接登録済',
        'EXAMINEE_USER_CSV_UPLOAD_MS' => '受検準備中',
        'EXAMINEE_CRONJOB_TYPE5_STATUS' => '受検中',
        'EXAMINEE_MARKSHEET_MS_STATUS' => '回答の修正が必要',
        'EXAMINEE_INVALID_FLG_TRUE_MS_STATUS' => '回答の修正が必要',
        'EXAMINEE_INVALID_FLG_FALSE_MS_STATUS' => '受検完了',
        'ADMIN_ANSWERS_UPDATE_INTERVIEW_TARGET_FLG_STATUS_1' => '集団分析レポート準備中',
        'COMPANY_PDF_UPLOAD_STATUS' => '集団分析レポート準備完了/意向確認が必要なユーザーあり',
        'SET_INTERVIEW_FLG_STATUS' => '終了'
        ]
];
