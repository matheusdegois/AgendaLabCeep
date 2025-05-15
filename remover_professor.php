<?php
require 'config/db.php';
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    $_SESSION['erro'] = "Acesso negado. Você não tem permissão para a função solicitada.";
    header("Location: professores.php");
    exit();
}

$id = $_GET['id'] ?? null;
$usuario = $_SESSION['usuario'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("UPDATE professores SET PRO_EXCLUIDO = 1, PRO_PRO_EXCLUIDO = ?, PRO_DATA_EXCLUIDO = NOW() WHERE PRO_ID = ?");
    $stmt->execute([$usuario, $id]);
    $_SESSION['mensagem'] = "Professor excluído com sucesso.";
}

header("Location: professores.php");
exit;
