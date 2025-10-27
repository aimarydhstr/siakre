<?php

namespace App\Http\Controllers;

use App\Models\Expertise;
use App\Models\ExpertiseField;
use App\Models\Lecturer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ExpertiseFieldController extends Controller
{
    // Halaman khusus sub-bidang milik satu parent
    public function index($expertiseId)
    {
        $expertise = Expertise::findOrFail($expertiseId);

        $fields = ExpertiseField::where('expertise_id', $expertise->id)
            ->orderBy('name')
            ->paginate(10);

        $user = Auth::user();

        // Untuk dropdown pindahkan sub ke parent lain saat edit
        $allExpertises = Expertise::orderBy('name')->get(['id','name']);

        return view('expertise_fields.index', compact('expertise','fields','allExpertises', 'user'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'expertise_id' => ['required','exists:expertises,id'],
            'name' => [
                'required','string','max:255',
                Rule::unique('expertise_fields','name')->where(function($q) use ($request){
                    return $q->where('expertise_id', $request->expertise_id);
                }),
            ],
        ]);

        ExpertiseField::create([
            'expertise_id' => $request->expertise_id,
            'name'         => $request->name,
        ]);

        return back()->with('success', 'Sub bidang berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $field = ExpertiseField::findOrFail($id);

        $request->validate([
            'expertise_id' => ['required','exists:expertises,id'],
            'name' => [
                'required','string','max:255',
                Rule::unique('expertise_fields','name')
                    ->ignore($field->id)
                    ->where(function($q) use ($request){
                        return $q->where('expertise_id', $request->expertise_id);
                    }),
            ],
        ]);

        $field->update([
            'expertise_id' => $request->expertise_id,
            'name'         => $request->name,
        ]);

        return back()->with('success', 'Sub bidang berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $field = ExpertiseField::findOrFail($id);

        $used = Lecturer::where('expertise_field_id', $field->id)->exists();
        if ($used) {
            return back()->withErrors(['delete' => 'Tidak bisa menghapus. Sub-bidang sedang dipakai dosen.']);
        }

        $field->delete();

        return back()->with('success', 'Sub bidang berhasil dihapus.');
    }
}
