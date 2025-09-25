@extends('layouts.dashboard')

@section('title', 'Editar Permissão - BSDrive')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Editar Permissão</h1>
                <p class="text-neutral-600">Atualize as informações da permissão</p>
            </div>
            <a href="{{ route('permissions.index') }}" class="inline-flex items-center text-neutral-600 hover:text-neutral-900 transition-colors">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar para Permissões
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
        <form action="{{ route('permissions.update', $permission) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nome -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Nome da Permissão
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $permission->name) }}"
                           placeholder="Ex: Criar Empresas"
                           class="w-full px-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500 transition-colors @error('name') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Descrição -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                        </svg>
                        Descrição
                    </label>
                    <textarea id="description"
                              name="description"
                              rows="3"
                              placeholder="Descreva o que esta permissão permite fazer..."
                              class="w-full px-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500 transition-colors @error('description') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">{{ old('description', $permission->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Módulo -->
                <div>
                    <label for="module" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Módulo
                    </label>
                    <select id="module"
                            name="module"
                            class="w-full px-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500 transition-colors @error('module') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                        <option value="">Selecione um módulo</option>
                        @foreach($modules as $key => $value)
                            <option value="{{ $key }}" {{ old('module', $permission->module) === $key ? 'selected' : '' }}>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                    @error('module')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Ação -->
                <div>
                    <label for="action" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Ação
                    </label>
                    <select id="action"
                            name="action"
                            class="w-full px-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500 transition-colors @error('action') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                        <option value="">Selecione uma ação</option>
                        @foreach($actions as $key => $value)
                            <option value="{{ $key }}" {{ old('action', $permission->action) === $key ? 'selected' : '' }}>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                    @error('action')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Preview -->
            <div class="mt-6 p-4 bg-neutral-50 rounded-lg border border-neutral-200">
                <h3 class="text-sm font-medium text-neutral-900 mb-2">Preview da Permissão</h3>
                <div class="text-sm text-neutral-600">
                    <p><strong>Slug:</strong> <span id="slug-preview" class="font-mono text-neutral-800">{{ $permission->slug }}</span></p>
                    <p><strong>Descrição:</strong> <span id="description-preview" class="text-neutral-800">{{ $permission->description ?: 'Sem descrição' }}</span></p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-3 mt-8 pt-6 border-t border-neutral-200">
                <a href="{{ route('permissions.index') }}" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-neutral-900 border border-transparent rounded-lg hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 transition-colors">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Atualizar Permissão
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Preview em tempo real
    const moduleSelect = document.getElementById('module');
    const actionSelect = document.getElementById('action');
    const slugPreview = document.getElementById('slug-preview');
    const descriptionPreview = document.getElementById('description-preview');

    function updatePreview() {
        const module = moduleSelect.value;
        const action = actionSelect.value;

        if (module && action) {
            const slug = module + '-' + action;
            slugPreview.textContent = slug;

            const moduleNames = {
                'companies': 'Empresas',
                'users': 'Usuários',
                'sectors': 'Setores',
                'folders': 'Pastas',
                'files': 'Arquivos'
            };

            const actionNames = {
                'create': 'Criar',
                'read': 'Visualizar',
                'update': 'Editar',
                'delete': 'Excluir',
                'manage': 'Gerenciar'
            };

            const description = `${actionNames[action]} ${moduleNames[module]}`;
            descriptionPreview.textContent = description;
        } else {
            slugPreview.textContent = '-';
            descriptionPreview.textContent = '-';
        }
    }

    moduleSelect.addEventListener('change', updatePreview);
    actionSelect.addEventListener('change', updatePreview);
</script>

@endsection
