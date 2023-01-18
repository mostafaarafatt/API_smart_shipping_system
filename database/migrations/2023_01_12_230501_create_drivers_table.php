<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
			$table->string('password');
			$table->string('phone')->unique();
			$table->string('national_ID')->unique();
			$table->string('car_number');
			$table->string('address')->nullable();
			$table->string('image')->nullable();
			$table->double('user_rate',15,8)->nullable();
			$table->double('count_rate',15,8)->nullable();
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
        Schema::dropIfExists('drivers');
    }
};
