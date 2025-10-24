<?php

namespace App\Http\Controllers;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Auth;

class DepartmentController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $departments = Department::with('faculty')->latest()->paginate(10);
        $faculties = Faculty::orderBy('name', 'asc')->get();
        return view('department.index', compact('departments', 'user', 'faculties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'faculty_id' => 'required'
        ]);

        $department = new Department();
        $department->name = $request->name;
        $department->faculty_id = $request->faculty_id;
        $department->save();

        return redirect()->route('departments.index')->with('success', 'Program studi berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'faculty_id' => 'required'
        ]);
        
        $department = Department::findOrFail($id);
        $department->name = $request->name;
        $department->faculty_id = $request->faculty_id;
        $department->save();

        return redirect()->route('departments.index')->with('success', 'Program studi berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $department = Department::findOrFail($id)->delete();
        return redirect()->route('departments.index')->with('success', 'Program studi berhasil dihapus!');
    }
}

?>