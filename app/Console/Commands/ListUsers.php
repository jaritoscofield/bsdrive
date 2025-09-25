<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ListUsers extends Command
{
    protected $signature = 'list:users';
    protected $description = 'Lista todos os usuários do sistema';

    public function handle()
    {
        $users = User::all(['id', 'name', 'email', 'role']);
        
        $this->info("👥 Usuários do sistema:");
        $this->newLine();
        
        foreach ($users as $user) {
            $this->line("   ID: {$user->id} - {$user->name} ({$user->email}) - {$user->role}");
        }
        
        return 0;
    }
}
