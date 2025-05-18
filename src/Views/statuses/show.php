<?php
use App\Utils\Helper;
?>
<h1><?php echo Helper::e($pageTitle ?? 'Status Details'); ?></h1>

<?php if (isset($status) && $status): ?>
    <dl class="row">
        <dt class="col-sm-3">ID</dt>
        <dd class="col-sm-9"><?php echo Helper::e($status['id']); ?></dd>

        <dt class="col-sm-3">Name</dt>
        <dd class="col-sm-9"><?php echo Helper::e($status['name']); ?></dd>

        <dt class="col-sm-3">Availability Type</dt>
        <dd class="col-sm-9"><?php echo Helper::e(str_replace('_', ' ', ucfirst($status['availability_type']))); ?></dd>

        <dt class="col-sm-3">Description</dt>
        <dd class="col-sm-9"><?php echo Helper::e($status['description'] ?? 'N/A'); ?></dd>

        <dt class="col-sm-3">Created At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($status['created_at']))); ?></dd>

        <dt class="col-sm-3">Last Updated At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($status['updated_at']))); ?></dd>
    </dl>

    <div class="mt-4">
        <a href="<?php echo APP_URL; ?>/statuses/edit/<?php echo Helper::e($status['id']); ?>" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>
        <a href="<?php echo APP_URL; ?>/statuses" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Back to List</a>
        <form action="<?php echo APP_URL; ?>/statuses/delete/<?php echo Helper::e($status['id']); ?>" method="POST" class="d-inline ms-2" onsubmit="return confirm('Delete this status?');">
            <?php echo Helper::csrfInput(); ?>
            <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Delete</button>
        </form>
    </div>
<?php else: ?>
    <div class="alert alert-warning">Status not found.</div>
    <a href="<?php echo APP_URL; ?>/statuses" class="btn btn-secondary">Back to List</a>
<?php endif; ?>