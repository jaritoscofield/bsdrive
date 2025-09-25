<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company_id',
        'google_drive_folder_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function files()
    {
        return $this->hasMany(File::class, 'uploaded_by');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
                    ->withPivot('granted')
                    ->withTimestamps();
    }

    public function userFolders()
    {
        return $this->hasMany(UserFolder::class);
    }

    public function allowedFolders()
    {
        return $this->userFolders()->active();
    }

    public function hasPermission($permission)
    {
        if (is_string($permission)) {
            return $this->permissions()->where('slug', $permission)->wherePivot('granted', true)->exists();
        }

        return $this->permissions()->where('id', $permission->id)->wherePivot('granted', true)->exists();
    }

    public function hasModulePermission($module, $action)
    {
        return $this->permissions()
                    ->where('module', $module)
                    ->where('action', $action)
                    ->wherePivot('granted', true)
                    ->exists();
    }

    public function hasFolderAccess($googleDriveFolderId, $permissionLevel = 'read')
    {
        $query = $this->allowedFolders()->where('google_drive_folder_id', $googleDriveFolderId);

        switch ($permissionLevel) {
            case 'read':
                return $query->exists();
            case 'write':
                return $query->whereIn('permission_level', ['write', 'admin'])->exists();
            case 'admin':
                return $query->where('permission_level', 'admin')->exists();
            default:
                return false;
        }
    }

    /**
     * Obtém ou cria a pasta pessoal do usuário no Google Drive
     */
    public function getOrCreatePersonalFolder()
    {
        $googleDriveService = app(\App\Services\GoogleDriveService::class);
        
        // Se já tem pasta, verificar se ainda existe no Google Drive
        if ($this->google_drive_folder_id) {
            if ($googleDriveService->fileExists($this->google_drive_folder_id)) {
                return $this->google_drive_folder_id;
            } else {
                // Pasta não existe mais, limpar o campo
                $this->google_drive_folder_id = null;
                $this->save();
            }
        }

        // Obter ou criar pasta principal "bsdrive"
        $bsDriveFolderId = $this->getOrCreateBSDriveFolder();
        if (!$bsDriveFolderId) {
            throw new \Exception("Não foi possível criar/obter pasta bsdrive");
        }

        // Cria pasta pessoal dentro da pasta "bsdrive"
        $folderName = "Usuario_{$this->id}_{$this->name}";
        
        try {
            $folder = $googleDriveService->createFolder($folderName, $bsDriveFolderId);
            
            // Salva o ID da pasta no usuário
            $this->google_drive_folder_id = $folder['id'];
            $this->save();
            
            return $folder['id'];
        } catch (\Exception $e) {
            \Log::error("Erro ao criar pasta pessoal para usuário {$this->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtém a pasta pessoal do usuário (apenas se já existir)
     */
    public function getPersonalFolderId()
    {
        return $this->google_drive_folder_id;
    }

    /**
     * Verifica se o usuário tem pasta pessoal
     */
    public function hasPersonalFolder()
    {
        return !empty($this->google_drive_folder_id);
    }

    /**
     * Obtém os IDs de pastas que o usuário pode acessar
     * Inclui sua pasta pessoal + outras permissões
     */
    public function getAccessibleFolderIds($permissionLevel = 'read')
    {
        $folderIds = [];

        // Se for admin_sistema, retorna todos os IDs de pastas do Google Drive
        if ($this->role === 'admin_sistema') {
            $allFolders = app(\App\Services\GoogleDriveService::class)->listFiles(null, 'files(id,name,mimeType,parents)');
            $folderIds = collect($allFolders)
                ->filter(function($file) {
                    return isset($file['mimeType']) && $file['mimeType'] === 'application/vnd.google-apps.folder';
                })
                ->pluck('id')
                ->toArray();
            return $folderIds;
        }

        // Sempre inclui a pasta pessoal do usuário
        if ($this->hasPersonalFolder()) {
            $folderIds[] = $this->getPersonalFolderId();
        }

        // Adiciona outras pastas com permissão
        $query = $this->allowedFolders();
        switch ($permissionLevel) {
            case 'read':
                $additionalIds = $query->pluck('google_drive_folder_id')->toArray();
                break;
            case 'write':
                $additionalIds = $query->whereIn('permission_level', ['write', 'admin'])
                            ->pluck('google_drive_folder_id')->toArray();
                break;
            case 'admin':
                $additionalIds = $query->where('permission_level', 'admin')
                            ->pluck('google_drive_folder_id')->toArray();
                break;
            default:
                $additionalIds = [];
        }

        return array_unique(array_merge($folderIds, $additionalIds));
    }

    public function getFolderPermissionLevel($googleDriveFolderId)
    {
        $userFolder = $this->allowedFolders()
                           ->where('google_drive_folder_id', $googleDriveFolderId)
                           ->first();

        return $userFolder ? $userFolder->permission_level : null;
    }

    public function canAccessCompanyFolder($googleDriveFolderId)
    {
        // Admin do sistema pode acessar tudo
        if ($this->role === 'admin_sistema') {
            return true;
        }

        // Verifica se o usuário tem acesso direto à pasta
        if ($this->hasFolderAccess($googleDriveFolderId)) {
            return true;
        }

        // Verifica se a empresa do usuário tem acesso à pasta
        if ($this->company && $this->company->hasFolderAccess($googleDriveFolderId)) {
            return true;
        }

        return false;
    }

    /**
     * Obtém ou cria a pasta principal "bsdrive" no Google Drive
     */
    private function getOrCreateBSDriveFolder()
    {
        $googleDriveService = app(\App\Services\GoogleDriveService::class);
        
        // Procurar se já existe uma pasta "bsdrive"
        try {
            $query = "name='bsdrive' and mimeType='application/vnd.google-apps.folder' and trashed=false";
            $files = $googleDriveService->searchFiles($query);
            
            if (!empty($files)) {
                return $files[0]['id'];
            }
            
            // Se não existe, criar a pasta "bsdrive"
            $folder = $googleDriveService->createFolder('bsdrive', null); // null = pasta raiz
            return $folder['id'];
            
        } catch (\Exception $e) {
            \Log::error("Erro ao criar/obter pasta bsdrive: " . $e->getMessage());
            return null;
        }
    }
}
