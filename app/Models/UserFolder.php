<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'google_drive_folder_id',
        'folder_name',
        'description',
        'permission_level',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByGoogleDriveFolder($query, $folderId)
    {
        return $query->where('google_drive_folder_id', $folderId);
    }

    public function scopeByPermissionLevel($query, $level)
    {
        return $query->where('permission_level', $level);
    }

    public function hasReadPermission()
    {
        return in_array($this->permission_level, ['read', 'write', 'admin']);
    }

    public function hasWritePermission()
    {
        return in_array($this->permission_level, ['write', 'admin']);
    }

    public function hasAdminPermission()
    {
        return $this->permission_level === 'admin';
    }
}
