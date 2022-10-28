<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BootcampSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('bootcamps')->insert([
            [
                'title' => 'Intensive Bootcamp Web Development dengan Laravel',
                'price' => 350000,
                'thumbnail' => 'laravel.png'
            ],
            [
                'title' => 'SQL Intensive Bootcamp',
                'price' => 499000,
                'thumbnail' => 'sql.png'
            ],
            [
                'title' => 'Intensive Bootcamp Ms Excel: Ms Excel for Data Analysis',
                'price' => 249000,
                'thumbnail' => 'excel.png'
            ],
            [
                'title' => 'Secure Your Contract with No Insecurity',
                'price' => 149000,
                'thumbnail' => 'contract.png'
            ],
            [
                'title' => 'Mobile Development Intensive Bootcamp with Flutter',
                'price' => 599000,
                'thumbnail' => 'flutter.png'
            ],
            [
                'title' => 'Intensive Bootcamp Backend Development with Go',
                'price' => 499000,
                'thumbnail' => 'go.png'
            ]
        ]);
    }
}
