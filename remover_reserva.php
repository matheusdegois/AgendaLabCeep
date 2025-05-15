<?php
session_start();
require 'config/db.php';

// Verificar se o ID foi passado
if (!isset($_GET['id'])) {
    $_SESSION['erro'] = "Reserva não especificada.";
    header("Location: reservas.php");
    exit;
}

$id = (int)$_GET['id'];

// Buscar reserva para confirmar o nome
$stmt = $pdo->prepare("SELECT a.*, l.LAB_NOME, p.PRO_NOME, p.PRO_SOBRENOME 
                      FROM agendamentos a
                      JOIN laboratorios l ON a.AGE_LAB_ID = l.LAB_ID
                      JOIN professores p ON a.AGE_PRO_ID = p.PRO_ID
                      WHERE a.AGE_ID = ?");
$stmt->execute([$id]);
$reserva = $stmt->fetch();

if (!$reserva) {
    $_SESSION['erro'] = "Reserva não encontrada.";
    header("Location: reservas.php");
    exit;
}

// Processar exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE agendamentos SET 
                              AGE_EXCLUIDO = 1, AGE_DATA_EXCLUIDO = NOW() 
                              WHERE AGE_ID = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        $_SESSION['mensagem'] = "Reserva removida com sucesso!";
        header("Location: reservas.php");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['erro'] = "Erro ao remover reserva: " . $e->getMessage();
        header("Location: reservas.php");
        exit;
    }
}

// Se não for POST, mostrar página de confirmação
$titulo = "Remover Reserva";
ob_start();
?>

<div class="container-fluid px-4 py-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
            <h5 class="card-title mb-0">
                <i class="fas fa-calendar-times me-2"></i><?= $titulo ?>
            </h5>
            <a href="reservas.php" class="btn btn-secondary" 
               style="min-height: 44px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-arrow-left me-2"></i><span>Voltar</span>
            </a>
        </div>
        
        <div class="card-body">
            <div class="alert alert-danger">
                <h5 class="alert-heading">
                    <i class="fas fa-exclamation-triangle me-2"></i>Atenção!
                </h5>
                <p class="mb-0">Você está prestes a remover a seguinte reserva:</p>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-flask me-2"></i><?= htmlspecialchars($reserva['LAB_NOME']) ?>
                    </h5>
                    <p class="card-text">
                        <i class="fas fa-user-tie me-2"></i><?= htmlspecialchars($reserva['PRO_NOME']) ?> <?= htmlspecialchars($reserva['PRO_SOBRENOME']) ?>
                    </p>
                    <p class="card-text">
                        <i class="fas fa-calendar-day me-2"></i><?= date('d/m/Y', strtotime($reserva['AGE_DATA'])) ?>
                    </p>
                </div>
            </div>
            
            <form method="POST">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="reservas.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="fas fa-trash-alt me-2"></i>Confirmar Remoção
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$conteudo = ob_get_clean();
include 'includes/layout.php';