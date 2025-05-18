<?php
use App\Utils\Helper;
?>
<h1><?php echo Helper::e($pageTitle ?? 'Storage Location Details'); ?></h1>

<?php if (isset($storageLocation) && $storageLocation): ?>
    <dl class="row">
        <dt class="col-sm-3">ID</dt>
        <dd class="col-sm-9"><?php echo Helper::e($storageLocation['id']); ?></dd>

        <dt class="col-sm-3">Room</dt>
        <dd class="col-sm-9"><?php echo Helper::e($storageLocation['room']); ?></dd>

        <dt class="col-sm-3">Area/Closet</dt>
        <dd class="col-sm-9"><?php echo Helper::e($storageLocation['area'] ?? 'N/A'); ?></dd>

        <dt class="col-sm-3">Shelf/Rack/Dresser</dt>
        <dd class="col-sm-9"><?php echo Helper::e($storageLocation['shelf_or_rack'] ?? 'N/A'); ?></dd>
        
        <dt class="col-sm-3">Level/Drawer/Section</dt>
        <dd class="col-sm-9"><?php echo Helper::e($storageLocation['level_or_section'] ?? 'N/A'); ?></dd>

        <dt class="col-sm-3">Specific Spot/Box</dt>
        <dd class="col-sm-9"><?php echo Helper::e($storageLocation['specific_spot_or_box'] ?? 'N/A'); ?></dd>

        <dt class="col-sm-3 text-primary">Full Path</dt>
        <dd class="col-sm-9 fw-bold text-primary"><?php echo Helper::e($storageLocation['full_location_path'] ?? 'N/A'); ?></dd>

        <dt class="col-sm-3">Created At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($storageLocation['created_at']))); ?></dd>

        <dt class="col-sm-3">Last Updated At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($storageLocation['updated_at']))); ?></dd>
    </dl>

    <div class="mt-4">
        <a href="<?php echo APP_URL; ?>/storagelocations/edit/<?php echo Helper::e($storageLocation['id']); ?>" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>
        <a href="<?php echo APP_URL; ?>/storagelocations" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Back to List</a>
        <form action="<?php echo APP_URL; ?>/storagelocations/delete/<?php echo Helper::e($storageLocation['id']); ?>" method="POST" class="d-inline ms-2" onsubmit="return confirm('Delete this location?');">
            <?php echo Helper::csrfInput(); ?>
            <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Delete</button>
        </form>
    </div>
<?php else: ?>
    <div class="alert alert-warning">Location not found.</div>
    <a href="<?php echo APP_URL; ?>/storagelocations" class="btn btn-secondary">Back to List</a>
<?php endif; ?>