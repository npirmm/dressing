<?php
use App\Utils\Helper;
$isEditMode = isset($eventType) && $eventType !== null;

$oldFormData = $_SESSION['form_data'] ?? [];
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

$formName = $oldFormData['name'] ?? ($eventType['name'] ?? '');
$formDescription = $oldFormData['description'] ?? ($eventType['description'] ?? '');
// Pour les checkboxes, on utilise $selectedDayMomentIds qui est passé par le contrôleur
// et on vérifie aussi $oldFormData['day_moment_ids'] si le formulaire a été resoumis avec une erreur.
$idsToPreselect = $oldFormData['day_moment_ids'] ?? ($selectedDayMomentIds ?? []);

function display_error_et(string $field, array $errors): string {
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
        <label for="name" class="form-label">Event Type Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control <?php echo !empty($formErrors['name']) ? 'is-invalid' : ''; ?>"
               id="name" name="name" value="<?php echo Helper::e($formName); ?>" required>
        <?php echo display_error_et('name', $formErrors); ?>
    </div>
    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control <?php echo !empty($formErrors['description']) ? 'is-invalid' : ''; ?>"
                  id="description" name="description" rows="3"><?php echo Helper::e($formDescription); ?></textarea>
        <?php echo display_error_et('description', $formErrors); ?>
    </div>

    <div class="mb-3">
        <label class="form-label">Typical Day Moments <small class="text-muted">(select one or more)</small></label>
        <?php if (!empty($formErrors['day_moment_ids'])): ?>
             <?php echo display_error_et('day_moment_ids', $formErrors); ?>
        <?php endif; ?>
        <div class="row">
            <?php if (!empty($allDayMoments)): ?>
                <?php foreach ($allDayMoments as $moment): ?>
                    <div class="col-md-3 col-sm-4 col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="day_moment_ids[]" 
                                   value="<?php echo Helper::e($moment['id']); ?>" 
                                   id="moment_<?php echo Helper::e($moment['id']); ?>"
                                   <?php echo in_array($moment['id'], $idsToPreselect) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="moment_<?php echo Helper::e($moment['id']); ?>">
                                <?php echo Helper::e(ucfirst($moment['name'])); ?>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No day moments defined in the system.</p>
            <?php endif; ?>
        </div>
    </div>

    <button type="submit" class="btn btn-success"><?php echo $isEditMode ? 'Update' : 'Create'; ?></button>
    <a href="<?php echo APP_URL; ?>/eventtypes" class="btn btn-secondary">Cancel</a>
</form>