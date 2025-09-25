@extends('layouts.dashboard')

@section('title', 'Editar Pasta - BSDrive')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Editar Pasta</h1>
            <p class="text-neutral-600">{{ $company->name }} - Edite as informações da pasta</p>
        </div>
        <a href="{{ route('companies.folders.index', $company) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-600 bg-neutral-100 border border-neutral-300 rounded-lg hover:bg-neutral-200 transition-colors">
            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Voltar
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
        <div class="p-6">
            <form action="{{ route('companies.folders.update', [$company, $companyFolder]) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                
                <!-- ID do Google Drive (readonly) -->
                <div>
                    <label for="google_drive_folder_id" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-5l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                        ID do Google Drive
                    </label>
                    <input type="text" value="{{ $companyFolder->google_drive_folder_id }}" class="w-full px-3 py-2 border border-neutral-300 rounded-lg bg-neutral-50 text-neutral-500" readonly>
                </div>

                <!-- Nome da Pasta -->
                <div>
                    <label for="folder_name" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Nome da Pasta *
                    </label>
                    <input type="text" name="folder_name" id="folder_name" value="{{ old('folder_name', $companyFolder->folder_name) }}" class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500" placeholder="Nome da pasta para referência" required>
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
                    <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500" placeholder="Descreva a pasta...">{{ old('description', $companyFolder->description) }}</textarea>
                    @error('description')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="active" value="1" {{ old('active', $companyFolder->active) ? 'checked' : '' }} class="h-4 w-4 text-neutral-600 focus:ring-neutral-500 border-neutral-300 rounded">
                        <span class="ml-2 text-sm text-neutral-700">Pasta ativa</span>
                    </label>
                    <p class="text-xs text-neutral-500 mt-1">Desmarque para desativar temporariamente o acesso a esta pasta</p>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-neutral-200">
                    <a href="{{ route('companies.folders.index', $company) }}" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-neutral-900 border border-transparent rounded-lg hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 transition-colors">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection 