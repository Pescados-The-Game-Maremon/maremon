<?php
define("MAREMON_ROOT", dirname(__DIR__));
                                                  
spl_autoload_register(static function (string $classe): void {
    $prefixo = 'Maremon\\';
    if (!str_starts_with($classe, $prefixo)) return;
    $relativo = substr($classe, strlen($prefixo));
    $arquivo = __DIR__ . '/' . str_replace('\\', '/', $relativo) . '.php';
    if (is_file($arquivo)) require_once $arquivo;
});
