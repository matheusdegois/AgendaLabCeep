<?php
require 'config/db.php';
session_start();

$titulo = "Horários de Aulas por Turno";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Atualizar horários
    if (isset($_POST['horario_inicial'], $_POST['horario_final'], $_POST['aula_id'])) {
        $horario_inicio = $_POST['horario_inicial'];
        $horario_fim = $_POST['horario_final'];
        $aula_id = $_POST['aula_id'];

        // Verificar se os horários são válidos
        if (strtotime($horario_inicio) >= strtotime($horario_fim)) {
            $_SESSION['erro'] = "O horário de início deve ser anterior ao horário de término.";
        } else {
            // Atualizar no banco de dados
            $stmt = $pdo->prepare("UPDATE aulas SET AUL_HORARIO_INICIO = ?, AUL_HORARIO_FIM = ? WHERE AUL_ID = ?");
            $stmt->execute([$horario_inicio, $horario_fim, $aula_id]);
            $_SESSION['sucesso'] = "Horários atualizados com sucesso!";
        }
        header("Location: horarios.php" . (isset($_GET['turno_id']) ? '?turno_id=' . $_GET['turno_id'] : ''));
        exit();
    }
}

// Buscar os turnos disponíveis
$stmt_turnos = $pdo->query("SELECT * FROM turnos ORDER BY TUR_NOME");
$turnos = $stmt_turnos->fetchAll();

// Buscar as aulas do turno selecionado, se houver
$as_aulas = [];
if (isset($_GET['turno_id'])) {
    $turno_id = $_GET['turno_id'];
    $stmt_aulas = $pdo->prepare("SELECT * FROM aulas WHERE AUL_TUR_ID = ? ORDER BY AUL_AULA_NUMERO");
    $stmt_aulas->execute([$turno_id]);
    $as_aulas = $stmt_aulas->fetchAll();
}

ob_start();
?>

<div class="container-fluid px-4 py-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-clock me-2"></i><?= $titulo ?>
            </h5>
        </div>
        
        <div class="card-body">
            <!-- Exibir mensagem de erro ou sucesso -->
            <?php if (isset($_SESSION['erro'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['erro']; unset($_SESSION['erro']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['sucesso'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>

            <!-- Filtro de Turno -->
            <form method="GET" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="turno_id" class="form-label">Selecione o Turno:</label>
                        <select name="turno_id" id="turno_id" class="form-select" style="min-height: 44px;" required>
                            <option value="">Escolha um Turno</option>
                            <?php foreach ($turnos as $turno): ?>
                                <option value="<?= $turno['TUR_ID'] ?>" <?= isset($_GET['turno_id']) && $_GET['turno_id'] == $turno['TUR_ID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($turno['TUR_NOME']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100" style="min-height: 44px;">
                            <i class="fas fa-filter me-2"></i>Filtrar
                        </button>
                    </div>
                </div>
            </form>

            <!-- Listagem de Aulas do Turno -->
            <?php if (!empty($as_aulas)): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-list-check me-2"></i>Aulas do Turno
                    </h5>
                    <small class="text-muted">Total: <?= count($as_aulas) ?> aulas</small>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th width="20%">Aula</th>
                                <th width="30%">Horário Inicial</th>
                                <th width="30%">Horário Final</th>
                                <th width="20%" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($as_aulas as $aula): ?>
                                <tr>
                                    <td><?= $aula['AUL_AULA_NUMERO'] ?>ª Aula</td>
                                    <td><?= substr($aula['AUL_HORARIO_INICIO'], 0, 5) ?></td>
                                    <td><?= substr($aula['AUL_HORARIO_FIM'], 0, 5) ?></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-warning btn-sm" 
                                                data-bs-toggle="modal" data-bs-target="#editarModal" 
                                                data-aula-id="<?= $aula['AUL_ID'] ?>" 
                                                data-horario-inicial="<?= $aula['AUL_HORARIO_INICIO'] ?>" 
                                                data-horario-final="<?= $aula['AUL_HORARIO_FIM'] ?>"
                                                style="min-width: 80px;">
                                            <i class="fas fa-edit me-1"></i>Editar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif (isset($_GET['turno_id'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Nenhuma aula encontrada para o turno selecionado.
                </div>
            <?php else: ?>
                <div class="alert alert-secondary">
                    <i class="fas fa-arrow-up me-2"></i>Selecione um turno para visualizar os horários.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Edição -->
<div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="editarModalLabel">
                    <i class="fas fa-clock me-2"></i>Editar Horários
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="aula_id" id="aula_id">
                    <div class="mb-3">
                        <label for="horario_inicial" class="form-label">Horário de Início:</label>
                        <input type="time" name="horario_inicial" id="horario_inicial" class="form-control form-control-lg" required>
                    </div>
                    <div class="mb-3">
                        <label for="horario_final" class="form-label">Horário de Término:</label>
                        <input type="time" name="horario_final" id="horario_final" class="form-control form-control-lg" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2"></i>Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Preencher os campos do modal com os dados da aula
document.addEventListener('DOMContentLoaded', function() {
    var editarModal = document.getElementById('editarModal');
    if (editarModal) {
        editarModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var modal = this;
            
            modal.querySelector('#aula_id').value = button.getAttribute('data-aula-id');
            modal.querySelector('#horario_inicial').value = button.getAttribute('data-horario-inicial').substr(0, 5);
            modal.querySelector('#horario_final').value = button.getAttribute('data-horario-final').substr(0, 5);
        });
    }
    
    // Esconder alertas após 5 segundos
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>

<?php
$conteudo = ob_get_clean();
include 'includes/layout.php';