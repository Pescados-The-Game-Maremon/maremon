<?php
namespace Maremon\Models;
use PDO;
use InvalidArgumentException;
use RuntimeException;

final class LogCombateRepository {
    public static function atualizarLogCombate(
        PDO $pdo,
        int $logId,
        int $pescadorId,
        string $resultado,
        int $turnos,
        int $dinheiroGanho = 0
    ): void {

        $resultadosPermitidos = [
            "ativo",
            "vitoria",
            "derrota",
            "fuga",
            "captura"
        ];

        if (!in_array($resultado, $resultadosPermitidos, true)) {
            throw new InvalidArgumentException(
                "Resultado de combate inválido."
            );
        }

        $stmt = $pdo->prepare("
            UPDATE public.combates_log
            SET
                resultado = :resultado,
                turnos = :turnos,
                dinheiro_ganho = :dinheiro,
                finalizado_em = CASE
                    WHEN :encerrado = 1
                    THEN CURRENT_TIMESTAMP
                    ELSE NULL
                END
            WHERE id = :log_id
              AND pescador_id = :pescador_id
              AND resultado = 'ativo'
        ");

        $stmt->execute([
            "resultado" => $resultado,
            "turnos" => $turnos,
            "dinheiro" => $dinheiroGanho,
            "encerrado" => $resultado === "ativo" ? 0 : 1,
            "log_id" => $logId,
            "pescador_id" => $pescadorId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                "Log de combate não encontrado ou encerrado."
            );
        }
    }
}
