<?php
// Cierra la sesión. Solo funciona por POST (el botón "Salir" de cada pantalla),
// así ninguna otra página puede sacar al usuario del panel con un simple enlace.
require __DIR__ . '/auth.php';

cabecerasSeguras();
iniciarSesionSegura();
verificarOrigen();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cerrarSesionActual();
    header('Location: login.php?salio=1');
    exit;
}

session_write_close();
header('Location: index.php');
