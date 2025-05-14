<?php use App\Utils\Helper; ?>

<!-- DÉBOGAGE -->
<?php  // echo "Debug: Top of brands/show.php<br>"; var_dump($brand ?? null); ?>

<h1><?php echo Helper::e($pageTitle ?? 'Brand Details'); ?></h1>

<?php if ($brand): // $brand est la variable extraite de $data ?>
    <!-- DÉBOGAGE -->
    <?php // echo "Debug: Brand data exists in view<br>"; ?>
    <dl class="row">
        <dt class="col-sm-3">ID</dt>
        <dd class="col-sm-9"><?php echo Helper::e($brand['id']); ?></dd>

        <dt class="col-sm-3">Name</dt>
        <dd class="col-sm-9"><?php echo Helper::e($brand['name']); ?></dd>

        <dt class="col-sm-3">Abbreviation</dt>
        <dd class="col-sm-9"><?php echo Helper::e($brand['abbreviation'] ?? 'N/A'); ?></dd>

        <dt class="col-sm-3">Created At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($brand['created_at']))); ?></dd>

        <dt class="col-sm-3">Last Updated At</dt>
        <dd class="col-sm-9"><?php echo Helper::e(date('Y-m-d H:i:s', strtotime($brand['updated_at']))); ?></dd>
    </dl>

    <div class="mt-4">
        <a href="<?php echo APP_URL; ?>/brands/edit/<?php echo Helper::e($brand['id']); ?>" class="btn btn-primary">
            <i class="bi bi-pencil-square"></i> Edit
        </a>
        <a href="<?php echo APP_URL; ?>/brands" class="btn btn-secondary">
            <i class="bi bi-arrow-left-circle"></i> Back to List
        </a>
        <form action="<?php echo APP_URL; ?>/brands/delete/<?php echo Helper::e($brand['id']); ?>" method="POST" class="d-inline ms-2" onsubmit="return confirm('Are you sure you want to delete this brand?');">
            <?php echo Helper::csrfInput(); ?>
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash"></i> Delete
            </button>
        </form>
    </div>
<?php else: ?>
    <!-- DÉBOGAGE -->
    <?php // echo "Debug: Brand data does NOT exist in view, or is false/null.<br>"; ?>
    <div class="alert alert-warning">Brand not found.</div>
    <a href="<?php echo APP_URL; ?>/brands" class="btn btn-secondary">Back to List</a>
<?php endif; ?>
<!-- DÉBOGAGE -->
<?php // echo "Debug: Bottom of brands/show.php<br>"; ?>