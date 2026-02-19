<?php
// Simular entorno para probar messenger_requests.php vía CLI
$_GET['page'] = 'messenger_requests';
$_POST['action'] = 'create_ot';
$_POST['request_id'] = '1';

// Mock de sesión
session_start();
$_SESSION['user_id'] = 1;

require_once 'config.php';
// Mapear la inclusión de la página
$_SERVER['REQUEST_METHOD'] = 'POST';
include 'pages/messenger_requests.php';
