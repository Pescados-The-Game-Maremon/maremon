<?php
namespace Maremon\Controllers\Http;

use PDO;
use Throwable;
use Maremon\Services\ItemService;
use Maremon\Services\CombateMotor;
use Maremon\Services\EquipeCombate;
use Maremon\Services\BossService;
use Maremon\Models\CombateRepository;
use Maremon\Exceptions\JogoException;

final class UsarItemCombateAction {
    public function executar(): void {
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store, private");

        [$pdo, $pescadorId] = \Maremon\Core\Autenticacao::conectarJogador();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->saidaItemBatalha([
                "sucesso" => false,
                "erro" => "Utilize POST."
            ], 405);
        }

        $dados = json_decode(file_get_contents("php://input"), true);

        if (!is_array($dados)) {
            $this->saidaItemBatalha([
                "sucesso" => false,
                "erro" => "Dados inválidos."
            ], 400);
        }

        $combate = $_SESSION["combate"] ?? null;

        if (
            !is_array($combate) ||
            !($combate["ativo"] ?? false) ||
            (int) ($combate["pescador_id"] ?? 0) !== $pescadorId
        ) {
            $this->saidaItemBatalha([
                "sucesso" => false,
                "erro" => "Combate inválido ou encerrado."
            ], 409);
        }

        \Maremon\Core\TokenCombate::exigirTokenCombate($combate, $dados, "csrf_token");

        $itemId = filter_var($dados["item_id"] ?? null, FILTER_VALIDATE_INT);
        $pescadoId = filter_var($dados["pescado_id"] ?? null, FILTER_VALIDATE_INT);

        if (!$itemId || !$pescadoId) {
            $this->saidaItemBatalha([
                "sucesso" => false,
                "erro" => "Escolha inválida."
            ], 400);
        }

        try {
            if (EquipeCombate::exigeTroca($combate)) {
                throw new JogoException("Troque de Pescado antes de utilizar itens.");
            }

            $pdo->beginTransaction();

            $stmt = (new \Maremon\Models\InventarioRepository($pdo))->bloquearContaBatalha(["id" => $pescadorId]);

            if (!$stmt->fetch()) {
                throw new JogoException("Jogador não encontrado.");
            }

            $stmt = (new \Maremon\Models\InventarioRepository($pdo))->bloquearItemBatalha([
                "id" => $pescadorId,
                "item" => $itemId
            ]);

            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item || (int) $item["quantidade"] < 1) {
                throw new JogoException("Este item acabou.");
            }

            $stmt = (new \Maremon\Models\InventarioRepository($pdo))->bloquearAlvoBatalha([
                "id" => $pescadorId,
                "pescado" => $pescadoId
            ]);

