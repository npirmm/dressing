<?php
use App\Utils\Helper;
$isEditMode = isset($itemUser) && $itemUser !== null;

$oldFormData = $_SESSION['form_data'] ?? [];
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

$formName = $oldFormData['name'] ?? ($itemUser['name'] ?? '');
$formAbbreviation = $oldFormData['abbreviation'] ?? ($itemUser['abbreviation'] ?? '');

function display_error_iu(string $field, array $errors): string {
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
        <?php echo display_error_iu('name', $formErrors); ?>
    </div>
    <div class="mb-3">
        <label for="abbreviation" class="form-label">Abbreviation <span class="text-danger">*</span></label> <!-- {/* Ajout de * */} -->
        <input type="text" class="form-control <?php echo !empty($formErrors['abbreviation']) ? 'is-invalid' : ''; ?>"
               id="abbreviation" name="abbreviation" value="<?php echo Helper::e($formAbbreviation); ?>" required maxlength="20"> <!-- {/* Ajout de required */} -->
        <?php echo display_error_iu('abbreviation', $formErrors); ?>
    </div>
    <button type="submit" class="btn btn-success"><?php echo $isEditMode ? 'Update' : 'Create'; ?></button>
    <a href="<?php echo APP_URL; ?>/itemusers" class="btn btn-secondary">Cancel</a>
</form>