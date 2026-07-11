<?php

namespace App\Http\Controllers;

use App\Models\MasterProgram;
use App\Imports\MasterProgramImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class MasterProgramController extends Controller
{
    public function index()
    {
        $masterPrograms = MasterProgram::with('parent')->get();
        return view('master-program.index', compact('masterPrograms'));
    }



    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $import = new MasterProgramImport;
        Excel::import($import, $request->file('file'));

        $msg = "Import selesai: {$import->importedCount} data berhasil diimport.";

        if ($import->skippedCount > 0) {
            $msg .= " {$import->skippedCount} baris dilewati (kosong/error).";
        }

        $errors = $import->getAllErrors();
        if (!empty($errors)) {
            return back()
                ->with('warning', $msg)
                ->with('import_errors', array_slice($errors, 0, 10)); // max 10 error ditampilkan
        }

        return back()->with('success', $msg);
    }

    public function create()
    {
        $parentPrograms = MasterProgram::all();
        return view('master-program.create', compact('parentPrograms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|unique:master_program,kode',
            'nama' => 'required',
            'program' => 'nullable|string',
            'sub_program' => 'nullable|string',
            'parent_id' => 'nullable|exists:master_program,id',
            'level' => 'required|integer',
        ]);

        MasterProgram::create($validated);

        return redirect()->route('master-program.index')->with('success', 'Master Program berhasil ditambahkan.');
    }

    public function edit(MasterProgram $masterProgram)
    {
        $parentPrograms = MasterProgram::where('id', '!=', $masterProgram->id)->get();
        return view('master-program.edit', compact('masterProgram', 'parentPrograms'));
    }

    public function update(Request $request, MasterProgram $masterProgram)
    {
        $validated = $request->validate([
            'kode' => 'required|unique:master_program,kode,' . $masterProgram->id,
            'nama' => 'required',
            'program' => 'nullable|string',
            'sub_program' => 'nullable|string',
            'parent_id' => 'nullable|exists:master_program,id',
            'level' => 'required|integer',
        ]);

        $masterProgram->update($validated);

        return redirect()->route('master-program.index')->with('success', 'Master Program berhasil diupdate.');
    }

    public function destroy(MasterProgram $masterProgram)
    {
        $masterProgram->delete();
        return back()->with('success', 'Master Program berhasil dihapus.');
    }
}
