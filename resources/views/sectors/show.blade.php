@extends('layouts.dashboard')

@section('title', 'Detalhes do Setor - BSDrive')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Detalhes do Setor</h1>
            <p class="text-neutral-600">Informações completas sobre o setor</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('sectors.edit', $sector) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 transition-colors">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Editar Setor
            </a>
            <a href="{{ route('sectors.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-600 bg-neutral-100 border border-neutral-300 rounded-lg hover:bg-neutral-200 transition-colors">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
                <div class="p-6">
                    <div class="flex items-center mb-6">
                        <div class="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-neutral-900">{{ $sector->name }}</h2>
                            <p class="text-neutral-500">ID: {{ $sector->id }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-neutral-200">
                            <span class="text-sm font-medium text-neutral-700">Empresa</span>
                            <span class="text-sm text-neutral-900">{{ $sector->company->name ?? 'Sem empresa' }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between py-3 border-b border-neutral-200">
                            <span class="text-sm font-medium text-neutral-700">Descrição</span>
                            <span class="text-sm text-neutral-900">{{ $sector->description ?? 'Sem descrição' }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between py-3 border-b border-neutral-200">
                            <span class="text-sm font-medium text-neutral-700">Status</span>
                            @if($sector->active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Ativo
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Inativo
                                </span>
                            @endif
                        </div>
                        
                        <div class="flex items-center justify-between py-3 border-b border-neutral-200">
                            <span class="text-sm font-medium text-neutral-700">Criado em</span>
                            <span class="text-sm text-neutral-900">{{ $sector->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between py-3">
                            <span class="text-sm font-medium text-neutral-700">Última atualização</span>
                            <span class="text-sm text-neutral-900">{{ $sector->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-neutral-900 mb-4">Estatísticas</h3>
                    
                    <div class="space-y-4">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="h-8 w-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-blue-600">{{ $sector->files_count ?? 0 }}</div>
                                    <div class="text-sm text-blue-700">Arquivos</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="h-8 w-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-5l-2-2H5a2 2 0 00-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-green-600">{{ $sector->folders_count ?? 0 }}</div>
                                    <div class="text-sm text-green-700">Pastas</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 