<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;
use Illuminate\View\View;

class CareersController extends Controller
{
    public function index(): View
    {
        return view('pages.careers.index', ['vacancies' => Vacancy::open()->orderBy('department')->orderBy('title')->get()]);
    }

    public function show(Vacancy $vacancy): View
    {
        abort_unless($vacancy->isOpen(), 404);

        return view('pages.careers.show', ['vacancy' => $vacancy]);
    }
}
