<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'google_drive_folder_id',
        'folder_name',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByGoogleDriveFolder($query, $folderId)
    {
        return $query->where('google_drive_folder_id', $folderId);
    }
}
