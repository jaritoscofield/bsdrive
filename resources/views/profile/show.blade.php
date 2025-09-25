@extends('layouts.dashboard')

@section('title', 'Meu Perfil')

@section('content')
<div class="max-w-2xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-neutral-900 mb-6">Meu Perfil</h2>
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
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-neutral-700 mb-1">Nome</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    <input type="text" id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required placeholder="Seu nome" class="pl-10 pr-3 py-2 block w-full rounded-md border border-neutral-300 text-neutral-900 bg-white shadow-sm focus:border-neutral-500 focus:ring-2 focus:ring-neutral-500 transition placeholder-neutral-400">
                </div>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-neutral-700 mb-1">E-mail</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H8m8 0a4 4 0 11-8 0 4 4 0 018 0zm0 0v1a4 4 0 01-8 0v-1" />
                        </svg>
                    </span>
                    <input type="email" id="email" name="email" value="{{ old('email', auth()->user()->email) }}" required placeholder="seu@email.com" class="pl-10 pr-3 py-2 block w-full rounded-md border border-neutral-300 text-neutral-900 bg-white shadow-sm focus:border-neutral-500 focus:ring-2 focus:ring-neutral-500 transition placeholder-neutral-400">
                </div>
            </div>
            @if(auth()->user()->company)
            <div class="mb-4">
                <label for="company" class="block text-sm font-medium text-neutral-700 mb-1">Empresa</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                        </svg>
                    </span>
                    <input type="text" id="company" value="{{ auth()->user()->company->name }}" disabled class="pl-10 pr-3 py-2 block w-full rounded-md border border-neutral-200 bg-neutral-100 text-neutral-700 shadow-sm placeholder-neutral-400">
                </div>
            </div>
            @endif
            <div class="mb-6">
                <label for="role" class="block text-sm font-medium text-neutral-700 mb-1">Papel</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                    </span>
                    <input type="text" id="role" value="{{ __(ucwords(str_replace('_', ' ', auth()->user()->role))) }}" disabled class="pl-10 pr-3 py-2 block w-full rounded-md border border-neutral-200 bg-neutral-100 text-neutral-700 shadow-sm placeholder-neutral-400">
                </div>
            </div>
            <div class="flex flex-col sm:flex-row justify-end gap-2">
                <a href="{{ route('profile.password') }}" class="inline-flex items-center px-4 py-2 bg-neutral-900 text-white text-sm font-medium rounded-lg hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                    Alterar Senha
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
