<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'original_name',
        'path',
        'mime_type',
        'size',
        'description',
        'folder_id',
        'company_id',
        'sector_id',
        'uploaded_by',
        'active',
        'google_drive_id',
    ];

    protected $casts = [
        'size' => 'integer',
        'active' => 'boolean',
    ];

    protected $appends = [
        'formatted_size',
        'download_url',
        'preview_url',
        'icon_class',
    ];

    // Relacionamentos
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Scopes
    public function scopeNotDeleted($query)
    {
        return $query->where('active', true);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByFolder($query, $folderId)
    {
        return $query->where('folder_id', $folderId);
    }

    public function scopePublic($query)
    {
        return $query->where('active', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('uploaded_by', $userId);
    }

    // Accessors
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDownloadUrlAttribute()
    {
        return route('files.download', $this->id);
    }

    public function getPreviewUrlAttribute()
    {
        if ($this->isImage()) {
            return route('files.preview', $this->id);
        }
        return null;
    }

    public function getIconClassAttribute()
    {
        return $this->getFileIcon();
    }

    // Métodos
    public function isImage()
    {
        return Str::startsWith($this->mime_type, 'image/');
    }

    public function isVideo()
    {
        return Str::startsWith($this->mime_type, 'video/');
    }

    public function isAudio()
    {
        return Str::startsWith($this->mime_type, 'audio/');
    }

    public function isDocument()
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
        ];

        return in_array($this->mime_type, $documentTypes);
    }

    public function isArchive()
    {
        $archiveTypes = [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/gzip',
            'application/x-tar',
        ];

        return in_array($this->mime_type, $archiveTypes);
    }

    public function getFileIcon()
    {
        if ($this->isImage()) {
            return 'image';
        } elseif ($this->isVideo()) {
            return 'video';
        } elseif ($this->isAudio()) {
            return 'audio';
        } elseif ($this->isDocument()) {
            return 'document';
        } elseif ($this->isArchive()) {
            return 'archive';
        } else {
            return 'file';
        }
    }

    public function getStoragePath()
    {
        return Storage::disk('local')->path($this->path);
    }

    public function exists()
    {
        return Storage::disk('local')->exists($this->path);
    }

    public function deleteFile()
    {
        if ($this->exists()) {
            Storage::disk('local')->delete($this->path);
        }
    }

    public function softDelete()
    {
        $this->update([
            'active' => false,
        ]);
    }

    public function restore()
    {
        $this->update([
            'active' => true,
        ]);
    }

    public function canBeAccessedBy(User $user)
    {
        // Admin sistema pode acessar tudo
        if ($user->role === 'admin_sistema') {
            return true;
        }

        // Usuário que fez upload pode acessar
        if ($user->id === $this->uploaded_by) {
            return true;
        }

        // Admin empresa pode acessar arquivos da sua empresa
        if ($user->role === 'admin_empresa' && $user->company_id === $this->company_id) {
            return true;
        }

        // Usuário comum pode acessar arquivos da sua empresa
        if ($user->company_id === $this->company_id) {
            return true;
        }

        return false;
    }

    public function canBeModifiedBy(User $user)
    {
        // Admin sistema pode modificar tudo
        if ($user->role === 'admin_sistema') {
            return true;
        }

        // Usuário que fez upload pode modificar
        if ($user->id === $this->uploaded_by) {
            return true;
        }

        // Admin empresa pode modificar arquivos da sua empresa
        if ($user->role === 'admin_empresa' && $user->company_id === $this->company_id) {
            return true;
        }

        return false;
    }

    public function canBeDeletedBy(User $user)
    {
        return $this->canBeModifiedBy($user);
    }
}
