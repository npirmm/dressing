<?php
use App\Utils\Helper; // Assurez-vous que Helper est importé

// --- Logique de tri ---
$currentSortColumn = $_GET['sort'] ?? 'name';
$currentSortOrder = $_GET['order'] ?? 'asc';
$baseUrl = APP_URL . '/' . trim(str_replace(APP_URL, '', strtok($_SERVER['REQUEST_URI'], '?')), '/');
// --- Fin logique de tri ---
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo Helper::e($pageTitle ?? 'Categories/Types'); ?></h1>
    <a href="<?php echo APP_URL; ?>/categorytypes/create" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add New
    </a>
</div>

<!-- Champs de recherche -->
<div class="row mb-3">
    <div class="col-md-3">
        <input type="text" id="ctNameSearch" class="form-control" placeholder="Search Name...">
    </div>
    <div class="col-md-3">
        <input type="text" id="ctCategorySearch" class="form-control" placeholder="Search Category...">
    </div>
    <div class="col-md-3">
        <input type="text" id="ctCodeSearch" class="form-control" placeholder="Search Code...">
    </div>
</div>

<?php if (empty($categoryTypes)): ?>
    <div class="alert alert-info">No items found.</div>
<?php else: ?>
    <table class="table table-striped table-hover" id="categoryTypesTable">
        <thead class="table-dark">
            <tr>
                <th><?php echo Helper::generateSortLink('id', 'ID', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('name', 'Name', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('category', 'Category', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('code', 'Code', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categoryTypes as $item): ?>
                <tr>
                    <td><?php echo Helper::e($item['id']); ?></td>
                    <td><?php echo Helper::e($item['name']); ?></td>       <!-- {/* col 1 pour JS */} -->
                    <td><?php echo Helper::e(ucfirst($item['category'])); ?></td> <!--{/* col 2 pour JS */} -->
                    <td><?php echo Helper::e(strtoupper($item['code'])); ?></td> <!-- {/* col 3 pour JS */} -->
                    <td>
                        <a href="<?php echo APP_URL; ?>/categorytypes/edit/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                        <a href="<?php echo APP_URL; ?>/categorytypes/show/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                        <form action="<?php echo APP_URL; ?>/categorytypes/delete/<?php echo $item['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this item?');">
                            <?php echo Helper::csrfInput(); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>