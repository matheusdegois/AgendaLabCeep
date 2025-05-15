<?php
require 'config/db.php';
session_start();

$titulo = "Cadastrar Laboratório";

// Processamento do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);

    if (empty($nome)) {
        $erro = "O nome do laboratório é obrigatório.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO laboratorios (LAB_NOME, LAB_EXCLUIDO) VALUES (?, 0)");
            $stmt->execute([$nome]);
            
            // Registra o sucesso e limpa o campo
            $sucesso = "Laboratório cadastrado com sucesso!";
            $nome = ""; // Limpa o campo após o sucesso
        } catch (PDOException $e) {
            $erro = "Erro ao cadastrar: " . $e->getMessage();
        }
    }
}

// Layout da página
ob_start();
?>

<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-flask me-2"></i><?= $titulo ?>
                    </h5>
                </div>
                
                <div class="card-body">
                    <?php if (isset($erro)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= $erro ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($sucesso)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?= $sucesso ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="formLaboratorio" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do Laboratório:</label>
                            <input type="text" id="nome" name="nome" class="form-control" 
                                   value="<?= isset($nome) ? htmlspecialchars($nome) : '' ?>" 
                                   required autocomplete="off" maxlength="100">
                            <div class="invalid-feedback">
                                Por favor, informe o nome do laboratório.
                            </div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between gap-2 mt-4">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Salvar
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-eraser me-2"></i>Limpar
                            </button>
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
    const form = document.getElementById('formLaboratorio');
    
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