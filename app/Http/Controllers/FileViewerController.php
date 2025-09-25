<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Auth;

class FileViewerController extends Controller
{
    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Visualizar arquivo
     */
    public function view($fileId)
    {
        $user = Auth::user();

        try {
            // Buscar informações do arquivo
            $file = $this->googleDriveService->getFile($fileId);
            
            if (!$file) {
                abort(404, 'Arquivo não encontrado.');
            }

            // Verificar permissões (básico)
            // TODO: Implementar verificação mais robusta de permissões

            // Determinar tipo de visualização
            $mimeType = $file->getMimeType();
            $fileName = $file->getName();
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $viewData = [
                'file' => $file,
                'fileName' => $fileName,
                'mimeType' => $mimeType,
                'fileExtension' => $fileExtension,
                'fileId' => $fileId,
                'viewType' => $this->determineViewType($mimeType, $fileExtension),
                'downloadUrl' => route('files.download', $fileId),
                'fileSize' => $this->formatFileSize($file->getSize() ?? 0)
            ];

            // Se for Office, usar a página unificada files.show (evita aninhar layouts)
            if ($this->isOfficeFile($mimeType, $fileExtension)) {
                return redirect()->route('files.show', $fileId);
            }

            return view('files.viewer', $viewData);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Erro ao carregar arquivo: ' . $e->getMessage()]);
        }
    }

    /**
     * Obter conteúdo do arquivo para visualização
     */
    public function getContent($fileId)
    {
        try {
            $file = $this->googleDriveService->getFile($fileId);
            
            if (!$file) {
                return response()->json(['error' => 'Arquivo não encontrado'], 404);
            }

            $mimeType = $file->getMimeType();
            $fileExtension = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));

            // Para arquivos de texto
            if ($this->isTextFile($mimeType, $fileExtension)) {
                $content = $this->googleDriveService->downloadFileContent($fileId);
                return response()->json([
                    'type' => 'text',
                    'content' => $content
                ]);
            }

            // Para imagens, retornar URL de visualização
            if ($this->isImageFile($mimeType, $fileExtension)) {
                return response()->json([
                    'type' => 'image',
                    'url' => $this->getGoogleDriveViewUrl($fileId)
                ]);
            }

            // Para PDFs e outros
            return response()->json([
                'type' => 'iframe',
                'url' => $this->getGoogleDriveViewUrl($fileId)
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Determinar tipo de visualização
     */
    private function determineViewType($mimeType, $fileExtension)
    {
        // Imagens
        if ($this->isImageFile($mimeType, $fileExtension)) {
            return 'image';
        }

        // Arquivos de texto
        if ($this->isTextFile($mimeType, $fileExtension)) {
            return 'text';
        }

        // PDFs
        if ($mimeType === 'application/pdf' || $fileExtension === 'pdf') {
            return 'pdf';
        }

        // Documentos do Google (Docs, Sheets, Slides)
        if ($this->isGoogleDocument($mimeType)) {
            return 'google_doc';
        }

        // Vídeos
        if ($this->isVideoFile($mimeType, $fileExtension)) {
            return 'video';
        }

        // Áudios
        if ($this->isAudioFile($mimeType, $fileExtension)) {
            return 'audio';
        }

        // Arquivos do Office
        if ($this->isOfficeFile($mimeType, $fileExtension)) {
            return 'office';
        }

        // Arquivos compactados
        if ($this->isArchiveFile($mimeType, $fileExtension)) {
            return 'archive';
        }

        // Padrão
        return 'download';
    }

    private function isImageFile($mimeType, $fileExtension)
    {
        $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        
        return in_array($mimeType, $imageMimes) || in_array($fileExtension, $imageExtensions);
    }

    private function isTextFile($mimeType, $fileExtension)
    {
        $textMimes = ['text/plain', 'text/html', 'text/css', 'text/javascript', 'application/json', 'application/xml'];
        $textExtensions = ['txt', 'html', 'htm', 'css', 'js', 'json', 'xml', 'md', 'csv'];
        
        return in_array($mimeType, $textMimes) || in_array($fileExtension, $textExtensions);
    }

    private function isVideoFile($mimeType, $fileExtension)
    {
        $videoMimes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/webm'];
        $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'webm', 'mkv'];
        
        return in_array($mimeType, $videoMimes) || in_array($fileExtension, $videoExtensions);
    }

    private function isAudioFile($mimeType, $fileExtension)
    {
        $audioMimes = ['audio/mp3', 'audio/wav', 'audio/ogg', 'audio/mpeg'];
        $audioExtensions = ['mp3', 'wav', 'ogg', 'flac', 'm4a'];
        
        return in_array($mimeType, $audioMimes) || in_array($fileExtension, $audioExtensions);
    }

    private function isOfficeFile($mimeType, $fileExtension)
    {
        $officeMimes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/msword',
            'application/vnd.ms-excel',
            'application/vnd.ms-powerpoint'
        ];
        $officeExtensions = ['docx', 'xlsx', 'pptx', 'doc', 'xls', 'ppt'];
        
        return in_array($mimeType, $officeMimes) || in_array($fileExtension, $officeExtensions);
    }

    private function isArchiveFile($mimeType, $fileExtension)
    {
        $archiveMimes = ['application/zip', 'application/x-rar-compressed', 'application/x-tar'];
        $archiveExtensions = ['zip', 'rar', 'tar', 'gz', '7z'];
        
        return in_array($mimeType, $archiveMimes) || in_array($fileExtension, $archiveExtensions);
    }

    private function isGoogleDocument($mimeType)
    {
        $googleMimes = [
            'application/vnd.google-apps.document',
            'application/vnd.google-apps.spreadsheet',
            'application/vnd.google-apps.presentation'
        ];
        
        return in_array($mimeType, $googleMimes);
    }

    private function getGoogleDriveViewUrl($fileId)
    {
        // Prefer local endpoints which handle auth and headers
        return route('files.view-image', $fileId);
    }

    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return number_format($bytes, 2, ',', '.') . ' ' . $units[$pow];
    }
}
