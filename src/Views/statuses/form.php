<?php
use App\Utils\Helper;
$isEditMode = isset($status) && $status !== null;

$oldFormData = $_SESSION['form_data'] ?? [];
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

$formName = $oldFormData['name'] ?? ($status['name'] ?? '');
$formAvailability = $oldFormData['availability_type'] ?? ($status['availability_type'] ?? '');
$formDescription = $oldFormData['description'] ?? ($status['description'] ?? '');

function display_error_status(string $field, array $errors): string {
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
        <label for="name" class="form-label">Status Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control <?php echo !empty($formErrors['name']) ? 'is-invalid' : ''; ?>"
               id="name" name="name" value="<?php echo Helper::e($formName); ?>" required>
        <?php echo display_error_status('name', $formErrors); ?>
    </div>
    <div class="mb-3">
        <label for="availability_type" class="form-label">Availability Type <span class="text-danger">*</span></label>
        <select class="form-select <?php echo !empty($formErrors['availability_type']) ? 'is-invalid' : ''; ?>" 
                id="availability_type" name="availability_type" required>
            <option value="">Select...</option>
            <?php foreach ($availabilityTypes as $type): ?>
                <option value="<?php echo Helper::e($type); ?>" <?php echo ($formAvailability === $type) ? 'selected' : ''; ?>>
                    <?php echo Helper::e(str_replace('_', ' ', ucfirst($type))); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php echo display_error_status('availability_type', $formErrors); ?>
    </div>
    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <input type="text" class="form-control <?php echo !empty($formErrors['description']) ? 'is-invalid' : ''; ?>"
               id="description" name="description" value="<?php echo Helper::e($formDescription); ?>" maxlength="255">
        <?php echo display_error_status('description', $formErrors); ?>
    </div>
    <button type="submit" class="btn btn-success"><?php echo $isEditMode ? 'Update' : 'Create'; ?></button>
    <a href="<?php echo APP_URL; ?>/statuses" class="btn btn-secondary">Cancel</a>
</form>