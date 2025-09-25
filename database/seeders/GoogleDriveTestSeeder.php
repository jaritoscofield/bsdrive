<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Folder;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GoogleDriveTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar empresa de teste se não existir
        $company = Company::firstOrCreate(
            ['name' => 'Empresa Teste Google Drive'],
            [
                'name' => 'Empresa Teste Google Drive',
                'email' => 'teste@empresa.com',
                'phone' => '(11) 99999-9999',
                'address' => 'Rua Teste, 123 - São Paulo, SP',
                'cnpj' => '12.345.678/0001-90',
                'active' => true,
            ]
        );

        // Criar usuário admin se não existir
        $admin = User::firstOrCreate(
            ['email' => 'admin@teste.com'],
            [
                'name' => 'Admin Teste',
                'email' => 'admin@teste.com',
                'password' => Hash::make('password'),
                'role' => 'admin_empresa',
                'company_id' => $company->id
            ]
        );

        // Criar estrutura de pastas de teste
        $this->createTestFolders($company, $admin);

        // Criar arquivos de teste
        $this->createTestFiles($company, $admin);
    }

    private function createTestFolders($company, $admin)
    {
        // Pastas raiz
        $pastasRaiz = [
            'Documentos',
            'Imagens',
            'Vídeos',
            'Projetos',
            'Backup'
        ];

        foreach ($pastasRaiz as $nomePasta) {
            $pasta = Folder::firstOrCreate(
                [
                    'name' => $nomePasta,
                    'company_id' => $company->id,
                    'parent_id' => null
                ],
                [
                    'name' => $nomePasta,
                    'description' => "Pasta {$nomePasta} para teste do Google Drive",
                    'path' => $nomePasta,
                    'parent_id' => null,
                    'company_id' => $company->id,
                    'sector_id' => null,
                    'active' => true,
                ]
            );

            // Criar subpastas para algumas pastas raiz
            if (in_array($nomePasta, ['Documentos', 'Projetos'])) {
                $this->createSubfolders($pasta, $company, $admin);
            }
        }
    }

    private function createSubfolders($parentFolder, $company, $admin)
    {
        $subfolders = [];

        if ($parentFolder->name === 'Documentos') {
            $subfolders = [
                'Contratos',
                'Relatórios',
                'Faturas',
                'Manuais'
            ];
        } elseif ($parentFolder->name === 'Projetos') {
            $subfolders = [
                'Projeto A',
                'Projeto B',
                'Projeto C',
                'Arquivo'
            ];
        }

        foreach ($subfolders as $nomeSubpasta) {
            $subpasta = Folder::firstOrCreate(
                [
                    'name' => $nomeSubpasta,
                    'company_id' => $company->id,
                    'parent_id' => $parentFolder->id
                ],
                [
                    'name' => $nomeSubpasta,
                    'description' => "Subpasta {$nomeSubpasta} dentro de {$parentFolder->name}",
                    'path' => $parentFolder->path . '/' . $nomeSubpasta,
                    'parent_id' => $parentFolder->id,
                    'company_id' => $company->id,
                    'sector_id' => null,
                    'active' => true,
                ]
            );

            // Criar sub-subpastas para algumas subpastas
            if ($parentFolder->name === 'Projetos' && $nomeSubpasta === 'Projeto A') {
                $this->createSubSubfolders($subpasta, $company, $admin);
            }
        }
    }

    private function createSubSubfolders($parentFolder, $company, $admin)
    {
        $subSubfolders = [
            'Documentação',
            'Código Fonte',
            'Testes',
            'Deploy'
        ];

        foreach ($subSubfolders as $nomeSubSubpasta) {
            Folder::firstOrCreate(
                [
                    'name' => $nomeSubSubpasta,
                    'company_id' => $company->id,
                    'parent_id' => $parentFolder->id
                ],
                [
                    'name' => $nomeSubSubpasta,
                    'description' => "Sub-subpasta {$nomeSubSubpasta} dentro de {$parentFolder->name}",
                    'path' => $parentFolder->path . '/' . $nomeSubSubpasta,
                    'parent_id' => $parentFolder->id,
                    'company_id' => $company->id,
                    'sector_id' => null,
                    'active' => true,
                ]
            );
        }
    }

    private function createTestFiles($company, $admin)
    {
        // Buscar algumas pastas para adicionar arquivos
        $pastas = Folder::where('company_id', $company->id)->get();

        foreach ($pastas as $pasta) {
            // Criar 1-3 arquivos por pasta
            $numArquivos = rand(1, 3);

            for ($i = 1; $i <= $numArquivos; $i++) {
                $tiposArquivo = [
                    'documento' => [
                        'name' => "Documento {$i}",
                        'original_name' => "documento_{$i}.pdf",
                        'mime_type' => 'application/pdf',
                        'size' => rand(100000, 5000000)
                    ],
                    'imagem' => [
                        'name' => "Imagem {$i}",
                        'original_name' => "imagem_{$i}.jpg",
                        'mime_type' => 'image/jpeg',
                        'size' => rand(50000, 2000000)
                    ],
                    'planilha' => [
                        'name' => "Planilha {$i}",
                        'original_name' => "planilha_{$i}.xlsx",
                        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'size' => rand(50000, 1000000)
                    ]
                ];

                $tipo = array_rand($tiposArquivo);
                $arquivo = $tiposArquivo[$tipo];

                File::firstOrCreate(
                    [
                        'name' => $arquivo['name'],
                        'company_id' => $company->id,
                        'folder_id' => $pasta->id
                    ],
                    [
                        'name' => $arquivo['name'],
                        'original_name' => $arquivo['original_name'],
                        'path' => "files/{$company->id}/2024/12/teste_{$i}.{$tipo}",
                        'mime_type' => $arquivo['mime_type'],
                        'size' => $arquivo['size'],
                        'description' => "Arquivo de teste {$i} na pasta {$pasta->name}",
                        'folder_id' => $pasta->id,
                        'company_id' => $company->id,
                        'sector_id' => null,
                        'uploaded_by' => $admin->id,
                        'active' => true,
                    ]
                );
            }
        }
    }
}
