<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StaticFileController extends Controller
{
    /**
     * Serve static files (images, documents, etc.) from various directories
     */
    public function serve(Request $request, $path)
    {
        // Decode the path
        $path = urldecode($path);
        
        // Try different possible locations for the file
        $possiblePaths = [
            public_path('uploads/' . $path),
            public_path('files/' . $path),
            public_path('imagens/' . $path),
            storage_path('app/public/' . $path),
            public_path($path) // Direct access to public folder
        ];
        
        $filePath = null;
        foreach ($possiblePaths as $possiblePath) {
            if (file_exists($possiblePath) && is_file($possiblePath)) {
                $filePath = $possiblePath;
                break;
            }
        }
        
        if (!$filePath) {
            abort(404, 'File not found');
        }
        
        // Security check - ensure file is within allowed directories
        $realPath = realpath($filePath);
        $allowedPaths = [
            realpath(public_path('uploads')),
            realpath(public_path('files')),
            realpath(public_path('imagens')),
            realpath(storage_path('app/public')),
            realpath(public_path())
        ];
        
        $isAllowed = false;
        foreach ($allowedPaths as $allowedPath) {
            if ($allowedPath && strpos($realPath, $allowedPath) === 0) {
                $isAllowed = true;
                break;
            }
        }
        
        if (!$isAllowed) {
            abort(403, 'Access denied');
        }
        
        // Get file info
        $mimeType = $this->getMimeType($filePath);
        $fileSize = filesize($filePath);
        $lastModified = filemtime($filePath);
        
        // Set appropriate headers
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Cache-Control' => 'public, max-age=31536000', // Cache for 1 year
            'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', $lastModified),
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000),
        ];
        
        // For images, add specific headers
        if (strpos($mimeType, 'image/') === 0) {
            $headers['X-Content-Type-Options'] = 'nosniff';
        }
        
        return response()->file($filePath, $headers);
    }
    
    /**
     * Display file info (for debugging)
     */
    public function info(Request $request, $path)
    {
        $path = urldecode($path);
        
        $possiblePaths = [
            public_path('uploads/' . $path),
            public_path('files/' . $path),
            public_path('imagens/' . $path),
            storage_path('app/public/' . $path),
            public_path($path)
        ];
        
        $fileInfo = [];
        foreach ($possiblePaths as $possiblePath) {
            $fileInfo[] = [
                'path' => $possiblePath,
                'exists' => file_exists($possiblePath),
                'is_file' => is_file($possiblePath),
                'is_readable' => is_readable($possiblePath),
                'size' => file_exists($possiblePath) ? filesize($possiblePath) : null,
                'mime' => file_exists($possiblePath) ? $this->getMimeType($possiblePath) : null,
            ];
        }
        
        return response()->json([
            'requested_path' => $path,
            'file_info' => $fileInfo,
            'public_path' => public_path(),
            'storage_path' => storage_path('app/public'),
            'current_working_directory' => getcwd(),
        ]);
    }
    
    /**
     * Get MIME type of a file
     */
    private function getMimeType($filePath)
    {
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filePath);
            if ($mimeType) {
                return $mimeType;
            }
        }
        
        // Fallback to extension-based detection
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            // Images
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            
            // Documents
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'rtf' => 'application/rtf',
            
            // Web files
            'html' => 'text/html',
            'htm' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            
            // Archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed',
            'tar' => 'application/x-tar',
            'gz' => 'application/gzip',
            
            // Audio/Video
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'wmv' => 'video/x-ms-wmv',
            
            // Fonts
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];
        
        return isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
    }
}
