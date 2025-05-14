<?php use App\Utils\Helper; ?>

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
                <th>ID</th>
                <th>Name</th>
                <th>Created At</th>
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