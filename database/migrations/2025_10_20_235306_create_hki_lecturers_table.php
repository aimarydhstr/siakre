<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHkiLecturersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hki_lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hki_id')
                  ->constrained('hkis')
                  ->cascadeOnDelete();
            $table->foreignId('lecturer_id')
                  ->constrained('lecturers')
                  ->cascadeOnDelete();
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
        Schema::dropIfExists('hki_lecturers');
    }
}
