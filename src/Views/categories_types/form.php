<?php
use App\Utils\Helper;
$isEditMode = isset($categoryType) && $categoryType !== null;

$oldFormData = $_SESSION['form_data'] ?? [];
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

$formName = $oldFormData['name'] ?? ($categoryType['name'] ?? '');
$formCategory = $oldFormData['category'] ?? ($categoryType['category'] ?? '');
$formCode = $oldFormData['code'] ?? ($categoryType['code'] ?? '');

function display_error_ct(string $field, array $errors): string {
    if (!empty($errors[$field])) {
        return '<div class="invalid-feedback d-block">' . Helper::e($errors[$field][0]) . '</div>';
    }
    return '';
}
?>
<h1><?php echo Helper::e($pageTitle); ?></h1>
<form action="<?php echo Helper::e($formAction); ?>" method="POST" novalidate>
    <?php echo Helper::csrfInput(); ?>
    <div class="mb-3">
        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control <?php echo !empty($formErrors['name']) ? 'is-invalid' : ''; ?>"
               id="name" name="name" value="<?php echo Helper::e($formName); ?>" required>
        <?php echo display_error_ct('name', $formErrors); ?>
    </div>
    <div class="mb-3">
        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
        <select class="form-select <?php echo !empty($formErrors['category']) ? 'is-invalid' : ''; ?>" 
                id="category" name="category" required>
            <option value="">Select a category...</option>
            <?php foreach ($availableCategories as $cat): ?>
                <option value="<?php echo Helper::e($cat); ?>" <?php echo ($formCategory === $cat) ? 'selected' : ''; ?>>
                    <?php echo Helper::e(ucfirst($cat)); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php echo display_error_ct('category', $formErrors); ?>
    </div>
    <div class="mb-3">
        <label for="code" class="form-label">Code (3-5 chars, unique) <span class="text-danger">*</span></label>
        <input type="text" class="form-control <?php echo !empty($formErrors['code']) ? 'is-invalid' : ''; ?>"
               id="code" name="code" value="<?php echo Helper::e($formCode); ?>" required maxlength="5" style="text-transform: uppercase;">
        <?php echo display_error_ct('code', $formErrors); ?>
        <small class="form-text text-muted">Example: VRO for Robe (Vêtement), BBA for Bague (Bijou).</small>
    </div>
    <button type="submit" class="btn btn-success"><?php echo $isEditMode ? 'Update' : 'Create'; ?></button>
    <a href="<?php echo APP_URL; ?>/categorytypes" class="btn btn-secondary">Cancel</a>
</form>