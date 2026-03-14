<?php

namespace App\Http\Controllers\Relatorios;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class RelatorioAnaliticoController extends Controller
{
    public function executar(Request $request, string $codigo)
    {
        $codigo = strtolower($codigo);
        $sql = $this->getRelatorioSql($codigo);
        if (!$sql) {
            return response()->json(['message' => 'Relatorio nao encontrado'], Response::HTTP_NOT_FOUND);
        }

        $empresaId = $this->empresaIdObrigatorio($request);
        if (!$empresaId) {
            return response()->json(['message' => 'empresa_id nao informado'], Response::HTTP_BAD_REQUEST);
        }

        $tipoFiltro = $this->normalizeTipo($request->input('tipo_filtro', 'periodo'));
        $tipoAgrupamento = $this->normalizeTipo($request->input('tipo_agrupamento', 'dia'));
        if (!$tipoFiltro || !$tipoAgrupamento) {
            return response()->json(['message' => 'tipo_filtro ou tipo_agrupamento invalido'], Response::HTTP_BAD_REQUEST);
        }

        $diaRef = Carbon::parse($request->input('dia_ref', now()->toDateString()));
        $anoRef = (int) $request->input('ano_ref', $diaRef->year);
        $mesRef = (int) $request->input('mes_ref', $diaRef->month);
        $semanaRef = (int) $request->input('semana_ref', (int) $diaRef->format('W'));

        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');
        if ($dataInicio) {
            $dataInicio = Carbon::parse($dataInicio)->startOfDay()->format('Y-m-d H:i:s');
        } else {
            $dataInicio = $diaRef->copy()->startOfMonth()->startOfDay()->format('Y-m-d H:i:s');
        }
        if ($dataFim) {
            $dataFim = Carbon::parse($dataFim)->endOfDay()->format('Y-m-d H:i:s');
        } else {
            $dataFim = $diaRef->copy()->endOfDay()->format('Y-m-d H:i:s');
        }

        $dataFechamentoExpr = $this->dataFechamentoExpr();

        $dados = DB::transaction(function () use (
            $empresaId,
            $tipoFiltro,
            $tipoAgrupamento,
            $diaRef,
            $anoRef,
            $mesRef,
            $semanaRef,
            $dataInicio,
            $dataFim,
            $dataFechamentoExpr,
            $sql
        ) {
            $this->setSessionVars($empresaId, $tipoFiltro, $tipoAgrupamento, $diaRef, $anoRef, $mesRef, $semanaRef, $dataInicio, $dataFim);
            $this->createTempTables($dataFechamentoExpr);
            return DB::select($sql);
        });

        return response()->json([
            'codigo' => strtoupper($codigo),
            'parametros' => [
                'empresa_id' => $empresaId,
                'tipo_filtro' => $tipoFiltro,
                'tipo_agrupamento' => $tipoAgrupamento,
                'dia_ref' => $diaRef->toDateString(),
                'ano_ref' => $anoRef,
                'mes_ref' => $mesRef,
                'semana_ref' => $semanaRef,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
            ],
            'dados' => $dados,
        ]);
    }

    protected function empresaIdObrigatorio(Request $request): ?int
    {
        $empresaId = $request->header('X-Empresa-Id');
        if ($empresaId) {
            return (int) $empresaId;
        }

        $user = $request->user();
        if ($user && isset($user->empresa_id)) {
            return (int) $user->empresa_id;
        }

        return null;
    }

    protected function normalizeTipo(string $tipo): ?string
    {
        $tipo = strtolower(trim($tipo));
        $permitidos = ['dia', 'semana', 'mes', 'ano', 'periodo'];
        return in_array($tipo, $permitidos, true) ? $tipo : null;
    }

    protected function dataFechamentoExpr(): string
    {
        $temUpdateAt = Schema::hasColumn('tb_pedidos', 'update_at');
        if ($temUpdateAt) {
            return 'COALESCE(p.updated_at, p.update_at, p.criado_em)';
        }

        return 'COALESCE(p.updated_at, p.criado_em)';
    }

    protected function setSessionVars(
        int $empresaId,
        string $tipoFiltro,
        string $tipoAgrupamento,
        Carbon $diaRef,
        int $anoRef,
        int $mesRef,
        int $semanaRef,
        string $dataInicio,
        string $dataFim
    ): void {
        DB::statement('SET @empresa_id := ?', [$empresaId]);
        DB::statement('SET @tipo_filtro := ?', [$tipoFiltro]);
        DB::statement('SET @tipo_agrupamento := ?', [$tipoAgrupamento]);
        DB::statement('SET @dia_ref := ?', [$diaRef->toDateString()]);
        DB::statement('SET @ano_ref := ?', [$anoRef]);
        DB::statement('SET @mes_ref := ?', [$mesRef]);
        DB::statement('SET @semana_ref := ?', [$semanaRef]);
        DB::statement('SET @data_inicio := ?', [$dataInicio]);
        DB::statement('SET @data_fim := ?', [$dataFim]);
    }

