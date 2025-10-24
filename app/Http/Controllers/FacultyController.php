<?php

namespace App\Http\Controllers;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Auth;

class FacultyController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $faculties = Faculty::latest()->paginate(10);
        return view('faculty.index', compact('faculties', 'user'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $faculty = new Faculty();
        $faculty->name = $request->name;
        $faculty->save();

        return redirect()->route('faculties.index')->with('success', 'Fakultas berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
        ]);
        
        $faculty = Faculty::findOrFail($id);
        $faculty->name = $request->name;
        $faculty->save();

        return redirect()->route('faculties.index')->with('success', 'Fakultas berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $faculty = Faculty::findOrFail($id)->delete();
        return redirect()->route('faculties.index')->with('success', 'Fakultas berhasil dihapus!');
    }
}

?>