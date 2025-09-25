<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'path',
        'parent_id',
        'company_id',
        'sector_id',
        'active',
        'google_drive_id',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $appends = [
        'full_path',
        'files_count',
        'subfolders_count',
    ];

    // Relacionamentos
    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    public function files()
    {
        return $this->hasMany(File::class)->notDeleted();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
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

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopePublic($query)
    {
        return $query->where('active', true);
    }

    // Accessors
    public function getFullPathAttribute()
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' / ', $path);
    }

    public function getFilesCountAttribute()
    {
        return $this->files()->count();
    }

    public function getSubfoldersCountAttribute()
    {
        return $this->children()->notDeleted()->count();
    }

    // Métodos


    public function getBreadcrumb()
    {
        $breadcrumb = [];
        $current = $this;

        while ($current) {
            array_unshift($breadcrumb, $current);
            $current = $current->parent;
        }

        return $breadcrumb;
    }

    public function getAllFiles()
    {
        $files = $this->files;

        foreach ($this->children()->notDeleted()->get() as $child) {
            $files = $files->merge($child->getAllFiles());
        }

        return $files;
    }

    public function getAllSubfolders()
    {
        $subfolders = $this->children()->notDeleted();

        foreach ($this->children()->notDeleted()->get() as $child) {
            $subfolders = $subfolders->merge($child->getAllSubfolders());
        }

        return $subfolders;
    }

    public function getTotalSize()
    {
        return $this->getAllFiles()->sum('size');
    }

    public function softDelete()
    {
        $this->update([
            'active' => false,
        ]);

        // Soft delete todos os arquivos e subpastas
        $this->files()->update(['active' => false]);

        foreach ($this->children()->notDeleted()->get() as $child) {
            $child->softDelete();
        }
    }

    public function restore()
    {
        $this->update([
            'active' => true,
        ]);

        // Restaurar todos os arquivos e subpastas
        $this->files()->update(['active' => true]);

        foreach ($this->children()->notDeleted()->get() as $child) {
            $child->restore();
        }
    }

    public function canBeAccessedBy(User $user)
    {
        // Admin sistema pode acessar tudo
        if ($user->role === 'admin_sistema') {
            return true;
        }

        // Admin empresa pode acessar pastas da sua empresa
        if ($user->role === 'admin_empresa' && $user->company_id === $this->company_id) {
            return true;
        }

        // Usuário comum pode acessar pastas da sua empresa
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

        // Admin empresa pode modificar pastas da sua empresa
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
