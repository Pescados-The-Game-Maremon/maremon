<?php
require_once __DIR__ . "/../app/bootstrap.php";
(new \Maremon\Controllers\Http\LogoutAction())->executar();
