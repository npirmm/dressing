<?php
use App\Utils\Helper;
?>
<h1><?php echo Helper::e($pageTitle ?? 'Supplier Details'); ?></h1>

<?php if (isset($supplier) && $supplier): ?>
    <dl class="row">
        <dt class="col-sm-3">ID</dt>
        <dd class="col-sm-9"><?php echo Helper::e($supplier['id']); ?></dd>

        <dt class="col-sm-3">Name</dt>
        <dd class="col-sm-9"><?php echo Helper::e($supplier['name']); ?></dd>

        <dt class="col-sm-3">Contact Person</dt>
        <dd class="col-sm-9"><?php echo Helper::e($supplier['contact_person'] ?? 'N/A'); ?></dd>

        <dt class="col-sm-3">Email</dt>
        <dd class="col-sm-9"><?php echo Helper::e($supplier['email'] ?? 'N/A'); ?></dd>

        <dt class="col-sm-3">Phone</dt>
        <dd class="col-sm-9"><?php echo Helper::e($supplier['phone'] ?? 'N/A'); ?></dd>

        <dt class="col-sm-3">Address</dt>
        <dd class="col-sm-9"><?php echo nl2br(Helper::e($supplier['address'] ?? 'N/A')); ?></dd>

        <dt class="col-sm-3">Notes</dt>
        <dd class="col-sm-9"><?php echo nl2br(Helper::e($supplier['notes'] ?? 'N/A')); ?></dd>

        <dt class="col-sm-3">Created At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($supplier['created_at']))); ?></dd>

        <dt class="col-sm-3">Last Updated At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($supplier['updated_at']))); ?></dd>
    </dl>

    <div class="mt-4">
        <a href="<?php echo APP_URL; ?>/suppliers/edit/<?php echo Helper::e($supplier['id']); ?>" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>
        <a href="<?php echo APP_URL; ?>/suppliers" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Back to List</a>
        <form action="<?php echo APP_URL; ?>/suppliers/delete/<?php echo Helper::e($supplier['id']); ?>" method="POST" class="d-inline ms-2" onsubmit="return confirm('Delete this supplier?');">
            <?php echo Helper::csrfInput(); ?>
            <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Delete</button>
        </form>
    </div>
<?php else: ?>
    <div class="alert alert-warning">Supplier not found.</div>
    <a href="<?php echo APP_URL; ?>/suppliers" class="btn btn-secondary">Back to List</a>
<?php endif; ?>