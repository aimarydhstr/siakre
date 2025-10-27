<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnLecturersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lecturers', function (Blueprint $table) {
            // Identitas
            $table->string('nik', 32)
                ->nullable()
                ->after('department_id');
            $table->string('nidn', 32)
                ->nullable()
                ->after('nik');

            // TTL & alamat
            $table->string('birth_place')
                ->nullable()
                ->after('nidn');
            $table->date('birth_date')
                ->nullable()
                ->after('birth_place');
            $table->text('address')
                ->nullable()
                ->after('birth_date');

            // Jabatan fungsional & status keluarga
            $table->enum('position', ['Asisten Ahli','Lektor','Lektor Kepala','Profesor'])
                ->nullable()
                ->after('address');
            $table->enum('marital_status', ['Menikah','Belum Menikah'])
                ->nullable()
                ->after('position');

            // Relasi ke sub-bidang (expertise_fields)
            $table->foreignId('expertise_field_id')
                ->nullable()
                ->after('marital_status')
                ->constrained('expertise_fields')
                ->nullOnDelete();
        });

        // Tambah unique index terpisah (beri nama sendiri agar mudah di-drop saat rollback)
        Schema::table('lecturers', function (Blueprint $table) {
            $table->unique('nik',  'lecturers_nik_unique');
            $table->unique('nidn', 'lecturers_nidn_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lecturers', function (Blueprint $table) {
            // Drop unique index dulu
            if (Schema::hasColumn('lecturers', 'nik')) {
                $table->dropUnique('lecturers_nik_unique');
            }
            if (Schema::hasColumn('lecturers', 'nidn')) {
                $table->dropUnique('lecturers_nidn_unique');
            }

            // Drop FK & kolom relasi
            if (Schema::hasColumn('lecturers', 'expertise_field_id')) {
                $table->dropConstrainedForeignId('expertise_field_id');
            }

            // Drop kolom lainnya bila ada
            foreach ([
                'marital_status',
                'position',
                'address',
                'birth_date',
                'birth_place',
                'nidn',
                'nik',
            ] as $col) {
                if (Schema::hasColumn('lecturers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
