<?php
require 'config/db.php';
session_start();

$titulo = "Editar Laboratório";

// Verifica se foi passado o ID via GET
$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['erro'] = "ID do laboratório não informado.";
    header("Location: laboratorios.php");
    exit;
}

// Busca o laboratório
$stmt = $pdo->prepare("SELECT * FROM laboratorios WHERE LAB_ID = ? AND LAB_EXCLUIDO = 0");
$stmt->execute([$id]);
$laboratorio = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não encontrou
if (!$laboratorio) {
    $_SESSION['erro'] = "Laboratório não encontrado ou removido.";
    header("Location: laboratorios.php");
    exit;
}

// Se veio um POST (salvar edição)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');

    if (!empty($nome)) {
        try {
            $update = $pdo->prepare("UPDATE laboratorios SET LAB_NOME = ? WHERE LAB_ID = ?");
            $update->execute([$nome, $id]);
            $_SESSION['mensagem'] = "Laboratório atualizado com sucesso!";
            header("Location: laboratorios.php");
            exit;
        } catch (PDOException $e) {
            $erro = "Erro ao atualizar laboratório: " . $e->getMessage();
        }
    } else {
        $erro = "O nome do laboratório é obrigatório.";
    }
}

ob_start();
?>

<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i><?= $titulo ?>
                    </h5>
                </div>
                
                <div class="card-body">
                    <?php if (isset($erro)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= $erro ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['erro'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= $_SESSION['erro']; unset($_SESSION['erro']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="formEditarLaboratorio" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do Laboratório:</label>
                            <input type="text" id="nome" name="nome" class="form-control" 
                                   value="<?= htmlspecialchars($laboratorio['LAB_NOME']) ?>" 
                                   required autocomplete="off" maxlength="100">
                            <div class="invalid-feedback">
                                Por favor, informe o nome do laboratório.
                            </div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between gap-2 mt-4">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Salvar
                            </button>
                            <a href="laboratorios.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                            <a href="laboratorios.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Voltar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validação de formulário client-side
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formEditarLaboratorio');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
    
    // Esconder alertas após 5 segundos
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>

<?php
$conteudo = ob_get_clean();
include 'includes/layout.php';
?>