<?php
namespace Maremon\Controllers\Http;

use PDO;
use Throwable;
use RuntimeException;
use Maremon\Services\BossService;
use Maremon\Services\FormacaoEquipe;

final class IniciarCombateAction {
    public function executar(): void {
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store, private");
        header("Vary: Cookie");

        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        try {
            // Escolher até três integrantes que o jogador organizou antes da batalha.
            $stmtEquipe = (new \Maremon\Models\CombateDadosRepository($pdo))->listarFormacaoInicial(["pescador_id" => $pescadorId]);
            $integrantes = $stmtEquipe->fetchAll(PDO::FETCH_ASSOC);

            if (!$integrantes) {
                throw new RuntimeException("Monte uma equipe de até 3 Pescados antes da batalha.");
            }

            $equipe = [];
            $formacao = [];
            $registrosHp = [];

            foreach ($integrantes as $integrante) {
                $integrante["hp"] = max(0, min(
                    (int) $integrante["hp_max"],
                    (int) $integrante["hp"]
                ));
                $equipe[(int) $integrante["id"]] = $integrante;

                $formacao[(int)$integrante["equipe_slot"]] = (int)$integrante["id"];
                $registrosHp[] = ["pescado_id" => (int)$integrante["id"], "hp" => $integrante["hp"]];
            }

            $idInicial = FormacaoEquipe::primeiroVivo($registrosHp, $formacao);
            if ($idInicial === null) {
                throw new RuntimeException("Todos os Pescados da equipe estão sem HP. Vá ao Centro de Cura ou escolha um integrante vivo antes de pescar.");
            }

            $jogador = $equipe[$idInicial];

            // Cada novo encontro realiza um sorteio independente de 5% para o
            // chefe correspondente à isca escolhida e desbloqueada pelo jogador.
            $iscaAtual = BossService::iscaAtual($pdo, $pescadorId);
            $bossOrdem = null;
            $inimigo = null;

            if (random_int(1, 100) <= 5) {
                $stmtBoss = (new \Maremon\Models\CombateDadosRepository($pdo))->buscarBoss(["ordem" => $iscaAtual]);
                $inimigo = $stmtBoss->fetch(PDO::FETCH_ASSOC) ?: null;

                if ($inimigo !== null) {
                    $bossOrdem = (int) $inimigo["boss_ordem"];
                }
            }

            // Nos 95% de encontros normais, as espécies dos oito chefes
            // não entram no sorteio. O chefe nunca aparece como selvagem comum.
            if ($inimigo === null) {
                $inimigo = (new \Maremon\Models\CombateDadosRepository($pdo))->sortearSelvagem()->fetch();
            }

            if (!$inimigo) {
                throw new RuntimeException("Nenhum Pescado selvagem disponível.");
            }

            $pdo->beginTransaction();

            // Fechar um registro anterior, caso o jogador tenha saído sem encerrar.
            $stmt = (new \Maremon\Models\CombateDadosRepository($pdo))->encerrarRegistroAnterior([
                "pescador_id" => $pescadorId
            ]);

            $stmt = (new \Maremon\Models\CombateDadosRepository($pdo))->abrirRegistro([
                "pescador_id" => $pescadorId,
                "jogador_id" => (int) $jogador["id"],
                "inimigo_id" => (int) $inimigo["id"]
            ]);

            $combateLogId = (int) $stmt->fetchColumn();
            $pdo->commit();

            // Os dois tokens pertencem a este encontro e à sessão do jogador.
            $_SESSION["combate"] = [
                "pescador_id" => $pescadorId,
                "jogador" => $jogador,
                "equipe" => $equipe,
                "pp_equipe" => [],
                "inimigo" => $inimigo,
                "boss_ordem" => $bossOrdem,
                "isca_atual" => $iscaAtual,
                "turno" => 1,
                "ativo" => true,
                "captura_tentada" => false,
                "log_id" => $combateLogId,
                "pp_jogador" => [],
                "csrf_captura" => bin2hex(random_bytes(32)),
                "csrf_acao" => bin2hex(random_bytes(32))
            ];

            echo json_encode([
                "sucesso" => true,
                "jogador" => $jogador,
                "equipe" => \Maremon\Services\EquipeCombate::membros($_SESSION["combate"]),
                "troca_obrigatoria" => false,
                "inimigo" => $inimigo,
                "boss_ordem" => $bossOrdem,
                "isca_atual" => $iscaAtual,
                "turno" => 1,
                "combate_id" => $combateLogId,
                "mensagem" => $bossOrdem !== null
                    ? "BOSS! Um chefe DRK apareceu com cinco vezes a vida normal!"
                    : (count($equipe) < 2
                        ? "Equipe com um integrante: configure mais Pescados para poder trocar."
                        : "Um Pescado selvagem apareceu!"),
                "csrf_captura" => $_SESSION["combate"]["csrf_captura"],
                "captura_tentada" => false,
                "csrf_acao" => $_SESSION["combate"]["csrf_acao"]
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());
            http_response_code(400);

            echo json_encode([
                "sucesso" => false,
                "erro" => $e instanceof RuntimeException
                    ? $e->getMessage()
                    : "Não foi possível iniciar o combate."
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
