<?php
use App\Utils\Helper;
$currentSortColumn = $_GET['sort'] ?? 'name';
$currentSortOrder = $_GET['order'] ?? 'asc';
$baseUrl = APP_URL . '/' . trim(str_replace(APP_URL, '', strtok($_SERVER['REQUEST_URI'], '?')), '/');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo Helper::e($pageTitle ?? 'Item Users'); ?></h1>
    <a href="<?php echo APP_URL; ?>/itemusers/create" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add New</a>
</div>

<div class="row mb-3">
    <div class="col-md-5">
        <input type="text" id="iuNameSearch" class="form-control" placeholder="Search Name...">
    </div>
    <div class="col-md-5">
        <input type="text" id="iuAbbreviationSearch" class="form-control" placeholder="Search Abbreviation...">
    </div>
</div>

<?php if (empty($itemUsers)): ?>
    <div class="alert alert-info">No item users found.</div>
<?php else: ?>
    <table class="table table-striped table-hover" id="itemUsersTable">
        <thead class="table-dark">
            <tr>
                <th><?php echo Helper::generateSortLink('id', 'ID', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('name', 'Name', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('abbreviation', 'Abbreviation', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itemUsers as $item): ?>
                <tr>
                    <td><?php echo Helper::e($item['id']); ?></td>
                    <td><?php echo Helper::e($item['name']); ?></td>       <!-- {/* col 1 pour JS */} -->
                    <td><?php echo Helper::e($item['abbreviation'] ?? 'N/A'); ?></td> <!-- {{/* col 2 pour JS */} -->
                    <td>
                        <a href="<?php echo APP_URL; ?>/itemusers/edit/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                        <a href="<?php echo APP_URL; ?>/itemusers/show/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                        <form action="<?php echo APP_URL; ?>/itemusers/delete/<?php echo $item['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this item user?');">
                            <?php echo Helper::csrfInput(); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>