    protected function createTempTables(string $dataFechamentoExpr): void
    {
        $stmts = [
            "DROP TEMPORARY TABLE IF EXISTS tmp_pedidos_empresa",
            "CREATE TEMPORARY TABLE tmp_pedidos_empresa AS
            SELECT
                p.id,
                p.empresa_id,
                p.usuario_id,
                p.mesa_id,
                p.comanda_id,
                p.cliente_id,
                p.tipo,
                p.status,
                COALESCE(p.total, 0) AS total,
                p.criado_em AS data_pedido,
                {$dataFechamentoExpr} AS data_fechamento,
                DATE(p.criado_em) AS dia_pedido,
                YEAR(p.criado_em) AS ano_pedido,
                MONTH(p.criado_em) AS mes_pedido,
                WEEK(p.criado_em, 1) AS semana_pedido,
                DATE({$dataFechamentoExpr}) AS dia_fechamento,
                YEAR({$dataFechamentoExpr}) AS ano_fechamento,
                MONTH({$dataFechamentoExpr}) AS mes_fechamento,
                WEEK({$dataFechamentoExpr}, 1) AS semana_fechamento,
                CASE
                    WHEN @tipo_agrupamento = 'dia' THEN DATE_FORMAT(p.criado_em, '%Y-%m-%d')
                    WHEN @tipo_agrupamento = 'semana' THEN CONCAT(YEAR(p.criado_em), '-S', LPAD(WEEK(p.criado_em, 1), 2, '0'))
                    WHEN @tipo_agrupamento = 'mes' THEN DATE_FORMAT(p.criado_em, '%Y-%m')
                    WHEN @tipo_agrupamento = 'ano' THEN DATE_FORMAT(p.criado_em, '%Y')
                    ELSE 'PERIODO'
                END AS periodo_pedido,
                CASE
                    WHEN @tipo_agrupamento = 'dia' THEN DATE_FORMAT({$dataFechamentoExpr}, '%Y-%m-%d')
                    WHEN @tipo_agrupamento = 'semana' THEN CONCAT(YEAR({$dataFechamentoExpr}), '-S', LPAD(WEEK({$dataFechamentoExpr}, 1), 2, '0'))
                    WHEN @tipo_agrupamento = 'mes' THEN DATE_FORMAT({$dataFechamentoExpr}, '%Y-%m')
                    WHEN @tipo_agrupamento = 'ano' THEN DATE_FORMAT({$dataFechamentoExpr}, '%Y')
                    ELSE 'PERIODO'
                END AS periodo_fechamento
            FROM tb_pedidos p
            WHERE p.empresa_id = @empresa_id",

            "DROP TEMPORARY TABLE IF EXISTS tmp_pedidos_venda",
            "CREATE TEMPORARY TABLE tmp_pedidos_venda AS
            SELECT *
            FROM tmp_pedidos_empresa p
            WHERE p.status = 'fechado'
              AND (
                  (@tipo_filtro = 'dia' AND p.dia_fechamento = @dia_ref)
                  OR (@tipo_filtro = 'semana' AND p.ano_fechamento = @ano_ref AND p.semana_fechamento = @semana_ref)
                  OR (@tipo_filtro = 'mes' AND p.ano_fechamento = @ano_ref AND p.mes_fechamento = @mes_ref)
                  OR (@tipo_filtro = 'ano' AND p.ano_fechamento = @ano_ref)
                  OR (@tipo_filtro = 'periodo' AND p.data_fechamento >= @data_inicio AND p.data_fechamento <= @data_fim)
              )",

            "DROP TEMPORARY TABLE IF EXISTS tmp_pedidos_operacao",
            "CREATE TEMPORARY TABLE tmp_pedidos_operacao AS
            SELECT *
            FROM tmp_pedidos_empresa p
            WHERE (
                  (@tipo_filtro = 'dia' AND p.dia_pedido = @dia_ref)
                  OR (@tipo_filtro = 'semana' AND p.ano_pedido = @ano_ref AND p.semana_pedido = @semana_ref)
                  OR (@tipo_filtro = 'mes' AND p.ano_pedido = @ano_ref AND p.mes_pedido = @mes_ref)
                  OR (@tipo_filtro = 'ano' AND p.ano_pedido = @ano_ref)
                  OR (@tipo_filtro = 'periodo' AND p.data_pedido >= @data_inicio AND p.data_pedido <= @data_fim)
              )",

            "DROP TEMPORARY TABLE IF EXISTS tmp_itens_venda",
            "CREATE TEMPORARY TABLE tmp_itens_venda AS
            SELECT
                p.periodo_fechamento AS periodo,
                p.id AS pedido_id,
                p.usuario_id,
                p.mesa_id,
                p.comanda_id,
                p.cliente_id,
                pr.id AS produto_id,
                pr.nome AS produto,
                c.id AS categoria_id,
                c.nome AS categoria,
                COALESCE(pi.quantidade, 0) AS quantidade,
                COALESCE(pi.preco, 0) AS preco_unitario,
                COALESCE(pi.quantidade, 0) * COALESCE(pi.preco, 0) AS receita_item,
                COALESCE(pi.quantidade, 0) * COALESCE(pr.custo, 0) AS custo_estimado_item
            FROM tmp_pedidos_venda p
            JOIN tb_pedido_itens pi ON pi.pedido_id = p.id
            LEFT JOIN tb_produtos pr ON pr.id = pi.produto_id
            LEFT JOIN tb_categorias c ON c.id = pr.categoria_id",

            "DROP TEMPORARY TABLE IF EXISTS tmp_pagamentos_venda",
            "CREATE TEMPORARY TABLE tmp_pagamentos_venda AS
            SELECT
                p.periodo_fechamento AS periodo,
                p.id AS pedido_id,
                pg.id AS pagamento_id,
                fp.id AS forma_pagamento_id,
                fp.nome AS forma_pagamento,
                COALESCE(pg.valor, 0) AS valor_bruto,
                COALESCE(pg.troco, 0) AS troco,
                GREATEST(COALESCE(pg.valor, 0) - COALESCE(pg.troco, 0), 0) AS valor_liquido
            FROM tmp_pedidos_venda p
            JOIN tb_pagamentos pg ON pg.pedido_id = p.id
            LEFT JOIN tb_formas_pagamento fp ON fp.id = pg.forma_pagamento_id",

            "DROP TEMPORARY TABLE IF EXISTS tmp_usuarios_user",
            "CREATE TEMPORARY TABLE tmp_usuarios_user AS
            SELECT DISTINCT
                u.id,
                u.nome,
                u.email
            FROM tb_usuarios u
            JOIN tb_usuario_perfis up ON up.usuario_id = u.id
            JOIN tb_perfis pf ON pf.id = up.perfil_id
            WHERE u.empresa_id = @empresa_id
              AND u.ativo = 1
              AND pf.nome = 'user'",

            "DROP TEMPORARY TABLE IF EXISTS tmp_pedidos_user_mesa",
            "CREATE TEMPORARY TABLE tmp_pedidos_user_mesa AS
            SELECT
                p.periodo_fechamento AS periodo,
                p.id,
                p.usuario_id,
                u.nome AS usuario,
                p.mesa_id,
                p.comanda_id,
                p.total,
                p.data_pedido,
                p.data_fechamento
            FROM tmp_pedidos_venda p
            JOIN tmp_usuarios_user u ON u.id = p.usuario_id
            WHERE p.tipo = 'mesa'
              AND p.mesa_id IS NOT NULL",

            "DROP TEMPORARY TABLE IF EXISTS tmp_clientes_cadastro",
            "CREATE TEMPORARY TABLE tmp_clientes_cadastro AS
            SELECT
                c.id,
                c.nome,
                c.telefone,
                c.email,
                c.created_at,
                CASE
                    WHEN @tipo_agrupamento = 'dia' THEN DATE_FORMAT(c.created_at, '%Y-%m-%d')
                    WHEN @tipo_agrupamento = 'semana' THEN CONCAT(YEAR(c.created_at), '-S', LPAD(WEEK(c.created_at, 1), 2, '0'))
                    WHEN @tipo_agrupamento = 'mes' THEN DATE_FORMAT(c.created_at, '%Y-%m')
                    WHEN @tipo_agrupamento = 'ano' THEN DATE_FORMAT(c.created_at, '%Y')
                    ELSE 'PERIODO'
                END AS periodo
            FROM tb_clientes c
            WHERE c.empresa_id = @empresa_id
              AND (
                  (@tipo_filtro = 'dia' AND DATE(c.created_at) = @dia_ref)
                  OR (@tipo_filtro = 'semana' AND YEAR(c.created_at) = @ano_ref AND WEEK(c.created_at, 1) = @semana_ref)
                  OR (@tipo_filtro = 'mes' AND YEAR(c.created_at) = @ano_ref AND MONTH(c.created_at) = @mes_ref)
                  OR (@tipo_filtro = 'ano' AND YEAR(c.created_at) = @ano_ref)
                  OR (@tipo_filtro = 'periodo' AND c.created_at >= @data_inicio AND c.created_at <= @data_fim)
              )",

            "DROP TEMPORARY TABLE IF EXISTS tmp_delivery_operacao",
            "CREATE TEMPORARY TABLE tmp_delivery_operacao AS
            SELECT
                p.periodo_pedido AS periodo,
                p.id AS pedido_id,
                p.status AS status_pedido,
                p.total,
                p.data_pedido,
                e.entregador_id,
                COALESCE(e.taxa, 0) AS taxa_entrega,
                COALESCE(e.status, 'sem_registro') AS status_entrega
            FROM tmp_pedidos_operacao p
            LEFT JOIN tb_entregas e ON e.pedido_id = p.id
            WHERE p.tipo = 'delivery'",

            "DROP TEMPORARY TABLE IF EXISTS tmp_cozinha_periodo",
            "CREATE TEMPORARY TABLE tmp_cozinha_periodo AS
            SELECT
                CASE
                    WHEN @tipo_agrupamento = 'dia' THEN DATE_FORMAT(ci.created_at, '%Y-%m-%d')
                    WHEN @tipo_agrupamento = 'semana' THEN CONCAT(YEAR(ci.created_at), '-S', LPAD(WEEK(ci.created_at, 1), 2, '0'))
                    WHEN @tipo_agrupamento = 'mes' THEN DATE_FORMAT(ci.created_at, '%Y-%m')
                    WHEN @tipo_agrupamento = 'ano' THEN DATE_FORMAT(ci.created_at, '%Y')
                    ELSE 'PERIODO'
                END AS periodo,
                ped.id AS pedido_id,
                pi.id AS pedido_item_id,
                ce.id AS estacao_id,
                ce.nome AS estacao,
                ci.status,
                ci.created_at,
                ci.updated_at,
                TIMESTAMPDIFF(MINUTE, ci.created_at, ci.updated_at) AS tempo_ciclo_min
            FROM tb_cozinha_itens ci
            JOIN tb_pedido_itens pi ON pi.id = ci.pedido_item_id
            JOIN tb_pedidos ped ON ped.id = pi.pedido_id
            LEFT JOIN tb_cozinha_estacoes ce ON ce.id = ci.estacao_id
            WHERE ped.empresa_id = @empresa_id
              AND (
                  (@tipo_filtro = 'dia' AND DATE(ci.created_at) = @dia_ref)
                  OR (@tipo_filtro = 'semana' AND YEAR(ci.created_at) = @ano_ref AND WEEK(ci.created_at, 1) = @semana_ref)
                  OR (@tipo_filtro = 'mes' AND YEAR(ci.created_at) = @ano_ref AND MONTH(ci.created_at) = @mes_ref)
                  OR (@tipo_filtro = 'ano' AND YEAR(ci.created_at) = @ano_ref)
                  OR (@tipo_filtro = 'periodo' AND ci.created_at >= @data_inicio AND ci.created_at <= @data_fim)
              )",

            "DROP TEMPORARY TABLE IF EXISTS tmp_caixa_periodo",
            "CREATE TEMPORARY TABLE tmp_caixa_periodo AS
            SELECT
                CASE
                    WHEN @tipo_agrupamento = 'dia' THEN DATE_FORMAT(c.created_at, '%Y-%m-%d')
                    WHEN @tipo_agrupamento = 'semana' THEN CONCAT(YEAR(c.created_at), '-S', LPAD(WEEK(c.created_at, 1), 2, '0'))
                    WHEN @tipo_agrupamento = 'mes' THEN DATE_FORMAT(c.created_at, '%Y-%m')
                    WHEN @tipo_agrupamento = 'ano' THEN DATE_FORMAT(c.created_at, '%Y')
                    ELSE 'PERIODO'
                END AS periodo,
                c.id AS caixa_id,
                c.usuario_id,
                u.nome AS usuario,
                c.aberto_em,
                c.fechado_em,
                COALESCE(c.saldo_inicial, 0) AS saldo_inicial,
                COALESCE(c.saldo_final, 0) AS saldo_final
            FROM tb_caixas c
            LEFT JOIN tb_usuarios u ON u.id = c.usuario_id
            WHERE c.empresa_id = @empresa_id
              AND (
                  (@tipo_filtro = 'dia' AND DATE(c.created_at) = @dia_ref)
                  OR (@tipo_filtro = 'semana' AND YEAR(c.created_at) = @ano_ref AND WEEK(c.created_at, 1) = @semana_ref)
                  OR (@tipo_filtro = 'mes' AND YEAR(c.created_at) = @ano_ref AND MONTH(c.created_at) = @mes_ref)
                  OR (@tipo_filtro = 'ano' AND YEAR(c.created_at) = @ano_ref)
                  OR (@tipo_filtro = 'periodo' AND c.created_at >= @data_inicio AND c.created_at <= @data_fim)
              )",

            "DROP TEMPORARY TABLE IF EXISTS tmp_caixa_mov_periodo",
            "CREATE TEMPORARY TABLE tmp_caixa_mov_periodo AS
            SELECT
                CASE
                    WHEN @tipo_agrupamento = 'dia' THEN DATE_FORMAT(cm.criado_em, '%Y-%m-%d')
                    WHEN @tipo_agrupamento = 'semana' THEN CONCAT(YEAR(cm.criado_em), '-S', LPAD(WEEK(cm.criado_em, 1), 2, '0'))
                    WHEN @tipo_agrupamento = 'mes' THEN DATE_FORMAT(cm.criado_em, '%Y-%m')
                    WHEN @tipo_agrupamento = 'ano' THEN DATE_FORMAT(cm.criado_em, '%Y')
                    ELSE 'PERIODO'
                END AS periodo,
                c.id AS caixa_id,
                c.usuario_id,
                u.nome AS usuario,
                cm.tipo,
                COALESCE(cm.valor, 0) AS valor,
                cm.descricao,
                cm.criado_em
            FROM tb_caixa_movimentos cm
            JOIN tb_caixas c ON c.id = cm.caixa_id
            LEFT JOIN tb_usuarios u ON u.id = c.usuario_id
            WHERE c.empresa_id = @empresa_id
              AND (
                  (@tipo_filtro = 'dia' AND DATE(cm.criado_em) = @dia_ref)
                  OR (@tipo_filtro = 'semana' AND YEAR(cm.criado_em) = @ano_ref AND WEEK(cm.criado_em, 1) = @semana_ref)
                  OR (@tipo_filtro = 'mes' AND YEAR(cm.criado_em) = @ano_ref AND MONTH(cm.criado_em) = @mes_ref)
                  OR (@tipo_filtro = 'ano' AND YEAR(cm.criado_em) = @ano_ref)
                  OR (@tipo_filtro = 'periodo' AND cm.criado_em >= @data_inicio AND cm.criado_em <= @data_fim)
              )",

            "DROP TEMPORARY TABLE IF EXISTS tmp_estoque_mov_periodo",
            "CREATE TEMPORARY TABLE tmp_estoque_mov_periodo AS
            SELECT
                CASE
                    WHEN @tipo_agrupamento = 'dia' THEN DATE_FORMAT(em.criado_em, '%Y-%m-%d')
                    WHEN @tipo_agrupamento = 'semana' THEN CONCAT(YEAR(em.criado_em), '-S', LPAD(WEEK(em.criado_em, 1), 2, '0'))
                    WHEN @tipo_agrupamento = 'mes' THEN DATE_FORMAT(em.criado_em, '%Y-%m')
                    WHEN @tipo_agrupamento = 'ano' THEN DATE_FORMAT(em.criado_em, '%Y')
                    ELSE 'PERIODO'
                END AS periodo,
                pr.id AS produto_id,
                pr.nome AS produto,
                em.tipo,
                COALESCE(em.quantidade, 0) AS quantidade,
                em.criado_em
            FROM tb_estoque_movimentos em
            JOIN tb_produtos pr ON pr.id = em.produto_id
            WHERE pr.empresa_id = @empresa_id
              AND (
                  (@tipo_filtro = 'dia' AND DATE(em.criado_em) = @dia_ref)
                  OR (@tipo_filtro = 'semana' AND YEAR(em.criado_em) = @ano_ref AND WEEK(em.criado_em, 1) = @semana_ref)
                  OR (@tipo_filtro = 'mes' AND YEAR(em.criado_em) = @ano_ref AND MONTH(em.criado_em) = @mes_ref)
                  OR (@tipo_filtro = 'ano' AND YEAR(em.criado_em) = @ano_ref)
                  OR (@tipo_filtro = 'periodo' AND em.criado_em >= @data_inicio AND em.criado_em <= @data_fim)
              )",
        ];

        foreach ($stmts as $sql) {
            DB::statement($sql);
        }
    }
    protected function getRelatorioSql(string $codigo): ?string
    {
        return match ($codigo) {
            'r01' => "SELECT
                p.periodo_fechamento AS periodo,
                COUNT(*) AS pedidos_fechados,
                SUM(p.total) AS faturamento,
                ROUND(AVG(p.total), 2) AS ticket_medio,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, p.data_pedido, p.data_fechamento)), 2) AS ciclo_medio_min
            FROM tmp_pedidos_venda p
            GROUP BY p.periodo_fechamento
            ORDER BY p.periodo_fechamento",

            'r02' => "SELECT
                p.periodo_fechamento AS periodo,
                p.tipo,
                COUNT(*) AS pedidos,
                SUM(p.total) AS faturamento,
                ROUND(AVG(p.total), 2) AS ticket_medio
            FROM tmp_pedidos_venda p
            GROUP BY p.periodo_fechamento, p.tipo
            ORDER BY p.periodo_fechamento, faturamento DESC",

            'r03' => "SELECT
                p.periodo_pedido AS periodo,
                p.status,
                COUNT(*) AS quantidade_pedidos,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, p.data_pedido, NOW())), 2) AS idade_media_min
            FROM tmp_pedidos_operacao p
            GROUP BY p.periodo_pedido, p.status
            ORDER BY p.periodo_pedido, quantidade_pedidos DESC",

            'r04' => "SELECT
                HOUR(p.data_pedido) AS hora,
                COUNT(*) AS quantidade_pedidos,
                SUM(CASE WHEN p.status = 'fechado' THEN p.total ELSE 0 END) AS faturamento_fechado
            FROM tmp_pedidos_operacao p
            GROUP BY HOUR(p.data_pedido)
            ORDER BY hora",

            'r05' => "SELECT
                DAYNAME(p.data_fechamento) AS dia_semana,
                COUNT(*) AS pedidos_fechados,
                SUM(p.total) AS faturamento,
                ROUND(AVG(p.total), 2) AS ticket_medio
            FROM tmp_pedidos_venda p
            GROUP BY DAYNAME(p.data_fechamento)
            ORDER BY faturamento DESC",

            'r06' => "SELECT
                i.produto_id,
                i.produto,
                SUM(i.quantidade) AS quantidade_vendida,
                SUM(i.receita_item) AS faturamento_estimado
            FROM tmp_itens_venda i
            GROUP BY i.produto_id, i.produto
            ORDER BY quantidade_vendida DESC, faturamento_estimado DESC",

            'r07' => "SELECT
                i.produto_id,
                i.produto,
                SUM(i.quantidade) AS quantidade_vendida,
                SUM(i.receita_item) AS faturamento_estimado
            FROM tmp_itens_venda i
            GROUP BY i.produto_id, i.produto
            ORDER BY faturamento_estimado DESC, quantidade_vendida DESC",

            'r08' => "SELECT
                i.periodo,
                COALESCE(i.categoria, 'sem_categoria') AS categoria,
                SUM(i.quantidade) AS quantidade_vendida,
                SUM(i.receita_item) AS faturamento_estimado
            FROM tmp_itens_venda i
            GROUP BY i.periodo, COALESCE(i.categoria, 'sem_categoria')
            ORDER BY i.periodo, faturamento_estimado DESC",

            'r09' => "SELECT
                i.produto_id,
                i.produto,
                SUM(i.receita_item) AS receita_estimada,
                SUM(i.custo_estimado_item) AS custo_estimado,
                SUM(i.receita_item - i.custo_estimado_item) AS margem_estimada,
                ROUND(
                    CASE
                        WHEN SUM(i.receita_item) = 0 THEN 0
                        ELSE (SUM(i.receita_item - i.custo_estimado_item) / SUM(i.receita_item)) * 100
                    END,
                    2
                ) AS margem_percentual
            FROM tmp_itens_venda i
            GROUP BY i.produto_id, i.produto
            ORDER BY margem_estimada DESC",

            'r10' => "SELECT
                COALESCE(i.categoria, 'sem_categoria') AS categoria,
                SUM(i.receita_item) AS receita_estimada,
                SUM(i.custo_estimado_item) AS custo_estimado,
                SUM(i.receita_item - i.custo_estimado_item) AS margem_estimada,
                ROUND(
                    CASE
                        WHEN SUM(i.receita_item) = 0 THEN 0
                        ELSE (SUM(i.receita_item - i.custo_estimado_item) / SUM(i.receita_item)) * 100
                    END,
                    2
                ) AS margem_percentual
            FROM tmp_itens_venda i
            GROUP BY COALESCE(i.categoria, 'sem_categoria')
            ORDER BY margem_estimada DESC",

            'r11' => "SELECT
                i.periodo,
                COUNT(DISTINCT i.pedido_id) AS pedidos_com_itens,
                SUM(i.quantidade) AS total_itens,
                ROUND(SUM(i.quantidade) / NULLIF(COUNT(DISTINCT i.pedido_id), 0), 2) AS media_itens_por_pedido,
                ROUND(SUM(i.receita_item) / NULLIF(COUNT(DISTINCT i.pedido_id), 0), 2) AS media_receita_itens_por_pedido
            FROM tmp_itens_venda i
            GROUP BY i.periodo
            ORDER BY i.periodo",

            'r12' => "SELECT
                COALESCE(pg.forma_pagamento, 'sem_forma_pagamento') AS forma_pagamento,
                COUNT(*) AS quantidade_lancamentos,
                SUM(pg.valor_bruto) AS valor_bruto,
                SUM(pg.troco) AS troco,
                SUM(pg.valor_liquido) AS valor_liquido_estimado
            FROM tmp_pagamentos_venda pg
            GROUP BY COALESCE(pg.forma_pagamento, 'sem_forma_pagamento')
            ORDER BY valor_liquido_estimado DESC",

            'r13' => "SELECT
                pg.periodo,
                COALESCE(pg.forma_pagamento, 'sem_forma_pagamento') AS forma_pagamento,
                COUNT(*) AS quantidade_lancamentos,
                SUM(pg.valor_liquido) AS valor_liquido_estimado
            FROM tmp_pagamentos_venda pg
            GROUP BY pg.periodo, COALESCE(pg.forma_pagamento, 'sem_forma_pagamento')
            ORDER BY pg.periodo, valor_liquido_estimado DESC",

            'r14' => "SELECT
                COALESCE(pg.forma_pagamento, 'sem_forma_pagamento') AS forma_pagamento,
                SUM(pg.troco) AS troco_total,
                ROUND(AVG(pg.troco), 2) AS troco_medio
            FROM tmp_pagamentos_venda pg
            GROUP BY COALESCE(pg.forma_pagamento, 'sem_forma_pagamento')
            ORDER BY troco_total DESC",

            'r15' => "SELECT
                p.periodo_fechamento AS periodo,
                COUNT(*) AS pedidos,
                SUM(p.total) AS total_pedidos,
                SUM(COALESCE(pg.total_pago_liquido, 0)) AS total_pago_liquido,
                SUM(COALESCE(pg.total_pago_bruto, 0)) AS total_pago_bruto,
                SUM(COALESCE(pg.total_pago_liquido, 0) - p.total) AS diferenca_liquida
            FROM tmp_pedidos_venda p
            LEFT JOIN (
                SELECT
                    pedido_id,
                    SUM(valor_bruto) AS total_pago_bruto,
                    SUM(valor_liquido) AS total_pago_liquido
                FROM tmp_pagamentos_venda
                GROUP BY pedido_id
            ) pg ON pg.pedido_id = p.id
            GROUP BY p.periodo_fechamento
            ORDER BY p.periodo_fechamento",

            'r16' => "SELECT
                u.usuario_id,
                u.usuario,
                COUNT(*) AS pedidos_fechados,
                COUNT(DISTINCT u.mesa_id) AS mesas_atendidas,
                SUM(u.total) AS faturamento,
                ROUND(AVG(u.total), 2) AS ticket_medio,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, u.data_pedido, u.data_fechamento)), 2) AS ciclo_medio_min
            FROM tmp_pedidos_user_mesa u
            GROUP BY u.usuario_id, u.usuario
            ORDER BY faturamento DESC, pedidos_fechados DESC",

            'r17' => "SELECT
                u.periodo,
                u.usuario_id,
                u.usuario,
                COUNT(*) AS pedidos_fechados,
                SUM(u.total) AS faturamento,
                ROUND(AVG(u.total), 2) AS ticket_medio
            FROM tmp_pedidos_user_mesa u
            GROUP BY u.periodo, u.usuario_id, u.usuario
            ORDER BY u.periodo, faturamento DESC",

            'r18' => "SELECT
                u.usuario_id,
                u.usuario,
                u.mesa_id,
                COUNT(*) AS pedidos_fechados,
                SUM(u.total) AS faturamento,
                ROUND(AVG(u.total), 2) AS ticket_medio
            FROM tmp_pedidos_user_mesa u
            GROUP BY u.usuario_id, u.usuario, u.mesa_id
            ORDER BY u.usuario, faturamento DESC",

            'r19' => "SELECT
                p.periodo_fechamento AS periodo,
                m.numero AS mesa,
                COUNT(*) AS pedidos_fechados,
                SUM(p.total) AS faturamento,
                ROUND(AVG(p.total), 2) AS ticket_medio
            FROM tmp_pedidos_venda p
            JOIN tb_mesas m ON m.id = p.mesa_id
            GROUP BY p.periodo_fechamento, m.numero
            ORDER BY p.periodo_fechamento, faturamento DESC",

            'r20' => "SELECT
                m.numero AS mesa,
                COUNT(*) AS pedidos_fechados,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, p.data_pedido, p.data_fechamento)), 2) AS ciclo_medio_min,
                SUM(p.total) AS faturamento
            FROM tmp_pedidos_venda p
            JOIN tb_mesas m ON m.id = p.mesa_id
            GROUP BY m.numero
            ORDER BY faturamento DESC",

            'r21' => "SELECT
                p.periodo_fechamento AS periodo,
                c.numero AS comanda,
                COUNT(*) AS pedidos_fechados,
                SUM(p.total) AS faturamento,
                ROUND(AVG(p.total), 2) AS ticket_medio
            FROM tmp_pedidos_venda p
            JOIN tb_comandas c ON c.id = p.comanda_id
            GROUP BY p.periodo_fechamento, c.numero
            ORDER BY p.periodo_fechamento, faturamento DESC",

            'r22' => "SELECT
                c.id AS cliente_id,
                c.nome AS cliente,
                COUNT(*) AS pedidos_fechados,
                SUM(p.total) AS faturamento,
                ROUND(AVG(p.total), 2) AS ticket_medio
            FROM tmp_pedidos_venda p
            JOIN tb_clientes c ON c.id = p.cliente_id
            GROUP BY c.id, c.nome
            ORDER BY faturamento DESC, pedidos_fechados DESC",

            'r23' => "SELECT
                c.periodo,
                COUNT(*) AS clientes_novos
            FROM tmp_clientes_cadastro c
            GROUP BY c.periodo
            ORDER BY c.periodo",

            'r24' => "SELECT
                COUNT(CASE WHEN x.qtd_pedidos = 1 THEN 1 END) AS clientes_compra_unica,
                COUNT(CASE WHEN x.qtd_pedidos > 1 THEN 1 END) AS clientes_recorrentes,
                COUNT(*) AS clientes_identificados
            FROM (
                SELECT
                    p.cliente_id,
                    COUNT(*) AS qtd_pedidos
                FROM tmp_pedidos_venda p
                WHERE p.cliente_id IS NOT NULL
                GROUP BY p.cliente_id
            ) x",

            'r25' => "SELECT
                c.id AS cliente_id,
                c.nome AS cliente,
                COUNT(*) AS pedidos_fechados,
                SUM(p.total) AS faturamento,
                MAX(p.data_fechamento) AS ultima_compra
            FROM tmp_pedidos_venda p
            JOIN tb_clientes c ON c.id = p.cliente_id
            GROUP BY c.id, c.nome
            ORDER BY faturamento DESC",

            'r26' => "SELECT
                d.periodo,
                d.status_pedido,
                d.status_entrega,
                COUNT(*) AS quantidade_pedidos,
                SUM(d.total) AS valor_pedidos,
                SUM(d.taxa_entrega) AS taxa_entrega_total
            FROM tmp_delivery_operacao d
            GROUP BY d.periodo, d.status_pedido, d.status_entrega
            ORDER BY d.periodo, quantidade_pedidos DESC",

            'r27' => "SELECT
                d.periodo,
                COUNT(*) AS pedidos_delivery,
                SUM(d.total) AS faturamento_pedidos,
                SUM(d.taxa_entrega) AS receita_taxa_entrega,
                ROUND(AVG(d.total), 2) AS ticket_medio_delivery
            FROM tmp_delivery_operacao d
            GROUP BY d.periodo
            ORDER BY d.periodo",

            'r28' => "SELECT
                COALESCE(e.nome, 'sem_entregador') AS entregador,
                COUNT(*) AS pedidos_delivery,
                SUM(d.total) AS faturamento_pedidos,
                SUM(d.taxa_entrega) AS taxa_entrega_total
            FROM tmp_delivery_operacao d
            LEFT JOIN tb_entregadores e ON e.id = d.entregador_id
            GROUP BY COALESCE(e.nome, 'sem_entregador')
            ORDER BY faturamento_pedidos DESC",

            'r29' => "SELECT
                COALESCE(c.estacao, 'sem_estacao') AS estacao,
                COUNT(*) AS quantidade_itens,
                ROUND(AVG(c.tempo_ciclo_min), 2) AS tempo_medio_min,
                MIN(c.tempo_ciclo_min) AS menor_tempo_min,
                MAX(c.tempo_ciclo_min) AS maior_tempo_min
            FROM tmp_cozinha_periodo c
            GROUP BY COALESCE(c.estacao, 'sem_estacao')
            ORDER BY quantidade_itens DESC",

            'r30' => "SELECT
                c.periodo,
                c.status,
                COUNT(*) AS quantidade_itens
            FROM tmp_cozinha_periodo c
            GROUP BY c.periodo, c.status
            ORDER BY c.periodo, quantidade_itens DESC",

            'r31' => "SELECT
                c.periodo,
                COALESCE(c.estacao, 'sem_estacao') AS estacao,
                COUNT(*) AS quantidade_itens,
                ROUND(AVG(c.tempo_ciclo_min), 2) AS tempo_medio_min
            FROM tmp_cozinha_periodo c
            GROUP BY c.periodo, COALESCE(c.estacao, 'sem_estacao')
            ORDER BY c.periodo, quantidade_itens DESC",

            'r32' => "SELECT
                c.periodo,
                COUNT(*) AS quantidade_caixas,
                SUM(c.saldo_inicial) AS saldo_inicial_total,
                SUM(c.saldo_final) AS saldo_final_total,
                SUM(c.saldo_final - c.saldo_inicial) AS variacao_total
            FROM tmp_caixa_periodo c
            GROUP BY c.periodo
            ORDER BY c.periodo",

            'r33' => "SELECT
                COALESCE(c.usuario, 'sem_usuario') AS usuario,
                COUNT(*) AS quantidade_caixas,
                SUM(c.saldo_inicial) AS saldo_inicial_total,
                SUM(c.saldo_final) AS saldo_final_total,
                SUM(c.saldo_final - c.saldo_inicial) AS variacao_total
            FROM tmp_caixa_periodo c
            GROUP BY COALESCE(c.usuario, 'sem_usuario')
            ORDER BY variacao_total DESC",

            'r34' => "SELECT
                m.periodo,
                m.tipo,
                COUNT(*) AS quantidade_movimentos,
                SUM(m.valor) AS valor_total
            FROM tmp_caixa_mov_periodo m
            GROUP BY m.periodo, m.tipo
            ORDER BY m.periodo, valor_total DESC",

            'r35' => "SELECT
                COALESCE(m.usuario, 'sem_usuario') AS usuario,
                m.tipo,
                COUNT(*) AS quantidade_movimentos,
                SUM(m.valor) AS valor_total
            FROM tmp_caixa_mov_periodo m
            GROUP BY COALESCE(m.usuario, 'sem_usuario'), m.tipo
            ORDER BY usuario, valor_total DESC",

            'r36' => "SELECT
                pr.id AS produto_id,
                pr.nome AS produto,
                COALESCE(c.nome, 'sem_categoria') AS categoria,
                COALESCE(e.quantidade, 0) AS estoque_atual,
                COALESCE(pr.custo, 0) AS custo_unitario_atual,
                COALESCE(e.quantidade, 0) * COALESCE(pr.custo, 0) AS valor_estoque_custo
            FROM tb_estoque e
            JOIN tb_produtos pr ON pr.id = e.produto_id
            LEFT JOIN tb_categorias c ON c.id = pr.categoria_id
            WHERE e.empresa_id = @empresa_id
            ORDER BY estoque_atual ASC, produto",

            'r37' => "SELECT
                m.periodo,
                m.tipo,
                COUNT(*) AS quantidade_movimentos,
                SUM(m.quantidade) AS quantidade_total
            FROM tmp_estoque_mov_periodo m
            GROUP BY m.periodo, m.tipo
            ORDER BY m.periodo, quantidade_total DESC",

            'r38' => "SELECT
                m.produto_id,
                m.produto,
                m.tipo,
                SUM(m.quantidade) AS quantidade_total
            FROM tmp_estoque_mov_periodo m
            GROUP BY m.produto_id, m.produto, m.tipo
            ORDER BY m.produto, m.tipo",

            'r39' => "SELECT
                p.id AS pedido_id,
                p.periodo_fechamento AS periodo,
                p.total AS total_pedido,
                COALESCE(i.total_itens, 0) AS total_itens,
                COALESCE(i.total_itens, 0) - p.total AS diferenca
            FROM tmp_pedidos_venda p
            LEFT JOIN (
                SELECT
                    pedido_id,
                    SUM(receita_item) AS total_itens
                FROM tmp_itens_venda
                GROUP BY pedido_id
            ) i ON i.pedido_id = p.id
            WHERE ROUND(COALESCE(i.total_itens, 0) - p.total, 2) <> 0
            ORDER BY ABS(COALESCE(i.total_itens, 0) - p.total) DESC, p.id",

            'r40' => "SELECT
                p.id AS pedido_id,
                p.periodo_fechamento AS periodo,
                p.total AS total_pedido,
                COALESCE(pg.total_pago_liquido, 0) AS total_pago_liquido,
                COALESCE(pg.total_pago_liquido, 0) - p.total AS diferenca
            FROM tmp_pedidos_venda p
            LEFT JOIN (
                SELECT
                    pedido_id,
                    SUM(valor_liquido) AS total_pago_liquido
                FROM tmp_pagamentos_venda
                GROUP BY pedido_id
            ) pg ON pg.pedido_id = p.id
            WHERE ROUND(COALESCE(pg.total_pago_liquido, 0) - p.total, 2) <> 0
            ORDER BY ABS(COALESCE(pg.total_pago_liquido, 0) - p.total) DESC, p.id",

            default => null,
        };
    }
}
