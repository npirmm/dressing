<?php
use App\Utils\Helper;
$currentSortColumn = $_GET['sort'] ?? 'name';
$currentSortOrder = $_GET['order'] ?? 'asc';
$baseUrl = APP_URL . '/' . trim(str_replace(APP_URL, '', strtok($_SERVER['REQUEST_URI'], '?')), '/');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo Helper::e($pageTitle ?? 'Statuses'); ?></h1>
    <a href="<?php echo APP_URL; ?>/statuses/create" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add New</a>
</div>

<div class="row mb-3">
    <div class="col-md-4"><input type="text" id="statusNameSearch" class="form-control" placeholder="Search Name..."></div>
    <div class="col-md-4"><input type="text" id="statusAvailSearch" class="form-control" placeholder="Search Availability..."></div>
    <div class="col-md-4"><input type="text" id="statusDescSearch" class="form-control" placeholder="Search Description..."></div>
</div>

<?php if (empty($statuses)): ?>
    <div class="alert alert-info">No statuses found.</div>
<?php else: ?>
    <table class="table table-striped table-hover" id="statusesTable">
        <thead class="table-dark">
            <tr>
                <th><?php echo Helper::generateSortLink('id', 'ID', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('name', 'Name', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('availability_type', 'Availability', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('description', 'Description', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($statuses as $item): ?>
                <tr>
                    <td><?php echo Helper::e($item['id']); ?></td>
                    <td><?php echo Helper::e($item['name']); ?></td>         <!-- {/* col 1 */} -->
                    <td><?php echo Helper::e(str_replace('_', ' ', ucfirst($item['availability_type']))); ?></td> <!-- {/* col 2 */} -->
                    <td><?php echo Helper::e(mb_strimwidth($item['description'] ?? '', 0, 70, '...')); ?></td> <!-- {/* col 3 */} -->
                    <td>
                        <a href="<?php echo APP_URL; ?>/statuses/edit/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                        <a href="<?php echo APP_URL; ?>/statuses/show/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                        <form action="<?php echo APP_URL; ?>/statuses/delete/<?php echo $item['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this status?');">
                            <?php echo Helper::csrfInput(); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>