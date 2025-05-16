<?php
// src/Views/categories_types/show.php
use App\Utils\Helper;
?>

<h1><?php echo Helper::e($pageTitle ?? 'Category/Type Details'); ?></h1>

<?php if (isset($categoryType) && $categoryType): ?>
    <dl class="row">
        <dt class="col-sm-3">ID</dt>
        <dd class="col-sm-9"><?php echo Helper::e($categoryType['id']); ?></dd>

        <dt class="col-sm-3">Name</dt>
        <dd class="col-sm-9"><?php echo Helper::e($categoryType['name']); ?></dd>

        <dt class="col-sm-3">Category</dt>
        <dd class="col-sm-9"><?php echo Helper::e(ucfirst($categoryType['category'])); ?></dd>

        <dt class="col-sm-3">Code</dt>
        <dd class="col-sm-9"><?php echo Helper::e(strtoupper($categoryType['code'])); ?></dd>

        <dt class="col-sm-3">Created At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($categoryType['created_at']))); ?></dd>

        <dt class="col-sm-3">Last Updated At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($categoryType['updated_at']))); ?></dd>
    </dl>

    <div class="mt-4">
        <a href="<?php echo APP_URL; ?>/categorytypes/edit/<?php echo Helper::e($categoryType['id']); ?>" class="btn btn-primary">
            <i class="bi bi-pencil-square"></i> Edit
        </a>
        <a href="<?php echo APP_URL; ?>/categorytypes" class="btn btn-secondary">
            <i class="bi bi-arrow-left-circle"></i> Back to List
        </a>
        <form action="<?php echo APP_URL; ?>/categorytypes/delete/<?php echo Helper::e($categoryType['id']); ?>" method="POST" class="d-inline ms-2" onsubmit="return confirm('Are you sure you want to delete this category/type? This action cannot be undone if it is in use.');">
            <?php echo Helper::csrfInput(); ?>
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash"></i> Delete
            </button>
        </form>
    </div>
<?php else: ?>
    <div class="alert alert-warning">Category/Type not found.</div>
    <a href="<?php echo APP_URL; ?>/categorytypes" class="btn btn-secondary">Back to List</a>
<?php endif; ?>