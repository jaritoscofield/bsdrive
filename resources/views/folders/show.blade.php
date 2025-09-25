@extends('layouts.dashboard')

@section('title', $folder['name'])

@section('content')
<div class="p-6">
    {{-- Breadcrumb removido pois $folder->getBreadcrumb() não existe para BSDrive --}}

    <!-- Folder Header -->
    <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-neutral-900">{{ $folder['name'] }}</h1>
                    @if(isset($folder['description']) && $folder['description'])
                        <p class="text-neutral-600 mt-1">{{ $folder['description'] }}</p>
                    @endif
                    <div class="flex items-center space-x-4 mt-2 text-sm text-neutral-500">
                        <span>ID: {{ $folder['id'] ?? '-' }}</span>
                        <span>Criado em {{ isset($folder['createdTime']) ? date('d/m/Y H:i', strtotime($folder['createdTime'])) : '-' }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('folders.edit', $folder['id']) }}" class="inline-flex items-center px-3 py-2 border border-neutral-300 shadow-sm text-sm leading-4 font-medium rounded-md text-neutral-700 bg-white hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar
                </a>
                <form action="{{ route('folders.destroy', $folder['id']) }}" method="POST" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" onclick="return confirm('Tem certeza que deseja excluir esta pasta do BSDrive?')">>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <a href="{{ route('folders.create', ['parent_id' => $folder['id']]) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-neutral-900 hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nova Subpasta
            </a>
            <a href="{{ route('files.create', ['folder_id' => $folder['id']]) }}" class="inline-flex items-center px-4 py-2 border border-neutral-300 shadow-sm text-sm font-medium rounded-md text-neutral-700 bg-white hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                Upload de Arquivo
            </a>
            <button type="button" onclick="document.getElementById('zip-folder-input').click()" class="inline-flex items-center px-4 py-2 border border-neutral-300 shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 4H6a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-8l-2-2z"/>
                </svg>
                Enviar Pasta ZIP
            </button>
        </div>
    </div>

    <!-- Content Tabs -->
    <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
        <div class="border-b border-neutral-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button class="border-neutral-500 text-neutral-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" id="subfolders-tab">
                    Subpastas ({{ count($subfolders) }})
                </button>
                <button class="border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" id="files-tab">
                    Arquivos ({{ count($files) }})
                </button>
            </nav>
        </div>

        <div class="p-6">
            <!-- Subfolders Tab -->
            <div id="subfolders-content">
                @if(count($subfolders) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($subfolders as $subfolder)
                            <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('folders.show', $subfolder['id']) }}" class="text-sm font-medium text-gray-900 hover:text-blue-600 truncate block">
                                            {{ $subfolder['name'] }}
                                        </a>
                                        <p class="text-xs text-gray-500">
                                            Criado em {{ isset($subfolder['createdTime']) ? date('d/m/Y H:i', strtotime($subfolder['createdTime'])) : 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <button 
                                            onclick="confirmDelete('{{ $subfolder['id'] }}', '{{ $subfolder['name'] }}')" 
                                            class="p-2 rounded hover:bg-red-50 text-red-600 hover:text-red-900 transition-colors" 
                                            title="Excluir subpasta"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma subpasta</h3>
                        <p class="mt-1 text-sm text-gray-500">Comece criando uma nova subpasta.</p>
                        <div class="mt-6">
                            <a href="{{ route('folders.create', ['parent_id' => $folder['id']]) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-neutral-900 hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Nova Subpasta
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Files Tab -->
            <div id="files-content" class="hidden">
                @if(count($files) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach($files as $file)
                            <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center">
                                            @if(str_starts_with($file['mimeType'], 'image/'))
                                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                                </svg>
                                            @elseif(str_starts_with($file['mimeType'], 'application/pdf') || str_starts_with($file['mimeType'], 'text/'))
                                                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                                </svg>
                                            @elseif(str_starts_with($file['mimeType'], 'video/'))
                                                <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M2 6a2 2 0 012-2h6l2 2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm font-medium text-gray-900 block">
                                            {{ $file['name'] }}
                                        </span>
                                        <p class="text-xs text-gray-500">
                                            {{ isset($file['size']) ? number_format($file['size'] / 1024, 2) . ' KB' : 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center justify-between">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('files.download-google-drive', $file['id']) }}" class="text-xs text-blue-600 hover:text-blue-800">
                                            Download
                                        </a>
                                        @if(str_starts_with($file['mimeType'], 'image/') || str_starts_with($file['mimeType'], 'application/pdf') || str_starts_with($file['mimeType'], 'text/'))
                                            <button onclick="openFilePreview('{{ $file['id'] }}', '{{ $file['name'] }}', '{{ $file['mimeType'] }}')" class="text-xs text-neutral-600 hover:text-neutral-800">
                                                Visualizar
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination removed - BSDrive doesn't support Laravel pagination -->
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum arquivo</h3>
                        <p class="mt-1 text-sm text-gray-500">Comece fazendo upload de um arquivo.</p>
                        <div class="mt-6">
                            <a href="{{ route('files.create', ['folder_id' => $folder['id']]) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-neutral-900 hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                Upload de Arquivo
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Formulário oculto para upload de pasta ZIP nesta subpasta -->
<form id="zip-upload-form" action="{{ route('files.upload-folder') }}" method="POST" enctype="multipart/form-data" style="display:none;">
    @csrf
    <input type="hidden" name="parent_id" value="{{ $folder['id'] }}">
    <input type="file" id="zip-folder-input" name="folder" accept=".zip" required>
</form>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Confirmar Exclusão</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Tem certeza que deseja excluir a pasta <strong id="folderName"></strong>?
                </p>
                <p class="text-xs text-gray-400 mt-2">
                    Esta ação não pode ser desfeita. Todos os arquivos e subpastas serão removidos.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="cancelDelete" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancelar
                </button>
                <button id="confirmDelete" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-24 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Excluir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- File Preview Modal -->
<div id="filePreviewModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full max-h-[90vh] overflow-auto relative">
        <button onclick="closeFilePreview()" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4" id="filePreviewTitle">Visualização em tempo real</h2>
            <div id="filePreviewContent"></div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const subfoldersTab = document.getElementById('subfolders-tab');
    const filesTab = document.getElementById('files-tab');
    const filePreviewModal = document.getElementById('filePreviewModal');
    
    if (subfoldersTab) {
        subfoldersTab.addEventListener('click', function() {
            subfoldersTab.classList.add('border-neutral-500', 'text-neutral-600');
            subfoldersTab.classList.remove('border-transparent', 'text-neutral-500');
            filesTab.classList.remove('border-neutral-500', 'text-neutral-600');
            filesTab.classList.add('border-transparent', 'text-neutral-500');

            document.getElementById('subfolders-content').classList.remove('hidden');
            document.getElementById('files-content').classList.add('hidden');
        });
    }

    if (filesTab) {
        filesTab.addEventListener('click', function() {
            filesTab.classList.add('border-neutral-500', 'text-neutral-600');
            filesTab.classList.remove('border-transparent', 'text-neutral-500');
            subfoldersTab.classList.remove('border-neutral-500', 'text-neutral-600');
            subfoldersTab.classList.add('border-transparent', 'text-neutral-500');

            document.getElementById('files-content').classList.remove('hidden');
            document.getElementById('subfolders-content').classList.add('hidden');
        });
    }

    // Close file preview modal with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeFilePreview();
        }
    });

    // Close file preview modal when clicking outside
    if (filePreviewModal) {
        filePreviewModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeFilePreview();
            }
        });
    }
});

