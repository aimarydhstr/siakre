<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookLecturersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('book_lecturers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')
                  ->constrained('books')
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
        Schema::dropIfExists('book_lecturers');
    }
}
