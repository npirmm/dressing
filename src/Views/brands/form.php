<?php
use App\Utils\Helper;
$isEditMode = isset($brand) && $brand !== null;

// Retrieve form data from session if it exists (e.g., after a validation error)
$oldFormData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']); // Clear it after use

// Use old form data if available, otherwise use brand data (for edit) or empty
$formName = $oldFormData['name'] ?? ($brand['name'] ?? '');
$formAbbreviation = $oldFormData['abbreviation'] ?? ($brand['abbreviation'] ?? '');
?>

<h1><?php echo Helper::e($pageTitle); ?></h1>

<!-- Les messages flash (danger, success, info) sont déjà gérés dans layouts/main.php -->

<form action="<?php echo Helper::e($formAction); ?>" method="POST">
    <?php echo Helper::csrfInput(); ?>

    <div class="mb-3">
        <label for="name" class="form-label">Brand Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name"
               value="<?php echo Helper::e($formName); ?>" required>
        <!-- You can add more specific error messages below the field if needed -->
    </div>

    <div class="mb-3">
        <label for="abbreviation" class="form-label">Abbreviation</label>
        <input type="text" class="form-control" id="abbreviation" name="abbreviation"
               value="<?php echo Helper::e($formAbbreviation); ?>">
    </div>

    <button type="submit" class="btn btn-success">
        <i class="bi <?php echo $isEditMode ? 'bi-check-circle-fill' : 'bi-plus-circle-fill'; ?>"></i>
        <?php echo $isEditMode ? 'Update Brand' : 'Create Brand'; ?>
    </button>
    <a href="<?php echo APP_URL; ?>/brands" class="btn btn-secondary">
        <i class="bi bi-x-circle"></i> Cancel
    </a>
</form>