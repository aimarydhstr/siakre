<?php

namespace App\Http\Controllers;

use App\Models\Expertise;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Auth;

class ExpertiseController extends Controller
{
    public function index()
    {
        $expertises = Expertise::withCount('fields')
            ->orderBy('name')
            ->paginate(10);
        $user = Auth::user();

        return view('expertises.index', compact('expertises', 'user'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required','string','max:255','unique:expertises,name'],
        ]);

        Expertise::create(['name' => $request->name]);

        return back()->with('success', 'Bidang keilmuan berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $expertise = Expertise::findOrFail($id);

        $request->validate([
            'name' => [
                'required','string','max:255',
                Rule::unique('expertises','name')->ignore($expertise->id),
            ],
        ]);

        $expertise->update(['name' => $request->name]);

        return back()->with('success', 'Bidang keilmuan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $expertise = Expertise::withCount('fields')->findOrFail($id);

        if ($expertise->fields_count > 0) {
            return back()->withErrors(['delete' => 'Tidak bisa menghapus. Hapus sub-bidang terlebih dahulu.']);
        }

        $expertise->delete();

        return back()->with('success', 'Bidang keilmuan berhasil dihapus.');
    }

    // JSON untuk dropdown sub-bidang (tetap disediakan)
    public function fieldsJson($expertiseId)
    {
        $fields = \App\Models\ExpertiseField::where('expertise_id', $expertiseId)
            ->orderBy('name')
            ->get(['id','name']);

        return response()->json($fields);
    }
}
