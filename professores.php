<?php
session_start();
require 'config/db.php';
ob_start();



$isAdmin = $_SESSION['is_admin'] ?? 0;

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    $_SESSION['erro'] = "Acesso negado. Você não tem permissão para acessar esta página.";
    header("Location: dashboard.php");
    exit();
}

$titulo = "Gerenciar Professores";

// Configuração de paginação
$limite_opcoes = [10, 20, 30, 50];
$limite = isset($_GET['limite']) && in_array((int)$_GET['limite'], $limite_opcoes) ? (int)$_GET['limite'] : 10;
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? max((int)$_GET['pagina'], 1) : 1;
$offset = ($pagina - 1) * $limite;

// Busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$where = "PRO_EXCLUIDO = 0";
if (!empty($busca)) {
    $where .= " AND (PRO_NOME LIKE :busca OR PRO_SOBRENOME LIKE :busca OR PRO_EMAIL LIKE :busca)";
}

// Total de professores
$sql_count = "SELECT COUNT(*) FROM professores WHERE $where";
$stmt_count = $pdo->prepare($sql_count);
if (!empty($busca)) {
    $stmt_count->bindValue(':busca', "%$busca%", PDO::PARAM_STR);
}
$stmt_count->execute();
$total = $stmt_count->fetchColumn();
$total_paginas = ceil($total / $limite);

