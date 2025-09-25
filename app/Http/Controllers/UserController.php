<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $query = User::with(['company'])->withCount('files');

        // Se não for admin_sistema, mostrar apenas usuários da mesma empresa
        if (Auth::user()->role !== 'admin_sistema') {
            $query->where('company_id', Auth::user()->company_id);
        }

        $users = $query->paginate(10);
        $companies = Company::where('active', true)->get();

        return view('users.index', compact('users', 'companies'));
    }

    public function create()
    {
        $companies = Company::where('active', true)->get();
        $companyId = null;
        if (auth()->user()->role === 'admin_empresa') {
            $companyId = auth()->user()->company_id;
        } elseif (auth()->user()->role === 'admin_sistema' && request('company_id')) {
            $companyId = request('company_id');
        }
        $availableFolders = [];
        if ($companyId) {
            // Buscar pastas que a empresa tem acesso através do modelo CompanyFolder
            $company = Company::find($companyId);
            if ($company) {
                $availableFolders = $company->companyFolders()
                    ->where('active', true)
                    ->get()
                    ->map(function($companyFolder) {
                        return [
                            'id' => $companyFolder->google_drive_folder_id,
                            'name' => $companyFolder->folder_name,
                            'description' => $companyFolder->description
                        ];
                    })
                    ->toArray();
            }
        }
        return view('users.create', compact('companies', 'availableFolders', 'companyId'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin_sistema,admin_empresa,usuario',
            'company_id' => 'required|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Verificar permissões
        if (Auth::user()->role === 'admin_empresa' && $request->role === 'admin_sistema') {
            return back()->withErrors(['role' => 'Você não tem permissão para criar usuários admin do sistema.'])->withInput();
        }

        if (Auth::user()->role === 'admin_empresa' && $request->company_id != Auth::user()->company_id) {
            return back()->withErrors(['company_id' => 'Você só pode criar usuários para sua própria empresa.'])->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'company_id' => $request->company_id,
        ]);
        // Salvar permissões de pastas
        if ($request->has('folder_ids')) {
            foreach ($request->folder_ids as $folderId) {
                $user->userFolders()->create([
                    'google_drive_folder_id' => $folderId,
                    'folder_name' => '', // pode ser preenchido depois
                    'permission_level' => 'read',
                    'active' => true,
                ]);
            }
        }

        return redirect()->route('users.index')
            ->with('success', 'Usuário criado com sucesso!');
    }

    public function show(User $user)
    {
        // Verificar permissões
        if (Auth::user()->role === 'admin_empresa' && $user->company_id !== Auth::user()->company_id) {
            abort(403, 'Acesso negado.');
        }

        $user->load(['company', 'files']);
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        // Verificar permissões
        if (Auth::user()->role === 'admin_empresa' && $user->company_id !== Auth::user()->company_id) {
            abort(403, 'Acesso negado.');
        }

        $companies = Company::where('active', true)->get();
        $company = $user->company;
        $availableFolders = [];
        if ($company) {
            // Buscar pastas que a empresa tem acesso através do modelo CompanyFolder
            $availableFolders = $company->companyFolders()
                ->where('active', true)
                ->get()
                ->map(function($companyFolder) {
                    return [
                        'id' => $companyFolder->google_drive_folder_id,
                        'name' => $companyFolder->folder_name,
                        'description' => $companyFolder->description
                    ];
                })
                ->toArray();
        }
        $userFolderIds = $user->userFolders()->pluck('google_drive_folder_id')->toArray();
        return view('users.edit', compact('user', 'companies', 'availableFolders', 'userFolderIds'));
    }

    public function update(Request $request, User $user)
    {
        // Verificar permissões
        if (Auth::user()->role === 'admin_empresa' && $user->company_id !== Auth::user()->company_id) {
            abort(403, 'Acesso negado.');
        }



        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:admin_sistema,admin_empresa,usuario',
            'company_id' => 'required|exists:companies,id',
        ]);



        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }



        // Verificar permissões
        if (Auth::user()->role === 'admin_empresa' && $request->role === 'admin_sistema') {
            return back()->withErrors(['role' => 'Você não tem permissão para alterar usuários para admin do sistema.'])->withInput();
        }

        if (Auth::user()->role === 'admin_empresa' && $request->company_id != Auth::user()->company_id) {
            return back()->withErrors(['company_id' => 'Você só pode alterar usuários para sua própria empresa.'])->withInput();
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'company_id' => $request->company_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Atualizar permissões de pastas
        $user->userFolders()->delete();
        if ($request->has('folder_ids')) {
            foreach ($request->folder_ids as $folderId) {
                $user->userFolders()->create([
                    'google_drive_folder_id' => $folderId,
                    'folder_name' => '',
                    'permission_level' => 'read',
                    'active' => true,
                ]);
            }
        }

        return redirect()->route('users.index')
            ->with('success', 'Usuário atualizado com sucesso!');
    }

    public function destroy(User $user)
    {
        // Verificar permissões
        if (Auth::user()->role === 'admin_empresa' && $user->company_id !== Auth::user()->company_id) {
            abort(403, 'Acesso negado.');
        }

        // Não permitir excluir o próprio usuário
        if ($user->id === Auth::id()) {
            return back()->withErrors(['delete' => 'Você não pode excluir sua própria conta.']);
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Usuário excluído com sucesso!');
    }
}
