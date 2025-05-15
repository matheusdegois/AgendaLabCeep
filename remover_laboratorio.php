<?php
require 'config/db.php';
session_start();

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("UPDATE laboratorios SET LAB_EXCLUIDO = 1 WHERE LAB_ID = ?");
    $stmt->execute([$id]);
    $_SESSION['mensagem'] = "Laboratório excluído com sucesso.";
}

header("Location: laboratorios.php");
exit;
