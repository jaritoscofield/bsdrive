@extends('layouts.dashboard')

@section('title', 'Criar Usuário - BSDrive')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Criar Novo Usuário</h1>
                <p class="text-neutral-600">Adicione um novo usuário ao sistema</p>
            </div>
            <a href="{{ route('users.index') }}" class="inline-flex items-center text-neutral-600 hover:text-neutral-900 transition-colors">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar para Usuários
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
        <form action="{{ route('users.store') }}" method="POST" class="p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nome -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Nome Completo
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           placeholder="Digite o nome completo do usuário"
                           class="w-full px-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500 transition-colors @error('name') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div class="md:col-span-2">
                    <label for="email" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                        </svg>
                        Email
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="usuario@empresa.com"
                           class="w-full px-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500 transition-colors @error('email') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Senha -->
                <div>
                    <label for="password" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Senha
                    </label>
                    <input type="password"
                           id="password"
                           name="password"
                           placeholder="Mínimo 8 caracteres"
                           class="w-full px-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500 transition-colors @error('password') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirmar Senha -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Confirmar Senha
                    </label>
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           placeholder="Confirme a senha"
                           class="w-full px-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500 transition-colors">
                </div>

                <!-- Papel -->
                <div>
                    <label for="role" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.025 12.025 0 002 12a12.025 12.025 0 001.382 6.056A11.955 11.955 0 0112 21.056a11.955 11.955 0 018.618-3.04A12.025 12.025 0 0022 12a12.025 12.025 0 00-1.382-6.056z"></path>
                        </svg>
                        Papel
                    </label>
                    <select id="role"
                            name="role"
                            class="w-full px-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500 transition-colors @error('role') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                        <option value="">Selecione o papel do usuário</option>
                        <option value="admin_sistema" {{ old('role') === 'admin_sistema' ? 'selected' : '' }}>Administrador</option>
                        <option value="usuario" {{ old('role') === 'usuario' ? 'selected' : '' }}>Usuário Comum</option>
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Empresa (oculto para admin) -->
                @if(auth()->user()->role !== 'admin')
                <div>
                    <label for="company_id" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"></path>
                        </svg>
                        Empresa
                    </label>
                    <select id="company_id"
                            name="company_id"
                            class="w-full px-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500 transition-colors @error('company_id') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                        <option value="">Selecione a empresa</option>
                        @foreach($companies as $company)
                            @if(auth()->user()->role === 'admin_empresa' && auth()->user()->company_id === $company->id)
                                <option value="{{ $company->id }}" {{ (isset($companyId) && $companyId == $company->id) ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @elseif(auth()->user()->role === 'admin_sistema')
                                <option value="{{ $company->id }}" {{ (isset($companyId) && $companyId == $company->id) ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('company_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                <!-- Pastas que o usuário poderá acessar -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-neutral-700 mb-2">
                        Pastas que o usuário poderá acessar
                    </label>
                    <div id="folders-checkboxes" class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-48 overflow-y-auto border rounded p-2 bg-neutral-50">
                        @foreach($availableFolders as $folder)
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="folder_ids[]" value="{{ $folder['id'] }}" class="rounded border-neutral-300 text-neutral-900 shadow-sm focus:ring-neutral-500">
                                <span>{{ $folder['name'] }}</span>
                            </label>
                        @endforeach
                        @if(count($availableFolders) === 0)
                            <span class="text-neutral-500 text-sm">Nenhuma pasta disponível para seleção.</span>
                        @endif
                    </div>
                    @error('folder_ids')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Ações -->
            <div class="mt-6">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg shadow-md hover:bg-blue-700 transition-colors">
                    Criar Usuário
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Garantir que o campo company_id seja enviado corretamente
    @if(auth()->user()->role === 'admin_empresa')
        document.getElementById('company_id').value = '{{ auth()->user()->company_id }}';
    @endif

    // Atualizar pastas ao selecionar empresa (apenas para admin_sistema)
    @if(auth()->user()->role === 'admin_sistema')
    document.getElementById('company_id').addEventListener('change', function(e) {
        var companyId = e.target.value;
        var foldersDiv = document.getElementById('folders-checkboxes');
        foldersDiv.innerHTML = '<span class="text-neutral-500 text-sm">Carregando pastas...</span>';
        if(companyId) {
            fetch('/api/empresas/' + companyId + '/pastas')
                .then(response => response.json())
                .then(data => {
                    if(data.length === 0) {
                        foldersDiv.innerHTML = '<span class="text-neutral-500 text-sm">Nenhuma pasta disponível para seleção.</span>';
                    } else {
                        foldersDiv.innerHTML = '';
                        data.forEach(function(folder) {
                            var label = document.createElement('label');
                            label.className = 'flex items-center space-x-2';
                            var checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.name = 'folder_ids[]';
                            checkbox.value = folder.id;
                            checkbox.className = 'rounded border-neutral-300 text-neutral-900 shadow-sm focus:ring-neutral-500';
                            var span = document.createElement('span');
                            span.textContent = folder.name;
                            label.appendChild(checkbox);
                            label.appendChild(span);
                            foldersDiv.appendChild(label);
                        });
                    }
                });
        } else {
            foldersDiv.innerHTML = '<span class="text-neutral-500 text-sm">Nenhuma pasta disponível para seleção.</span>';
        }
    });
    @endif
</script>
@endsection
