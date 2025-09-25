@extends('layouts.dashboard')

@section('title', 'Editar Pasta: ' . $folder['name'] . ' - BSDrive')

@section('content')
<div class="min-h-screen bg-neutral-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-neutral-900 flex items-center">
                <svg class="w-8 h-8 mr-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Editar Pasta
            </h1>
            <div>
                <a href="{{ route('folders.show', $folder['id']) }}" 
                   class="inline-flex items-center px-4 py-2 bg-neutral-600 hover:bg-neutral-700 text-white font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>

        <!-- Form Card -->
        <div class="max-w-2xl">
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h2 class="text-lg font-semibold text-neutral-900">Informações da Pasta</h2>
                </div>
                <div class="p-6">
                    <form action="{{ route('folders.update', $folder['id']) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="name" class="block text-sm font-medium text-neutral-700 mb-2">
                                Nome da Pasta 
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('name') border-red-300 ring-2 ring-red-500 @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $folder['name']) }}" 
                                   required 
                                   placeholder="Digite o novo nome da pasta">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Salvar Alterações
                            </button>
                            <a href="{{ route('folders.show', $folder['id']) }}" 
                               class="inline-flex items-center px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Info Card -->
        <div class="max-w-md mt-6">
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-semibold text-neutral-900">Informações</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-start space-x-2">
                        <svg class="w-5 h-5 mt-0.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm text-neutral-600">
                            A pasta será renomeada diretamente no BSDrive.
                        </p>
                    </div>
                    <div class="space-y-2">
                        <div>
                            <span class="text-sm font-medium text-neutral-700">Nome atual:</span>
                            <p class="text-sm text-neutral-600">{{ $folder['name'] }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-neutral-700">ID:</span>
                            <p class="text-xs text-neutral-500 font-mono bg-neutral-100 px-2 py-1 rounded">{{ $folder['id'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Danger Zone Card -->
        <div class="max-w-md mt-6">
            <div class="bg-white rounded-lg shadow-sm border border-red-200">
                <div class="px-6 py-4 border-b border-red-200">
                    <h3 class="text-lg font-semibold text-red-700">Zona de Perigo</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-start space-x-2">
                        <svg class="w-5 h-5 mt-0.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <p class="text-sm text-red-600">
                            Excluir uma pasta é uma ação irreversível.
                        </p>
                    </div>
                    <form action="{{ route('folders.destroy', $folder['id']) }}" 
                          method="POST" 
                          onsubmit="return confirm('Tem certeza que deseja excluir esta pasta? Esta ação não pode ser desfeita.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Excluir Pasta
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
