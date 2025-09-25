<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:test-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria um usuário de teste para acessar o sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Criando Usuário de Teste ===');
        
        // Verificar se já existe usuário
        $existingUser = User::where('email', 'admin@teste.com')->first();
        if ($existingUser) {
            $this->info('Usuário admin@teste.com já existe!');
            $this->info('Email: admin@teste.com');
            $this->info('Senha: 12345678');
            return;
        }

        // Verificar se há empresa
        $company = Company::first();
        if (!$company) {
            $this->info('Criando empresa de teste...');
            $company = Company::create([
                'name' => 'Empresa Teste',
                'cnpj' => '12345678000199',
                'email' => 'empresa@teste.com',
                'telefone' => '(11) 99999-9999',
                'endereco' => 'Rua Teste, 123',
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'cep' => '01234-567'
            ]);
        }

        // Criar usuário
        $user = User::create([
            'name' => 'Admin Teste',
            'email' => 'admin@teste.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin_sistema',
            'company_id' => $company->id
        ]);

        $this->info('✅ Usuário criado com sucesso!');
        $this->info('Email: admin@teste.com');
        $this->info('Senha: 12345678');
        $this->info('Role: admin_sistema');
        $this->info('');
        $this->info('Agora você pode acessar http://127.0.0.1:8000/login');
    }
}
