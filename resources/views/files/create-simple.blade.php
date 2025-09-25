@extends('layouts.dashboard')

@section('title', 'Enviar Arquivos')

@section('content')
<div class="p-6">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-neutral-900">Enviar Arquivos para Sua Pasta Pessoal</h1>
            <p class="text-neutral-600">Os arquivos serão enviados diretamente para sua pasta pessoal no BSDrive</p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Upload Form -->
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
            <div class="p-6">
                <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                    @csrf

                    <!-- File Upload Area -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-neutral-700 mb-3">
                            Selecionar Arquivos
                        </label>

                        <div class="border-2 border-dashed border-neutral-300 rounded-lg p-8 text-center">
                            <div class="space-y-4">
                                <svg class="mx-auto h-12 w-12 text-neutral-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div>
                                    <p class="text-lg font-medium text-neutral-900">Clique para selecionar arquivos</p>
                                    <p class="text-sm text-neutral-500">Ou arraste arquivos aqui (máximo 50MB cada)</p>
                                </div>
                                <input type="file" name="files[]" id="fileInput" multiple required class="hidden">
                                <button type="button" onclick="document.getElementById('fileInput').click()" 
                                        class="inline-flex items-center px-4 py-2 bg-neutral-900 text-white text-sm font-medium rounded-lg hover:bg-neutral-800">
                                    Selecionar Arquivos
                                </button>
                            </div>
                        </div>

                        <!-- Selected Files List -->
                        <div id="selectedFiles" class="mt-4 hidden">
                            <h4 class="text-sm font-medium text-neutral-700 mb-2">Arquivos Selecionados:</h4>
                            <div id="filesList" class="space-y-2"></div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('files.index') }}" 
                           class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50">
                            Cancelar
                        </a>
                        <button type="submit" id="submitBtn"
                                class="inline-flex items-center px-6 py-2 bg-neutral-900 text-white text-sm font-medium rounded-lg hover:bg-neutral-800 disabled:opacity-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Enviar para Minha Pasta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('fileInput');
    const selectedFiles = document.getElementById('selectedFiles');
    const filesList = document.getElementById('filesList');
    const submitBtn = document.getElementById('submitBtn');
    const uploadForm = document.getElementById('uploadForm');

    fileInput.addEventListener('change', function() {
        const files = this.files;
        console.log('Files selected:', files);
        
        if (files.length > 0) {
            selectedFiles.classList.remove('hidden');
            filesList.innerHTML = '';
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileDiv = document.createElement('div');
                fileDiv.className = 'flex items-center justify-between p-2 bg-neutral-50 rounded';
                fileDiv.innerHTML = `
                    <span class="text-sm text-neutral-700">${file.name}</span>
                    <span class="text-xs text-neutral-500">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                `;
                filesList.appendChild(fileDiv);
            }
            
            submitBtn.disabled = false;
        } else {
            selectedFiles.classList.add('hidden');
            submitBtn.disabled = true;
        }
    });

    uploadForm.addEventListener('submit', function(e) {
        console.log('Form submitting...');
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Enviando...
        `;
    });

    // Initially disable submit button
    submitBtn.disabled = true;
});
</script>
@endsection
