<?php
use App\Utils\Helper;
$currentSortColumn = $_GET['sort'] ?? 'name';
$currentSortOrder = $_GET['order'] ?? 'asc';
$baseUrl = APP_URL . '/' . trim(str_replace(APP_URL, '', strtok($_SERVER['REQUEST_URI'], '?')), '/');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo Helper::e($pageTitle ?? 'Suppliers'); ?></h1>
    <a href="<?php echo APP_URL; ?>/suppliers/create" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add New</a>
</div>

<div class="row mb-3">
    <div class="col-md-3"><input type="text" id="supNameSearch" class="form-control" placeholder="Search Name..."></div>
    <div class="col-md-3"><input type="text" id="supContactSearch" class="form-control" placeholder="Search Contact..."></div>
    <div class="col-md-3"><input type="text" id="supEmailSearch" class="form-control" placeholder="Search Email..."></div>
    <div class="col-md-3"><input type="text" id="supPhoneSearch" class="form-control" placeholder="Search Phone..."></div>
</div>

<?php if (empty($suppliers)): ?>
    <div class="alert alert-info">No suppliers found.</div>
<?php else: ?>
    <table class="table table-striped table-hover" id="suppliersTable">
        <thead class="table-dark">
            <tr>
                <th><?php echo Helper::generateSortLink('id', 'ID', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('name', 'Name', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('contact_person', 'Contact', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('email', 'Email', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('phone', 'Phone', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($suppliers as $item): ?>
                <tr>
                    <td><?php echo Helper::e($item['id']); ?></td>
                    <td><?php echo Helper::e($item['name']); ?></td>       <!--  {/* col 1 */}  -->
                    <td><?php echo Helper::e($item['contact_person'] ?? 'N/A'); ?></td> <!--  {/* col 2 */} -->
                    <td><?php echo Helper::e($item['email'] ?? 'N/A'); ?></td>    <!--  {/* col 3 */} -->
                    <td><?php echo Helper::e($item['phone'] ?? 'N/A'); ?></td>    <!--  {/* col 4 */} -->
                    <td>
                        <a href="<?php echo APP_URL; ?>/suppliers/edit/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                        <a href="<?php echo APP_URL; ?>/suppliers/show/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                        <form action="<?php echo APP_URL; ?>/suppliers/delete/<?php echo $item['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this supplier?');">
                            <?php echo Helper::csrfInput(); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>