<?php
use App\Utils\Helper;

// --- Logique de tri (identique à materials/index.php) ---
$currentSortColumn = $_GET['sort'] ?? 'name'; // Colonne par défaut pour les marques
$currentSortOrder = $_GET['order'] ?? 'asc';

$baseUrl = APP_URL . '/' . trim(str_replace(APP_URL, '', $_SERVER['REQUEST_URI']), '/');
$baseUrl = strtok($baseUrl, '?');
// --- Fin logique de tri ---
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo Helper::e($pageTitle ?? 'Brands'); ?></h1>
    <a href="<?php echo APP_URL; ?>/brands/create" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add New Brand
    </a>
</div>

<!-- Champs de recherche -->
<div class="row mb-3">
    <div class="col-md-5">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="brandNameSearch" class="form-control" placeholder="Search by Brand Name...">
        </div>
    </div>
    <div class="col-md-5">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="brandAbbreviationSearch" class="form-control" placeholder="Search by Abbreviation...">
        </div>
    </div>
</div>

<?php if (empty($brands)): ?>
    <div class="alert alert-info">No brands found. <a href="<?php echo APP_URL; ?>/brands/create">Add one?</a></div>
<?php else: ?>
    <table class="table table-striped table-hover" id="brandsTable"> <!-- ID unique pour la table -->
        <thead class="table-dark">
            <tr>
                <th><?php echo Helper::generateSortLink('id', 'ID', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('name', 'Name', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('abbreviation', 'Abbreviation', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('created_at', 'Created At', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($brands as $brand): ?>
                <tr>
                    <td><?php echo Helper::e($brand['id']); ?></td>
                    <td><?php echo Helper::e($brand['name']); ?></td> <!-- Colonne 1 pour JS (Name) -->
                    <td><?php echo Helper::e($brand['abbreviation'] ?? 'N/A'); ?></td> <!-- Colonne 2 pour JS (Abbreviation) -->
                    <td><?php echo Helper::e(date('Y-m-d H:i', strtotime($brand['created_at']))); ?></td>
                    <td>
                        <a href="<?php echo APP_URL; ?>/brands/edit/<?php echo Helper::e($brand['id']); ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                        <a href="<?php echo APP_URL; ?>/brands/show/<?php echo Helper::e($brand['id']); ?>" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                        <form action="<?php echo APP_URL; ?>/brands/delete/<?php echo Helper::e($brand['id']); ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this brand?');">
                            <?php echo Helper::csrfInput(); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>