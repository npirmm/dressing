<?php
use App\Utils\Helper;


$oldFormData = $_SESSION['form_data_event'] ?? [];
$formErrors = $_SESSION['form_errors_event'] ?? [];
unset($_SESSION['form_data_event'], $_SESSION['form_errors_event']);

$f_new_status_id = $oldFormData['new_status_id'] ?? '';
$f_log_date = $oldFormData['log_date'] ?? date('Y-m-d');
$f_log_time = $oldFormData['log_time'] ?? ''; // Heure optionnelle

// Champs conditionnels
$f_event_type_id = $oldFormData['event_type_id_event'] ?? '';
$f_event_name = $oldFormData['event_name_event'] ?? '';
$f_description_event = $oldFormData['description_event'] ?? '';
$f_item_user_id = $oldFormData['item_user_id_event'] ?? '';
$f_related_supplier_id = $oldFormData['related_supplier_id_event'] ?? '';
$f_cost_associated = $oldFormData['cost_associated_event'] ?? '';
$f_currency = $oldFormData['currency_event'] ?? 'EUR';
$f_storage_location_id = $oldFormData['storage_location_id_event'] ?? '';

// Champs pour événement groupé
$f_create_new_grouped_event_name = $oldFormData['create_new_grouped_event_name'] ?? '';
$f_grouped_event_notes = $oldFormData['grouped_event_notes'] ?? '';
// $f_link_to_grouped_event_id = $oldFormData['link_to_grouped_event_id'] ?? ''; // Pour lier à un existant

// Pour la fonction display_field_error, assurez-vous qu'elle est disponible
// (soit définie ici, soit dans Helper.php et appelée via Helper::displayFieldError)
if (!function_exists('display_field_error_event')) {
    function display_field_error_event(string $field, array $errors): string {
        if (!empty($errors[$field])) {
            $message = is_array($errors[$field]) ? $errors[$field][0] : $errors[$field];
            return '<div class="invalid-feedback d-block">' . Helper::e($message) . '</div>';
        }
        return '';
    }
}
$isStillNewPurchase = ($article['status_name'] === 'Acheté (Nouveau)'); // Ou comparez les IDs
?>
<h1><?php echo Helper::e($pageTitle); ?></h1>
<div class="card mb-3">
    <div class="card-header">
        Article: <strong><?php echo Helper::e($article['name']); ?></strong> (Ref: <?php echo Helper::e($article['article_ref']); ?>)
    </div>
    <div class="card-body">
        <p class="card-text mb-1">Current Status: <strong><?php echo Helper::e($article['status_name']); ?></strong></p>
        <?php if(!empty($article['storage_location_full_path'])): ?>
            <p class="card-text">Current Location: <?php echo Helper::e($article['storage_location_full_path']); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($availableStatuses)): ?>
    <div class="alert alert-warning">
        No further statuses available to transition to from "<?php echo Helper::e($article['status_name']); ?>".
        This might indicate a configuration issue or that the article is in a final state.
        You can <a href="<?php echo APP_URL; ?>/articles/show/<?php echo $article['id']; ?>">return to the article details</a>.
    </div>
<?php else: ?>

