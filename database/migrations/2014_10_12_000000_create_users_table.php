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
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('telephone')->nullable();
            $table->string('adresse')->nullable();
            $table->string('complement_adresse')->nullable();
            $table->string('code_postale')->nullable();
            $table->string('ville')->nullable();
            $table->enum('role',[env('ADMIN_ROLE'), env("SIMPLE_USER_ROLE")])->nullable();
            $table->string('siren')->nullable();
            $table->string('nomEntreprise')->nullable();
            $table->boolean('wantToAddHabitat')->default(0);
            $table->boolean('canAddHabitat')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->text("facebook_id")->nullable();
            $table->text("google_id")->nullable();
            $table->string('password');
            $table->longText('password2');
            $table->rememberToken();
            $table->timestamps();
        });
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
