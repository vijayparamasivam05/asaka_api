<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('examinee_id');
            $table->index('examinee_id');
            $table->foreign('examinee_id')->references('id')->on('examinee');

            for ($i=1; $i<=19; $i++) {
                $table->integer("personal_cal_{$i}")->nullable();
            }
            
            $table->integer("raw_stress_factor")->nullable();
            $table->integer("raw_stress_response")->nullable();
            $table->integer("raw_support_factor")->nullable();
            $table->integer("total_stress_factor")->nullable();
            $table->integer("total_stress_response")->nullable();
            $table->integer("total_support_factor")->nullable();
            $table->string("stressor")->nullable();
            $table->string("stress_response")->nullable();
            $table->integer("stressor_stress_response")->nullable();
            $table->integer("judgment")->default(0);
            $table->string("weather_mark")->nullable();
            $table->boolean('high_stress_flg')->nullable();

            for ($i=1; $i<=17; $i++) {
                $table->integer("bjsq_a_{$i}")->nullable();
            }
            for ($i=1; $i<=29; $i++) {
                $table->integer("bjsq_b_{$i}")->nullable();
            }
            for ($i=1; $i<=9; $i++) {
                $table->integer("bjsq_c_{$i}")->nullable();
            }
            $table->integer("bjsq_d_1")->nullable();
            $table->integer("bjsq_d_2")->nullable();
            for ($i=1; $i<=45; $i++) {
                $table->integer("mirror_{$i}")->nullable();
            }
            $table->boolean('invalid_flg')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('answers');
    }
}
