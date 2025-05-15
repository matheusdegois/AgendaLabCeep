<?php
session_start();
require 'config/db.php';
ob_start();

$titulo = "Gerenciar Reservas";

// Configuração de fuso horário e data atual
date_default_timezone_set('America/Sao_Paulo');
$data_atual = date('Y-m-d');

// Verificação do usuário
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
$usuario_id = $_SESSION['usuario_id'] ?? null;

// Configuração de visualização
$visualizacao = isset($_GET['view']) && in_array($_GET['view'], ['calendario', 'lista']) ? $_GET['view'] : 'lista';

// Configuração de paginação
$limite_opcoes = [10, 20, 30, 50];
$limite = isset($_GET['limite']) && in_array((int)$_GET['limite'], $limite_opcoes) ? (int)$_GET['limite'] : 10;
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? max((int)$_GET['pagina'], 1) : 1;
$offset = ($pagina - 1) * $limite;

// Filtros básicos
$where = "a.AGE_EXCLUIDO = 0 AND l.LAB_EXCLUIDO = 0";
$params = [];

// Se não for admin, filtra apenas as reservas do usuário
if (!$is_admin && $usuario_id) {
    $where .= " AND a.AGE_PRO_ID = :usuario_id";
    $params[':usuario_id'] = $usuario_id;
}

// Busca por termo
if (!empty($_GET['busca'])) {
    $busca = trim($_GET['busca']);
    $where .= " AND (l.LAB_NOME LIKE :busca OR p.PRO_NOME LIKE :busca OR p.PRO_SOBRENOME LIKE :busca)";
    $params[':busca'] = "%$busca%";
}

// Filtro por intervalo de datas (com data atual como padrão)
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : $data_atual;
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : $data_atual;

if (!empty($data_inicio)) {
    $where .= " AND a.AGE_DATA >= :data_inicio";
    $params[':data_inicio'] = $data_inicio;
}

if (!empty($data_fim)) {
    $where .= " AND a.AGE_DATA <= :data_fim";
    $params[':data_fim'] = $data_fim;
}

// Filtro por laboratório
$laboratorio_id = isset($_GET['laboratorio']) ? (int)$_GET['laboratorio'] : 0;
if ($laboratorio_id > 0) {
    $where .= " AND a.AGE_LAB_ID = :laboratorio_id";
    $params[':laboratorio_id'] = $laboratorio_id;
}

// Filtro por turno
$turno_id = isset($_GET['turno']) ? (int)$_GET['turno'] : 0;
if ($turno_id > 0) {
    $where .= " AND a.AGE_TUR_ID = :turno_id";
    $params[':turno_id'] = $turno_id;
}

// Buscar opções para filtros
$sql_laboratorios = "SELECT LAB_ID, LAB_NOME FROM laboratorios WHERE LAB_EXCLUIDO = 0 ORDER BY LAB_NOME";
$laboratorios = $pdo->query($sql_laboratorios)->fetchAll(PDO::FETCH_ASSOC);

$sql_turnos = "SELECT TUR_ID, TUR_NOME FROM turnos ORDER BY TUR_NOME";
$turnos = $pdo->query($sql_turnos)->fetchAll(PDO::FETCH_ASSOC);

// Consulta para contar o total
$sql_count = "SELECT COUNT(*) FROM agendamentos a
              JOIN laboratorios l ON a.AGE_LAB_ID = l.LAB_ID
              JOIN professores p ON a.AGE_PRO_ID = p.PRO_ID
              WHERE $where";

$stmt_count = $pdo->prepare($sql_count);
foreach ($params as $key => $value) {
    $stmt_count->bindValue($key, $value);
}
$stmt_count->execute();
$total = $stmt_count->fetchColumn();
$total_paginas = ceil($total / $limite);

