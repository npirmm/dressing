<?php use App\Utils\Helper; ?>

<h1><?php echo Helper::e($pageTitle); ?></h1>

<?php if ($color): ?>
    <div class="row">
        <div class="col-md-8">
            <dl class="row">
                <dt class="col-sm-4">ID</dt>
                <dd class="col-sm-8"><?php echo Helper::e($color['id']); ?></dd>

                <dt class="col-sm-4">Name</dt>
                <dd class="col-sm-8"><?php echo Helper::e($color['name']); ?></dd>

                <dt class="col-sm-4">Hex Code</dt>
                <dd class="col-sm-8" style="font-family: monospace;">
                    <span style="display:inline-block; width: 20px; height:20px; background-color:<?php echo Helper::e($color['hex_code'] ?? 'transparent'); ?>; border:1px solid #ccc; margin-right: 5px; vertical-align: middle;"></span>
                    <?php echo Helper::e($color['hex_code'] ?? 'N/A'); ?>
                </dd>

                <dt class="col-sm-4">Base Color Category</dt>
                <dd class="col-sm-8"><?php echo Helper::e($color['base_color_category'] ?? 'N/A'); ?></dd>

                <dt class="col-sm-4">Created At</dt>
                <dd class="col-sm-8"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($color['created_at']))); ?></dd>

                <dt class="col-sm-4">Last Updated At</dt>
                <dd class="col-sm-8"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($color['updated_at']))); ?></dd>
            </dl>
        </div>
        <div class="col-md-4">
            <?php if (!empty($color['image_filename'])): ?>
                <h5>Color Swatch</h5>
                <img src="<?php echo Helper::e($imagePath . $color['image_filename']); ?>"
                     alt="<?php echo Helper::e($color['name']); ?> swatch" class="img-fluid rounded border" style="max-width: 200px;">
            <?php else: ?>
                <p class="text-muted">No swatch image uploaded.</p>
                <?php if(!empty($color['hex_code'])): ?>
                     <div style="width: 100px; height: 100px; background-color: <?php echo Helper::e($color['hex_code']); ?>; border: 1px solid #ccc; border-radius: 5px; margin-top:10px;" title="Color from Hex"></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>


    <div class="mt-4">
        <a href="<?php echo APP_URL; ?>/colors/edit/<?php echo Helper::e($color['id']); ?>" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>
        <a href="<?php echo APP_URL; ?>/colors" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Back to List</a>
         <form action="<?php echo APP_URL; ?>/colors/delete/<?php echo Helper::e($color['id']); ?>" method="POST" class="d-inline ms-2" onsubmit="return confirm('Are you sure you want to delete this color? This will also delete its image.');">
            <?php echo Helper::csrfInput(); ?>
            <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Delete</button>
        </form>
    </div>
<?php else: ?>
    <div class="alert alert-warning">Color not found.</div>
    <a href="<?php echo APP_URL; ?>/colors" class="btn btn-secondary">Back to List</a>
<?php endif; ?>