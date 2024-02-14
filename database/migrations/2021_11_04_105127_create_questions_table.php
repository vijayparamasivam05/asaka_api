<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('ans_id');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('question');
            $table->string('answer_text_1')->nullable();
            $table->string('answer_text_2')->nullable();
            $table->string('answer_text_3')->nullable();
            $table->string('answer_text_4')->nullable();
            $table->enum('questionnaire_type', ['BJSQ', 'Mirror']);
            $table->enum('language' , ['JA', 'EN']);
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
        Schema::dropIfExists('questions');
    }
}
