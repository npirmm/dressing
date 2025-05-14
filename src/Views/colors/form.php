<?php
use App\Utils\Helper;
$isEditMode = isset($color) && $color !== null;

$oldFormData = $_SESSION['form_data'] ?? [];
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

$formName = $oldFormData['name'] ?? ($color['name'] ?? '');
$formHexCode = $oldFormData['hex_code'] ?? ($color['hex_code'] ?? '');
$formBaseCategory = $oldFormData['base_color_category'] ?? ($color['base_color_category'] ?? '');
$currentImage = $color['image_filename'] ?? null;

function display_error_colors(string $field, array $errors): string {
    if (!empty($errors[$field])) {
        $message = is_array($errors[$field]) ? $errors[$field][0] : $errors[$field];
        return '<div class="invalid-feedback d-block">' . Helper::e($message) . '</div>';
    }
    return '';
}
?>

<h1><?php echo Helper::e($pageTitle); ?></h1>

<form action="<?php echo Helper::e($formAction); ?>" method="POST" enctype="multipart/form-data" novalidate>
    <?php echo Helper::csrfInput(); ?>

    <div class="row">
        <div class="col-md-8">
            <div class="mb-3">
                <label for="name" class="form-label">Color Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?php echo !empty($formErrors['name']) ? 'is-invalid' : ''; ?>"
                       id="name" name="name" value="<?php echo Helper::e($formName); ?>" required>
                <?php echo display_error_colors('name', $formErrors); ?>
            </div>

            <div class="mb-3">
                <label for="hex_code" class="form-label">Hex Code (e.g., #RRGGBB)</label>
                <input type="text" class="form-control <?php echo !empty($formErrors['hex_code']) ? 'is-invalid' : ''; ?>"
                       id="hex_code" name="hex_code" value="<?php echo Helper::e($formHexCode); ?>" pattern="#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})">
                <?php echo display_error_colors('hex_code', $formErrors); ?>
            </div>

            <div class="mb-3">
                <label for="base_color_category" class="form-label">Base Color Category (e.g., Red, Blue, Neutral)</label>
                <input type="text" class="form-control <?php echo !empty($formErrors['base_color_category']) ? 'is-invalid' : ''; ?>"
                       id="base_color_category" name="base_color_category" value="<?php echo Helper::e($formBaseCategory); ?>">
                <?php echo display_error_colors('base_color_category', $formErrors); ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="image_filename" class="form-label">Color Swatch Image</label>
                <input type="file" class="form-control <?php echo !empty($formErrors['image_filename']) ? 'is-invalid' : ''; ?>"
                       id="image_filename" name="image_filename" accept="image/png, image/jpeg, image/gif, image/webp">
                <?php echo display_error_colors('image_filename', $formErrors); ?>
                 <small class="form-text text-muted">Max 5MB. Allowed: JPG, PNG, GIF, WEBP.</small>
            </div>
            <?php if ($isEditMode && $currentImage): ?>
                <div class="mb-2">
                    <p>Current Image:</p>
                    <img src="<?php echo Helper::e($imagePath . $currentImage); ?>" alt="Current color image" style="max-width: 100px; max-height: 100px; border:1px solid #ccc;">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" value="1" id="remove_image" name="remove_image">
                        <label class="form-check-label" for="remove_image">
                            Remove current image
                        </label>
                    </div>
                </div>
            <?php elseif ($isEditMode): ?>
                 <p class="text-muted">No current image.</p>
            <?php endif; ?>
        </div>
    </div>

    <button type="submit" class="btn btn-success">
        <i class="bi <?php echo $isEditMode ? 'bi-check-circle-fill' : 'bi-plus-circle-fill'; ?>"></i>
        <?php echo $isEditMode ? 'Update Color' : 'Create Color'; ?>
    </button>
    <a href="<?php echo APP_URL; ?>/colors" class="btn btn-secondary">
        <i class="bi bi-x-circle"></i> Cancel
    </a>
</form>