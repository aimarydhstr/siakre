<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // ======================== FACULTY ========================
        DB::table('faculties')->insert([
            ['name' => 'Sains & Teknologi'],
            ['name' => 'Kesehatan'],
        ]);

        // ======================== DEPARTMENT ========================
        DB::table('departments')->insert([
            ['name' => 'Informatika', 'faculty_id' => 1],
            ['name' => 'Sistem Informasi', 'faculty_id' => 1],
            ['name' => 'Anestiologi', 'faculty_id' => 2],
            ['name' => 'Keperawatan', 'faculty_id' => 2],
        ]);

        // ======================== USERS =============================
        DB::table('users')->insert([
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'password' => Hash::make('password'), 'role' => 'admin'],
            ['name' => 'Head CS', 'email' => 'headcs@example.com', 'password' => Hash::make('password'), 'role' => 'department_head'],
            ['name' => 'Head IS', 'email' => 'headis@example.com', 'password' => Hash::make('password'), 'role' => 'department_head'],
            ['name' => 'Lecturer EE', 'email' => 'lectureree@example.com', 'password' => Hash::make('password'), 'role' => 'lecturer'],
            ['name' => 'Lecturer ME', 'email' => 'lecturerme@example.com', 'password' => Hash::make('password'), 'role' => 'lecturer'],
        ]);

        // ======================== DEPARTMENT_HEADS =================
        DB::table('department_heads')->insert([
            ['user_id' => 2, 'department_id' => 1],
            ['user_id' => 3, 'department_id' => 2],
        ]);

        // ======================== LECTURERS ========================
        DB::table('lecturers')->insert([
            ['user_id' => 4, 'department_id' => 1],
            ['user_id' => 5, 'department_id' => 2],
        ]);

        // ======================== STUDENTS =========================
        DB::table('students')->insert([
            ['nim' => '10001', 'name' => 'Alice', 'photo' => 'alice.jpg', 'department_id' => 1],
            ['nim' => '10002', 'name' => 'Bob', 'photo' => 'bob.jpg', 'department_id' => 1],
            ['nim' => '10003', 'name' => 'Charlie', 'photo' => 'charlie.jpg', 'department_id' => 1],
            ['nim' => '10004', 'name' => 'David', 'photo' => 'david.jpg', 'department_id' => 2],
            ['nim' => '10005', 'name' => 'Eve', 'photo' => 'eve.jpg', 'department_id' => 2],
        ]);

        // ======================== ARTICLES =========================
        DB::table('articles')->insert([
            [
                'department_id' => 1,
                'title' => 'Tuning of PID Controller Parameters with Genetic Algorithm Method on DC Motor',
                'type_journal' => 'Seminar Nasional',
                'url' => 'https://pubs2.ascee.org/index.php/IJRCS/article/view/249',
                'doi' => '10.1234/ijrcs.249',
                'publisher' => 'International Journal of Robotics and Control Systems',
                'date' => '2023-05-10',
                'category' => 'mahasiswa',
                'volume' => '1',
                'number' => '1',
                'file' => '_1691311477_249-912-4-PB.pdf',
            ],
            [
                'department_id' => 1,
                'title' => 'Stirring System Design for Automatic Coffee Maker Using OMRON PLC and PID Control',
                'type_journal' => 'Seminar Internasional',
                'url' => 'https://pubs2.ascee.org/index.php/IJRCS/article/view/457',
                'doi' => '10.1234/ijrcs.457',
                'publisher' => 'International Journal of Robotics and Control Systems',
                'date' => '2023-06-15',
                'category' => 'dosen',
                'volume' => '1',
                'number' => '1',
                'file' => '_1691310641_457-1665-3-PB.pdf',
            ],
            [
                'department_id' => 1,
                'title' => 'Artificial Potential Field Path Planning Algorithm in Differential Drive Mobile Robot Platform for Dynamic Environment',
                'type_journal' => 'Jurnal Internasional',
                'url' => 'https://pubs2.ascee.org/index.php/IJRCS/article/view/944',
                'doi' => '10.1234/ijrcs.944',
                'publisher' => 'International Journal of Robotics and Control Systems',
                'date' => '2023-07-01',
                'category' => 'mahasiswa',
                'volume' => '2',
                'number' => '1',
                'file' => '_1691308535_944-3074-2-PB.pdf',
            ],
            [
                'department_id' => 2,
                'title' => 'Design and Application of PLC-based Speed Control for DC Motor Using PID with Identification System and MATLAB Tuner',
                'type_journal' => 'Jurnal Nasional Terakreditasi',
                'url' => 'https://pubs2.ascee.org/index.php/IJRCS/article/view/775',
                'doi' => '10.1234/ijrcs.775',
                'publisher' => 'International Journal of Robotics and Control Systems',
                'date' => '2022-09-20',
                'category' => 'dosen',
                'volume' => '3',
                'number' => '1',
                'file' => '_1691307976_775-3170-1-PB.pdf',
            ],
            [
                'department_id' => 2,
                'title' => 'ME Seminar',
                'type_journal' => 'Seminar Nasional',
                'url' => '#',
                'doi' => '10.1234/ijrcs.009',
                'publisher' => 'Univ D',
                'date' => '2021-03-30',
                'category' => 'mahasiswa',
                'volume' => '1',
                'number' => '1',
                'file' => 'me1.pdf',
            ],
        ]);


        // ======================== STUDENT_ARTICLES =================
        DB::table('student_articles')->insert([
            ['student_id' => 1, 'article_id' => 1],
            ['student_id' => 2, 'article_id' => 1],
            ['student_id' => 3, 'article_id' => 3],
            ['student_id' => 4, 'article_id' => 5],
            ['student_id' => 5, 'article_id' => 5],
        ]);

        // ======================== LECTURER_ARTICLES ===============
        DB::table('lecturer_articles')->insert([
            ['lecturer_id' => 1, 'article_id' => 1],
            ['lecturer_id' => 1, 'article_id' => 2],
            ['lecturer_id' => 1, 'article_id' => 3],
            ['lecturer_id' => 2, 'article_id' => 4],
            ['lecturer_id' => 2, 'article_id' => 5],
        ]);

        // ======================== ACHIEVEMENTS ====================
        DB::table('achievements')->insert([
            ['department_id'=>1,'team'=>'Team A','type_achievement'=>'Tim','field'=>'Akademik','level'=>'Region','competition'=>'Competition 1','rank'=>'1','organizer'=>'Org 1','month'=>'5','year'=>'2023'],
            ['department_id'=>1,'team'=>'Team B','type_achievement'=>'Tim','field'=>'Akademik','level'=>'National','competition'=>'Competition 2','rank'=>'2','organizer'=>'Org 2','month'=>'6','year'=>'2023'],
            ['department_id'=>2,'team'=>'Team C','type_achievement'=>'Individu','field'=>'NonAkademik','level'=>'Region','competition'=>'Competition 3','rank'=>'3','organizer'=>'Org 3','month'=>'7','year'=>'2024'],
        ]);

        // ======================== STUDENT_ACHIEVEMENTS =================
        DB::table('student_achievements')->insert([
            ['student_id'=>1,'achievement_id'=>1,'certificate'=>'cert1.jpg'],
            ['student_id'=>2,'achievement_id'=>1,'certificate'=>'cert2.jpg'],
            ['student_id'=>3,'achievement_id'=>2,'certificate'=>'cert3.jpg'],
            ['student_id'=>4,'achievement_id'=>2,'certificate'=>'cert4.jpg'],
            ['student_id'=>5,'achievement_id'=>3,'certificate'=>'cert5.jpg'],
        ]);

        // ======================== ACHIEVEMENT_DOCUMENTATIONS =========
        DB::table('achievement_documentations')->insert([
            ['achievement_id'=>1,'image'=>'doc1.jpg'],
            ['achievement_id'=>1,'image'=>'doc2.jpg'],
            ['achievement_id'=>2,'image'=>'doc3.jpg'],
            ['achievement_id'=>2,'image'=>'doc4.jpg'],
            ['achievement_id'=>3,'image'=>'doc5.jpg'],
        ]);

        DB::table('cooperations')->insert([
            [
                'letter_number' => 'UHB/UMP/RSRCH/001/2025',
                'letter_date'   => '2025-01-15',
                'partner'       => 'UMP',
                'type_coop'     => 'research',       
                'level'         => 'national',       
                'file'          => 'coop1.pdf',
                'user_id'       => 2,             
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'letter_number' => 'UHB/KU/EDU/014/2025',
                'letter_date'   => '2025-03-10',
                'partner'       => 'Kyoto University',
                'type_coop'     => 'education',
                'level'         => 'international',
                'file'          => 'coop2.pdf',
                'user_id'       => 2,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'letter_number' => 'UHB/PEMDA-BMS/COMM/009/2025',
                'letter_date'   => '2025-05-20',
                'partner'       => 'Tokyo University',
                'type_coop'     => 'community_service',
                'level'         => 'international',
                'file'          => 'coop3.pdf',
                'user_id'       => 2,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ]);

        DB::table('ias')->insert([
            [
                'cooperation_id' => 1,
                'mou_name'       => 'MoU UHB–UMP Research 2025',
                'ia_name'        => 'Joint Research on AI in Healthcare',
                'file'           => 'ia1.pdf',
                'proof'          => 'proof1.pdf',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'cooperation_id' => 2,
                'mou_name'       => 'MoU UHB–Kyoto University Education 2025',
                'ia_name'        => 'Student Exchange Spring 2026',
                'file'           => 'ia2.pdf',
                'proof'          => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'cooperation_id' => 3,
                'mou_name'       => 'MoU UHB–Tokyo University Community 2025',
                'ia_name'        => 'Community Service on Digital Literacy',
                'file'           => 'ia3.pdf',
                'proof'          => 'proof3.png',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);
    }
}