            $alvo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$alvo) {
                throw new JogoException("Você não possui este Pescado.");
            }

                                                                     
                                                                                 
            EquipeCombate::sincronizar($combate);

            if (!isset($combate["equipe"][$pescadoId])) {
                throw new JogoException("Use itens somente nos integrantes da equipe.");
            }

            $ativo = (int) $combate["jogador"]["id"] === $pescadoId;
            $alvo = array_merge($alvo, $combate["equipe"][$pescadoId]);

            if ($alvo["hp"] === null) {
                $alvo["hp"] = $alvo["hp_max"];
            }

            $hpAnterior = (int) $alvo["hp"];
            $curado = (new ItemService())->aplicar($item, $alvo, true);

            $stmt = (new \Maremon\Models\InventarioRepository($pdo))->salvarCuraBatalha([
                "hp" => (int) $curado["hp"],
                "status" => $curado["status"],
                "turnos" => (int) $curado["status_turnos"],
                "id" => $pescadorId,
                "pescado" => $pescadoId
            ]);

            $stmt = (new \Maremon\Models\InventarioRepository($pdo))->consumirItemBatalha([
                "id" => $pescadorId,
                "item" => $itemId
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new JogoException("Estoque insuficiente.");
            }

            $stmt = (new \Maremon\Models\InventarioRepository($pdo))->removerEstoqueVazioBatalha([
                "id" => $pescadorId,
                "item" => $itemId
            ]);

            foreach (["hp", "status", "status_turnos"] as $chave) {
                $combate["equipe"][$pescadoId][$chave] = $curado[$chave];

                if ($ativo) {
                    $combate["jogador"][$chave] = $curado[$chave];
                }
            }

                                                                      
            $repo = new CombateRepository($pdo);
            $ataques = $repo->ataques(
                (int) $combate["inimigo"]["id"],
                (int) $combate["inimigo"]["nivel"]
            );

            if (!$ataques) {
                throw new JogoException("O inimigo não possui ataques.");
            }

            $ataqueInimigo = $ataques[random_int(0, count($ataques) - 1)];
            $motor = new CombateMotor();
                                                                             
                                                                     
            $forcarTroca = EquipeCombate::exigeTroca($combate);
            $eventos = $forcarTroca ? [] : $motor->contraAtaque(
                $combate["jogador"],
                $combate["inimigo"],
                $ataqueInimigo
            );

            EquipeCombate::sincronizar($combate);
            $resultado = EquipeCombate::resultado($combate);

                                                                                     
            $expGanha = $resultado === "vitoria"
                ? (!empty($combate["boss_ordem"]) ? 2500 : 100)
                : 0;
            $moedasGanhas = $resultado === "vitoria" ? 25 : 0;

            if ($expGanha > 0) {
                $stmt = (new \Maremon\Models\InventarioRepository($pdo))->bloquearProgressaoBatalha([
                    "id" => $pescadorId,
                    "pescado" => (int) $combate["jogador"]["id"]
                ]);

                $progresso = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$progresso) {
                    throw new JogoException("Pescado ativo não encontrado.");
                }

                $expTotal = (int) $progresso["exp"] + $expGanha;
                $nivelNovo = \Maremon\Services\ExperienciaService::calcularNivel((int) $progresso["nivel"], $expTotal);
                $ganhos = $nivelNovo - (int) $progresso["nivel"];

                if ($ganhos > 0) {
                    $combate["jogador"]["nivel"] = $nivelNovo;
                    $combate["jogador"]["hp_max"] += 2 * $ganhos;

                    foreach (["atk", "def", "spe"] as $campo) {
                        $combate["jogador"][$campo] += $ganhos;
                    }

                    $combate["jogador"]["hp"] = min(
                        (int) $combate["jogador"]["hp_max"],
                        (int) $combate["jogador"]["hp"] + 2 * $ganhos
                    );
                }

                $stmt = (new \Maremon\Models\InventarioRepository($pdo))->salvarProgressaoBatalha([
                    "nivel" => $nivelNovo,
                    "exp" => $expTotal,
                    "id" => $pescadorId,
                    "pescado" => (int) $combate["jogador"]["id"]
                ]);

                $stmt = (new \Maremon\Models\InventarioRepository($pdo))->creditarMoedasBatalha([
                    "moedas" => $moedasGanhas,
                    "id" => $pescadorId
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new JogoException("Falha ao creditar moedas.");
                }
            }

                                                                     
            EquipeCombate::salvar($pdo, $pescadorId, $combate);

            \Maremon\Models\LogCombateRepository::atualizarLogCombate(
                $pdo,
                (int) $combate["log_id"],
                $pescadorId,
                $resultado === "continuar" ? "ativo" : $resultado,
                (int) $combate["turno"],
                $moedasGanhas
            );

            $bossVitoria = $resultado === "vitoria"
                ? BossService::registrarVitoria($pdo, $pescadorId, $combate)
                : null;

            $pdo->commit();

            if ($resultado !== "continuar") {
                $combate["ativo"] = false;
            }

            $combate["turno"]++;
            $_SESSION["combate"] = $combate;

            $this->saidaItemBatalha([
                "sucesso" => true,
                "mensagem" => $item["nome"] . " utilizado! O inimigo respondeu.",
                "hp_anterior" => $hpAnterior,
                "jogador" => $combate["jogador"],
                "inimigo" => $combate["inimigo"],
                "eventos" => $eventos,
                "resultado" => $resultado,
                "turno" => $combate["turno"],
                "dinheiro_ganho" => $moedasGanhas,
                "exp_ganha" => $expGanha,
                "boss_vitoria" => $bossVitoria,
                "boss_ordem" => $combate["boss_ordem"] ?? null,
                "equipe" => EquipeCombate::membros($combate),
                "troca_obrigatoria" => EquipeCombate::exigeTroca($combate)
            ]);

        } catch (JogoException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->saidaItemBatalha([
                "sucesso" => false,
                "erro" => $e->getMessage()
            ], 400);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log((string) $e);
            $this->saidaItemBatalha([
                "sucesso" => false,
                "erro" => "Não foi possível usar o item no combate."
            ], 500);
        }
    }

    private function saidaItemBatalha(array $dados, int $status = 200): never
    {
        http_response_code($status);
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