<form action="<?php echo Helper::e($formAction); ?>" method="POST" enctype="multipart/form-data" novalidate>
    <?php echo Helper::csrfInput(); ?>

    <fieldset class="mb-3">
            <legend class="h5">Core Event Information</legend>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="new_status_id" class="form-label">New Status for Article <span class="text-danger">*</span></label>
                    <select class="form-select <?php echo !empty($formErrors['new_status_id']) ? 'is-invalid' : ''; ?>" 
                            id="new_status_id" name="new_status_id" required>
                        <option value="">Select new status...</option>
                        <?php foreach ($availableStatuses as $statusOpt): // Utilise la liste filtrée ?>
                            <option value="<?php echo $statusOpt['id']; ?>" data-status-name="<?php echo Helper::e(strtolower($statusOpt['name'])); ?>" <?php echo ($f_new_status_id == $statusOpt['id']) ? 'selected' : ''; ?>>
                                <?php echo Helper::e($statusOpt['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php echo display_field_error_event('new_status_id', $formErrors); ?>
                </div>
            <div class="col-md-4 mb-3">
                <label for="log_date" class="form-label">Date of Event/Status Change <span class="text-danger">*</span></label>
                <input type="date" class="form-control <?php echo !empty($formErrors['log_date']) ? 'is-invalid' : ''; ?>"
                       id="log_date" name="log_date" value="<?php echo Helper::e($f_log_date); ?>" required>
                <?php echo display_field_error_event('log_date', $formErrors); ?>
            </div>
            <div class="col-md-4 mb-3">
                <label for="log_time" class="form-label">Time (Optional)</label>
                <input type="time" class="form-control <?php echo !empty($formErrors['log_time']) ? 'is-invalid' : ''; ?>"
                       id="log_time" name="log_time" value="<?php echo Helper::e($f_log_time); ?>">
                <?php echo display_field_error_event('log_time', $formErrors); ?>
            </div>
        </div>
    </fieldset>

    <!-- {/* Champs conditionnels pour "En cours d'utilisation" */} -->
    <fieldset class="mb-3 conditional-fields" id="usageFields" style="display: none;">
        <legend class="h5">Usage Details</legend>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="event_type_id_event" class="form-label">Type of Event <span class="text-danger">*</span></label>
                <select class="form-select <?php echo !empty($formErrors['event_type_id_event']) ? 'is-invalid' : ''; ?>" 
                        id="event_type_id_event" name="event_type_id_event">
                    <option value="">Select event type...</option>
                    <?php foreach ($allEventTypes as $et): ?>
                        <option value="<?php echo $et['id']; ?>" <?php echo ($f_event_type_id == $et['id']) ? 'selected' : ''; ?>>
                            <?php echo Helper::e($et['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php echo display_field_error_event('event_type_id_event', $formErrors); ?>
            </div>
            <div class="col-md-6 mb-3">
                <label for="item_user_id_event" class="form-label">Used By <span class="text-danger">*</span></label>
                <select class="form-select <?php echo !empty($formErrors['item_user_id_event']) ? 'is-invalid' : ''; ?>" 
                        id="item_user_id_event" name="item_user_id_event">
                    <option value="">Select user...</option>
                    <?php foreach ($allItemUsers as $iu): ?>
                        <option value="<?php echo $iu['id']; ?>" <?php echo ($f_item_user_id == $iu['id']) ? 'selected' : ''; ?>>
                            <?php echo Helper::e($iu['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php echo display_field_error_event('item_user_id_event', $formErrors); ?>
            </div>
        </div>
    </fieldset>

    <!-- {/* Champs conditionnels pour "Nettoyage", "Réparation", "Vendu" */} -->
    <fieldset class="mb-3 conditional-fields" id="serviceSupplierFields" style="display: none;">
        <legend class="h5">Service/Supplier Details</legend>
        <div class="row">
            <div class="col-md-8 mb-3">
                <label for="related_supplier_id_event" class="form-label">Supplier</label>
                <select class="form-select <?php echo !empty($formErrors['related_supplier_id_event']) ? 'is-invalid' : ''; ?>" 
                        id="related_supplier_id_event" name="related_supplier_id_event">
                    <option value="">Select supplier...</option>
                    <?php foreach ($allSuppliers as $sup): ?>
                        <option value="<?php echo $sup['id']; ?>" <?php echo ($f_related_supplier_id == $sup['id']) ? 'selected' : ''; ?>>
                            <?php echo Helper::e($sup['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php echo display_field_error_event('related_supplier_id_event', $formErrors); ?>
            </div>
            <div class="col-md-2 mb-3">
                <label for="cost_associated_event" class="form-label">Cost/Price</label>
                <input type="number" step="0.01" class="form-control <?php echo !empty($formErrors['cost_associated_event']) ? 'is-invalid' : ''; ?>"
                       id="cost_associated_event" name="cost_associated_event" value="<?php echo Helper::e($f_cost_associated); ?>">
                <?php echo display_field_error_event('cost_associated_event', $formErrors); ?>
            </div>
             <div class="col-md-2 mb-3">
                <label for="currency_event" class="form-label">Currency</label>
                <input type="text" class="form-control" id="currency_event" name="currency_event" value="<?php echo Helper::e($f_currency); ?>" maxlength="3">
            </div>
        </div>
    </fieldset>

    <!-- {/* Champ conditionnel pour "Disponible et rangé" */} -->
    <fieldset class="mb-3 conditional-fields" id="storageLocationEventSection" style="display: none;">
        <legend class="h5">Storage</legend>
        <div class="mb-3">
            <label for="storage_location_id_event" class="form-label">Storage Location <span class="text-danger">*</span></label>
            <select class="form-select <?php echo !empty($formErrors['storage_location_id_event']) ? 'is-invalid' : ''; ?>" 
                    id="storage_location_id_event" name="storage_location_id_event"> <!-- {/* Pas required ici, géré par JS/Contrôleur */} -->
                <option value="">Select location...</option>
                <?php foreach ($allStorageLocations as $loc): ?>
                    <option value="<?php echo $loc['id']; ?>" <?php echo ($f_storage_location_id == $loc['id']) ? 'selected' : ''; ?>>
                        <?php echo Helper::e($loc['full_location_path']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php echo display_field_error_event('storage_location_id_event', $formErrors); ?>
        </div>
    </fieldset>

    <!-- {/* Champs communs à plusieurs statuts/événements */} -->
    <fieldset class="mb-3">
        <legend class="h5">General Event Details</legend>
        <div class="mb-3">
            <label for="event_name_event" class="form-label">Event Name / Short Note</label>
            <input type="text" class="form-control <?php echo !empty($formErrors['event_name_event']) ? 'is-invalid' : ''; ?>"
                   id="event_name_event" name="event_name_event" value="<?php echo Helper::e($f_event_name); ?>" maxlength="150">
            <?php echo display_field_error_event('event_name_event', $formErrors); ?>
        </div>
        <div class="mb-3">
            <label for="description_event" class="form-label">Event Description / Details</label>
            <textarea class="form-control <?php echo !empty($formErrors['description_event']) ? 'is-invalid' : ''; ?>"
                      id="description_event" name="description_event" rows="3"><?php echo Helper::e($f_description_event); ?></textarea>
            <?php echo display_field_error_event('description_event', $formErrors); ?>
        </div>
        <div class="mb-3">
            <label for="event_images" class="form-label">Event Images</label>
            <input type="file" class="form-control" id="event_images" name="event_images[]" multiple accept="image/*">
            <small class="form-text text-muted">Add photos related to this event (e.g., you wearing the item, item at the cleaner's).</small>
             <?php // Affichage des erreurs d'upload d'image d'événement si on les gère via formErrors ?>
        </div>
    </fieldset>
    
    <!-- {/* Section pour "Grouped Event" - Simplifié */} -->
    <fieldset class="mb-3 conditional-fields" id="groupedEventFields" style="display:none;">
        <legend class="h5">Group with Other Articles (for this usage event)</legend>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="create_new_grouped_event_checkbox" id="createNewGroupedEventCheckbox" value="1">
            <label class="form-check-label" for="createNewGroupedEventCheckbox">
                Create a new grouped event for this occasion (e.g., "Gala Dinner Outfit")
            </label>
        </div>
        <div id="newGroupedEventNameSection" style="display:none;">
            <div class="mb-3">
                <label for="create_new_grouped_event_name" class="form-label">Grouped Event Name</label>
                <input type="text" class="form-control" name="create_new_grouped_event_name" id="create_new_grouped_event_name" value="<?php echo Helper::e($f_create_new_grouped_event_name); ?>">
            </div>
             <div class="mb-3">
                <label for="grouped_event_notes" class="form-label">Notes for Grouped Event</label>
                <textarea name="grouped_event_notes" id="grouped_event_notes" class="form-control"><?php echo Helper::e($f_grouped_event_notes); ?></textarea>
            </div>
        </div>
        <!-- {/* TODO: Ajouter une liste déroulante pour lier à un grouped_event EXISTANT */} -->
    </fieldset>


	<hr>
	<button type="submit" class="btn btn-primary">Log Event & Update Article</button>
	<?php $isStillNewPurchase = (defined('STATUS_ID_NEW_PURCHASE') && $article['current_status_id'] == STATUS_ID_NEW_PURCHASE); ?>
	<?php if ($isStillNewPurchase): ?>
		<a href="<?php echo APP_URL; ?>/articles/show/<?php echo $article['id']; ?>" class="btn btn-outline-secondary">
			Skip for Now (Keep as "<?php echo Helper::e($article['status_name']); ?>")
		</a>
	<?php else: ?>
		<a href="<?php echo APP_URL; ?>/articles/show/<?php echo $article['id']; ?>" class="btn btn-secondary">Cancel</a>
	<?php endif; ?>
</form>
<?php endif; ?>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('new_status_id');
    
    const usageFields = document.getElementById('usageFields');
    const serviceSupplierFields = document.getElementById('serviceSupplierFields');
    const storageLocationEventSection = document.getElementById('storageLocationEventSection');
    const groupedEventFields = document.getElementById('groupedEventFields'); // Pour les événements groupés
    const createNewGroupedEventCheckbox = document.getElementById('createNewGroupedEventCheckbox');
    const newGroupedEventNameSection = document.getElementById('newGroupedEventNameSection');


    // Noms de statut (en minuscules) qui déclenchent certaines sections
    const usageStatusNames = ['utilisation', 'porté']; // Ajoutez d'autres synonymes si nécessaire
    const serviceStatusNames = ['nettoyage', 'réparation', 'vendu'];
    const storageStatusNames = ['disponible et rangé'];

    function updateConditionalFields() {
        const selectedOption = statusSelect.options[statusSelect.selectedIndex];
        const selectedStatusNameLower = selectedOption ? (selectedOption.dataset.statusName || '').toLowerCase() : '';

        let showUsage = false;
        usageStatusNames.forEach(name => {
            if (selectedStatusNameLower.includes(name)) showUsage = true;
        });
        usageFields.style.display = showUsage ? 'block' : 'none';
        document.getElementById('event_type_id_event').required = showUsage;
        document.getElementById('item_user_id_event').required = showUsage;
        groupedEventFields.style.display = showUsage ? 'block' : 'none'; // Afficher pour les statuts d'utilisation


        let showService = false;
        serviceStatusNames.forEach(name => {
            if (selectedStatusNameLower.includes(name)) showService = true;
        });
        serviceSupplierFields.style.display = showService ? 'block' : 'none';

        let showStorage = false;
        storageStatusNames.forEach(name => {
            if (selectedStatusNameLower.includes(name)) showStorage = true;
        });
        storageLocationEventSection.style.display = showStorage ? 'block' : 'none';
        document.getElementById('storage_location_id_event').required = showStorage;
    }
    
    function toggleNewGroupedEventName() {
        if (createNewGroupedEventCheckbox && newGroupedEventNameSection) {
            newGroupedEventNameSection.style.display = createNewGroupedEventCheckbox.checked ? 'block' : 'none';
            document.getElementById('create_new_grouped_event_name').required = createNewGroupedEventCheckbox.checked;
        }
    }

    if (statusSelect) {
        statusSelect.addEventListener('change', updateConditionalFields);
        updateConditionalFields(); // Appel initial
    }
    if (createNewGroupedEventCheckbox) {
        createNewGroupedEventCheckbox.addEventListener('change', toggleNewGroupedEventName);
        toggleNewGroupedEventName(); // Appel initial
    }
});
</script>