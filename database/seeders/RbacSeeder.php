<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\PerfilPermissao;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissoes = [
            ['nome' => 'admin_master', 'descricao' => 'Acesso total ao sistema'],

            ['nome' => 'planos.listar', 'descricao' => 'Listar planos'],
            ['nome' => 'planos.criar', 'descricao' => 'Criar planos'],
            ['nome' => 'planos.atualizar', 'descricao' => 'Atualizar planos'],
            ['nome' => 'planos.excluir', 'descricao' => 'Excluir planos'],

            ['nome' => 'empresas.listar', 'descricao' => 'Listar empresas'],
            ['nome' => 'empresas.criar', 'descricao' => 'Criar empresas'],
            ['nome' => 'empresas.atualizar', 'descricao' => 'Atualizar empresas'],
            ['nome' => 'empresas.excluir', 'descricao' => 'Excluir empresas'],

            ['nome' => 'assinaturas.listar', 'descricao' => 'Listar assinaturas'],
            ['nome' => 'assinaturas.criar', 'descricao' => 'Criar assinaturas'],
            ['nome' => 'assinaturas.atualizar', 'descricao' => 'Atualizar assinaturas'],
            ['nome' => 'assinaturas.excluir', 'descricao' => 'Excluir assinaturas'],

            ['nome' => 'usuarios.listar', 'descricao' => 'Listar usuarios'],
            ['nome' => 'usuarios.criar', 'descricao' => 'Criar usuarios'],
            ['nome' => 'usuarios.atualizar', 'descricao' => 'Atualizar usuarios'],
            ['nome' => 'usuarios.excluir', 'descricao' => 'Excluir usuarios'],

            ['nome' => 'perfis.listar', 'descricao' => 'Listar perfis'],
            ['nome' => 'perfis.criar', 'descricao' => 'Criar perfis'],
            ['nome' => 'perfis.atualizar', 'descricao' => 'Atualizar perfis'],
            ['nome' => 'perfis.excluir', 'descricao' => 'Excluir perfis'],

            ['nome' => 'permissoes.listar', 'descricao' => 'Listar permissoes'],
            ['nome' => 'permissoes.criar', 'descricao' => 'Criar permissoes'],
            ['nome' => 'permissoes.atualizar', 'descricao' => 'Atualizar permissoes'],
            ['nome' => 'permissoes.excluir', 'descricao' => 'Excluir permissoes'],

            ['nome' => 'categorias.listar', 'descricao' => 'Listar categorias'],
            ['nome' => 'categorias.criar', 'descricao' => 'Criar categorias'],
            ['nome' => 'categorias.atualizar', 'descricao' => 'Atualizar categorias'],
            ['nome' => 'categorias.excluir', 'descricao' => 'Excluir categorias'],

            ['nome' => 'produtos.listar', 'descricao' => 'Listar produtos'],
            ['nome' => 'produtos.criar', 'descricao' => 'Criar produtos'],
            ['nome' => 'produtos.atualizar', 'descricao' => 'Atualizar produtos'],
            ['nome' => 'produtos.excluir', 'descricao' => 'Excluir produtos'],

            ['nome' => 'mesas.listar', 'descricao' => 'Listar mesas'],
            ['nome' => 'mesas.criar', 'descricao' => 'Criar mesas'],
            ['nome' => 'mesas.atualizar', 'descricao' => 'Atualizar mesas'],
            ['nome' => 'mesas.excluir', 'descricao' => 'Excluir mesas'],

            ['nome' => 'comandas.listar', 'descricao' => 'Listar comandas'],
            ['nome' => 'comandas.criar', 'descricao' => 'Criar comandas'],
            ['nome' => 'comandas.atualizar', 'descricao' => 'Atualizar comandas'],
            ['nome' => 'comandas.excluir', 'descricao' => 'Excluir comandas'],

            ['nome' => 'clientes.listar', 'descricao' => 'Listar clientes'],
            ['nome' => 'clientes.criar', 'descricao' => 'Criar clientes'],
            ['nome' => 'clientes.atualizar', 'descricao' => 'Atualizar clientes'],
            ['nome' => 'clientes.excluir', 'descricao' => 'Excluir clientes'],

            ['nome' => 'entregadores.listar', 'descricao' => 'Listar entregadores'],
            ['nome' => 'entregadores.criar', 'descricao' => 'Criar entregadores'],
            ['nome' => 'entregadores.atualizar', 'descricao' => 'Atualizar entregadores'],
            ['nome' => 'entregadores.excluir', 'descricao' => 'Excluir entregadores'],

            ['nome' => 'entregas.listar', 'descricao' => 'Listar entregas'],
            ['nome' => 'entregas.criar', 'descricao' => 'Criar entregas'],
            ['nome' => 'entregas.atualizar', 'descricao' => 'Atualizar entregas'],
            ['nome' => 'entregas.excluir', 'descricao' => 'Excluir entregas'],

            ['nome' => 'pedidos.listar', 'descricao' => 'Listar pedidos'],
            ['nome' => 'pedidos.criar', 'descricao' => 'Criar pedidos'],
            ['nome' => 'pedidos.atualizar', 'descricao' => 'Atualizar pedidos'],
            ['nome' => 'pedidos.excluir', 'descricao' => 'Excluir pedidos'],
            ['nome' => 'pedidos.enviar_cozinha', 'descricao' => 'Enviar pedido para cozinha'],
            ['nome' => 'pedidos.fechar', 'descricao' => 'Fechar pedido'],

            ['nome' => 'pagamentos.listar', 'descricao' => 'Listar pagamentos'],
            ['nome' => 'pagamentos.criar', 'descricao' => 'Criar pagamentos'],
            ['nome' => 'pagamentos.atualizar', 'descricao' => 'Atualizar pagamentos'],
            ['nome' => 'pagamentos.excluir', 'descricao' => 'Excluir pagamentos'],

            ['nome' => 'caixas.listar', 'descricao' => 'Listar caixas'],
            ['nome' => 'caixas.criar', 'descricao' => 'Criar caixas'],
            ['nome' => 'caixas.atualizar', 'descricao' => 'Atualizar caixas'],
            ['nome' => 'caixas.excluir', 'descricao' => 'Excluir caixas'],
            ['nome' => 'caixas.abrir', 'descricao' => 'Abrir caixa'],
            ['nome' => 'caixas.fechar', 'descricao' => 'Fechar caixa'],

            ['nome' => 'cozinha.listar', 'descricao' => 'Listar cozinha'],
            ['nome' => 'cozinha.atualizar', 'descricao' => 'Atualizar status cozinha'],

            ['nome' => 'estoque.listar', 'descricao' => 'Listar estoque'],
            ['nome' => 'estoque.criar', 'descricao' => 'Criar estoque'],
            ['nome' => 'estoque.atualizar', 'descricao' => 'Atualizar estoque'],
            ['nome' => 'estoque.excluir', 'descricao' => 'Excluir estoque'],

            ['nome' => 'configuracoes.listar', 'descricao' => 'Listar configuracoes'],
            ['nome' => 'configuracoes.atualizar', 'descricao' => 'Atualizar configuracoes'],

            ['nome' => 'relatorios.visualizar', 'descricao' => 'Visualizar relatorios'],
        ];

        foreach ($permissoes as $perm) {
            Permissao::updateOrCreate(
                ['nome' => $perm['nome']],
                ['descricao' => $perm['descricao']]
            );
        }

        $perfil = Perfil::updateOrCreate(
            ['nome' => 'admin_master'],
            ['descricao' => 'Perfil master com acesso total']
        );

        $permissoesIds = Permissao::pluck('id');
        foreach ($permissoesIds as $permId) {
            PerfilPermissao::updateOrCreate(
                ['perfil_id' => $perfil->id, 'permissao_id' => $permId],
                []
            );
        }
    }
}
