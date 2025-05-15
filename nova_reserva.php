<?php
session_start();
require 'config/db.php';
ob_start();

// Definir timezone para America/Sao_Paulo (Brasília)
date_default_timezone_set('America/Sao_Paulo');

$titulo = "Nova Reserva";


// Função para obter o intervalo da semana atual (segunda a sexta)
function getSemanaAtual() {
    $hoje = new DateTime();
    $diaSemana = $hoje->format('N'); // 1 (segunda) a 7 (domingo)
    
    // Se for fim de semana, já mostra a próxima semana
    if ($diaSemana >= 6) {
        $hoje->modify('next monday');
    }
    
    $segunda = clone $hoje;
    $segunda->modify('monday this week');
    
    $sexta = clone $segunda;
    $sexta->modify('friday this week');
    
    return [
        'inicio' => $segunda->format('Y-m-d'),
        'fim' => $sexta->format('Y-m-d'),
        'inicio_formatado' => $segunda->format('d/m/Y'),
        'fim_formatado' => $sexta->format('d/m/Y')
    ];
}

$semanaAtual = getSemanaAtual();
$data_atual = date('Y-m-d');

// Verificar se usuário é admin
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// Buscar dados para formulário
$laboratorios = $pdo->query("SELECT * FROM laboratorios WHERE LAB_EXCLUIDO = 0 ORDER BY LAB_NOME")->fetchAll();
$turnos = $pdo->query("SELECT * FROM turnos ORDER BY TUR_NOME")->fetchAll();
$aulas = $pdo->query("SELECT * FROM aulas ORDER BY AUL_TUR_ID, AUL_AULA_NUMERO")->fetchAll();

