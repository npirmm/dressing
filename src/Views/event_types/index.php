<?php
use App\Utils\Helper;
$currentSortColumn = $_GET['sort'] ?? 'name';
$currentSortOrder = $_GET['order'] ?? 'asc';
$baseUrl = APP_URL . '/' . trim(str_replace(APP_URL, '', strtok($_SERVER['REQUEST_URI'], '?')), '/');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo Helper::e($pageTitle ?? 'Event Types'); ?></h1>
    <a href="<?php echo APP_URL; ?>/eventtypes/create" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add New</a>
</div>

<div class="row mb-3">
    <div class="col-md-5">
        <input type="text" id="etNameSearch" class="form-control" placeholder="Search Name...">
    </div>
    <div class="col-md-5">
        <input type="text" id="etDescriptionSearch" class="form-control" placeholder="Search Description...">
    </div>
    <!-- Filtrer par moments de la journée est plus complexe, à voir plus tard -->
</div>

<?php if (empty($eventTypes)): ?>
    <div class="alert alert-info">No event types found.</div>
<?php else: ?>
    <table class="table table-striped table-hover" id="eventTypesTable">
        <thead class="table-dark">
            <tr>
                <th><?php echo Helper::generateSortLink('id', 'ID', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('name', 'Name', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('description', 'Description', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th>Day Moments</th> <!-- {/* Non triable directement ici */} -->
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($eventTypes as $item): ?>
                <tr>
                    <td><?php echo Helper::e($item['id']); ?></td>
                    <td><?php echo Helper::e($item['name']); ?></td>       <!-- {/* col 1 pour JS */} -->
                    <td><?php echo Helper::e(mb_strimwidth($item['description'] ?? '', 0, 70, '...')); ?></td> <!-- {/* col 2 pour JS */} -->
                    <td><?php echo Helper::e($item['day_moments_names'] ?? 'N/A'); ?></td> <!-- {/* col 3 pour JS (si on veut filtrer dessus, sinon juste pour affichage) */} -->
                    <td>
                        <a href="<?php echo APP_URL; ?>/eventtypes/edit/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                        <a href="<?php echo APP_URL; ?>/eventtypes/show/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                        <form action="<?php echo APP_URL; ?>/eventtypes/delete/<?php echo $item['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this item?');">
                            <?php echo Helper::csrfInput(); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>