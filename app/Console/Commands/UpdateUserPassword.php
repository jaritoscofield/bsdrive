<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:password {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza a senha de um usuário';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Usuário com email '{$email}' não encontrado!");
            return;
        }
        
        $user->password = Hash::make($password);
        $user->save();
        
        $this->info("✅ Senha do usuário '{$user->name}' ({$user->email}) atualizada com sucesso!");
        $this->info("Nova senha: {$password}");
    }
}
