<?php

namespace Maremon\Models;

use PDO;
use Maremon\Exceptions\JogoException;

final class CombateRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ataques(int $pescadoId, int $nivel = 100): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                a.id,
                a.nome,
                a.tipo,
                a.poder,
                a.precisao,
                COALESCE(a.pp_max, 20) AS pp_max,
                a.status_aplicado,
                a.chance_status,
                a.duracao_status
            FROM public.pescado_ataques pa
            JOIN public.ataques a ON a.id = pa.ataque_id
            WHERE pa.pescado_id = :id
              AND COALESCE(pa.nivel_necessario, 1) <= :nivel
            ORDER BY pa.slot NULLS LAST, a.id
            LIMIT 4
        ");

        $stmt->execute([
            "id" => $pescadoId,
            "nivel" => $nivel
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function salvarJogador(
        int $pescadorId,
        int $pescadoId,
        array $jogador,
        int $nivel,
        int $exp
    ): void {
        $stmt = $this->pdo->prepare("
            UPDATE public.pescador_pescado
            SET
                hp_atual = :hp,
                status = :status,
                status_turnos = :turnos,
                nivel = :nivel,
                exp = :exp
            WHERE pescador_id = :jogador
              AND pescado_id = :pescado
        ");

        $stmt->execute([
            "hp" => (int) $jogador["hp"],
            "status" => $jogador["status"] ?? "normal",
            "turnos" => (int) ($jogador["status_turnos"] ?? 0),
            "nivel" => $nivel,
            "exp" => $exp,
            "jogador" => $pescadorId,
            "pescado" => $pescadoId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new JogoException("Não foi possível salvar o Pescado.");
        }
    }
}
