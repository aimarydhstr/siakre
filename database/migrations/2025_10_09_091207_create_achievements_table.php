<?php
// database/migrations/xxxx_xx_xx_create_achievements_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAchievementsTable extends Migration
{
    public function up()
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->string('team');
            $table->string('type_achievement');
            $table->string('field');
            $table->string('level');
            $table->string('competition');
            $table->string('rank');
            $table->string('organizer');
            $table->string('month');
            $table->string('year');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('achievements');
    }
}