// Buscar professores (todos se for admin, apenas o próprio se não for)
if ($is_admin) {
    $professores = $pdo->query("SELECT * FROM professores WHERE PRO_EXCLUIDO = 0 ORDER BY PRO_NOME")->fetchAll();
} else {
    // Busca o professor vinculado ao usuário logado
    $stmt_professor = $pdo->prepare("SELECT * FROM professores WHERE PRO_ID = ? AND PRO_EXCLUIDO = 0");
    $stmt_professor->execute([$_SESSION['professor_id']]); // Assumindo que professor_id está na sessão
    $professores = $stmt_professor->fetchAll();
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $laboratorio_id = $_POST['laboratorio'];
        $professor_id = $_POST['professor'];
        $data = $_POST['data'];
        $turno_id = $_POST['turno'];
        $aula_id = $_POST['aula'];

        // Validar se a data está dentro da semana atual
        if ($data < $semanaAtual['inicio'] || $data > $semanaAtual['fim']) {
            $_SESSION['erro'] = "Só é possível agendar para a semana atual (de {$semanaAtual['inicio_formatado']} a {$semanaAtual['fim_formatado']})";
            header("Location: nova_reserva.php");
            exit;
        }

        // Verificar se já existe reserva para esta combinação
        $stmt_verifica = $pdo->prepare("SELECT COUNT(*) FROM agendamentos 
                                      WHERE AGE_LAB_ID = ? 
                                      AND AGE_DATA = ? 
                                      AND AGE_TUR_ID = ? 
                                      AND AGE_AUL_ID = ? 
                                      AND ifnull(AGE_EXCLUIDO,0) = 0");
        $stmt_verifica->execute([$laboratorio_id, $data, $turno_id, $aula_id]);
        $reserva_existente = $stmt_verifica->fetchColumn();

        if ($reserva_existente > 0) {
            $_SESSION['erro'] = "Já existe uma reserva para este laboratório no dia, turno e aula selecionados!";
            header("Location: nova_reserva.php");
            exit;
        }

        // Iniciar transação
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO agendamentos 
                              (AGE_PRO_ID, AGE_LAB_ID, AGE_DATA, AGE_TUR_ID, AGE_AUL_ID) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $professor_id,
            $laboratorio_id,
            $data,
            $turno_id,
            $aula_id
        ]);
        
        $pdo->commit();
        $_SESSION['mensagem'] = "Reserva cadastrada com sucesso!";
        header("Location: reservas.php");
        exit;
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['erro'] = "Erro ao cadastrar reserva: " . $e->getMessage();
        header("Location: nova_reserva.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card {
            border-radius: 10px;
        }
        .form-control-lg, .form-select-lg {
            min-height: 45px;
            font-size: 1rem;
        }
        .btn-lg {
            padding: 0.5rem 1.5rem;
            font-size: 1.1rem;
        }
        .readonly-combobox {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container-fluid px-4 py-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-plus me-2"></i><?= $titulo ?>
                </h5>
                <a href="reservas.php" class="btn btn-secondary" 
                   style="min-height: 44px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-arrow-left me-2"></i><span>Voltar</span>
                </a>
            </div>
            
            <div class="card-body">
                <?php if (isset($_SESSION['erro'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= $_SESSION['erro']; unset($_SESSION['erro']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Você só pode agendar para a semana atual: 
                    <strong><?= $semanaAtual['inicio_formatado'] ?> a <?= $semanaAtual['fim_formatado'] ?></strong>
                </div>

                <form method="POST">
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="laboratorio" class="form-label">
                                <i class="fas fa-flask me-1"></i>Laboratório *
                            </label>
                            <select class="form-select form-control-lg" id="laboratorio" name="laboratorio" required>
                                <option value="">Selecione um laboratório</option>
                                <?php foreach ($laboratorios as $lab): ?>
                                    <option value="<?= $lab['LAB_ID'] ?>" <?= isset($_POST['laboratorio']) && $_POST['laboratorio'] == $lab['LAB_ID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($lab['LAB_NOME']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="professor" class="form-label">
                                <i class="fas fa-user-tie me-1"></i>Professor *
                            </label>
                            <?php if ($is_admin): ?>
                                <!-- Para administradores: combobox normal -->
                                <select class="form-select form-control-lg" id="professor" name="professor" required>
                                    <option value="">Selecione um professor</option>
                                    <?php foreach ($professores as $prof): ?>
                                        <option value="<?= $prof['PRO_ID'] ?>" <?= isset($_POST['professor']) && $_POST['professor'] == $prof['PRO_ID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($prof['PRO_NOME']) ?> <?= htmlspecialchars($prof['PRO_SOBRENOME']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <!-- Para não-administradores: combobox com apenas o próprio professor -->
                                <select class="form-select form-control-lg readonly-combobox" id="professor" name="professor" required disabled>
                                    <?php if (!empty($professores)): ?>
                                        <option value="<?= $professores[0]['PRO_ID'] ?>" selected>
                                            <?= htmlspecialchars($professores[0]['PRO_NOME']) ?> <?= htmlspecialchars($professores[0]['PRO_SOBRENOME']) ?>
                                        </option>
                                    <?php else: ?>
                                        <option value="<?= $_SESSION['professor_id'] ?? $_SESSION['usuario_id'] ?>" selected>
                                            Meu cadastro
                                        </option>
                                    <?php endif; ?>
                                </select>
                                <input type="hidden" name="professor" value="<?= !empty($professores) ? $professores[0]['PRO_ID'] : ($_SESSION['professor_id'] ?? $_SESSION['usuario_id']) ?>">
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="data" class="form-label">
                                <i class="fas fa-calendar-day me-1"></i>Data *
                            </label>
                            <input type="date" class="form-control form-control-lg" id="data" name="data" 
                                   min="<?= $semanaAtual['inicio'] ?>" 
                                   max="<?= $semanaAtual['fim'] ?>"
                                   value="<?= isset($_POST['data']) ? htmlspecialchars($_POST['data']) : $data_atual ?>" required>
                            <small class="text-muted">Semana atual: <?= $semanaAtual['inicio_formatado'] ?> a <?= $semanaAtual['fim_formatado'] ?></small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="turno" class="form-label">
                                <i class="fas fa-sun me-1"></i>Turno *
                            </label>
                            <select class="form-select form-control-lg" id="turno" name="turno" required>
                                <option value="">Selecione um turno</option>
                                <?php foreach ($turnos as $turno): ?>
                                    <option value="<?= $turno['TUR_ID'] ?>" <?= isset($_POST['turno']) && $_POST['turno'] == $turno['TUR_ID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($turno['TUR_NOME']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="aula" class="form-label">
                                <i class="fas fa-clock me-1"></i>Aula *
                            </label>
                            <select class="form-select form-control-lg" id="aula" name="aula" required>
                                <option value="">Selecione uma aula</option>
                                <?php foreach ($aulas as $aula): ?>
                                    <option value="<?= $aula['AUL_ID'] ?>" data-turno="<?= $aula['AUL_TUR_ID'] ?>"
                                        <?= isset($_POST['aula']) && $_POST['aula'] == $aula['AUL_ID'] ? 'selected' : '' ?>>
                                        Aula <?= $aula['AUL_AULA_NUMERO'] ?> (<?= substr($aula['AUL_HORARIO_INICIO'], 0, 5) ?> - <?= substr($aula['AUL_HORARIO_FIM'], 0, 5) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Salvar Reserva
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filtrar aulas por turno selecionado
        const turnoSelect = document.getElementById('turno');
        const aulaSelect = document.getElementById('aula');
        
        function filtrarAulas() {
            const selectedTurno = turnoSelect.value;
            const options = aulaSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                } else {
                    if (option.dataset.turno === selectedTurno || option.selected) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                }
            });
        }
        
        // Aplicar filtro quando a página carrega
        filtrarAulas();
        
        // Aplicar filtro quando o turno muda
        turnoSelect.addEventListener('change', filtrarAulas);
        
        // Atualizar os limites da data periodicamente
        function atualizarLimitesData() {
            const hoje = new Date();
            const diaSemana = hoje.getDay(); // 0 (domingo) a 6 (sábado)
            
            let segunda, sexta;
            
            // Se for fim de semana, mostrar próxima semana
            if (diaSemana === 0 || diaSemana === 6) {
                const proximaSegunda = new Date(hoje);
                proximaSegunda.setDate(hoje.getDate() + (1 + 7 - hoje.getDay()) % 7);
                segunda = proximaSegunda;
            } else {
                // Mostrar semana atual
                segunda = new Date(hoje);
                segunda.setDate(hoje.getDate() - hoje.getDay() + 1);
            }
            
            sexta = new Date(segunda);
            sexta.setDate(segunda.getDate() + 4);
            
            // Formatando para YYYY-MM-DD
            const formatarData = (date) => date.toISOString().split('T')[0];
            
            const inputData = document.getElementById('data');
            inputData.min = formatarData(segunda);
            inputData.max = formatarData(sexta);
            
            // Se a data atual estiver fora dos limites, ajustar
            if (inputData.value < inputData.min) {
                inputData.value = inputData.min;
            } else if (inputData.value > inputData.max) {
                inputData.value = inputData.max;
            }
        }
        
        // Chamar a função quando a página carrega
        atualizarLimitesData();
        
        // Atualizar a cada minuto caso a página fique aberta
        setInterval(atualizarLimitesData, 60000);
        
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
</body>
</html>

<?php
$conteudo = ob_get_clean();
include 'includes/layout.php';