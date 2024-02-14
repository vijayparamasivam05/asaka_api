<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fixed_company_id');
            $table->integer('yearmm');
            $table->string('name');
            $table->integer('industry_standard');
            $table->integer('industry_ascc');
            $table->integer('employees_num');
            $table->integer('exam_start');
            $table->integer('exam_end');
            $table->string('status')->nullable();
            $table->string('status_message')->nullable();
            $table->enum('criteria_type', ['素点', '合計']);
            $table->integer('high_stress_1')->nullable();
            $table->integer('high_stress_2')->nullable();
            $table->integer('high_stress_3')->nullable();
            $table->integer('high_stress_4')->nullable();
            $table->integer('high_stress_5')->nullable();
            $table->integer('high_stress_6')->nullable();
            $table->integer('name_end');
            $table->integer('answer_end');
            $table->integer('result_day');
            $table->string('guidance_subject');
            $table->string('guidance_email');
            $table->string('remind_subject');
            $table->string('remind_email');
            $table->string('result_subject');
            $table->string('result_email');
            $table->string('result_remind_subject');
            $table->string('result_remind_email');
            $table->string('generate_excel_report_url')->nullable();
            $table->string('excel_report_url')->nullable();
            $table->string('pdf_report_url')->nullable();
            $table->string('schedule_excel_url')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->softDeletes();
        });
        // Insert some stuff
        DB::table('companies')->insert(
            array(
                'id' => 'C000000001',
                'fixed_company_id' => 'Company123',
                'yearmm' => 0,
                'name' => 'Democompany',
                'industry_standard' => 0,
                'industry_ascc' => 0,
                'employees_num' => 0,
                'exam_start' => 0,
                'exam_end' => 0,
                'status' => null,
                'criteria_type' => "合計",
                'name_end' => 0,
                'answer_end' => 0,
                'result_day' => 0,
                "guidance_subject" => "guidance_subject",
                "guidance_email" => "admin@asaka.com",
                "remind_subject" => "remind_subject",
                "remind_email" => "admin@asaka.com",
                "result_subject" => "result_subject",
                "result_email" => "admin@asaka.com",
                "result_remind_subject" => "result_remind_subject",
                "result_remind_email" => "result_remind_email"
            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
}
