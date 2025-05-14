<?php
use App\Utils\Helper;

// Récupérer les paramètres de tri actuels depuis l'URL (GET)
$currentSortColumn = $_GET['sort'] ?? 'name'; // Colonne par défaut
$currentSortOrder = $_GET['order'] ?? 'asc';   // Ordre par défaut

// Fonction pour générer les liens de tri
function sort_link(string $column, string $displayName, string $currentSortColumn, string $currentSortOrder, string $baseUrl): string {
    $icon = '';
    $nextOrder = 'asc'; // Ordre par défaut si on clique sur une nouvelle colonne

    if ($column === $currentSortColumn) {
        if ($currentSortOrder === 'asc') {
            $icon = ' <i class="bi bi-sort-alpha-down"></i>'; // Ou bi-sort-numeric-down
            $nextOrder = 'desc';
        } else {
            $icon = ' <i class="bi bi-sort-alpha-up"></i>'; // Ou bi-sort-numeric-up
            $nextOrder = 'asc';
        }
    }
    // Conserver les autres paramètres GET existants (si vous en avez plus tard pour la pagination/filtre)
    // Pour l'instant, on ne gère que sort et order
    return '<a href="' . $baseUrl . '?sort=' . $column . '&order=' . $nextOrder . '">' . Helper::e($displayName) . $icon . '</a>';
}

$baseUrl = APP_URL . '/' . trim(str_replace(APP_URL, '', $_SERVER['REQUEST_URI']), '/');
// Enlever les anciens paramètres de tri de l'URL de base pour les reconstruire
$baseUrl = strtok($baseUrl, '?');

?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo Helper::e($pageTitle ?? 'Materials'); ?></h1>
    <a href="<?php echo APP_URL; ?>/materials/create" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add New Material
    </a>
</div>

<?php if (empty($materials)): ?>
    <div class="alert alert-info">No materials found. <a href="<?php echo APP_URL; ?>/materials/create">Add one?</a></div>
<?php else: ?>
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th><?php echo sort_link('id', 'ID', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo sort_link('name', 'Name', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo sort_link('created_at', 'Created At', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($materials as $material): ?>
                <tr>
                    <td><?php echo Helper::e($material['id']); ?></td>
                    <td><?php echo Helper::e($material['name']); ?></td>
                    <td><?php echo Helper::e(date('Y-m-d H:i', strtotime($material['created_at']))); ?></td>
                    <td>
                        <a href="<?php echo APP_URL; ?>/materials/edit/<?php echo Helper::e($material['id']); ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                        <a href="<?php echo APP_URL; ?>/materials/show/<?php echo Helper::e($material['id']); ?>" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                        <form action="<?php echo APP_URL; ?>/materials/delete/<?php echo Helper::e($material['id']); ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this material?');">
                            <?php echo Helper::csrfInput(); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>