<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMPersonalReportMessages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('m_personal_report_messages', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string("judgment");
            $table->string('title');
            $table->string('sub_body')->nullable();
            $table->string('main_body')->nullable();
            $table->enum('language', ['JA', 'EN']);
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
        Schema::dropIfExists('m_personal_report_messages');
    }
}
