@extends('layouts.dashboard')

@section('title', 'Editar Usuário - BSDrive')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Editar Usuário</h1>
                <p class="text-neutral-600">Atualize as informações do usuário</p>
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
        <form action="{{ route('users.update', $user) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')

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
                           value="{{ old('name', $user->name) }}"
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
                           value="{{ old('email', $user->email) }}"
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
                        Nova Senha
                    </label>
                    <input type="password"
                           id="password"
                           name="password"
                           placeholder="Deixe em branco para manter a atual"
                           class="w-full px-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500 transition-colors @error('password') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                    <p class="mt-1 text-xs text-neutral-500">Deixe em branco para manter a senha atual</p>
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
                        Confirmar Nova Senha
                    </label>
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           placeholder="Confirme a nova senha"
                           class="w-full px-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500 transition-colors">
                </div>

                <!-- Papel -->
                <div>
                    <label for="role" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Papel no Sistema
                    </label>
                    <select id="role"
                            name="role"
                            class="w-full px-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500 transition-colors @error('role') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                        <option value="">Selecione um papel</option>
                        @if(auth()->user()->role === 'admin_sistema')
                            <option value="admin_sistema" {{ old('role', $user->role) === 'admin_sistema' ? 'selected' : '' }}>Admin Sistema</option>
                        @endif
                        <option value="admin_empresa" {{ old('role', $user->role) === 'admin_empresa' ? 'selected' : '' }}>Admin Empresa</option>
                        <option value="usuario" {{ old('role', $user->role) === 'usuario' ? 'selected' : '' }}>Usuário</option>
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Empresa -->
                <div>
                    <label for="company_id" class="block text-sm font-medium text-neutral-700 mb-2">
                        <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        Empresa
                    </label>
                    <select id="company_id"
                            name="company_id"
                            class="w-full px-4 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500 transition-colors @error('company_id') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                        <option value="">Selecione uma empresa</option>
                        @foreach($companies as $company)
                            @if(auth()->user()->role === 'admin_sistema' || $company->id === auth()->user()->company_id)
                                <option value="{{ $company->id }}" {{ old('company_id', $user->company_id) == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>

                    <!-- Campo hidden para garantir que o valor seja enviado quando desabilitado -->
                    @if(auth()->user()->role === 'admin_empresa')
                        <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
                    @endif

                    @error('company_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Pastas que o usuário poderá acessar -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-neutral-700 mb-2">
                        Pastas que o usuário poderá acessar
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-48 overflow-y-auto border rounded p-2 bg-neutral-50">
                        @foreach($availableFolders as $folder)
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="folder_ids[]" value="{{ $folder['id'] }}" class="rounded border-neutral-300 text-neutral-900 shadow-sm focus:ring-neutral-500" @if(in_array($folder['id'], $userFolderIds)) checked @endif>
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

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-3 mt-8 pt-6 border-t border-neutral-200">
                <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-neutral-900 border border-transparent rounded-lg hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 transition-colors">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Atualizar Usuário
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Auto-select company if user is admin_empresa
    @if(auth()->user()->role === 'admin_empresa')
        document.getElementById('company_id').value = '{{ auth()->user()->company_id }}';
        document.getElementById('company_id').disabled = true;
    @endif
</script>

@endsection
