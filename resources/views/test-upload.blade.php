@extends('layouts.dashboard')

@section('title', 'Teste de Upload')

@section('content')
<div class="p-6">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-neutral-900 mb-6">Teste de Upload Simples</h1>
        
        <!-- Debug Info -->
        <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-6">
            <h3 class="font-bold text-blue-900 mb-2">Informações de Debug:</h3>
            <ul class="text-sm text-blue-800">
                <li><strong>URL do formulário:</strong> {{ route('files.store') }}</li>
                <li><strong>Método:</strong> POST</li>
                <li><strong>Encoding:</strong> multipart/form-data</li>
                <li><strong>Token CSRF:</strong> {{ csrf_token() }}</li>
            </ul>
        </div>

        <!-- Test Form -->
        <form action="{{ route('test-store') }}" method="POST" enctype="multipart/form-data" onsubmit="console.log('Test form submitted!'); return true;">
            @csrf
            
            <div class="bg-white p-6 rounded-lg border border-neutral-200 space-y-4">
                <div>
                    <label for="test-files" class="block text-sm font-medium text-neutral-700 mb-2">
                        Selecionar Arquivos de Teste:
                    </label>
                    <input type="file" 
                           name="files[]" 
                           id="test-files" 
                           multiple 
                           required
                           class="block w-full text-sm text-neutral-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-neutral-50 file:text-neutral-700 hover:file:bg-neutral-100"
                           onchange="console.log('Files selected:', this.files); updateFileList();">
                </div>
                
                <div>
                    <label for="parent_id" class="block text-sm font-medium text-neutral-700 mb-2">
                        Pasta de Destino (opcional):
                    </label>
                    <input type="text" 
                           name="parent_id" 
                           id="parent_id" 
                           placeholder="ID da pasta (deixe vazio para pasta raiz)"
                           class="block w-full px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-neutral-500 focus:border-neutral-500">
                </div>
                
                <div id="file-list" class="hidden">
                    <h4 class="font-medium text-neutral-700 mb-2">Arquivos Selecionados:</h4>
                    <ul id="selected-files" class="text-sm text-neutral-600"></ul>
                </div>
                
                <div class="flex space-x-3">
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        TESTE (Diagnóstico)
                    </button>
                    
                    <a href="{{ route('files.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-neutral-600 text-white text-sm font-medium rounded-lg hover:bg-neutral-700">
                        Voltar para Arquivos
                    </a>
                </div>
            </div>
        </form>
        
        <!-- Formulário Real para Comparação -->
        <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data" onsubmit="console.log('Real form submitted!'); return true;" class="mt-6">
            @csrf
            
            <div class="bg-green-50 p-6 rounded-lg border border-green-200 space-y-4">
                <h3 class="font-bold text-green-900">Upload Real (BSDrive)</h3>
                
                <div>
                    <label for="real-files" class="block text-sm font-medium text-green-700 mb-2">
                        Selecionar Arquivos:
                    </label>
                    <input type="file" 
                           name="files[]" 
                           id="real-files" 
                           multiple 
                           required
                           class="block w-full text-sm text-green-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-100 file:text-green-700 hover:file:bg-green-200">
                </div>
                
                <div class="flex space-x-3">
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Upload Real
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Upload Status Check -->
        <div class="mt-6 bg-white p-6 rounded-lg border border-neutral-200">
            <h3 class="font-bold text-neutral-900 mb-4">Verificar Status de Upload</h3>
            <button onclick="checkUploadStatus()" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Verificar Status
            </button>
        </div>
    </div>
</div>

<script>
function updateFileList() {
    const fileInput = document.getElementById('test-files');
    const fileList = document.getElementById('file-list');
    const selectedFiles = document.getElementById('selected-files');
    
    if (fileInput.files.length > 0) {
        fileList.classList.remove('hidden');
        selectedFiles.innerHTML = '';
        
        for (let i = 0; i < fileInput.files.length; i++) {
            const file = fileInput.files[i];
            const li = document.createElement('li');
            li.textContent = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
            selectedFiles.appendChild(li);
        }
    } else {
        fileList.classList.add('hidden');
    }
}

function checkUploadStatus() {
    console.log('Checking upload status...');
    
    fetch('/files/upload-status')
        .then(response => response.json())
        .then(data => {
            console.log('Upload status:', data);
            alert('Status: ' + JSON.stringify(data, null, 2));
        })
        .catch(error => {
            console.error('Error checking status:', error);
            alert('Erro: ' + error.message);
        });
}

// Log when page loads
console.log('Test upload page loaded');
console.log('CSRF Token:', '{{ csrf_token() }}');
console.log('Upload URL:', '{{ route("files.store") }}');
</script>
@endsection