// Enviar Pasta ZIP (submit automático ao escolher arquivo)
const zipInput = document.getElementById('zip-folder-input');
if (zipInput) {
    zipInput.addEventListener('change', function() {
        if (!zipInput.files || zipInput.files.length === 0) return;
        const file = zipInput.files[0];
        if (file && file.name && !file.name.toLowerCase().endsWith('.zip')) {
            alert('❌ Por favor, selecione um arquivo ZIP (.zip)');
            zipInput.value = '';
            return;
        }
        document.getElementById('zip-upload-form').submit();
    });
}

// File preview modal
function openFilePreview(fileId, fileName, mimeType) {
    document.getElementById('filePreviewTitle').textContent = 'Visualizando: ' + fileName;
    const content = document.getElementById('filePreviewContent');

    if (mimeType.startsWith('image/')) {
        content.innerHTML = `<img src="/files/${fileId}/preview" alt="${fileName}" class="max-w-full h-auto rounded-lg mx-auto">`;
    } else if (mimeType === 'application/pdf') {
        content.innerHTML = `<iframe src="/files/${fileId}/preview" class="w-full min-h-[60vh]" frameborder="0"></iframe>`;
    }

    document.getElementById('filePreviewModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeFilePreview() {
    document.getElementById('filePreviewModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

// Delete confirmation modal
function confirmDelete(folderId, folderName) {
    document.getElementById('folderName').textContent = folderName;
    document.getElementById('deleteModal').classList.remove('hidden');

    document.getElementById('confirmDelete').onclick = function() {
        // Criar um formulário temporário para enviar a requisição DELETE
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/folders/${folderId}`;
        
        // Adicionar token CSRF
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfToken);
        
        // Adicionar método DELETE
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        form.appendChild(methodField);
        
        // Adicionar formulário ao DOM e enviar
        document.body.appendChild(form);
        form.submit();
    };

    document.getElementById('cancelDelete').onclick = function() {
        document.getElementById('deleteModal').classList.add('hidden');
    };

    // Close modal when clicking outside
    document.getElementById('deleteModal').onclick = function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    };
}
</script>
@endpush
