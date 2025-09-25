@extends('layouts.dashboard')

@section('title', 'Pastas do Google Drive')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Pastas do BSDrive</h1>
                <p class="text-neutral-600">Gerencie suas pastas reais do BSDrive</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('files.create', ['section' => 'folder']) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Enviar Pasta
                </a>
                <button type="button" onclick="document.getElementById('zip-folder-input-index').click()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 4H6a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-8l-2-2z"></path>
                    </svg>
                    Enviar Pasta ZIP
                </button>
                <a href="{{ route('folders.create', ['parent_id' => request('parent_id')]) }}" class="inline-flex items-center px-4 py-2 bg-neutral-900 text-white text-sm font-medium rounded-lg hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Nova Pasta
                </a>
            </div>
            </div>
        </div>

        <!-- Tabela de Pastas do BSDrive -->
        <div class="bg-white rounded-lg shadow-sm border border-neutral-200">
            <div class="p-6">


                @if(count($folders) > 0)
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-100">
                        @foreach($folders as $folder)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-neutral-900 flex items-center">
                                <svg class="w-5 h-5 text-neutral-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-5l-2-2H5a2 2 0 00-2 2z"></path>
                                </svg>
                                <a href="{{ route('folders.show', $folder->google_drive_id) }}" class="hover:underline">{{ $folder->name ?? '-' }}</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-neutral-700 font-mono">{{ $folder->google_drive_id ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('folders.show', $folder->google_drive_id) }}" class="p-2 rounded hover:bg-neutral-100 text-neutral-600 hover:text-neutral-900 transition-colors" title="Detalhes">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <a href="{{ route('folders.edit', $folder->google_drive_id) }}" class="p-2 rounded hover:bg-neutral-100 text-neutral-600 hover:text-neutral-900 transition-colors" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <form id="delete-form-{{ $folder->google_drive_id }}" action="{{ route('folders.destroy', $folder->google_drive_id) }}" method="POST" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="p-2 rounded hover:bg-red-50 text-red-600 hover:text-red-900 transition-colors" title="Excluir" onclick="openDeleteModal('{{ $folder->google_drive_id }}')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="text-neutral-500">Nenhuma pasta encontrada no BSDrive.</div>
                @endif
            </div>
        </div>
</div>
<!-- Formulário oculto para upload de pasta ZIP no índice de pastas -->
<form id="zip-upload-form-index" action="{{ route('files.upload-folder') }}" method="POST" enctype="multipart/form-data" style="display:none;">
    @csrf
    <input type="hidden" name="parent_id" value="{{ request('parent_id') }}">
    <input type="file" id="zip-folder-input-index" name="folder" accept=".zip" required>
</form>
@endsection

<!-- Delete Confirmation Modal (igual ao de arquivos) -->
<div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full transform transition-all duration-300 scale-95 opacity-0" id="modalContentFolder">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-neutral-200">
                <div class="flex items-center">
                    <div class="h-10 w-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-neutral-900">Confirmar Exclusão</h3>
                        <p class="text-sm text-neutral-500">Esta ação não pode ser desfeita</p>
                    </div>
                </div>
                <button onclick="closeDeleteModal()" class="text-neutral-400 hover:text-neutral-600 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <!-- Modal Body -->
            <div class="p-6">
                <div class="flex items-start">
                    <div class="h-12 w-12 bg-red-100 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-5l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-base font-medium text-neutral-900 mb-2">Excluir Pasta</h4>
                        <p class="text-sm text-neutral-600 mb-4">
                            Tem certeza que deseja excluir esta pasta do BSDrive?
                        </p>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-red-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-red-800">Atenção</p>
                                    <p class="text-sm text-red-700 mt-1">
                                        Esta ação irá excluir permanentemente a pasta do BSDrive.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal Footer -->
            <div class="flex items-center justify-end space-x-3 p-6 border-t border-neutral-200 bg-neutral-50 rounded-b-lg">
                <button onclick="closeDeleteModal()" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 transition-colors">
                    Cancelar
                </button>
                <button type="button" onclick="confirmDelete()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Excluir Pasta
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    let folderIdToDelete = null;
    function openDeleteModal(folderId) {
        folderIdToDelete = folderId;
        document.getElementById('delete-modal').classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('modalContentFolder').classList.remove('scale-95', 'opacity-0');
            document.getElementById('modalContentFolder').classList.add('scale-100', 'opacity-100');
        }, 10);
    }
    function closeDeleteModal() {
        document.getElementById('modalContentFolder').classList.remove('scale-100', 'opacity-100');
        document.getElementById('modalContentFolder').classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            document.getElementById('delete-modal').classList.add('hidden');
            folderIdToDelete = null;
        }, 300);
    }
    function confirmDelete() {
        if (folderIdToDelete) {
            document.getElementById('delete-form-' + folderIdToDelete).submit();
        }
    }
    // Fechar modal ao clicar fora
    if(document.getElementById('delete-modal')) {
        document.getElementById('delete-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    }

    // Enviar Pasta ZIP (índice)
    const zipInputIndex = document.getElementById('zip-folder-input-index');
    if (zipInputIndex) {
        zipInputIndex.addEventListener('change', function() {
            if (!zipInputIndex.files || zipInputIndex.files.length === 0) return;
            const file = zipInputIndex.files[0];
            if (file && file.name && !file.name.toLowerCase().endsWith('.zip')) {
                alert('❌ Por favor, selecione um arquivo ZIP (.zip)');
                zipInputIndex.value = '';
                return;
            }
            document.getElementById('zip-upload-form-index').submit();
        });
    }
</script>
