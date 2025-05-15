<?php
require 'config/db.php';
session_start();

// Verificação de permissões
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    $_SESSION['erro'] = "Acesso negado. Você não tem permissão para acessar esta página.";
    header("Location: professores.php");
    exit();
}

$titulo = "Cadastro de Professor";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitização de inputs
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $sobrenome = filter_input(INPUT_POST, 'sobrenome', FILTER_SANITIZE_STRING);
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
    $celular = preg_replace('/[^0-9]/', '', $_POST['celular']);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    $confirma = $_POST['confirma'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // Validações
    $erros = [];

    if (empty($nome) || strlen($nome) > 100) {
        $erros[] = "Nome inválido.";
    }

    if (empty($sobrenome) || strlen($sobrenome) > 100) {
        $erros[] = "Sobrenome inválido.";
    }

    // Validação do telefone (10 dígitos)
    if (!empty($telefone) && strlen($telefone) != 10) {
        $erros[] = "Telefone deve conter 10 dígitos.";
    }

    // Validação do celular (11 dígitos)
    if (empty($celular) || strlen($celular) != 11) {
        $erros[] = "Celular deve conter 11 dígitos.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "E-mail inválido.";
    }

    if (strlen($senha) < 6) {
        $erros[] = "A senha deve ter pelo menos 6 caracteres.";
    }

    if ($senha !== $confirma) {
        $erros[] = "As senhas não coincidem.";
    }

    // Verificar se email já existe
    $stmt = $pdo->prepare("SELECT PRO_ID FROM professores WHERE PRO_EMAIL = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $erros[] = "Este e-mail já está cadastrado.";
    }

    if (empty($erros)) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO professores 
                (PRO_NOME, PRO_SOBRENOME, PRO_FONE, PRO_CELULAR, PRO_EMAIL, PRO_SENHA, PRO_IS_ADM, PRO_EXCLUIDO) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
                
            $stmt->execute([
                $nome,
                $sobrenome,
                $telefone,
                $celular,
                $email,
                password_hash($senha, PASSWORD_DEFAULT),
                $is_admin
            ]);
            
            $pdo->commit();
            $_SESSION['sucesso'] = "Professor cadastrado com sucesso!";
            header("Location: professores.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['erro'] = "Erro ao cadastrar professor: " . $e->getMessage();
            header("Location: cadastro_professor.php");
            exit();
        }
    } else {
        $_SESSION['erro'] = implode("<br>", $erros);
        header("Location: cadastro_professor.php");
        exit();
    }
}

// Recuperar mensagens da sessão
$erro = $_SESSION['erro'] ?? '';
$sucesso = $_SESSION['sucesso'] ?? '';
unset($_SESSION['erro'], $_SESSION['sucesso']);

ob_start();
?>

<div class="container-fluid px-4 py-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-chalkboard-teacher me-2"></i><?= $titulo ?>
            </h5>
        </div>
        
        <div class="card-body">
            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $erro ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($sucesso)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= $sucesso ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="formCadastro">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome" class="form-label">Nome*</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="sobrenome" class="form-label">Sobrenome*</label>
                        <input type="text" class="form-control" id="sobrenome" name="sobrenome" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="telefone" name="telefone" 
                               placeholder="(00) 0000-0000">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="celular" class="form-label">Celular*</label>
                        <input type="text" class="form-control" id="celular" name="celular" required
                               placeholder="(00) 00000-0000">
                    </div>
                    
                    <div class="col-12">
                        <label for="email" class="form-label">E-mail*</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="senha" class="form-label">Senha*</label>
                        <input type="password" class="form-control" id="senha" name="senha" required minlength="6">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="confirma" class="form-label">Confirme a Senha*</label>
                        <input type="password" class="form-control" id="confirma" name="confirma" required>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin">
                            <label class="form-check-label" for="is_admin">
                                <i class="fas fa-shield-alt me-1"></i> Professor Administrador
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Cadastrar
                            </button>
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-eraser me-2"></i>Limpar
                            </button>
                            <a href="professores.php" class="btn btn-outline-dark">
                                <i class="fas fa-arrow-left me-2"></i>Voltar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscaras para telefone e celular (apenas visual)
    const telefone = document.getElementById('telefone');
    const celular = document.getElementById('celular');
    
    if (telefone) {
        telefone.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.substring(0, 10);
                if (value.length <= 2) {
                    value = `(${value}`;
                } else if (value.length <= 6) {
                    value = `(${value.substring(0,2)}) ${value.substring(2)}`;
                } else {
                    value = `(${value.substring(0,2)}) ${value.substring(2,6)}-${value.substring(6)}`;
                }
            }
            e.target.value = value;
        });
    }
    
    if (celular) {
        celular.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.substring(0, 11);
                if (value.length <= 2) {
                    value = `(${value}`;
                } else if (value.length <= 7) {
                    value = `(${value.substring(0,2)}) ${value.substring(2)}`;
                } else {
                    value = `(${value.substring(0,2)}) ${value.substring(2,7)}-${value.substring(7)}`;
                }
            }
            e.target.value = value;
        });
    }

    // Form submission
    const form = document.getElementById('formCadastro');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Remover máscara antes de enviar
            if (telefone) {
                telefone.value = telefone.value.replace(/\D/g, '');
            }
            if (celular) {
                celular.value = celular.value.replace(/\D/g, '');
            }
            
            // Validar senhas
            const senha = document.getElementById('senha');
            const confirma = document.getElementById('confirma');
            
            if (senha.value !== confirma.value) {
                e.preventDefault();
                alert('As senhas não coincidem!');
                return false;
            }
            
            return true;
        });
    }
    
    // Esconder alertas após 5 segundos
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            new bootstrap.Alert(alert).close();
        });
    }, 5000);
});
</script>

<?php
$conteudo = ob_get_clean();
include 'includes/layout.php';