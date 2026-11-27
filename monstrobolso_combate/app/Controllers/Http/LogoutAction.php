<?php
namespace Maremon\Controllers\Http;

final class LogoutAction {
    public function executar(): void {
        header("Content-Type: application/json; charset=utf-8");

        ini_set("session.use_strict_mode", "1");
        \Maremon\Core\Sessao::iniciar();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            echo json_encode([
                "sucesso" => false,
                "erro" => "Método não permitido."
            ]);
            exit;
        }

        $_SESSION = [];

        session_destroy();

        $params = session_get_cookie_params();

        setcookie(session_name(), "", [
            "expires" => time() - 3600,
            "path" => $params["path"],
            "domain" => $params["domain"],
            "secure" => $params["secure"],
            "httponly" => $params["httponly"],
            "samesite" => $params["samesite"] ?? "Lax"
        ]);

        echo json_encode([
            "sucesso" => true
        ]);
    }
}
