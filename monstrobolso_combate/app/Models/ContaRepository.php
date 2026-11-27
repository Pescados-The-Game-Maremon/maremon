<?php
namespace Maremon\Models;
use PDO;
use PDOStatement;

final class ContaRepository {
    public function __construct(private PDO $pdo) {}

    public function buscarAutenticado(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
    SELECT id, nome, usuario
    FROM public.pescador
    WHERE id = :id
      AND usuario IS NOT NULL
      AND senha_hash IS NOT NULL
");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function cadastrar(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        INSERT INTO public.pescador
            (nome, usuario, senha_hash, dinheiro)
        VALUES
            (:nome, :usuario, :senha_hash, 0)
        RETURNING id
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function buscarPorUsuario(array $parametros): PDOStatement {
        $stmt = $this->pdo->prepare("
        SELECT id, nome, usuario, senha_hash
        FROM public.pescador
        WHERE LOWER(usuario) = LOWER(:usuario)
        LIMIT 1
    ");
        $stmt->execute($parametros);
        return $stmt;
    }

    public function possuiPescado(int $pescadorId): bool {
        $stmt = $this->pdo->prepare("SELECT EXISTS (SELECT 1 FROM public.pescador_pescado WHERE pescador_id = :id)");
        $stmt->execute(["id" => $pescadorId]);
        return in_array($stmt->fetchColumn(), [true, "t", "1", 1], true);
    }
}
