<?php
session_start();
require 'config/db.php';

// Redireciona se já estiver logado
if (isset($_SESSION['professor_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha']; // Não aplicar hash ainda

    $stmt = $pdo->prepare("
        SELECT * FROM professores 
        WHERE PRO_EMAIL = ? AND ifnull(PRO_EXCLUIDO,0) = 0
    ");
    $stmt->execute([$email]);
    $professor = $stmt->fetch();

    // Verificar a senha (compatível com md5 ou password_hash)
    if ($professor) {
        if (md5($senha) === $professor['PRO_SENHA'] || 
            password_verify($senha, $professor['PRO_SENHA'])) {
            
            $_SESSION['professor_id'] = $professor['PRO_ID'];
            $_SESSION['nome'] = $professor['PRO_NOME'];
            $_SESSION['sobrenome'] = $professor['PRO_SOBRENOME'];
            $_SESSION['email'] = $professor['PRO_EMAIL'];
            $_SESSION['is_admin'] = $professor['PRO_IS_ADM'];
            
            // Atualizar para password_hash se ainda estiver usando md5
            if (strlen($professor['PRO_SENHA']) === 32) {
                $stmt = $pdo->prepare("UPDATE professores SET PRO_SENHA = ? WHERE PRO_ID = ?");
                $stmt->execute([password_hash($senha, PASSWORD_DEFAULT), $professor['PRO_ID']]);
            }
            
            header("Location: dashboard.php");
            exit;
        }
    }
    
    // Mensagem genérica para evitar enumeração de usuários
    $_SESSION['login_erro'] = "Credenciais inválidas ou conta não encontrada.";
    header("Location: login.php");
    exit;
}

// Verificar mensagem de erro da sessão
$erro = isset($_SESSION['login_erro']) ? $_SESSION['login_erro'] : '';
unset($_SESSION['login_erro']);

ob_start();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AgendaLab</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos customizados -->
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
        }
        .login-header {
            background: #343a40;
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            height: 50px;
            border-radius: 8px;
            padding-left: 15px;
        }
        .btn-login {
            height: 50px;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            right: 15px;
            top: 15px;
            color: #6c757d;
        }
    </style>
</head>
<body class="login-container">
    <div class="container d-flex align-items-center justify-content-center">
        <div class="login-card" style="width: 100%; max-width: 450px;">
            <div class="login-header">
                <h3><i class="fas fa-flask me-2"></i> AgendaLab</h3>
                <p class="mb-0">Sistema de Reservas de Laboratórios</p>
            </div>
            
            <div class="login-body bg-white">
                <h4 class="text-center mb-4">Acesso do Professor</h4>
                
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($erro) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" novalidate>
                    <div class="mb-3 input-icon">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" name="email" id="email" class="form-control" 
                               placeholder="Digite seu e-mail" required autofocus>
                        <i class="fas fa-envelope"></i>
                    </div>
                    
                    <div class="mb-3 input-icon">
                        <label for="senha" class="form-label">Senha</label>
                        <input type="password" name="senha" id="senha" class="form-control" 
                               placeholder="Digite sua senha" required>
                        <i class="fas fa-lock"></i>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Entrar
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="card-footer text-center bg-light">
                <small class="text-muted"><i class="far fa-copyright me-1"></i> pdrmottadev <?= date('Y') ?></small>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Esconder alerta após 5 segundos
    setTimeout(function() {
        var alert = document.querySelector('.alert');
        if (alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
    </script>
</body>
</html>

<?php
$conteudo = ob_get_clean();
// Como este é um arquivo standalone, não incluímos o layout.php
echo $conteudo;
?>