@extends('layouts.dashboard')

@section('title', 'Detalhes da Pasta - BSDrive')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Detalhes da Pasta</h1>
            <p class="text-neutral-600">{{ $user->name }} - Informações da pasta</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('users.folders.edit', [$user, $userFolder]) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 transition-colors">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Editar
            </a>
            <a href="{{ route('users.folders.index', $user) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-600 bg-neutral-100 border border-neutral-300 rounded-lg hover:bg-neutral-200 transition-colors">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar
            </a>
        </div>
    </div>

    <!-- User Info -->
    <div class="bg-white rounded-lg shadow-sm border border-neutral-200 mb-6">
        <div class="p-6">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-neutral-100 rounded-full flex items-center justify-center">
                        <svg class="h-6 w-6 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-neutral-900">{{ $user->name }}</h3>
                    <p class="text-neutral-600">{{ $user->email }}</p>
                    <p class="text-sm text-neutral-500">{{ $user->company->name ?? 'Sem empresa' }} • {{ ucfirst($user->role) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Folder Details -->
    <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Folder Info -->
                <div>
                    <h3 class="text-lg font-medium text-neutral-900 mb-4">Informações da Pasta</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-500">Nome da Pasta</label>
                            <p class="mt-1 text-sm text-neutral-900">{{ $userFolder->folder_name }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-neutral-500">ID do Google Drive</label>
                            <code class="mt-1 inline-block bg-neutral-100 px-2 py-1 rounded text-xs">{{ $userFolder->google_drive_folder_id }}</code>
                        </div>
                        
                        @if($userFolder->description)
                            <div>
                                <label class="block text-sm font-medium text-neutral-500">Descrição</label>
                                <p class="mt-1 text-sm text-neutral-900">{{ $userFolder->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Permissions Info -->
                <div>
                    <h3 class="text-lg font-medium text-neutral-900 mb-4">Permissões</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-500">Nível de Permissão</label>
                            <div class="mt-1">
                                @switch($userFolder->permission_level)
                                    @case('read')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Leitura
                                        </span>
                                        <p class="text-xs text-neutral-500 mt-1">Apenas visualizar arquivos</p>
                                        @break
                                    @case('write')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Escrita
                                        </span>
                                        <p class="text-xs text-neutral-500 mt-1">Visualizar e editar arquivos</p>
                                        @break
                                    @case('admin')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Administrador
                                        </span>
                                        <p class="text-xs text-neutral-500 mt-1">Controle total da pasta</p>
                                        @break
                                @endswitch
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-neutral-500">Status</label>
                            <div class="mt-1">
                                @if($userFolder->active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Ativo
                                    </span>
                                    <p class="text-xs text-neutral-500 mt-1">Usuário tem acesso a esta pasta</p>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Inativo
                                    </span>
                                    <p class="text-xs text-neutral-500 mt-1">Acesso temporariamente desativado</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 pt-6 border-t border-neutral-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('users.folders.edit', [$user, $userFolder]) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 transition-colors">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Editar Pasta
                        </a>
                        <form action="{{ route('users.folders.destroy', [$user, $userFolder]) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja remover o acesso a esta pasta?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-700 bg-red-100 border border-red-300 rounded-lg hover:bg-red-200 transition-colors">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Remover Acesso
                            </button>
                        </form>
                    </div>
                    <a href="{{ route('users.folders.index', $user) }}" class="text-sm text-neutral-600 hover:text-neutral-900">
                        ← Voltar para lista
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 