<?php

namespace App\Http\Controllers;

use App\Models\Cooperation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Auth;

class CooperationController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        $user = Auth::user();

        $cooperations = Cooperation::with('pic')
            ->orderByDesc('letter_date')
            ->paginate(10);

        return view('cooperations.index', compact('cooperations', 'users', 'user'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'letter_number' => ['required','string','max:255','unique:cooperations,letter_number'],
            'letter_date'   => ['required','date'],
            'partner'       => ['required','string'],
            'type_coop'     => ['required','string'],
            'level'         => ['required','string'],
            'file'          => ['required','mimes:pdf','max:10240'],
            'user_id'       => ['nullable','exists:users,id'],
        ]);

        // Pastikan folder tujuan ada
        $dest = public_path('cooperations');
        if (!File::exists($dest)) {
            File::makeDirectory($dest, 0755, true);
        }

        // Upload file MoU
        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move($dest, $fileName);

        Cooperation::create([
            'letter_number' => $request->letter_number,
            'letter_date'   => $request->letter_date,
            'partner'       => $request->partner,
            'type_coop'     => $request->type_coop,
            'level'         => $request->level,
            'file'          => 'cooperations/' . $fileName, // simpan relative path
            'user_id'       => $request->user_id,
        ]);

        return redirect()->route('cooperations.index')->with('success', 'Cooperation created successfully.');
    }

    public function update(Request $request, $id)
    {
        $coop = Cooperation::findOrFail($id);

        $request->validate([
            'letter_number' => ['required','string','max:255', Rule::unique('cooperations','letter_number')->ignore($coop->id)],
            'letter_date'   => ['required','date'],
            'partner'       => ['required','string'],
            'type_coop'     => ['required','string'],
            'level'         => ['required','string'],
            'file'          => ['nullable','mimes:pdf','max:10240'],
            'user_id'       => ['nullable','exists:users,id'],
        ]);

        $coop->letter_number = $request->letter_number;
        $coop->letter_date   = $request->letter_date;
        $coop->partner       = $request->partner;
        $coop->type_coop     = $request->type_coop;
        $coop->level         = $request->level;
        $coop->user_id       = $request->user_id;

        if ($request->hasFile('file')) {
            $dest = public_path('cooperations');
            if (!File::exists($dest)) {
                File::makeDirectory($dest, 0755, true);
            }

            // Hapus file lama
            if ($coop->file && File::exists(public_path($coop->file))) {
                @File::delete(public_path($coop->file));
            }

            // Upload baru
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move($dest, $fileName);
            $coop->file = 'cooperations/' . $fileName;
        }

        $coop->save();

        return redirect()->route('cooperations.index')->with('success', 'Cooperation updated successfully.');
    }

    public function destroy($id)
    {
        $coop = Cooperation::with('ias')->findOrFail($id);

        // Hapus file IA (pdf & proof)
        foreach ($coop->ias as $ia) {
            if ($ia->file && File::exists(public_path($ia->file))) {
                @File::delete(public_path($ia->file));
            }
            if ($ia->proof && File::exists(public_path($ia->proof))) {
                @File::delete(public_path($ia->proof));
            }
        }

        // Hapus file MoU
        if ($coop->file && File::exists(public_path($coop->file))) {
            @File::delete(public_path($coop->file));
        }

        $coop->delete();

        return redirect()->route('cooperations.index')->with('success', 'Cooperation deleted successfully.');
    }
}
