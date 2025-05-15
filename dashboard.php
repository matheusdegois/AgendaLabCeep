<?php
session_start();
if (!isset($_SESSION['professor_id'])) {
    header("Location: login.php");
    exit;
}

$titulo = "Dashboard";
ob_start();
?>

<div class="container-fluid px-4 py-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </h5>
        </div>
        
        <div class="card-body">
            <!-- Mensagem de Boas-Vindas -->
            <div class="alert alert-info mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-user-circle fa-2x me-3"></i>
                    <div>
                        <h4 class="alert-heading mb-1">Bem-vindo(a), <?= htmlspecialchars($_SESSION['nome']) ?>!</h4>
                        <p class="mb-0">Sistema de gerenciamento de laboratórios acadêmicos.</p>
                    </div>
                </div>
            </div>

            <!-- Ações Principais -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-flask me-2"></i>Laboratórios
                            </h6>
                        </div>
                        <div class="card-body">
                            <a href="laboratorios.php" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-list me-2"></i>Ver Laboratórios
                            </a>
                            <a href="cadastro_laboratorio.php" class="btn btn-success w-100">
                                <i class="fas fa-plus me-2"></i>Cadastrar Novo
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>Reservas
                            </h6>
                        </div>
                        <div class="card-body">
                            <a href="reservas.php" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-list me-2"></i>Minhas Reservas
                            </a>
                            <a href="nova_reserva.php" class="btn btn-success w-100">
                                <i class="fas fa-plus me-2"></i>Nova Reserva
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acesso Rápido -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Acesso Rápido
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="professores.php" class="btn btn-outline-primary">
                            <i class="fas fa-users me-2"></i>Professores
                        </a>
                        <a href="meu_perfil.php" class="btn btn-outline-secondary">
                            <i class="fas fa-user-cog me-2"></i>Meu Perfil
                        </a>
                        <a href="relatorios.php" class="btn btn-outline-info">
                            <i class="fas fa-chart-bar me-2"></i>Relatórios
                        </a>
                        <a href="ajuda.php" class="btn btn-outline-dark">
                            <i class="fas fa-question-circle me-2"></i>Ajuda
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$conteudo = ob_get_clean();
include 'includes/layout.php';