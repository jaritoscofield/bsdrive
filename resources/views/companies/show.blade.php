@extends('layouts.dashboard')

@section('title', $company->name . ' - BSDrive')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">{{ $company->name }}</h1>
            <p class="text-neutral-600">Detalhes da empresa</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('companies.edit', $company) }}" class="inline-flex items-center bg-neutral-900 text-white px-4 py-2 rounded-lg hover:bg-neutral-800 transition-colors shadow-sm">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Editar
            </a>
            <a href="{{ route('companies.index') }}" class="inline-flex items-center bg-neutral-100 text-neutral-700 px-4 py-2 rounded-lg hover:bg-neutral-200 transition-colors">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Company Info -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-neutral-900 mb-4">Informações da Empresa</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-neutral-500 mb-1">Nome</label>
                            <p class="text-sm text-neutral-900">{{ $company->name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-500 mb-1">Email</label>
                            <p class="text-sm text-neutral-900">{{ $company->email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-500 mb-1">CNPJ</label>
                            <p class="text-sm text-neutral-900">{{ $company->cnpj }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-500 mb-1">Telefone</label>
                            <p class="text-sm text-neutral-900">{{ $company->phone ?? 'Não informado' }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-neutral-500 mb-1">Endereço</label>
                            <p class="text-sm text-neutral-900">{{ $company->address ?? 'Não informado' }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-500 mb-1">Status</label>
                            @if($company->active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Ativa
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Inativa
                                </span>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-500 mb-1">Criada em</label>
                            <p class="text-sm text-neutral-900">{{ $company->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-neutral-900 mb-4">Estatísticas</h2>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="h-8 w-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                </div>
                                <span class="ml-3 text-sm font-medium text-neutral-900">Usuários</span>
                            </div>
                            <span class="text-2xl font-bold text-blue-600">{{ $company->users_count }}</span>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="h-8 w-8 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <span class="ml-3 text-sm font-medium text-neutral-900">Setores</span>
                            </div>
                            <span class="text-2xl font-bold text-green-600">{{ $company->sectors_count }}</span>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="h-8 w-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="h-4 w-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <span class="ml-3 text-sm font-medium text-neutral-900">Arquivos</span>
                            </div>
                            <span class="text-2xl font-bold text-purple-600">{{ $company->files_count }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    @if($company->users->count() > 0 || $company->sectors->count() > 0 || $company->files->count() > 0)
    <div class="mt-6">
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-neutral-900 mb-4">Atividade Recente</h2>

                <div class="space-y-4">
                    @if($company->users->count() > 0)
                    <div>
                        <h3 class="text-sm font-medium text-neutral-700 mb-2">Usuários Recentes</h3>
                        <div class="space-y-2">
                            @foreach($company->users->take(3) as $user)
                            <div class="flex items-center justify-between p-3 bg-neutral-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 bg-neutral-200 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-neutral-600">{{ substr($user->name, 0, 1) }}</span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-neutral-900">{{ $user->name }}</p>
                                        <p class="text-xs text-neutral-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                                <span class="text-xs text-neutral-500">{{ $user->created_at->format('d/m/Y') }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($company->sectors->count() > 0)
                    <div>
                        <h3 class="text-sm font-medium text-neutral-700 mb-2">Setores</h3>
                        <div class="space-y-2">
                            @foreach($company->sectors->take(3) as $sector)
                            <div class="flex items-center justify-between p-3 bg-neutral-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 bg-green-100 rounded-lg flex items-center justify-center">
                                        <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-neutral-900">{{ $sector->name }}</p>
                                        <p class="text-xs text-neutral-500">{{ $sector->description ?? 'Sem descrição' }}</p>
                                    </div>
                                </div>
                                <span class="text-xs text-neutral-500">{{ $sector->created_at->format('d/m/Y') }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
