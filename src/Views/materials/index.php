<?php
use App\Utils\Helper;

// Récupérer les paramètres de tri actuels depuis l'URL (GET)
$currentSortColumn = $_GET['sort'] ?? 'name'; // Colonne par défaut
$currentSortOrder = $_GET['order'] ?? 'asc';   // Ordre par défaut

// Fonction pour générer les liens de tri
function sort_link(string $column, string $displayName, string $currentSortColumn, string $currentSortOrder, string $baseUrl): string {
    $nextOrder = 'asc';
    $iconClass = 'bi-arrow-down-up'; // Icône par défaut pour "triable mais non actif"
    $isActiveSort = false;

    if ($column === $currentSortColumn) {
        $isActiveSort = true;
        if ($currentSortOrder === 'asc') {
            $iconClass = 'bi-sort-alpha-down'; // Ou bi-sort-numeric-down, bi-sort-down pour générique date/nombre
            $nextOrder = 'desc';
        } else {
            $iconClass = 'bi-sort-alpha-up'; // Ou bi-sort-numeric-up, bi-sort-up
            $nextOrder = 'asc';
        }
    }

    // Construire le HTML pour l'icône
    // On ajoute une classe 'active-sort-icon' si c'est la colonne de tri actuelle
    $iconHtml = ' <span class="sort-icon-wrapper ' . ($isActiveSort ? 'active-sort-icon' : 'inactive-sort-icon') . '">';
    $iconHtml .= '<i class="bi ' . $iconClass . '"></i>';
    $iconHtml .= '</span>';

    // Le lien
    $link = '<a href="' . $baseUrl . '?sort=' . $column . '&order=' . $nextOrder . '">';
    $link .= Helper::e($displayName); // Le nom de la colonne
    $link .= $iconHtml; // L'icône est maintenant APRES le nom
    $link .= '</a>';

    return $link;
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

	<div class="row mb-3">
		<div class="col-md-6">
			<!-- Champ de recherche pour le nom -->
			<div class="input-group">
				<span class="input-group-text"><i class="bi bi-search"></i></span>
				<input type="text" id="materialNameSearch" class="form-control" placeholder="Search by Material Name...">
			</div>
		</div>
		<!-- On pourrait ajouter d'autres filtres ici pour d'autres colonnes pertinentes -->
	</div>


		<table class="table table-striped table-hover" id="materialsTable">
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