@extends('layouts.dashboard')

@section('title', 'Novo Setor - BSDrive')

@section('content')
<div class="p-6 max-w-xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Novo Setor</h1>
    <form action="{{ route('sectors.store') }}" method="POST" class="bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
        @csrf
        <div class="mb-4">
            <label for="company_id" class="block text-sm font-medium text-neutral-700 mb-2">Empresa</label>
            <select name="company_id" id="company_id" class="w-full px-4 py-2 border border-neutral-300 rounded-lg">
                @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-neutral-700 mb-2">Nome do Setor</label>
            <input type="text" name="name" id="name" class="w-full px-4 py-2 border border-neutral-300 rounded-lg" required>
        </div>
        <div class="mb-4">
            <label for="description" class="block text-sm font-medium text-neutral-700 mb-2">Descrição</label>
            <textarea name="description" id="description" rows="3" class="w-full px-4 py-2 border border-neutral-300 rounded-lg"></textarea>
        </div>
        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" name="active" value="1" checked class="rounded border-neutral-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                <span class="ml-2 text-sm text-neutral-700">Setor ativo</span>
            </label>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Criar Setor</button>
    </form>
</div>
@endsection 