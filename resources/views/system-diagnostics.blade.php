@extends('layouts.dashboard')

@section('title', 'Diagnóstico do Sistema')

@section('content')
<div class="p-6">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold text-neutral-900 mb-6">🔍 Diagnóstico do Sistema</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Configurações -->
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-blue-600 mb-4">⚙️ Configurações</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="font-medium">Shared Drive ID:</span>
                            <span class="{{ config('services.google.shared_drive_id') ? 'text-green-600' : 'text-red-600' }}">
                                {{ config('services.google.shared_drive_id') ?: '❌ Não configurado' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Service Account:</span>
                            <span class="{{ config('services.google.service_account_file') && file_exists(config('services.google.service_account_file')) ? 'text-green-600' : 'text-red-600' }}">
                                {{ config('services.google.service_account_file') && file_exists(config('services.google.service_account_file')) ? '✅ Configurado' : '❌ Não encontrado' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Extensão fileinfo:</span>
                            <span class="{{ extension_loaded('fileinfo') ? 'text-green-600' : 'text-red-600' }}">
                                {{ extension_loaded('fileinfo') ? '✅ Habilitada' : '❌ Desabilitada' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usuário Atual -->
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-green-600 mb-4">👤 Usuário Atual</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="font-medium">ID:</span>
                            <span>{{ Auth::user()->id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Nome:</span>
                            <span>{{ Auth::user()->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Empresa ID:</span>
                            <span class="{{ Auth::user()->company_id ? 'text-green-600' : 'text-red-600' }}">
                                {{ Auth::user()->company_id ?: '❌ Sem empresa' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Pasta Pessoal:</span>
                            <span class="{{ Auth::user()->google_drive_folder_id ? 'text-green-600' : 'text-red-600' }}">
                                {{ Auth::user()->google_drive_folder_id ?: '❌ Não configurada' }}
                            </span>
                        </div>
                        @if(Auth::user()->company)
                        <div class="flex justify-between">
                            <span class="font-medium">Pasta da Empresa:</span>
                            <span class="{{ Auth::user()->company->google_drive_folder_id ? 'text-green-600' : 'text-red-600' }}">
                                {{ Auth::user()->company->google_drive_folder_id ?: '❌ Não configurada' }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Testes -->
        <div class="mt-6 bg-white rounded-lg shadow-sm border border-neutral-200">
            <div class="p-6">
                <h2 class="text-lg font-bold text-purple-600 mb-4">🧪 Testes Disponíveis</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ route('files.create') }}" 
                       class="block p-4 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                        <h3 class="font-bold text-green-800">📤 Upload Normal</h3>
                        <p class="text-sm text-green-600">Upload padrão do sistema</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- Logs -->
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-3">📊 Como Ver os Logs:</h3>
            <div class="bg-gray-800 text-green-400 p-3 rounded text-sm font-mono">
                tail -f storage/logs/laravel.log | findstr "EMERGENCY"
            </div>
            <p class="text-gray-600 mt-2 text-sm">Execute este comando no terminal para acompanhar os logs em tempo real</p>
        </div>
    </div>
</div>
@endsection
