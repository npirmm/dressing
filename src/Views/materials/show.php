<?php use App\Utils\Helper; ?>

<h1><?php echo Helper::e($pageTitle); ?></h1>

<?php if ($material): ?>
    <dl class="row">
        <dt class="col-sm-3">ID</dt>
        <dd class="col-sm-9"><?php echo Helper::e($material['id']); ?></dd>

        <dt class="col-sm-3">Name</dt>
        <dd class="col-sm-9"><?php echo Helper::e($material['name']); ?></dd>

        <dt class="col-sm-3">Created At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($material['created_at']))); ?></dd>

        <dt class="col-sm-3">Last Updated At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($material['updated_at']))); ?></dd>
    </dl>

    <div class="mt-4">
        <a href="<?php echo APP_URL; ?>/materials/edit/<?php echo Helper::e($material['id']); ?>" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>
        <a href="<?php echo APP_URL; ?>/materials" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Back to List</a>
         <form action="<?php echo APP_URL; ?>/materials/delete/<?php echo Helper::e($material['id']); ?>" method="POST" class="d-inline ms-2" onsubmit="return confirm('Are you sure you want to delete this material?');">
            <?php echo Helper::csrfInput(); ?>
            <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Delete</button>
        </form>
    </div>
<?php else: ?>
    <div class="alert alert-warning">Material not found.</div>
    <a href="<?php echo APP_URL; ?>/materials" class="btn btn-secondary">Back to List</a>
<?php endif; ?>