<?php
use App\Utils\Helper;
$isEditMode = isset($brand) && $brand !== null; // $brand vient du controller pour le mode édition

// Récupérer les anciennes données du formulaire et les erreurs depuis la session
$oldFormData = $_SESSION['form_data'] ?? [];
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']); // Nettoyer après usage

// Priorité aux anciennes données, puis aux données du modèle (édition), puis vide
$formName = $oldFormData['name'] ?? ($brand['name'] ?? '');
$formAbbreviation = $oldFormData['abbreviation'] ?? ($brand['abbreviation'] ?? '');

/**
 * Helper function to display error for a field
 * @param string $field
 * @param array $errors
 * @return string
 */
function display_error(string $field, array $errors): string {
    if (!empty($errors[$field])) {
        return '<div class="invalid-feedback d-block">' . Helper::e($errors[$field][0]) . '</div>';
    }
    return '';
}
?>

<h1><?php echo Helper::e($pageTitle); ?></h1>

<!-- Les messages flash globaux sont dans layouts/main.php -->

<form action="<?php echo Helper::e($formAction); ?>" method="POST" novalidate> <!-- novalidate pour voir nos erreurs serveur -->
    <?php echo Helper::csrfInput(); ?>

    <div class="mb-3">
        <label for="name" class="form-label">Brand Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control <?php echo !empty($formErrors['name']) ? 'is-invalid' : ''; ?>" 
               id="name" name="name" value="<?php echo Helper::e($formName); ?>" required>
        <?php echo display_error('name', $formErrors); ?>
    </div>

    <div class="mb-3">
        <label for="abbreviation" class="form-label">Abbreviation</label>
        <input type="text" class="form-control <?php echo !empty($formErrors['abbreviation']) ? 'is-invalid' : ''; ?>" 
               id="abbreviation" name="abbreviation" value="<?php echo Helper::e($formAbbreviation); ?>">
        <?php echo display_error('abbreviation', $formErrors); ?>
    </div>

    <button type="submit" class="btn btn-success">
        <i class="bi <?php echo $isEditMode ? 'bi-check-circle-fill' : 'bi-plus-circle-fill'; ?>"></i>
        <?php echo $isEditMode ? 'Update Brand' : 'Create Brand'; ?>
    </button>
    <a href="<?php echo APP_URL; ?>/brands" class="btn btn-secondary">
        <i class="bi bi-x-circle"></i> Cancel
    </a>
</form>