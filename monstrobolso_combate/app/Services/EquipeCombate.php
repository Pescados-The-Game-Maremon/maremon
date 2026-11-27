<?php

namespace Maremon\Services;

use PDO;

/** Estado dos integrantes limitado à sessão de uma batalha. */
final class EquipeCombate
{
    public static function sincronizar(array &$combate): void
    {
        $ativo = $combate["jogador"] ?? null;

        if (is_array($ativo) && isset($ativo["id"])) {
            $combate["equipe"][(int) $ativo["id"]] = $ativo;
        }
    }

    public static function vivos(array $combate): array
    {
        return array_values(array_filter(
            $combate["equipe"] ?? [],
            static fn(array $p): bool => (int) ($p["hp"] ?? 0) > 0
        ));
    }

    public static function exigeTroca(array $combate): bool
    {
        return (int) ($combate["jogador"]["hp"] ?? 0) <= 0
            && count(self::vivos($combate)) > 0
            && (int) ($combate["inimigo"]["hp"] ?? 0) > 0;
    }

    public static function resultado(array $combate): string
    {
        if ((int) ($combate["inimigo"]["hp"] ?? 0) <= 0) {
            return "vitoria";
        }

        return count(self::vivos($combate)) === 0 ? "derrota" : "continuar";
    }

    public static function membros(array $combate): array
    {
        self::sincronizar($combate);

        return array_values(array_map(
            static fn(array $p): array => [
                "id" => (int) $p["id"],
                "nome" => (string) $p["nome"],
                "sprite" => $p["sprite"] ?? null,
                "tipo" => (string) $p["tipo"],
                "nivel" => (int) $p["nivel"],
                "hp" => (int) $p["hp"],
                "hp_max" => (int) $p["hp_max"],
                "status" => $p["status"] ?? "normal",
                "ativo" => (int) $p["id"] === (int) ($combate["jogador"]["id"] ?? 0)
            ],
            $combate["equipe"] ?? []
        ));
    }

    public static function salvar(PDO $pdo, int $pescadorId, array $combate): void
    {
        self::sincronizar($combate);

        $stmt = $pdo->prepare("
            UPDATE public.pescador_pescado
            SET hp_atual = :hp,
                status = :status,
                status_turnos = :turnos
            WHERE pescador_id = :pescador
              AND pescado_id = :pescado
        ");

        foreach ($combate["equipe"] as $pescado) {
            $stmt->execute([
                "hp" => (int) $pescado["hp"],
                "status" => (string) ($pescado["status"] ?? "normal"),
                "turnos" => (int) ($pescado["status_turnos"] ?? 0),
                "pescador" => $pescadorId,
                "pescado" => (int) $pescado["id"]
            ]);
        }
    }
}
