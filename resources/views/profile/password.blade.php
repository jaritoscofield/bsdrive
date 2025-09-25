@extends('layouts.dashboard')

@section('title', 'Alterar Senha')

@section('content')
<div class="max-w-md mx-auto p-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-neutral-900 mb-4">Alterar Senha</h2>
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('profile.password.update') }}">
            @csrf
            <div class="mb-4">
                <label for="current_password" class="block text-sm font-medium text-neutral-700 mb-1">Senha Atual</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.657-1.343-3-3-3s-3 1.343-3 3 1.343 3 3 3 3-1.343 3-3zm0 0v2a4 4 0 008 0v-2" />
                        </svg>
                    </span>
                    <input type="password" name="current_password" id="current_password" required placeholder="Digite sua senha atual" class="pl-10 pr-3 py-2 block w-full rounded-md border border-neutral-300 text-neutral-900 bg-white shadow-sm focus:border-neutral-500 focus:ring-2 focus:ring-neutral-500 transition placeholder-neutral-400">
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-neutral-700 mb-1">Nova Senha</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.657-1.343-3-3-3s-3 1.343-3 3 1.343 3 3 3 3-1.343 3-3zm0 0v2a4 4 0 008 0v-2" />
                        </svg>
                    </span>
                    <input type="password" name="password" id="password" required placeholder="Digite a nova senha" class="pl-10 pr-3 py-2 block w-full rounded-md border border-neutral-300 text-neutral-900 bg-white shadow-sm focus:border-neutral-500 focus:ring-2 focus:ring-neutral-500 transition placeholder-neutral-400">
                </div>
            </div>
            <div class="mb-6">
                <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 mb-1">Confirmar Nova Senha</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.657-1.343-3-3-3s-3 1.343-3 3 1.343 3 3 3 3-1.343 3-3zm0 0v2a4 4 0 008 0v-2" />
                        </svg>
                    </span>
                    <input type="password" name="password_confirmation" id="password_confirmation" required placeholder="Confirme a nova senha" class="pl-10 pr-3 py-2 block w-full rounded-md border border-neutral-300 text-neutral-900 bg-white shadow-sm focus:border-neutral-500 focus:ring-2 focus:ring-neutral-500 transition placeholder-neutral-400">
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-neutral-900 text-white text-sm font-medium rounded-lg hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                    Salvar Nova Senha
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
