<?php

namespace App\Repositories;

use App\Models\Bootcamp;

class BootcampRepository
{
    public function list()
    {
        return Bootcamp::orderBy('id', 'desc')->get();
    }

    public function detail($bootcampID)
    {
        return Bootcamp::where('id', $bootcampID)->first();
    }
}
