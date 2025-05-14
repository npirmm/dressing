<?php use App\Utils\Helper; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo Helper::e($pageTitle ?? 'Colors'); ?></h1>
    <a href="<?php echo APP_URL; ?>/colors/create" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add New Color
    </a>
</div>

<?php if (empty($colors)): ?>
    <div class="alert alert-info">No colors found. <a href="<?php echo APP_URL; ?>/colors/create">Add one?</a></div>
<?php else: ?>
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Hex Code</th>
                <th>Base Category</th>
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
                    <td><?php echo Helper::e($color['name']); ?></td>
                    <td style="font-family: monospace;"><?php echo Helper::e($color['hex_code'] ?? 'N/A'); ?></td>
                    <td><?php echo Helper::e($color['base_color_category'] ?? 'N/A'); ?></td>
                    <td>
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