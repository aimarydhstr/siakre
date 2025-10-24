<?php
// database/migrations/xxxx_xx_xx_create_achievement_documentations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAchievementDocumentationsTable extends Migration
{
    public function up()
    {
        Schema::create('achievement_documentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('achievement_id')->constrained('achievements')->onDelete('cascade');
            $table->string('image');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('achievement_documentations');
    }
}
