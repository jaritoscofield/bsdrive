@extends('layouts.dashboard')

@section('title', 'Teste de Upload - DEBUG')

@section('content')
<div class="p-6">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold text-neutral-900 mb-6">🔧 Teste de Upload - DEBUG</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Formulário 1: Rota Original -->
            <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-blue-600 mb-4">1️⃣ ROTA ORIGINAL (files.store)</h2>
                    <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data" id="form1">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-2">Arquivo:</label>
                                <input type="file" name="files[]" required class="block w-full text-sm text-neutral-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                                📤 TESTAR ROTA ORIGINAL
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Formulário 2: Rota Teste -->
            <div class="bg-white rounded-lg shadow-sm border border-red-200">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-red-600 mb-4">2️⃣ ROTA TESTE (test-upload-direct)</h2>
                    <form action="{{ route('test-upload-direct') }}" method="POST" enctype="multipart/form-data" id="form2">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-2">Arquivo:</label>
                                <input type="file" name="files[]" required class="block w-full text-sm text-neutral-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                                🔥 TESTAR ROTA DIRETA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Instruções -->
        <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <h3 class="text-lg font-bold text-yellow-800 mb-3">📋 INSTRUÇÕES DE TESTE:</h3>
            <ol class="list-decimal list-inside space-y-2 text-yellow-700">
                <li>Teste o <strong>Formulário 1</strong> primeiro (rota original)</li>
                <li>Verifique os logs em <code>storage/logs/laravel.log</code></li>
                <li>Teste o <strong>Formulário 2</strong> (rota direta)</li>
                <li>Compare os logs de ambos os testes</li>
                <li>Observe qualquer diferença no comportamento</li>
            </ol>
        </div>

        <!-- Seção de Logs -->
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-3">📊 VERIFICAR LOGS:</h3>
            <p class="text-gray-600 mb-4">Execute este comando no terminal para ver os logs em tempo real:</p>
            <code class="block bg-gray-800 text-green-400 p-3 rounded">tail -f storage/logs/laravel.log | grep EMERGENCY</code>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form1 = document.getElementById('form1');
    const form2 = document.getElementById('form2');

    form1.addEventListener('submit', function() {
        console.log('🔵 Formulário 1 (rota original) enviado');
    });

    form2.addEventListener('submit', function() {
        console.log('🔴 Formulário 2 (rota teste) enviado');
    });
});
</script>
@endsection
