<?php
use App\Utils\Helper;

// --- Logique de tri (identique) ---
$currentSortColumn = $_GET['sort'] ?? 'name';
$currentSortOrder = $_GET['order'] ?? 'asc';

function sort_link_colors(string $column, string $displayName, string $currentSortColumn, string $currentSortOrder, string $baseUrl): string {
    // Renommer la fonction pour éviter les conflits
    $nextOrder = 'asc';
    $iconClass = 'bi-arrow-down-up';
    $isActiveSort = false;
    if ($column === $currentSortColumn) {
        $isActiveSort = true;
        if ($currentSortOrder === 'asc') {
            $iconClass = ($column === 'id' ? 'bi-sort-numeric-down' : 'bi-sort-alpha-down');
            $nextOrder = 'desc';
        } else {
            $iconClass = ($column === 'id' ? 'bi-sort-numeric-up' : 'bi-sort-alpha-up');
            $nextOrder = 'asc';
        }
    }
    $iconHtml = ' <span class="sort-icon-wrapper ' . ($isActiveSort ? 'active-sort-icon' : 'inactive-sort-icon') . '">';
    $iconHtml .= '<i class="bi ' . $iconClass . '"></i>';
    $iconHtml .= '</span>';
    $link = '<a href="' . $baseUrl . '?sort=' . $column . '&order=' . $nextOrder . '">';
    $link .= Helper::e($displayName);
    $link .= $iconHtml;
    $link .= '</a>';
    return $link;
}

$baseUrl = APP_URL . '/' . trim(str_replace(APP_URL, '', $_SERVER['REQUEST_URI']), '/');
$baseUrl = strtok($baseUrl, '?');
// --- Fin logique de tri ---
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo Helper::e($pageTitle ?? 'Colors'); ?></h1>
    <a href="<?php echo APP_URL; ?>/colors/create" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add New Color
    </a>
</div>

<!-- Champs de recherche -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="colorNameSearch" class="form-control" placeholder="Search by Color Name...">
        </div>
    </div>
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="colorHexSearch" class="form-control" placeholder="Search by Hex Code...">
        </div>
    </div>
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="colorBaseCategorySearch" class="form-control" placeholder="Search by Base Category...">
        </div>
    </div>
</div>


<?php if (empty($colors)): ?>
    <div class="alert alert-info">No colors found. <a href="<?php echo APP_URL; ?>/colors/create">Add one?</a></div>
<?php else: ?>
    <table class="table table-striped table-hover" id="colorsTable"> <!-- {/* ID unique */} -->
        <thead class="table-dark">
            <tr>
                <th><?php echo sort_link_colors('id', 'ID', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th>Image</th> <!-- {/* Pas triable/filtrable pour l'instant */} -->
                <th><?php echo sort_link_colors('name', 'Name', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo sort_link_colors('hex_code', 'Hex Code', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo sort_link_colors('base_color_category', 'Base Category', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($colors as $color): ?>
                <tr>
                    <td><?php echo Helper::e($color['id']); ?></td>
                    <td>
                        <?php if (!empty($color['image_filename'])): ?>
                            <img src="<?php echo Helper::e($imagePath . $color['image_filename']); ?>"
                                 alt="<?php echo Helper::e($color['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; border: 1px solid <?php echo Helper::e($color['hex_code'] ?? '#ccc'); ?>;">
                        <?php else: ?>
                            <div style="width: 50px; height: 50px; background-color: <?php echo Helper::e($color['hex_code'] ?? '#f0f0f0'); ?>; border: 1px solid #ccc; border-radius: 5px; display: flex; align-items: center; justify-content: center; font-size:0.7em; color:#777;">No Img</div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo Helper::e($color['name']); ?></td> <!-- {/* Colonne 2 pour JS (Name) */} -->
                    <td style="font-family: monospace;"><?php echo Helper::e($color['hex_code'] ?? 'N/A'); ?></td> <!-- {/* Colonne 3 pour JS (Hex) */} -->
                    <td><?php echo Helper::e($color['base_color_category'] ?? 'N/A'); ?></td> <!-- {/* Colonne 4 pour JS (Base Cat) */} -->
                    <td>
                        <!-- {/* ... boutons d'action ... */} -->
                        <a href="<?php echo APP_URL; ?>/colors/edit/<?php echo Helper::e($color['id']); ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                        <a href="<?php echo APP_URL; ?>/colors/show/<?php echo Helper::e($color['id']); ?>" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                        <form action="<?php echo APP_URL; ?>/colors/delete/<?php echo Helper::e($color['id']); ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this color? This will also delete its image.');">
                            <?php echo Helper::csrfInput(); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>