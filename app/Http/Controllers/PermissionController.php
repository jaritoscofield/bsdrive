<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions = Permission::withCount('users')->paginate(10);
        return view('permissions.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $modules = [
            'companies' => 'Empresas',
            'users' => 'Usuários',
            'sectors' => 'Setores',
            'folders' => 'Pastas',
            'files' => 'Arquivos',
        ];

        $actions = [
            'create' => 'Criar',
            'read' => 'Visualizar',
            'update' => 'Editar',
            'delete' => 'Excluir',
            'manage' => 'Gerenciar',
        ];

        return view('permissions.create', compact('modules', 'actions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'module' => 'required|in:companies,users,sectors,folders,files',
            'action' => 'required|in:create,read,update,delete,manage',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $slug = Str::slug($request->module . '-' . $request->action);

        // Verificar se já existe uma permissão com este slug
        if (Permission::where('slug', $slug)->exists()) {
            return back()->withErrors(['module' => 'Esta permissão já existe.'])->withInput();
        }

        Permission::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'module' => $request->module,
            'action' => $request->action,
        ]);

        return redirect()->route('permissions.index')
            ->with('success', 'Permissão criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        $permission->load(['users' => function ($query) {
            $query->wherePivot('granted', true);
        }]);

        return view('permissions.show', compact('permission'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission)
    {
        $modules = [
            'companies' => 'Empresas',
            'users' => 'Usuários',
            'sectors' => 'Setores',
            'folders' => 'Pastas',
            'files' => 'Arquivos',
        ];

        $actions = [
            'create' => 'Criar',
            'read' => 'Visualizar',
            'update' => 'Editar',
            'delete' => 'Excluir',
            'manage' => 'Gerenciar',
        ];

        return view('permissions.edit', compact('permission', 'modules', 'actions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'module' => 'required|in:companies,users,sectors,folders,files',
            'action' => 'required|in:create,read,update,delete,manage',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $slug = Str::slug($request->module . '-' . $request->action);

        // Verificar se já existe uma permissão com este slug (exceto a atual)
        if (Permission::where('slug', $slug)->where('id', '!=', $permission->id)->exists()) {
            return back()->withErrors(['module' => 'Esta permissão já existe.'])->withInput();
        }

        $permission->update([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'module' => $request->module,
            'action' => $request->action,
        ]);

        return redirect()->route('permissions.index')
            ->with('success', 'Permissão atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('permissions.index')
            ->with('success', 'Permissão excluída com sucesso!');
    }

    public function assignUsers(Permission $permission)
    {
        $users = User::with('company')->get();
        $assignedUsers = $permission->users()->pluck('users.id')->toArray();

        return view('permissions.assign-users', compact('permission', 'users', 'assignedUsers'));
    }

    public function updateUserAssignments(Request $request, Permission $permission)
    {
        $validator = Validator::make($request->all(), [
            'users' => 'array',
            'users.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $userIds = $request->input('users', []);

        // Remover todas as atribuições existentes
        $permission->users()->detach();

        // Adicionar as novas atribuições
        if (!empty($userIds)) {
            $permission->users()->attach($userIds, ['granted' => true]);
        }

        return redirect()->route('permissions.show', $permission)
            ->with('success', 'Usuários atribuídos com sucesso!');
    }
}
