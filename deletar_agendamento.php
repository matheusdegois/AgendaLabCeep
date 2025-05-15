<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['professor_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("update agendamentos set excluido = 1 WHERE id = ? AND id_professor = ?");
$stmt->execute([$id, $_SESSION['professor_id']]);

header("Location: dashboard.php");
?>
