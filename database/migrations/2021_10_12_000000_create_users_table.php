<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('fixed_company_id');
            $table->enum('role', [1, 2, 3, 4, 5]);
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_token')->nullable();
            $table->string('password');
            $table->timestamp('password_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->softDeletes();
        });
          // Insert some stuff
          DB::table('users')->insert(
            array(
                'id' => 'A000000001',
                'fixed_company_id' => 'Company123',
                'role' => 1,
                'firstname' => 'Super Admin',
                'lastname' => 'Asaka',
                'email' => 'admin@asaka.com',
                'password' =>'admin123'
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
        Schema::dropIfExists('users');
    }
}
