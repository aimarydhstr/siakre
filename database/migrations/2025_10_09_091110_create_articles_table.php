<?php
// database/migrations/xxxx_xx_xx_create_articles_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    public function up()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->string('doi')->unique();
            $table->string('title');
            $table->string('type_journal');
            $table->string('url')->nullable();
            $table->string('publisher')->nullable();
            $table->date('date');
            $table->string('category');
            $table->string('volume')->nullable();
            $table->string('number')->nullable();
            $table->string('file')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('articles');
    }
}
