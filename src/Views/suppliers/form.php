<?php
use App\Utils\Helper;
$isEditMode = isset($supplier) && $supplier !== null;

$oldFormData = $_SESSION['form_data'] ?? [];
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

$formName = $oldFormData['name'] ?? ($supplier['name'] ?? '');
$formContact = $oldFormData['contact_person'] ?? ($supplier['contact_person'] ?? '');
$formEmail = $oldFormData['email'] ?? ($supplier['email'] ?? '');
$formPhone = $oldFormData['phone'] ?? ($supplier['phone'] ?? '');
$formAddress = $oldFormData['address'] ?? ($supplier['address'] ?? '');
$formNotes = $oldFormData['notes'] ?? ($supplier['notes'] ?? '');

function display_error_sup(string $field, array $errors): string {
    if (!empty($errors[$field])) {
        return '<div class="invalid-feedback d-block">' . Helper::e($errors[$field][0]) . '</div>';
    }
    return '';
}
?>
<h1><?php echo Helper::e($pageTitle); ?></h1>
<form action="<?php echo Helper::e($formAction); ?>" method="POST" novalidate>
    <?php echo Helper::csrfInput(); ?>
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="name" class="form-label">Supplier Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?php echo !empty($formErrors['name']) ? 'is-invalid' : ''; ?>"
                       id="name" name="name" value="<?php echo Helper::e($formName); ?>" required>
                <?php echo display_error_sup('name', $formErrors); ?>
            </div>
            <div class="mb-3">
                <label for="contact_person" class="form-label">Contact Person</label>
                <input type="text" class="form-control <?php echo !empty($formErrors['contact_person']) ? 'is-invalid' : ''; ?>"
                       id="contact_person" name="contact_person" value="<?php echo Helper::e($formContact); ?>">
                <?php echo display_error_sup('contact_person', $formErrors); ?>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control <?php echo !empty($formErrors['email']) ? 'is-invalid' : ''; ?>"
                       id="email" name="email" value="<?php echo Helper::e($formEmail); ?>">
                <?php echo display_error_sup('email', $formErrors); ?>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Phone</label>
                <input type="tel" class="form-control <?php echo !empty($formErrors['phone']) ? 'is-invalid' : ''; ?>"
                       id="phone" name="phone" value="<?php echo Helper::e($formPhone); ?>">
                <?php echo display_error_sup('phone', $formErrors); ?>
            </div>
        </div>
    </div>
    <div class="mb-3">
        <label for="address" class="form-label">Address</label>
        <textarea class="form-control <?php echo !empty($formErrors['address']) ? 'is-invalid' : ''; ?>"
                  id="address" name="address" rows="3"><?php echo Helper::e($formAddress); ?></textarea>
        <?php echo display_error_sup('address', $formErrors); ?>
    </div>
    <div class="mb-3">
        <label for="notes" class="form-label">Notes</label>
        <textarea class="form-control <?php echo !empty($formErrors['notes']) ? 'is-invalid' : ''; ?>"
                  id="notes" name="notes" rows="3"><?php echo Helper::e($formNotes); ?></textarea>
        <?php echo display_error_sup('notes', $formErrors); ?>
    </div>

    <button type="submit" class="btn btn-success"><?php echo $isEditMode ? 'Update' : 'Create'; ?></button>
    <a href="<?php echo APP_URL; ?>/suppliers" class="btn btn-secondary">Cancel</a>
</form>