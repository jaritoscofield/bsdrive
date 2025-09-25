<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'cnpj',
        'active',
        'max_storage_mb', // novo campo
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function sectors()
    {
        return $this->hasMany(Sector::class);
    }

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function companyFolders()
    {
        return $this->hasMany(CompanyFolder::class);
    }

    public function allowedFolders()
    {
        return $this->companyFolders()->active();
    }

    public function hasFolderAccess($googleDriveFolderId)
    {
        return $this->allowedFolders()
                    ->where('google_drive_folder_id', $googleDriveFolderId)
                    ->exists();
    }

    public function getAccessibleFolderIds()
    {
        return $this->allowedFolders()->pluck('google_drive_folder_id')->toArray();
    }
}
