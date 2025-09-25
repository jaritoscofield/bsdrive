<div class="folder-item border-l-2 border-neutral-200 pl-4 mb-4" style="margin-left: {{ $folder['level'] * 20 }}px;">
    <div class="bg-neutral-50 rounded-lg p-4 hover:bg-neutral-100 transition-colors">
        <div class="flex items-center justify-between">
            <div class="flex items-center flex-1">
                <!-- Toggle button para subpastas -->
                @if(!empty($folder['children']))
                    <button 
                        onclick="toggleFolder(this)" 
                        class="mr-3 text-xl hover:scale-110 transition-transform"
                        title="Expandir/Recolher"
                    >
                        📁
                    </button>
                @else
                    <span class="mr-3 text-xl">📄</span>
                @endif

                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h4 class="folder-name font-semibold text-neutral-900 text-lg">
                            {{ $folder['name'] }}
                        </h4>
                        @if($folder['level'] > 0)
                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                Nível {{ $folder['level'] }}
                            </span>
                        @endif
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-neutral-600">
                        <div>
                            <span class="font-medium">ID:</span>
                            <code class="folder-id bg-neutral-100 px-2 py-1 rounded text-xs font-mono">
                                {{ $folder['id'] }}
                            </code>
                        </div>
                        
                        <div>
                            @if($folder['createdTime'])
                                <span class="font-medium">Criada:</span>
                                {{ \Carbon\Carbon::parse($folder['createdTime'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                            @endif
                        </div>
                        
                        @if(!empty($folder['children']))
                            <div>
                                <span class="font-medium">Subpastas:</span>
                                {{ count($folder['children']) }}
                            </div>
                        @endif
                        
                        @if(!empty($folder['parents']))
                            <div>
                                <span class="font-medium">Parent ID:</span>
                                <code class="bg-neutral-100 px-2 py-1 rounded text-xs font-mono">
                                    {{ $folder['parents'][0] ?? 'root' }}
                                </code>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 ml-4">
                <button 
                    onclick="copyId('{{ $folder['id'] }}', this)"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-medium transition-colors"
                    title="Copiar ID"
                >
                    📋 Copiar ID
                </button>
                
                <a 
                    href="{{ route('google-folders.show', $folder['id']) }}"
                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-medium transition-colors"
                    title="Ver detalhes"
                >
                    👁️ Detalhes
                </a>
            </div>
        </div>
    </div>

    <!-- Children (subpastas) -->
    @if(!empty($folder['children']))
        <div class="folder-children mt-4" style="display: none;">
            @foreach($folder['children'] as $child)
                @include('google-drive.partials.folder-item', ['folder' => $child])
            @endforeach
        </div>
    @endif
</div>
