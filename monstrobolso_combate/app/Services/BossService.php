<?php

namespace Maremon\Services;

use PDO;
use RuntimeException;

/** A progressão é individual por conta; os oito chefes são cadastrados no SQL. */
final class BossService
{
    public static function iscas(PDO $pdo, int $pescadorId): array
    {
        $stmt = (new \Maremon\Models\BossRepository($pdo))->listarIscas([
            "pescador" => $pescadorId,
            "pescador_anterior" => $pescadorId
        ]);

        $iscas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($iscas as &$isca) {
            $isca["ordem"] = (int) $isca["ordem"];
            $isca["derrotado"] = self::pgBool($isca["derrotado"]);
            $isca["liberado"] = self::pgBool($isca["liberado"]);
        }
        unset($isca);

        return $iscas;
    }

    private static function pgBool(mixed $valor): bool
    {
        return in_array($valor, [true, 1, "1", "t", "true"], true);
    }

    public static function iscaAtual(PDO $pdo, int $pescadorId): int
    {
        $stmt = (new \Maremon\Models\BossRepository($pdo))->consultarIsca(["id" => $pescadorId]);
        $ordem = (int) $stmt->fetchColumn();
        foreach (self::iscas($pdo, $pescadorId) as $isca) {
            if ($isca["ordem"] === $ordem && $isca["liberado"]) {
                return $ordem;
            }
        }

        return 1;
    }

    public static function escolherIsca(PDO $pdo, int $pescadorId, int $ordem): void
    {
        $valida = false;
        foreach (self::iscas($pdo, $pescadorId) as $isca) {
            if ($isca["ordem"] === $ordem && $isca["liberado"]) {
                $valida = true;
                break;
            }
        }
        if (!$valida) {
            throw new RuntimeException("Essa isca ainda está bloqueada.");
        }

        $stmt = (new \Maremon\Models\BossRepository($pdo))->selecionarIsca(["ordem" => $ordem, "pescador" => $pescadorId]);
    }

    /** Deve ser chamada dentro da transação da vitória, antes do COMMIT. */
    public static function registrarVitoria(PDO $pdo, int $pescadorId, array $combate): ?array
    {
        $ordem = (int) ($combate["boss_ordem"] ?? 0);
        if ($ordem < 1) {
            return null;
        }

        $stmt = (new \Maremon\Models\BossRepository($pdo))->registrarVitoria(["pescador" => $pescadorId, "ordem" => $ordem]);
        $primeiraVitoria = $stmt->rowCount() === 1;

        $stmt = (new \Maremon\Models\BossRepository($pdo))->buscarChefe(["ordem" => $ordem]);
        $boss = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$boss) {
            throw new RuntimeException("Chefe não cadastrado.");
        }

        return [
            "nome" => $boss["nome"],
            "primeira_vitoria" => $primeiraVitoria,
            "dna_nome" => $boss["dna_nome"],
            "proxima_isca" => $ordem < 8 ? $ordem + 1 : null
        ];
    }
}
