<?php

namespace Maremon\Controllers;

use PDO;
use Maremon\Models\CombateRepository;
use Maremon\Services\CombateMotor;
use Maremon\Services\EquipeCombate;
use Maremon\Services\BossService;
use Maremon\Exceptions\JogoException;

final class CombateController
{
    private CombateRepository $repo;
    private CombateMotor $motor;

    public function __construct(private PDO $pdo)
    {
        $this->repo = new CombateRepository($pdo);
        $this->motor = new CombateMotor();
    }

    public function atacar(int $pescadorId, array &$combate, int $ataqueId): array
    {
        if (EquipeCombate::exigeTroca($combate)) {
            throw new JogoException("Escolha outro Pescado para continuar a batalha.");
        }

        $jogador = &$combate["jogador"];
        $inimigo = &$combate["inimigo"];

        $ataquesJogador = $this->repo->ataques(
            (int) $jogador["id"],
            (int) $jogador["nivel"]
        );

        $ataquesInimigo = $this->repo->ataques(
            (int) $inimigo["id"],
            (int) $inimigo["nivel"]
        );

        $escolhido = null;

        foreach ($ataquesJogador as $ataque) {
            if ((int) $ataque["id"] === $ataqueId) {
                $escolhido = $ataque;
                break;
            }
        }

        if ($escolhido === null) {
            throw new JogoException("Este ataque não está disponível para seu Pescado.");
        }

        if (!$ataquesInimigo) {
            throw new JogoException("O inimigo não possui ataques cadastrados.");
        }

        $ppAtual = (int) (
            $combate["pp_equipe"][(int) $jogador["id"]][$ataqueId] ?? $escolhido["pp_max"]
        );

        if ($ppAtual <= 0) {
            throw new JogoException("Este ataque está sem PP.");
        }

        $ataqueInimigo = $ataquesInimigo[random_int(0, count($ataquesInimigo) - 1)];
        $eventos = $this->motor->executarTurno(
            $jogador,
            $inimigo,
            $escolhido,
            $ataqueInimigo
        );

        $combate["pp_equipe"][(int) $jogador["id"]][$ataqueId] = $ppAtual - 1;
        EquipeCombate::sincronizar($combate);
        $resultado = EquipeCombate::resultado($combate);

        // Recompensas da versão atual do Maremon.
        $expGanha = $resultado === "vitoria"
        ? (!empty($combate["boss_ordem"]) ? 2500 : 100)
        : 0;
        $moedas = $resultado === "vitoria" ? 25 : 0;

        $this->pdo->beginTransaction();

        try {
            $stmt = (new \Maremon\Models\CombateDadosRepository($this->pdo))->bloquearProgressaoTurno([
                "jogador" => $pescadorId,
                "pescado" => (int) $jogador["id"]
            ]);

            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$registro) {
                throw new JogoException("Pescado ativo não encontrado.");
            }

            $nivelAnterior = (int) $registro["nivel"];
            $expTotal = (int) $registro["exp"] + $expGanha;
            $nivelNovo = \Maremon\Services\ExperienciaService::calcularNivel($nivelAnterior, $expTotal);
            $niveisGanhos = $nivelNovo - $nivelAnterior;

            if ($niveisGanhos > 0) {
                $jogador["nivel"] = $nivelNovo;
                $jogador["hp_max"] += 2 * $niveisGanhos;

                foreach (["atk", "def", "spe"] as $atributo) {
                    $jogador[$atributo] += $niveisGanhos;
                }

                $jogador["hp"] = min(
                    (int) $jogador["hp_max"],
                    (int) $jogador["hp"] + 2 * $niveisGanhos
                );
            }

            EquipeCombate::sincronizar($combate);
            EquipeCombate::salvar($this->pdo, $pescadorId, $combate);

            // EXP vai apenas para o integrante ativo que venceu.
            if ($expGanha > 0) {
                $stmt = (new \Maremon\Models\CombateDadosRepository($this->pdo))->salvarProgressaoTurno([
                    "nivel" => $nivelNovo,
                    "exp" => $expTotal,
                    "jogador" => $pescadorId,
                    "pescado" => (int) $jogador["id"]
                ]);
            }

            \Maremon\Models\LogCombateRepository::atualizarLogCombate(
                $this->pdo,
                (int) $combate["log_id"],
                $pescadorId,
                $resultado === "continuar" ? "ativo" : $resultado,
                (int) $combate["turno"],
                $moedas
            );

            if ($moedas > 0) {
                $stmt = (new \Maremon\Models\CombateDadosRepository($this->pdo))->creditarMoedasTurno([
                    "moedas" => $moedas,
                    "pescador_id" => $pescadorId
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new JogoException("Não foi possível creditar moedas.");
                }
            }

            $bossVitoria = $resultado === "vitoria"
                ? BossService::registrarVitoria($this->pdo, $pescadorId, $combate)
                : null;

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        if ($resultado !== "continuar") {
            $combate["ativo"] = false;
        }

        $combate["turno"]++;

        return [
            "sucesso" => true,
            "eventos" => $eventos,
            "jogador" => $jogador,
            "inimigo" => $inimigo,
            "resultado" => $resultado,
            "dinheiro_ganho" => $moedas,
            "exp_ganha" => $expGanha,
            "exp_total" => $expTotal,
            "nivel_anterior" => $nivelAnterior,
            "nivel_novo" => $nivelNovo,
            "niveis_ganhos" => $niveisGanhos,
            "boss_vitoria" => $bossVitoria,
            "boss_ordem" => $combate["boss_ordem"] ?? null,
            "turno" => $combate["turno"],
            "pp_jogador" => $combate["pp_equipe"][(int) $jogador["id"]] ?? [],
            "equipe" => EquipeCombate::membros($combate),
            "troca_obrigatoria" => EquipeCombate::exigeTroca($combate)
        ];
    }
}
