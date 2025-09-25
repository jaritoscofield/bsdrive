@extends('layouts.dashboard')

@section('title', 'Criar Pasta - BSDrive')

@section('content')
<div class="min-h-screen bg-neutral-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-neutral-900 flex items-center">
                <svg class="w-8 h-8 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Criar Pasta no BSDrive
            </h1>
            <div>
                <a href="{{ route('folders.index', ['parent_id' => $parentId]) }}" 
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
                    <form action="{{ route('folders.store') }}" method="POST" class="space-y-6">
                        @csrf
                        
                        @if($parentId)
                            <input type="hidden" name="parent_id" value="{{ $parentId }}">
                        @endif

                        <div>
                            <label for="name" class="block text-sm font-medium text-neutral-700 mb-2">
                                Nome da Pasta 
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('name') border-red-300 ring-2 ring-red-500 @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required 
                                   placeholder="Digite o nome da pasta">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="sector_id" class="block text-sm font-medium text-neutral-700 mb-2">
                                Setor <span class="text-red-500">*</span>
                            </label>
                            <select name="sector_id" id="sector_id" class="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" required>
                                <option value="">Selecione o setor</option>
                                @if(isset($sectors) && count($sectors) > 0)
                                    @foreach($sectors as $sector)
                                        <option value="{{ $sector->id }}" {{ old('sector_id') == $sector->id ? 'selected' : '' }}>{{ $sector->name }}</option>
                                    @endforeach
                                @else
                                    <option value="">Nenhum setor disponível</option>
                                @endif
                            </select>
                            @error('sector_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Criar Pasta
                            </button>
                            <a href="{{ route('folders.index', ['parent_id' => $parentId]) }}" 
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
                <div class="p-6 space-y-3">
                    <div class="flex items-start space-x-2">
                        <svg class="w-5 h-5 mt-0.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm text-neutral-600">
                            A pasta será criada diretamente no BSDrive.
                        </p>
                    </div>
                    @if($parentId)
                        <div class="flex items-start space-x-2">
                            <svg class="w-5 h-5 mt-0.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-5L12 5H5a2 2 0 00-2 2z"></path>
                            </svg>
                            <p class="text-sm text-neutral-600">
                                Esta pasta será criada dentro da pasta selecionada.
                            </p>
                        </div>
                    @else
                        <div class="flex items-start space-x-2">
                            <svg class="w-5 h-5 mt-0.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <p class="text-sm text-neutral-600">
                                Esta pasta será criada na raiz da sua pasta pessoal.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
