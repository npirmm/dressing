<?php use App\Utils\Helper; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo Helper::e($pageTitle ?? 'Brands'); ?></h1>
    <a href="<?php echo APP_URL; ?>/brands/create" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add New Brand
    </a>
</div>

<?php if (empty($brands)): ?>
    <div class="alert alert-info">No brands found. <a href="<?php echo APP_URL; ?>/brands/create">Add one?</a></div>
<?php else: ?>
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Abbreviation</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($brands as $brand): ?>
                <tr>
                    <td><?php echo Helper::e($brand['id']); ?></td>
                    <td><?php echo Helper::e($brand['name']); ?></td>
                    <td><?php echo Helper::e($brand['abbreviation'] ?? 'N/A'); ?></td>
                    <td><?php echo Helper::e(date('Y-m-d H:i', strtotime($brand['created_at']))); ?></td>
                    <td>
                        <a href="<?php echo APP_URL; ?>/brands/edit/<?php echo Helper::e($brand['id']); ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <a href="<?php echo APP_URL; ?>/brands/show/<?php echo Helper::e($brand['id']); ?>" class="btn btn-sm btn-outline-info" title="View Details">
                            <i class="bi bi-eye"></i>
                        </a>
                        <!-- Delete button using a form for POST request -->
                        <form action="<?php echo APP_URL; ?>/brands/delete/<?php echo Helper::e($brand['id']); ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this brand? This action cannot be undone.');">
                            <?php echo Helper::csrfInput(); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>