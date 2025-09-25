@extends('layouts.dashboard')

@section('title', 'Adicionar Pasta - BSDrive')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Adicionar Pasta</h1>
            <p class="text-neutral-600">{{ $user->name }} - Adicione uma pasta que o usuário poderá acessar</p>
        </div>
        <a href="{{ route('users.folders.index', $user) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-600 bg-neutral-100 border border-neutral-300 rounded-lg hover:bg-neutral-200 transition-colors">
            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Voltar
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
        <div class="p-6">
            <form action="{{ route('users.folders.store', $user) }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Pasta do Google Drive -->
                <div>
                    <label for="google_drive_folder_id" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-5l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                        Pasta do Google Drive *
                    </label>
                    <select name="google_drive_folder_id" id="google_drive_folder_id" class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500" required>
                        <option value="">Selecione uma pasta</option>
                        @foreach($availableFolders as $folder)
                            <option value="{{ $folder['id'] }}" data-name="{{ $folder['name'] }}">
                                {{ $folder['name'] }} ({{ $folder['id'] }})
                            </option>
                        @endforeach
                    </select>
                    @error('google_drive_folder_id')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nome da Pasta -->
                <div>
                    <label for="folder_name" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Nome da Pasta *
                    </label>
                    <input type="text" name="folder_name" id="folder_name" class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500" placeholder="Nome da pasta para referência" required>
                    @error('folder_name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Descrição -->
                <div>
                    <label for="description" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Descrição (opcional)
                    </label>
                    <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500" placeholder="Descreva a pasta..."></textarea>
                    @error('description')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nível de Permissão -->
                <div>
                    <label for="permission_level" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Nível de Permissão *
                    </label>
                    <select name="permission_level" id="permission_level" class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500" required>
                        <option value="">Selecione o nível de permissão</option>
                        <option value="read">Leitura - Apenas visualizar arquivos</option>
                        <option value="write">Escrita - Visualizar e editar arquivos</option>
                        <option value="admin">Administrador - Controle total da pasta</option>
                    </select>
                    @error('permission_level')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-neutral-200">
                    <a href="{{ route('users.folders.index', $user) }}" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-neutral-900 border border-transparent rounded-lg hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 transition-colors">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Adicionar Pasta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Auto-fill folder name when selecting a folder
    document.getElementById('google_drive_folder_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const folderNameInput = document.getElementById('folder_name');
        
        if (selectedOption && selectedOption.dataset.name) {
            folderNameInput.value = selectedOption.dataset.name;
        }
    });
</script>

@endsection 