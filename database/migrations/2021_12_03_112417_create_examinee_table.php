<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExamineeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('examinee', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('department_id');
            $table->string('director_id');
            $table->integer('yearmm');
            $table->string('firstname');
            $table->string('lastname');
            $table->integer('serial_number');
            $table->string('status')->nullable();
            $table->string('firstname_katakana');
            $table->string('lastname_katakana');

            $table->enum('gender', ['Not specified', 'Male', 'Female']);
            $table->integer('birth_day');

            $table->enum('question_method', ['WEB', 'MS']);
            $table->enum('questionnaire_type', ['BJSQ', 'Mirror','Both']);

            $table->enum('notification_type', ['email', 'post'])->default('post');
            $table->enum('question_output_method', ['email', 'post']);
            $table->enum('language', ['JA', 'EN']);
            $table->integer('employment_day')->nullable();
            $table->enum('job_status', ['currentjob', 'leave', 'retirement']);
            $table->boolean('result_view_flg')->default(false);
            $table->timestamp('result_view_created_at')->nullable();
            $table->boolean('high_stress_flg')->default(false);
            $table->enum('Interview_target_flg', [0,1,2])->default(0);
            $table->enum('Interview_request_flg', [0,1,2])->default(0);
            $table->string('consultation_text');
            $table->text('pdf_report_url')->nullable();
            $table->string('employment_num');
            $table->boolean('mismatch_flg')->default(false);
            $table->boolean('exam_complete_flg')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->softDeletes();
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->index('department_id');
            $table->foreign('department_id')->references('id')->on('departments');
            $table->index('director_id');
            $table->foreign('director_id')->references('id')->on('directors');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('examinee');
    }
}
