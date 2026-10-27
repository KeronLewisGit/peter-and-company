<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Vacancies on /careers. HR and administrators. */
class VacancyController extends Controller
{
    public function index(): View
    {
        return view('admin.vacancies.index', ['vacancies' => Vacancy::withCount('applications')->orderByDesc('published')->latest()->get()]);
    }

    public function create(): View
    {
        return view('admin.vacancies.form', ['vacancy' => new Vacancy(['location' => 'Cul de Sac, Castries', 'employment_type' => 'Full time', 'published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $vacancy = Vacancy::create($data + ['slug' => Slug::unique(Vacancy::class, $data['title'], 'vacancy')]);

        return to_route('admin.vacancies.index')->with('status', "{$vacancy->title} saved.");
    }

    public function edit(Vacancy $vacancy): View
    {
        return view('admin.vacancies.form', ['vacancy' => $vacancy]);
    }

    public function update(Request $request, Vacancy $vacancy): RedirectResponse
    {
        $vacancy->update($this->validated($request));

        return to_route('admin.vacancies.index')->with('status', "{$vacancy->title} saved.");
    }

    public function destroy(Vacancy $vacancy): RedirectResponse
    {
        // Applications keep their history; they just lose the link to the vacancy.
        $vacancy->delete();

        return to_route('admin.vacancies.index')->with('status', 'Vacancy deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'department' => ['required', 'string', 'max:80'],
            'location' => ['required', 'string', 'max:80'],
            'employment_type' => ['required', 'string', 'max:30'],
            'summary' => ['required', 'string', 'max:1500'],
            'duties' => ['nullable', 'string', 'max:4000'],
            'requirements' => ['nullable', 'string', 'max:4000'],
            'closes_on' => ['nullable', 'date_format:Y-m-d'],
            'published' => ['nullable', 'boolean'],
        ]);
        $data['published'] = $request->boolean('published');

        return $data;
    }
}
