<?php

namespace App\Http\Controllers;

use App\Models\Cooperation;
use App\Models\Ia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Auth;

class IaController extends Controller
{
    public function index($cooperationId)
    {
        $cooperation = Cooperation::findOrFail($cooperationId);
        $user = Auth::user();

        $ias = Ia::where('cooperation_id', $cooperation->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('ias.index', compact('cooperation', 'ias', 'user'));
    }

    public function store(Request $request, $cooperationId)
    {
        $cooperation = Cooperation::findOrFail($cooperationId);

        $request->validate([
            'mou_name' => ['required','string','max:255'],
            'ia_name'  => ['required','string','max:255'],
            'file'     => ['required','mimes:pdf','max:10240'],
            'proof'    => ['nullable','mimes:pdf,jpg,jpeg,png','max:10240'],
        ]);

        // Pastikan folder tujuan ada
        $destPdf   = public_path('ias');
        $destProof = public_path('ia_proofs');
        if (!File::exists($destPdf))   { File::makeDirectory($destPdf, 0755, true); }
        if (!File::exists($destProof)) { File::makeDirectory($destProof, 0755, true); }

        // Upload IA PDF
        $iaFile = $request->file('file');
        $iaName = time() . '_' . $iaFile->getClientOriginalName();
        $iaFile->move($destPdf, $iaName);

        // Upload Proof (opsional)
        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofFile = $request->file('proof');
            $proofName = time() . '_' . $proofFile->getClientOriginalName();
            $proofFile->move($destProof, $proofName);
            $proofPath = 'ia_proofs/' . $proofName;
        }

        Ia::create([
            'cooperation_id' => $cooperation->id,
            'mou_name'       => $request->mou_name,
            'ia_name'        => $request->ia_name,
            'file'           => 'ias/' . $iaName,
            'proof'          => $proofPath,
        ]);

        return redirect()->route('ias.index', $cooperation->id)->with('success', 'IA created successfully.');
    }

    public function update(Request $request, $cooperationId, $iaId)
    {
        $cooperation = Cooperation::findOrFail($cooperationId);
        $ia = Ia::where('cooperation_id', $cooperation->id)->findOrFail($iaId);

        $request->validate([
            'mou_name' => ['required','string','max:255'],
            'ia_name'  => ['required','string','max:255'],
            'file'     => ['nullable','mimes:pdf','max:10240'],
            'proof'    => ['nullable','mimes:pdf,jpg,jpeg,png','max:10240'],
        ]);

        $ia->mou_name = $request->mou_name;
        $ia->ia_name  = $request->ia_name;

        $destPdf   = public_path('ias');
        $destProof = public_path('ia_proofs');
        if (!File::exists($destPdf))   { File::makeDirectory($destPdf, 0755, true); }
        if (!File::exists($destProof)) { File::makeDirectory($destProof, 0755, true); }

        if ($request->hasFile('file')) {
            if ($ia->file && File::exists(public_path($ia->file))) {
                @File::delete(public_path($ia->file));
            }
            $iaFile = $request->file('file');
            $iaName = time() . '_' . $iaFile->getClientOriginalName();
            $iaFile->move($destPdf, $iaName);
            $ia->file = 'ias/' . $iaName;
        }

        if ($request->hasFile('proof')) {
            if ($ia->proof && File::exists(public_path($ia->proof))) {
                @File::delete(public_path($ia->proof));
            }
            $proofFile = $request->file('proof');
            $proofName = time() . '_' . $proofFile->getClientOriginalName();
            $proofFile->move($destProof, $proofName);
            $ia->proof = 'ia_proofs/' . $proofName;
        }

        $ia->save();

        return redirect()->route('ias.index', $cooperation->id)->with('success', 'IA updated successfully.');
    }

    public function destroy($cooperationId, $iaId)
    {
        $cooperation = Cooperation::findOrFail($cooperationId);
        $ia = Ia::where('cooperation_id', $cooperation->id)->findOrFail($iaId);

        if ($ia->file && File::exists(public_path($ia->file))) {
            @File::delete(public_path($ia->file));
        }
        if ($ia->proof && File::exists(public_path($ia->proof))) {
            @File::delete(public_path($ia->proof));
        }

        $ia->delete();

        return redirect()->route('ias.index', $cooperation->id)->with('success', 'IA deleted successfully.');
    }
}
