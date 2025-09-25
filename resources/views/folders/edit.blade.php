@extends('layouts.dashboard')

@section('title', 'Editar Pasta')

@section('content')
<div class="p-6">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-neutral-900">Editar Pasta</h1>
                    <p class="text-neutral-600">Modifique as informações da pasta</p>
                </div>
                <a href="{{ route('folders.show', $folderArray['id']) }}" class="inline-flex items-center px-4 py-2 bg-neutral-600 text-white text-sm font-medium rounded-lg hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>

        <!-- Exibir erros de validação -->
        @if($errors->any())
            <div class="mb-6">
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Erro!</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Formulário de edição -->
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
            <div class="p-6">
                <form action="{{ route('folders.update', $folderArray['id']) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Nome -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            </svg>
                            Nome da Pasta
                        </label>
                        <input type="text"
                               name="name"
                               id="name"
                               class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500"
                               placeholder="Nome da pasta"
                               value="{{ old('name', $folderArray['name']) }}"
                               required>
                    </div>

                    <!-- Descrição -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Descrição (opcional)
                        </label>
                        <textarea name="description"
                                  id="description"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500"
                                  placeholder="Descreva a pasta...">{{ old('description', $folderArray['description'] ?? '') }}</textarea>
                    </div>

                    <!-- Pasta Pai -->
                    <div>
                        <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-2">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            </svg>
                            Pasta Pai (opcional)
                        </label>
                        <select name="parent_id"
                                id="parent_id"
                                class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500">
                            <option value="">Nenhuma (pasta raiz)</option>
                            @foreach($folders as $parentFolder)
                                <option value="{{ $parentFolder['id'] }}"
                                        {{ old('parent_id', $folderArray['parents'][0] ?? '') == $parentFolder['id'] ? 'selected' : '' }}>
                                    {{ $parentFolder['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Setor -->
                    <div>
                        <label for="sector_id" class="block text-sm font-medium text-gray-700 mb-2">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-6 0h6"></path>
                            </svg>
                            Setor
                        </label>
                        <select name="sector_id" id="sector_id" class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500" required>
                            <option value="">Selecione o setor</option>
                            @foreach($sectors as $sector)
                                <option value="{{ $sector->id }}" {{ $folderArray['sector_id'] == $sector->id ? 'selected' : '' }}>{{ $sector->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Informações da Pasta -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                ID da Pasta
                            </label>
                            <input type="text"
                                   class="w-full px-3 py-2 border border-neutral-300 rounded-lg bg-neutral-50 text-neutral-600"
                                   value="{{ $folderArray['id'] }}"
                                   readonly>
                            <p class="text-xs text-neutral-500 mt-1">ID único da pasta no BSDrive</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Tipo
                            </label>
                            <input type="text"
                                   class="w-full px-3 py-2 border border-neutral-300 rounded-lg bg-neutral-50 text-neutral-600"
                                   value="Pasta do BSDrive"
                                   readonly>
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('folders.show', $folderArray['id']) }}"
                           class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-6 py-2 bg-neutral-900 text-white text-sm font-medium rounded-lg hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Atualizar Pasta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Validação do formulário
        $('form').on('submit', function(e) {
            const name = $('#name').val().trim();

            if (!name) {
                e.preventDefault();
                alert('Por favor, preencha o nome da pasta.');
                $('#name').focus();
                return false;
            }
        });
    });
</script>
@endpush
