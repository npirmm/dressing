<?php
use App\Utils\Helper;
?>
<h1><?php echo Helper::e($pageTitle ?? 'Event Type Details'); ?></h1>

<?php if (isset($eventType) && $eventType): ?>
    <dl class="row">
        <dt class="col-sm-3">ID</dt>
        <dd class="col-sm-9"><?php echo Helper::e($eventType['id']); ?></dd>

        <dt class="col-sm-3">Name</dt>
        <dd class="col-sm-9"><?php echo Helper::e($eventType['name']); ?></dd>

        <dt class="col-sm-3">Description</dt>
        <dd class="col-sm-9"><?php echo nl2br(Helper::e($eventType['description'] ?? 'N/A')); ?></dd>

        <dt class="col-sm-3">Typical Day Moments</dt>
        <dd class="col-sm-9">
            <?php if (!empty($eventType['day_moments_names_list'])): ?>
                <?php echo Helper::e(implode(', ', array_map('ucfirst', $eventType['day_moments_names_list']))); ?>
            <?php else: ?>
                N/A
            <?php endif; ?>
        </dd>

        <dt class="col-sm-3">Created At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($eventType['created_at']))); ?></dd>

        <dt class="col-sm-3">Last Updated At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($eventType['updated_at']))); ?></dd>
    </dl>

    <div class="mt-4">
        <a href="<?php echo APP_URL; ?>/eventtypes/edit/<?php echo Helper::e($eventType['id']); ?>" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>
        <a href="<?php echo APP_URL; ?>/eventtypes" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Back to List</a>
        <form action="<?php echo APP_URL; ?>/eventtypes/delete/<?php echo Helper::e($eventType['id']); ?>" method="POST" class="d-inline ms-2" onsubmit="return confirm('Delete this item?');">
            <?php echo Helper::csrfInput(); ?>
            <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Delete</button>
        </form>
    </div>
<?php else: ?>
    <div class="alert alert-warning">Event Type not found.</div>
    <a href="<?php echo APP_URL; ?>/eventtypes" class="btn btn-secondary">Back to List</a>
<?php endif; ?>