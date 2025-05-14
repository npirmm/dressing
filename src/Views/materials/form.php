<?php
use App\Utils\Helper;
$isEditMode = isset($material) && $material !== null;

$oldFormData = $_SESSION['form_data'] ?? [];
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

$formName = $oldFormData['name'] ?? ($material['name'] ?? '');

function display_error_material(string $field, array $errors): string { // Renommer la fonction pour éviter conflit si incluse plusieurs fois
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
        <label for="name" class="form-label">Material Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control <?php echo !empty($formErrors['name']) ? 'is-invalid' : ''; ?>" 
               id="name" name="name" value="<?php echo Helper::e($formName); ?>" required>
        <?php echo display_error_material('name', $formErrors); ?>
    </div>

    <button type="submit" class="btn btn-success">
        <i class="bi <?php echo $isEditMode ? 'bi-check-circle-fill' : 'bi-plus-circle-fill'; ?>"></i>
        <?php echo $isEditMode ? 'Update Material' : 'Create Material'; ?>
    </button>
    <a href="<?php echo APP_URL; ?>/materials" class="btn btn-secondary">
        <i class="bi bi-x-circle"></i> Cancel
    </a>
</form>