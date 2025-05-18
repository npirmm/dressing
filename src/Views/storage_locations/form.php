<?php
use App\Utils\Helper;
$isEditMode = isset($storageLocation) && $storageLocation !== null;

$oldFormData = $_SESSION['form_data'] ?? [];
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

$formRoom = $oldFormData['room'] ?? ($storageLocation['room'] ?? '');
$formArea = $oldFormData['area'] ?? ($storageLocation['area'] ?? '');
$formShelf = $oldFormData['shelf_or_rack'] ?? ($storageLocation['shelf_or_rack'] ?? '');
$formLevel = $oldFormData['level_or_section'] ?? ($storageLocation['level_or_section'] ?? '');
$formSpot = $oldFormData['specific_spot_or_box'] ?? ($storageLocation['specific_spot_or_box'] ?? '');

function display_error_sl(string $field, array $errors): string {
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
        <div class="col-md-6 mb-3">
            <label for="room" class="form-label">Room <span class="text-danger">*</span></label>
            <input type="text" class="form-control <?php echo !empty($formErrors['room']) ? 'is-invalid' : ''; ?>"
                   id="room" name="room" value="<?php echo Helper::e($formRoom); ?>" required>
            <?php echo display_error_sl('room', $formErrors); ?>
        </div>
        <div class="col-md-6 mb-3">
            <label for="area" class="form-label">Area/Closet</label>
            <input type="text" class="form-control <?php echo !empty($formErrors['area']) ? 'is-invalid' : ''; ?>"
                   id="area" name="area" value="<?php echo Helper::e($formArea); ?>">
            <?php echo display_error_sl('area', $formErrors); ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="shelf_or_rack" class="form-label">Shelf/Rack/Dresser</label>
            <input type="text" class="form-control <?php echo !empty($formErrors['shelf_or_rack']) ? 'is-invalid' : ''; ?>"
                   id="shelf_or_rack" name="shelf_or_rack" value="<?php echo Helper::e($formShelf); ?>">
            <?php echo display_error_sl('shelf_or_rack', $formErrors); ?>
        </div>
        <div class="col-md-6 mb-3">
            <label for="level_or_section" class="form-label">Level/Drawer/Section</label>
            <input type="text" class="form-control <?php echo !empty($formErrors['level_or_section']) ? 'is-invalid' : ''; ?>"
                   id="level_or_section" name="level_or_section" value="<?php echo Helper::e($formLevel); ?>">
            <?php echo display_error_sl('level_or_section', $formErrors); ?>
        </div>
    </div>
    <div class="mb-3">
        <label for="specific_spot_or_box" class="form-label">Specific Spot/Box/Hanger</label>
        <input type="text" class="form-control <?php echo !empty($formErrors['specific_spot_or_box']) ? 'is-invalid' : ''; ?>"
               id="specific_spot_or_box" name="specific_spot_or_box" value="<?php echo Helper::e($formSpot); ?>">
        <?php echo display_error_sl('specific_spot_or_box', $formErrors); ?>
    </div>

    <button type="submit" class="btn btn-success"><?php echo $isEditMode ? 'Update' : 'Create'; ?></button>
    <a href="<?php echo APP_URL; ?>/storagelocations" class="btn btn-secondary">Cancel</a>
</form>