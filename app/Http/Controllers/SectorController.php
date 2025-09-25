<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SectorController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $sectors = $user->role === 'admin_sistema'
            ? Sector::with('company')->withCount(['files', 'folders'])->get()
            : Sector::with('company')->withCount(['files', 'folders'])->where('company_id', $user->company_id)->get();
        
        return view('sectors.index', compact('sectors'));
    }

    public function create()
    {
        $user = Auth::user();
        $companies = $user->role === 'admin_sistema'
            ? Company::all()
            : Company::where('id', $user->company_id)->get();
        return view('sectors.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);
        Sector::create($request->only('company_id', 'name', 'description', 'active'));
        return redirect()->route('sectors.index')->with('success', 'Setor criado com sucesso!');
    }

    public function show(Sector $sector)
    {
        $sector->load('company');
        $sector->files_count = $sector->files()->count();
        $sector->folders_count = $sector->folders()->count();
        return view('sectors.show', compact('sector'));
    }

    public function edit(Sector $sector)
    {
        $user = Auth::user();
        $companies = $user->role === 'admin_sistema'
            ? Company::all()
            : Company::where('id', $user->company_id)->get();
        return view('sectors.edit', compact('sector', 'companies'));
    }

    public function update(Request $request, Sector $sector)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);
        $sector->update($request->only('company_id', 'name', 'description', 'active'));
        return redirect()->route('sectors.index')->with('success', 'Setor atualizado com sucesso!');
    }

    public function destroy(Sector $sector)
    {
        $sector->delete();
        return redirect()->route('sectors.index')->with('success', 'Setor excluído com sucesso!');
    }
}
