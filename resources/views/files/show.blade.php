@extends('layouts.dashboard')

@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
    <div class="flex items-center gap-3">
        <span class="bg-gray-800 p-2 rounded-full shadow">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </span>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $fileArray['name'] }}</h1>
            <p class="text-gray-600">Visualização de arquivo</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('files.download', $fileArray['id']) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Download
        </a>
        <a href="{{ route('files.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition duration-200 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Voltar
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Preview do arquivo -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6">
                @php
                    $mimeType = $fileArray['mimeType'];
                    $isImage = strpos($mimeType, 'image/') === 0;
                    $isPdf = $mimeType === 'application/pdf';
                    $isOffice = in_array($mimeType, [
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ]);
                @endphp

                @if($isImage)
                    <div class="text-center">
                        <img src="{{ route('files.view-image', $fileArray['id']) }}" 
                             class="max-w-full h-auto rounded-lg shadow-md mx-auto" 
                             style="max-height: 600px;"
                             alt="{{ $fileArray['name'] }}"
                             onerror="this.closest('div').innerHTML='<div class=\'bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center\'><svg class=\'w-12 h-12 text-yellow-400 mx-auto mb-4\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.96-.833-2.732 0L5.268 16.5C4.498 18.167 5.46 19.834 7 19.834z\'></path></svg><h3 class=\'text-lg font-medium text-yellow-800 mb-2\'>Não foi possível carregar a imagem</h3><p class=\'text-yellow-600 mb-4\'>Tente fazer o download do arquivo</p><a href=\'{{ route('files.download', $fileArray['id']) }}\' class=\'inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition\'><svg class=\'w-4 h-4 mr-2\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\'></path></svg>Baixar Arquivo</a></div>'">
                    </div>
                @elseif($isPdf)
                    <div class="text-center">
                        <iframe src="{{ route('files.preview', $fileArray['id']) }}" 
                                width="100%" 
                                height="600" 
                                class="border rounded-lg">
                        </iframe>
                        <p class="text-gray-500 text-sm mt-4">
                            Se o PDF não carregar, <a href="{{ route('files.download', $fileArray['id']) }}" class="text-blue-600 hover:underline">clique aqui para baixar</a>
                        </p>
                    </div>
                @elseif($isOffice)
                    @php
                        $signedUrl = URL::temporarySignedRoute('files.public-stream', now()->addMinutes(10), ['id' => $fileArray['id']]);
                    @endphp
                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-semibold text-gray-900">Planilha</h3>
                                <select id="sheet-select" class="text-sm border rounded px-2 py-1 hidden"></select>
                            </div>
                            <div class="flex items-center gap-2">
                                <button id="edit-toggle" class="text-sm px-3 py-1 rounded border text-gray-700 hover:bg-gray-50">Editar</button>
                                <button id="save-btn" class="hidden text-sm px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">Salvar</button>
                                <a href="{{ route('files.download', $fileArray['id']) }}" class="text-sm text-blue-600 hover:underline">Baixar</a>
                            </div>
                        </div>
                        <div id="xlsx-viewer" class="p-4 overflow-auto" style="max-height: 600px">
                            <div id="xlsx-loading" class="text-sm text-gray-500">Carregando...</div>
                        </div>
                        <div id="xlsx-error" class="hidden p-4 text-sm text-red-600">Não foi possível renderizar. Baixe o arquivo para abrir no Excel.</div>
                    </div>
                    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
                    <script>
                        (function(){
                            const url = @json($signedUrl);
                            const fileId = @json($fileArray['id']);
                            const csrf = document.querySelector('meta[name="csrf-token"]').content;
                            const viewer = document.getElementById('xlsx-viewer');
                            const loading = document.getElementById('xlsx-loading');
                            const errorBox = document.getElementById('xlsx-error');
                            const sheetSelect = document.getElementById('sheet-select');
                            const editToggle = document.getElementById('edit-toggle');
                            const saveBtn = document.getElementById('save-btn');
                            let workbook, currentSheetName, editMode = false;

                            function buildTable(rows, editable){
                                const maxRows = 200, maxCols = 50;
                                const limited = rows.slice(0, maxRows).map(r => r.slice(0, maxCols));
                                let html = '<table class="min-w-full border-collapse text-sm">';
                                limited.forEach((r, idx) => {
                                    html += '<tr>' + r.map((c, j) => {
                                        const safe = (c===undefined || c===null) ? '' : String(c)
                                            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                                        const isHeader = idx===0;
                                        const tag = isHeader ? 'th' : 'td';
                                        const cls = 'border border-gray-200 px-2 py-1 ' + (isHeader ? 'bg-gray-50 font-semibold' : (editable?'bg-white':'') );
                                        const attr = (!isHeader && editable) ? 'contenteditable' : '';
                                        return `<${tag} class="${cls}" ${attr} data-r="${idx}" data-c="${j}">${safe}</${tag}>`;
                                    }).join('') + '</tr>';
                                });
                                html += '</table>';
                                viewer.innerHTML = html + `<div class="text-xs text-gray-400 mt-2">Mostrando até 200 linhas / 50 colunas</div>`;
                            }

                            function renderSheet(name){
                                currentSheetName = name;
                                const ws = workbook.Sheets[name];
                                const rows = XLSX.utils.sheet_to_json(ws, { header: 1, blankrows: false });
                                buildTable(rows, editMode);
                            }

                            function tableToSheet(){
                                const table = viewer.querySelector('table');
                                const rows = Array.from(table.rows).map(tr => Array.from(tr.cells).map(td => td.textContent));
                                return XLSX.utils.aoa_to_sheet(rows);
                            }

                            function enableEdit(){ editMode = true; renderSheet(currentSheetName); saveBtn.classList.remove('hidden'); }
                            function disableEdit(){ editMode = false; renderSheet(currentSheetName); saveBtn.classList.add('hidden'); }

                            editToggle.addEventListener('click', () => {
                                editMode ? disableEdit() : enableEdit();
                            });

                            saveBtn.addEventListener('click', async () => {
                                try {
                                    // Atualiza a aba atual com conteúdo da tabela
                                    workbook.Sheets[currentSheetName] = tableToSheet();
                                    const out = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
                                    const blob = new Blob([out], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                                    const form = new FormData();
                                    form.append('_token', csrf);
                                    form.append('_method', 'PUT');
                                    form.append('name', @json($fileArray['name']));
                                    form.append('file', blob, @json($fileArray['name']));
                                    const resp = await fetch(`/files/${fileId}`, { method: 'POST', body: form, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                                    if (!resp.ok) throw new Error('HTTP '+resp.status);
                                    disableEdit();
                                    alert('Arquivo salvo!');
                                } catch(e){ console.error(e); alert('Falha ao salvar.'); }
                            });

                            fetch(url)
                                .then(r => { if(!r.ok) throw new Error('HTTP ' + r.status); return r.arrayBuffer(); })
                                .then(buf => {
                                    workbook = XLSX.read(new Uint8Array(buf), { type: 'array' });
                                    loading?.remove();
                                    if (workbook.SheetNames.length > 1) {
                                        sheetSelect.classList.remove('hidden');
                                        sheetSelect.innerHTML = workbook.SheetNames.map(n => `<option value="${n}">${n}</option>`).join('');
                                        sheetSelect.addEventListener('change', e => renderSheet(e.target.value));
                                    }
                                    renderSheet(workbook.SheetNames[0]);
                                })
                                .catch(err => { console.error('XLSX preview error', err); loading?.remove(); errorBox.classList.remove('hidden'); });
                        })();
                    </script>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Arquivo não suportado para visualização</h3>
                        <p class="text-gray-600 mb-2">Tipo: <span class="font-mono text-sm">{{ $mimeType }}</span></p>
                        <a href="{{ route('files.download', $fileArray['id']) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Baixar Arquivo
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Informações do arquivo -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informações do Arquivo</h3>
                
                <div class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nome</dt>
                        <dd class="mt-1 text-sm text-gray-900 break-all">{{ $fileArray['name'] }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tipo</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $mimeType }}
                            </span>
                        </dd>
                    </div>

                    @if(isset($fileArray['size']) && $fileArray['size'])
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tamanho</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @php
                                $size = $fileArray['size'];
                                if ($size >= 1048576) {
                                    $formattedSize = round($size / 1048576, 2) . ' MB';
                                } elseif ($size >= 1024) {
                                    $formattedSize = round($size / 1024, 2) . ' KB';
                                } else {
                                    $formattedSize = $size . ' bytes';
                                }
                            @endphp
                            {{ $formattedSize }}
                        </dd>
                    </div>
                    @endif

                    @if(isset($fileArray['createdTime']) && $fileArray['createdTime'])
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Data de Criação</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($fileArray['createdTime'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</dd>
                    </div>
                    @endif

                    @if(isset($fileArray['modifiedTime']) && $fileArray['modifiedTime'])
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Última Modificação</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($fileArray['modifiedTime'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</dd>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
