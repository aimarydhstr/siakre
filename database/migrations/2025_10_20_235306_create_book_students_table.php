<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookstudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('book_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')
                  ->constrained('books')
                  ->cascadeOnDelete();
            $table->foreignId('student_id')
                  ->constrained('students')
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
        Schema::dropIfExists('book_students');
    }
}
