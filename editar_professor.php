<?php
require 'config/db.php';
session_start();

// Verificação de permissões
if (!isset($_SESSION['professor_id'])) {
    $_SESSION['erro'] = "Acesso negado. Faça login para continuar.";
    header("Location: login.php");
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['erro'] = "ID do professor não informado ou inválido.";
    header("Location: professores.php");
    exit();
}

// Obter dados do professor
$stmt = $pdo->prepare("SELECT * FROM professores WHERE PRO_ID = ? AND PRO_EXCLUIDO = 0");
$stmt->execute([$id]);
$professor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$professor) {
    $_SESSION['erro'] = "Professor não encontrado ou removido.";
    header("Location: professores.php");
    exit();
}

// Verificar se o usuário atual tem permissão para editar
$isAdmin = $_SESSION['is_admin'] ?? 0;
$isOwnProfile = ($_SESSION['professor_id'] == $id);

if (!$isAdmin && !$isOwnProfile) {
    $_SESSION['erro'] = "Você não tem permissão para editar este perfil.";
    header("Location: professores.php");
    exit();
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $sobrenome = filter_input(INPUT_POST, 'sobrenome', FILTER_SANITIZE_STRING);
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
    $celular = preg_replace('/[^0-9]/', '', $_POST['celular']);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $novaSenha = $_POST['nova_senha'] ?? null;
    $confirmarSenha = $_POST['confirmar_senha'] ?? null;
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

    // Validação de senha
    if (!empty($novaSenha) || !empty($confirmarSenha)) {
        if (!$isAdmin && !$isOwnProfile) {
            $erros[] = "Apenas administradores podem alterar senhas de outros usuários.";
        } elseif ($novaSenha !== $confirmarSenha) {
            $erros[] = "As senhas não coincidem.";
        } elseif (strlen($novaSenha) < 6) {
            $erros[] = "A senha deve ter pelo menos 6 caracteres.";
        }
    }

    if (empty($erros)) {
        try {
            $pdo->beginTransaction();
            
            // Preparar query básica
            $query = "UPDATE professores SET 
                PRO_NOME = ?, PRO_SOBRENOME = ?, PRO_FONE = ?, PRO_CELULAR = ?, PRO_EMAIL = ?";
            $params = [$nome, $sobrenome, $telefone, $celular, $email];
            
            // Adicionar senha se fornecida e autorizada
            if (!empty($novaSenha) && ($isAdmin || $isOwnProfile)) {
                $query .= ", PRO_SENHA = ?";
                $params[] = password_hash($novaSenha, PASSWORD_DEFAULT);
            }
            
            // Adicionar is_admin se for admin
            if ($isAdmin) {
                $query .= ", PRO_IS_ADM = ?";
                $params[] = $is_admin;
            }
            
            $query .= " WHERE PRO_ID = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            $pdo->commit();
            
            $_SESSION['sucesso'] = "Professor atualizado com sucesso!";
            header("Location: professores.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['erro'] = "Erro ao atualizar professor: " . $e->getMessage();
            header("Location: editar_professor.php?id=$id");
            exit();
        }
    } else {
        $_SESSION['erro'] = implode("<br>", $erros);
        header("Location: editar_professor.php?id=$id");
        exit();
    }
}

// Recuperar mensagens da sessão
$erro = $_SESSION['erro'] ?? '';
$sucesso = $_SESSION['sucesso'] ?? '';
unset($_SESSION['erro'], $_SESSION['sucesso']);

// Função para formatar telefone para exibição
function formatarTelefone($numero) {
    $numero = preg_replace('/[^0-9]/', '', $numero);
    if (strlen($numero) === 10) {
        return '(' . substr($numero, 0, 2) . ') ' . substr($numero, 2, 4) . '-' . substr($numero, 6);
    } elseif (strlen($numero) === 11) {
        return '(' . substr($numero, 0, 2) . ') ' . substr($numero, 2, 5) . '-' . substr($numero, 7);
    }
    return $numero;
}

ob_start();
?>

<div class="container-fluid px-4 py-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-user-edit me-2"></i>Editar Professor
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

            <form method="POST" id="formEditar">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome" class="form-label">Nome*</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?= htmlspecialchars($professor['PRO_NOME']) ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="sobrenome" class="form-label">Sobrenome*</label>
                        <input type="text" class="form-control" id="sobrenome" name="sobrenome" 
                               value="<?= htmlspecialchars($professor['PRO_SOBRENOME']) ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="telefone" name="telefone" 
                               value="<?= formatarTelefone($professor['PRO_FONE']) ?>"
                               placeholder="(00) 0000-0000">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="celular" class="form-label">Celular*</label>
                        <input type="text" class="form-control" id="celular" name="celular" required
                               value="<?= formatarTelefone($professor['PRO_CELULAR']) ?>"
                               placeholder="(00) 00000-0000">
                    </div>
                    
                    <div class="col-12">
                        <label for="email" class="form-label">E-mail*</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($professor['PRO_EMAIL']) ?>" required>
                    </div>
                    
                    <?php if ($isAdmin || $isOwnProfile): ?>
                        <div class="col-md-6">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha">
                            <small class="text-muted">Deixe em branco para manter a senha atual</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha">
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($isAdmin): ?>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin" 
                                    <?= $professor['PRO_IS_ADM'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_admin">
                                    <i class="fas fa-shield-alt me-1"></i> Professor Administrador
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Salvar Alterações
                            </button>
                            <a href="professores.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
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
    const form = document.getElementById('formEditar');
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
            const novaSenha = document.getElementById('nova_senha');
            const confirmarSenha = document.getElementById('confirmar_senha');
            
            if (novaSenha && confirmarSenha && novaSenha.value !== confirmarSenha.value) {
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