// Consulta principal
$sql = "SELECT a.*, l.LAB_NOME, p.PRO_NOME, p.PRO_SOBRENOME, 
               t.TUR_NOME, au.AUL_HORARIO_INICIO, au.AUL_HORARIO_FIM
        FROM agendamentos a
        JOIN laboratorios l ON a.AGE_LAB_ID = l.LAB_ID
        JOIN professores p ON a.AGE_PRO_ID = p.PRO_ID
        JOIN turnos t ON a.AGE_TUR_ID = t.TUR_ID
        JOIN aulas au ON a.AGE_AUL_ID = au.AUL_ID
        WHERE $where
        ORDER BY a.AGE_DATA DESC, au.AUL_HORARIO_INICIO DESC
        LIMIT :limite OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <style>
        .btn-action {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-filter {
            min-height: 44px;
        }
    </style>
</head>
<body>
    <div class="container-fluid px-4 py-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-alt me-2"></i><?= $titulo ?>
                </h5>
                <div class="d-flex gap-2">
                    <a href="nova_reserva.php" class="btn btn-success btn-action">
                        <i class="fas fa-plus me-2"></i><span>Nova Reserva</span>
                    </a>
                    <div class="btn-group" role="group">
                        <a href="?view=calendario" class="btn btn-primary <?= $visualizacao === 'calendario' ? 'active' : '' ?> btn-action">
                            <i class="fas fa-calendar me-2"></i>
                        </a>
                        <a href="?view=lista" class="btn btn-primary <?= $visualizacao === 'lista' ? 'active' : '' ?> btn-action">
                            <i class="fas fa-list me-2"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (isset($_SESSION['mensagem'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= $_SESSION['mensagem']; unset($_SESSION['mensagem']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['erro'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= $_SESSION['erro']; unset($_SESSION['erro']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtros e pesquisa -->
                <form method="GET" class="mb-3">
                    <input type="hidden" name="view" value="<?= $visualizacao ?>">
                    
                    <div class="row g-3 align-items-end">
                        <!-- Barra de busca -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="input-group">
                                <input type="text" name="busca" class="form-control form-filter" 
                                       placeholder="Buscar por laboratório ou professor..." 
                                       value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
                                <button type="submit" class="btn btn-primary btn-action">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if (!empty($_GET['busca']) || !empty($_GET['data_inicio']) || !empty($_GET['data_fim']) || !empty($_GET['laboratorio']) || !empty($_GET['turno'])): ?>
                                    <a href="reservas.php?view=<?= $visualizacao ?>" class="btn btn-outline-secondary btn-action">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Filtro por intervalo de datas -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label for="data_inicio" class="form-label small mb-1">Data Início</label>
                                    <input type="date" name="data_inicio" id="data_inicio" class="form-control form-filter" 
                                           value="<?= htmlspecialchars($data_inicio) ?>">
                                </div>
                                <div class="col-6">
                                    <label for="data_fim" class="form-label small mb-1">Data Fim</label>
                                    <input type="date" name="data_fim" id="data_fim" class="form-control form-filter" 
                                           value="<?= htmlspecialchars($data_fim) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Filtro por laboratório -->
                        <div class="col-12 col-md-6 col-lg-2">
                            <label for="laboratorio" class="form-label small mb-1">Laboratório</label>
                            <select name="laboratorio" id="laboratorio" class="form-select form-filter">
                                <option value="">Todos</option>
                                <?php foreach ($laboratorios as $lab): ?>
                                    <option value="<?= $lab['LAB_ID'] ?>" <?= $laboratorio_id == $lab['LAB_ID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($lab['LAB_NOME']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Filtro por turno -->
                        <div class="col-12 col-md-6 col-lg-2">
                            <label for="turno" class="form-label small mb-1">Turno</label>
                            <select name="turno" id="turno" class="form-select form-filter">
                                <option value="">Todos</option>
                                <?php foreach ($turnos as $turno): ?>
                                    <option value="<?= $turno['TUR_ID'] ?>" <?= $turno_id == $turno['TUR_ID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($turno['TUR_NOME']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Botão aplicar filtros (visível apenas em mobile) -->
                        <div class="col-12 col-md-6 col-lg-2 d-block d-lg-none">
                            <button type="submit" class="btn btn-primary w-100 form-filter">Aplicar Filtros</button>
                        </div>
                        
                        <!-- Limite por página -->
                        <div class="col-12 col-md-6 col-lg-2">
                            <div class="d-flex align-items-center">
                                <label for="limite" class="form-label small mb-1 me-2">Exibir:</label>
                                <select name="limite" id="limite" class="form-select form-filter">
                                    <?php foreach ($limite_opcoes as $opcao): ?>
                                        <option value="<?= $opcao ?>" <?= $opcao == $limite ? 'selected' : '' ?>><?= $opcao ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Contagem de registros -->
                <p class="text-muted small mb-3">
                    <i class="fas fa-calendar-check me-1"></i> Exibindo <?= count($reservas) ?> de <?= $total ?> reserva(s).
                    <?php if (!empty($_GET['busca'])): ?>
                        <span class="ms-2"><i class="fas fa-filter me-1"></i> Busca: <strong><?= htmlspecialchars($_GET['busca']) ?></strong></span>
                    <?php endif; ?>
                    <?php if (!empty($data_inicio) || !empty($data_fim)): ?>
                        <span class="ms-2"><i class="fas fa-calendar me-1"></i> Período: 
                            <strong><?= date('d/m/Y', strtotime($data_inicio)) ?> a <?= date('d/m/Y', strtotime($data_fim)) ?></strong>
                        </span>
                    <?php endif; ?>
                    <?php if ($laboratorio_id > 0): ?>
                        <?php 
                        $lab_selecionado = '';
                        foreach ($laboratorios as $lab) {
                            if ($lab['LAB_ID'] == $laboratorio_id) {
                                $lab_selecionado = $lab['LAB_NOME'];
                                break;
                            }
                        }
                        ?>
                        <span class="ms-2"><i class="fas fa-flask me-1"></i> Laboratório: <strong><?= htmlspecialchars($lab_selecionado) ?></strong></span>
                    <?php endif; ?>
                    <?php if ($turno_id > 0): ?>
                        <?php 
                        $turno_selecionado = '';
                        foreach ($turnos as $turno) {
                            if ($turno['TUR_ID'] == $turno_id) {
                                $turno_selecionado = $turno['TUR_NOME'];
                                break;
                            }
                        }
                        ?>
                        <span class="ms-2"><i class="fas fa-sun me-1"></i> Turno: <strong><?= htmlspecialchars($turno_selecionado) ?></strong></span>
                    <?php endif; ?>
                </p>

                <?php if ($visualizacao === 'calendario'): ?>
                    <!-- Visualização em Calendário -->
                    <div id="calendario" class="mb-4"></div>
                <?php else: ?>
                    <!-- Visualização em Lista -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th><i class="fas fa-flask me-1"></i> Laboratório</th>
                                    <th><i class="fas fa-user-tie me-1"></i> Professor</th>
                                    <th><i class="fas fa-calendar-day me-1"></i> Data</th>
                                    <th><i class="fas fa-clock me-1"></i> Horário</th>
                                    <th><i class="fas fa-sun me-1"></i> Turno</th>
                                    <th class="text-center"><i class="fas fa-cogs me-1"></i> Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($reservas) > 0): ?>
                                    <?php foreach ($reservas as $reserva): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($reserva['LAB_NOME']) ?></td>
                                            <td><?= htmlspecialchars($reserva['PRO_NOME'] . ' ' . $reserva['PRO_SOBRENOME']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($reserva['AGE_DATA'])) ?></td>
                                            <td><?= substr($reserva['AUL_HORARIO_INICIO'], 0, 5) ?> - <?= substr($reserva['AUL_HORARIO_FIM'], 0, 5) ?></td>
                                            <td><?= htmlspecialchars($reserva['TUR_NOME']) ?></td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <?php if ($is_admin || $reserva['AGE_PRO_ID'] == $usuario_id): ?>
                                                        <a href="editar_reserva.php?id=<?= $reserva['AGE_ID'] ?>" 
                                                           class="btn btn-warning btn-action" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="remover_reserva.php?id=<?= $reserva['AGE_ID'] ?>" 
                                                           class="btn btn-danger btn-action" title="Remover"
                                                           onclick="return confirm('Tem certeza que deseja remover esta reserva?')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-3">
                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Nenhuma reserva encontrada
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <?php if ($total_paginas > 1): ?>
                        <nav aria-label="Navegação de páginas" class="mt-4">
                            <ul class="pagination justify-content-center flex-wrap">
                                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => 1])) ?>">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                </li>
                                
                                <?php for ($i = max(1, $pagina - 2); $i <= min($pagina + 2, $total_paginas); $i++): ?>
                                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                                <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $total_paginas])) ?>">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.js'></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Esconder alertas após 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Calendário
        <?php if ($visualizacao === 'calendario'): ?>
            const calendarEl = document.getElementById('calendario');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [
                    <?php foreach ($reservas as $reserva): ?>
                    {
                        title: '<?= addslashes($reserva['LAB_NOME']) ?> - <?= addslashes($reserva['PRO_NOME']) ?>',
                        start: '<?= $reserva['AGE_DATA'] ?>T<?= $reserva['AUL_HORARIO_INICIO'] ?>',
                        end: '<?= $reserva['AGE_DATA'] ?>T<?= $reserva['AUL_HORARIO_FIM'] ?>',
                        backgroundColor: '#0d6efd',
                        borderColor: '#0d6efd'
                    },
                    <?php endforeach; ?>
                ]
            });
            calendar.render();
        <?php endif; ?>
    });
    </script>
</body>
</html>

<?php
$conteudo = ob_get_clean();
include 'includes/layout.php';