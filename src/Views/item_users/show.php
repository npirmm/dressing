<?php
use App\Utils\Helper;
?>
<h1><?php echo Helper::e($pageTitle ?? 'Item User Details'); ?></h1>

<?php if (isset($itemUser) && $itemUser): ?>
    <dl class="row">
        <dt class="col-sm-3">ID</dt>
        <dd class="col-sm-9"><?php echo Helper::e($itemUser['id']); ?></dd>

        <dt class="col-sm-3">Name</dt>
        <dd class="col-sm-9"><?php echo Helper::e($itemUser['name']); ?></dd>

        <dt class="col-sm-3">Abbreviation</dt>
        <dd class="col-sm-9"><?php echo Helper::e($itemUser['abbreviation'] ?? 'N/A'); ?></dd>

        <dt class="col-sm-3">Created At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($itemUser['created_at']))); ?></dd>

        <dt class="col-sm-3">Last Updated At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($itemUser['updated_at']))); ?></dd>
    </dl>

    <div class="mt-4">
        <a href="<?php echo APP_URL; ?>/itemusers/edit/<?php echo Helper::e($itemUser['id']); ?>" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>
        <a href="<?php echo APP_URL; ?>/itemusers" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Back to List</a>
        <form action="<?php echo APP_URL; ?>/itemusers/delete/<?php echo Helper::e($itemUser['id']); ?>" method="POST" class="d-inline ms-2" onsubmit="return confirm('Delete this item user?');">
            <?php echo Helper::csrfInput(); ?>
            <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Delete</button>
        </form>
    </div>
<?php else: ?>
    <div class="alert alert-warning">Item User not found.</div>
    <a href="<?php echo APP_URL; ?>/itemusers" class="btn btn-secondary">Back to List</a>
<?php endif; ?>