// Buscar professores
$sql = "SELECT * FROM professores WHERE $where ORDER BY PRO_NOME, PRO_SOBRENOME LIMIT :limite OFFSET :offset";
$stmt = $pdo->prepare($sql);
if (!empty($busca)) {
    $stmt->bindValue(':busca', "%$busca%", PDO::PARAM_STR);
}
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$professores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4 py-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
            <h5 class="card-title mb-0">
                <i class="fas fa-chalkboard-teacher me-2"></i><?= $titulo ?>
            </h5>
            <a href="cadastro_professor.php" class="btn btn-success" 
               style="min-height: 44px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-plus me-2"></i><span>Novo Professor</span>
            </a>
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
            <div class="row mb-3 align-items-end">
                <div class="col-12 col-md-6 mb-3 mb-md-0">
                    <form method="GET" class="d-flex">
                        <div class="input-group">
                            <input type="text" name="busca" class="form-control form-control-lg" 
                                   style="min-height: 44px;" placeholder="Buscar professor..." 
                                   value="<?= htmlspecialchars($busca) ?>">
                            <button type="submit" class="btn btn-primary" 
                                    style="min-width: 44px; min-height: 44px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($busca)): ?>
                                <a href="professores.php" class="btn btn-outline-secondary"
                                   style="min-width: 44px; min-height: 44px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <div class="col-12 col-md-6">
                    <div class="d-flex justify-content-start justify-content-md-end align-items-center">
                        <form method="GET" class="d-flex align-items-center">
                            <?php if (!empty($busca)): ?>
                                <input type="hidden" name="busca" value="<?= htmlspecialchars($busca) ?>">
                            <?php endif; ?>
                            <label class="me-2 text-nowrap">Exibir:</label>
                            <select name="limite" class="form-select" 
                                    style="width: auto; min-height: 44px;" onchange="this.form.submit()">
                                <?php foreach ($limite_opcoes as $opcao): ?>
                                    <option value="<?= $opcao ?>" <?= $opcao == $limite ? 'selected' : '' ?>><?= $opcao ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="pagina" value="1">
                        </form>
                    </div>
                </div>
            </div>

            <!-- Contagem de registros -->
            <p class="text-muted small mb-3">
                <i class="fas fa-users me-1"></i> Exibindo <?= count($professores) ?> de <?= $total ?> professor(es) cadastrados.
                <?php if (!empty($busca)): ?>
                    <span class="ms-2"><i class="fas fa-filter me-1"></i> Filtro: <strong><?= htmlspecialchars($busca) ?></strong></span>
                <?php endif; ?>
            </p>

            <!-- Tabela de professores -->
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th><i class="fas fa-user me-1"></i> Nome</th>
                            <th>Sobrenome</th>
                            <th><i class="fas fa-envelope me-1"></i> Email</th>
                            <th><i class="fas fa-phone me-1"></i> Telefone</th>
                            <th><i class="fas fa-mobile-alt me-1"></i> Celular</th>
                            <th class="text-center" style="width: 150px;"><i class="fas fa-cogs me-1"></i> Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($professores) > 0): ?>
                            <?php foreach ($professores as $prof): ?>
                                <tr>
                                    <td><?= htmlspecialchars($prof['PRO_NOME']) ?></td>
                                    <td><?= htmlspecialchars($prof['PRO_SOBRENOME']) ?></td>
                                    <td><?= htmlspecialchars($prof['PRO_EMAIL']) ?></td>
                                    <td><?= htmlspecialchars($prof['PRO_FONE']) ?></td>
                                    <td><?= htmlspecialchars($prof['PRO_CELULAR']) ?></td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="editar_professor.php?id=<?= $prof['PRO_ID'] ?>" 
                                               class="btn btn-warning" title="Editar" 
                                               style="min-width: 44px; min-height: 44px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-edit fa-fw"></i>
                                            </a>
                                            <a href="remover_professor.php?id=<?= $prof['PRO_ID'] ?>" 
                                               class="btn btn-danger" title="Remover"
                                               style="min-width: 44px; min-height: 44px; display: flex; align-items: center; justify-content: center;"
                                               onclick="return confirm('Tem certeza que deseja remover o professor: <?= htmlspecialchars($prof['PRO_NOME'] . ' ' . $prof['PRO_SOBRENOME']) ?>?')">
                                                <i class="fas fa-trash-alt fa-fw"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-3">
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <?= !empty($busca) ? 'Nenhum professor encontrado com o termo pesquisado.' : 'Nenhum professor cadastrado.' ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
                <nav aria-label="Navegação de páginas">
                    <ul class="pagination pagination-lg justify-content-center mt-4 flex-wrap">
                        <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=1&limite=<?= $limite ?><?= !empty($busca) ? '&busca='.urlencode($busca) : '' ?>"
                               title="Primeira página" style="min-width: 44px; min-height: 44px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $pagina-1 ?>&limite=<?= $limite ?><?= !empty($busca) ? '&busca='.urlencode($busca) : '' ?>"
                               title="Página anterior" style="min-width: 44px; min-height: 44px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        
                        <?php
                        // Mostrar menos páginas em dispositivos móveis
                        $range = isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobi') !== false) ? 1 : 2;
                        $start_page = max(1, $pagina - $range);
                        $end_page = min($total_paginas, $pagina + $range);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=1&limite=<?= $limite ?><?= !empty($busca) ? '&busca='.urlencode($busca) : '' ?>"
                                   style="min-width: 44px; min-height: 44px; display: flex; align-items: center; justify-content: center;">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link" style="min-width: 44px; min-height: 44px; display: flex; align-items: center; justify-content: center;">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $i ?>&limite=<?= $limite ?><?= !empty($busca) ? '&busca='.urlencode($busca) : '' ?>"
                                   style="min-width: 44px; min-height: 44px; display: flex; align-items: center; justify-content: center;"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_paginas): ?>
                            <?php if ($end_page < $total_paginas - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link" style="min-width: 44px; min-height: 44px; display: flex; align-items: center; justify-content: center;">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?= $total_paginas ?>&limite=<?= $limite ?><?= !empty($busca) ? '&busca='.urlencode($busca) : '' ?>"
                                   style="min-width: 44px; min-height: 44px; display: flex; align-items: center; justify-content: center;"><?= $total_paginas ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $pagina+1 ?>&limite=<?= $limite ?><?= !empty($busca) ? '&busca='.urlencode($busca) : '' ?>"
                               title="Próxima página" style="min-width: 44px; min-height: 44px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $total_paginas ?>&limite=<?= $limite ?><?= !empty($busca) ? '&busca='.urlencode($busca) : '' ?>"
                               title="Última página" style="min-width: 44px; min-height: 44px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

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
});
</script>

<?php
$conteudo = ob_get_clean();
include 'includes/layout.php';