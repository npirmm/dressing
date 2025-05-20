<?php
use App\Utils\Helper;
$isEditMode = isset($article) && $article !== null;

// Récupérer les anciennes données du formulaire et les erreurs depuis la session
$oldFormData = $_SESSION['form_data'] ?? [];
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);

// Fonction pour afficher les erreurs (peut être mise dans Helper.php)
if (!function_exists('display_field_error')) { // Évite redéfinition si inclus ailleurs
    function display_field_error(string $field, array $errors): string {
        if (!empty($errors[$field])) {
            $message = is_array($errors[$field]) ? $errors[$field][0] : $errors[$field];
            return '<div class="invalid-feedback d-block">' . Helper::e($message) . '</div>';
        }
        return '';
    }
}

// Pré-remplissage des champs (beaucoup de champs !)
$f_name = $oldFormData['name'] ?? ($article['name'] ?? '');
$f_description = $oldFormData['description'] ?? ($article['description'] ?? '');
$f_season = $oldFormData['season'] ?? ($article['season'] ?? '');
$f_category_type_id = $oldFormData['category_type_id'] ?? ($article['category_type_id'] ?? '');
$f_brand_id = $oldFormData['brand_id'] ?? ($article['brand_id'] ?? '');
//$f_condition = $oldFormData['condition'] ?? ($article['condition'] ?? 'bon état');
$f_condition = $oldFormData['condition'] ?? ($article['condition'] ?? 'neuf');
$f_primary_color_id = $oldFormData['primary_color_id'] ?? ($article['primary_color_id'] ?? '');
$f_secondary_color_id = $oldFormData['secondary_color_id'] ?? ($article['secondary_color_id'] ?? '');
$f_material_id = $oldFormData['material_id'] ?? ($article['material_id'] ?? '');
$f_size = $oldFormData['size'] ?? ($article['size'] ?? '');
$f_weight_grams = $oldFormData['weight_grams'] ?? ($article['weight_grams'] ?? '');
$f_current_storage_location_id = $oldFormData['current_storage_location_id'] ?? ($article['current_storage_location_id'] ?? '');
//$f_current_status_id = $oldFormData['current_status_id'] ?? ($article['current_status_id'] ?? ''); // Un statut par défaut pourrait être défini
$defaultStatusId = 5; // Remplacez par l'ID réel de "Acheté (Nouveau)"
$f_current_status_id = $oldFormData['current_status_id'] ?? ($article['current_status_id'] ?? $defaultStatusId);
$f_purchase_date = $oldFormData['purchase_date'] ?? ($article['purchase_date'] ?? '');
$f_purchase_price = $oldFormData['purchase_price'] ?? ($article['purchase_price'] ?? '');
$f_supplier_id = $oldFormData['supplier_id'] ?? ($article['supplier_id'] ?? '');
$f_estimated_value = $oldFormData['estimated_value'] ?? ($article['estimated_value'] ?? '');
$f_rating = $oldFormData['rating'] ?? ($article['rating'] ?? '');
$f_notes = $oldFormData['notes'] ?? ($article['notes'] ?? '');
$f_selected_associated_ids = $oldFormData['associated_article_ids'] ?? ($selectedAssociatedArticleIds ?? []);

// L'article_ref n'est pas modifiable directement, il sera généré
?>

<h1><?php echo Helper::e($pageTitle); ?></h1>

<form action="<?php echo Helper::e($formAction); ?>" method="POST" enctype="multipart/form-data" novalidate>
    <?php echo Helper::csrfInput(); ?>

    <div class="row">
        <div class="col-md-8">
            <h4>Core Information</h4>
            <div class="mb-3">
                <label for="name" class="form-label">Article Name / Short Description <span class="text-danger">*</span></label>
                <input type="text" class="form-control <?php echo !empty($formErrors['name']) ? 'is-invalid' : ''; ?>"
                       id="name" name="name" value="<?php echo Helper::e($f_name); ?>" required>
                <?php echo display_field_error('name', $formErrors); ?>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Long Description</label>
                <textarea class="form-control <?php echo !empty($formErrors['description']) ? 'is-invalid' : ''; ?>"
                          id="description" name="description" rows="4"><?php echo Helper::e($f_description); ?></textarea>
                <?php echo display_field_error('description', $formErrors); ?>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="category_type_id" class="form-label">Category/Type <span class="text-danger">*</span></label>
                    <select class="form-select <?php echo !empty($formErrors['category_type_id']) ? 'is-invalid' : ''; ?>" 
                            id="category_type_id" name="category_type_id" required>
                        <option value="">Select...</option>
                        <?php foreach ($categoryTypes as $ct): ?>
                            <option value="<?php echo $ct['id']; ?>" <?php echo ($f_category_type_id == $ct['id']) ? 'selected' : ''; ?>>
                                <?php //echo Helper::e(ucfirst($ct['category']) . ' - ' . $ct['name'] . ' (' . strtoupper($ct['code']) . ')'); ?>
                                <?php echo Helper::e(ucfirst($ct['name']) . ' (' . strtoupper($ct['code']) . ')' . ' - ' . $ct['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php echo display_field_error('category_type_id', $formErrors); ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="brand_id" class="form-label">Brand</label>
                    <select class="form-select <?php echo !empty($formErrors['brand_id']) ? 'is-invalid' : ''; ?>" 
                            id="brand_id" name="brand_id">
                        <option value="">Select...</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>" <?php echo ($f_brand_id == $brand['id']) ? 'selected' : ''; ?>>
                                <?php echo Helper::e($brand['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php echo display_field_error('brand_id', $formErrors); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="primary_color_id" class="form-label">Primary Color</label>
                    <select class="form-select <?php echo !empty($formErrors['primary_color_id']) ? 'is-invalid' : ''; ?>" 
                            id="primary_color_id" name="primary_color_id">
                        <option value="">Select...</option>
                        <?php foreach ($colors as $color): ?>
							<option value="<?php echo Helper::e($color['id']); ?>" <?php echo ($f_primary_color_id == $color['id']) ? 'selected' : ''; ?>>
							<?php echo Helper::e($color['name']); ?> (<?php echo Helper::e($color['hex_code'] ?? 'N/A'); ?>)
							</option>
                        <?php endforeach; ?>
                    </select>
                    <?php echo display_field_error('primary_color_id', $formErrors); ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="secondary_color_id" class="form-label">Secondary Color</label>
                     <select class="form-select <?php echo !empty($formErrors['secondary_color_id']) ? 'is-invalid' : ''; ?>" 
                            id="secondary_color_id" name="secondary_color_id">
                        <option value="">Select...</option>
						<?php foreach ($colors as $color): ?>
							<option value="<?php echo Helper::e($color['id']); ?>" 
									<?php echo ($f_primary_color_id == $color['id']) ? 'selected' : ''; ?> 
									<?php /* Temporairement commenté : style="background-color:<?php echo Helper::e($color['hex_code'] ?? '#ffffff'); ?>; color: <?php echo Helper::e(luma_is_dark($color['hex_code'] ?? '#ffffff') ? '#fff' : '#000'); ?>" */ ?>
									>
								<?php echo Helper::e($color['name']); ?>
								 (<?php echo Helper::e($color['hex_code'] ?? 'No Hex'); ?>) <!-- {/* Ajout du hex pour débogage */} -->
							</option>
						<?php endforeach; ?>
                    </select>
                    <?php echo display_field_error('secondary_color_id', $formErrors); ?>
                </div>
            </div>
            <?php
            // Helper pour le style des options de couleur (à mettre dans Helper.php si réutilisé)
            if (!function_exists('luma_is_dark')) {
                function luma_is_dark(string $hexcolor): bool {
					if (empty($hexcolor)) { // Gérer les hex vides/nulls explicitement
						return false; // Ou true, selon la couleur de texte par défaut que vous voulez sur un fond "inconnu"
					}
                    $hexcolor = str_replace('#', '', $hexcolor);
					$r = $g = $b = 0; // Initialiser
                    if (strlen($hexcolor) == 3) {
                        $r = hexdec(substr($hexcolor, 0, 1) . substr($hexcolor, 0, 1));
                        $g = hexdec(substr($hexcolor, 1, 1) . substr($hexcolor, 1, 1));
                        $b = hexdec(substr($hexcolor, 2, 1) . substr($hexcolor, 2, 1));
                    } else if (strlen($hexcolor) == 6) {
                        $r = hexdec(substr($hexcolor, 0, 2));
                        $g = hexdec(substr($hexcolor, 2, 2));
                        $b = hexdec(substr($hexcolor, 4, 2));
                    } else {
                        return false; // Pas un hex valide pour ça, on assume clair
                    }
                    $luma = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b; // per ITU-R BT.709
                    return $luma < 128; // Seuil arbitraire, peut être ajusté
                }
            }
            ?>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="material_id" class="form-label">Material</label>
                     <select class="form-select <?php echo !empty($formErrors['material_id']) ? 'is-invalid' : ''; ?>" 
                            id="material_id" name="material_id">
                        <option value="">Select...</option>
                        <?php foreach ($materials as $material): ?>
                            <option value="<?php echo $material['id']; ?>" <?php echo ($f_material_id == $material['id']) ? 'selected' : ''; ?>>
                                <?php echo Helper::e($material['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php echo display_field_error('material_id', $formErrors); ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="size" class="form-label">Size</label>
                    <input type="text" class="form-control <?php echo !empty($formErrors['size']) ? 'is-invalid' : ''; ?>"
                           id="size" name="size" value="<?php echo Helper::e($f_size); ?>">
                    <?php echo display_field_error('size', $formErrors); ?>
                </div>
            </div>
            
            <hr>
            <h4>Status & Storage</h4>
             <div class="row">
    <?php /* Commenter ou supprimer le champ Current Status
                <div class="col-md-6 mb-3">
                    <label for="current_status_id" class="form-label">Current Status <span class="text-danger">*</span></label>
                     <select class="form-select <?php echo !empty($formErrors['current_status_id']) ? 'is-invalid' : ''; ?>" 
                            id="current_status_id" name="current_status_id" required>
                        <option value="">Select...</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo $status['id']; ?>" <?php echo ($f_current_status_id == $status['id']) ? 'selected' : ''; ?>>
                                <?php echo Helper::e($status['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php echo display_field_error('current_status_id', $formErrors); ?>
                </div>
	*/ ?>
    <?php /* Commenter ou supprimer le champ Storage Location
            <div class="col-md-6 mb-3">
                    <label for="current_storage_location_id" class="form-label">Storage Location</label>
                     <select class="form-select <?php echo !empty($formErrors['current_storage_location_id']) ? 'is-invalid' : ''; ?>" 
                            id="current_storage_location_id" name="current_storage_location_id">
                        <option value="">Select...</option>
                        <?php foreach ($storageLocations as $loc): ?>
                            <option value="<?php echo $loc['id']; ?>" <?php echo ($f_current_storage_location_id == $loc['id']) ? 'selected' : ''; ?>>
                                <?php echo Helper::e($loc['full_location_path']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php echo display_field_error('current_storage_location_id', $formErrors); ?>
                </div>
	*/ ?>
            </div>
             <div class="row">

                <div class="col-md-6 mb-3">
                    <label for="condition" class="form-label">Condition <span class="text-danger">*</span></label>
                    <select class="form-select <?php echo !empty($formErrors['condition']) ? 'is-invalid' : ''; ?>" 
                            id="condition" name="condition" required>
                        <?php foreach ($conditionOptions as $cond): ?>
                            <option value="<?php echo $cond; ?>" <?php echo ($f_condition == $cond) ? 'selected' : ''; ?>>
                                <?php echo Helper::e(ucfirst($cond)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php echo display_field_error('condition', $formErrors); ?>
                </div>

                <div class="col-md-6 mb-3">
                     <label for="season" class="form-label">Season</label>
                    <select class="form-select <?php echo !empty($formErrors['season']) ? 'is-invalid' : ''; ?>" 
                            id="season" name="season">
                        <option value="">Any/Not specified</option>
                        <?php foreach ($seasonOptions as $seas): ?>
                            <option value="<?php echo $seas; ?>" <?php echo ($f_season == $seas) ? 'selected' : ''; ?>>
                                <?php echo Helper::e(ucfirst($seas)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php echo display_field_error('season', $formErrors); ?>
                </div>

            </div>

            <hr>
            <h4>Purchase Information</h4>
            <div class="row">
                 <div class="col-md-4 mb-3">
                    <label for="purchase_date" class="form-label">Purchase Date</label>
                    <input type="date" class="form-control <?php echo !empty($formErrors['purchase_date']) ? 'is-invalid' : ''; ?>"
                           id="purchase_date" name="purchase_date" value="<?php echo Helper::e($f_purchase_date); ?>">
                    <?php echo display_field_error('purchase_date', $formErrors); ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="purchase_price" class="form-label">Purchase Price (€)</label>
                    <input type="number" step="0.01" class="form-control <?php echo !empty($formErrors['purchase_price']) ? 'is-invalid' : ''; ?>"
                           id="purchase_price" name="purchase_price" value="<?php echo Helper::e($f_purchase_price); ?>">
                    <?php echo display_field_error('purchase_price', $formErrors); ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="supplier_id" class="form-label">Supplier</label>
                     <select class="form-select <?php echo !empty($formErrors['supplier_id']) ? 'is-invalid' : ''; ?>" 
                            id="supplier_id" name="supplier_id">
                        <option value="">Select...</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?php echo $sup['id']; ?>" <?php echo ($f_supplier_id == $sup['id']) ? 'selected' : ''; ?>>
                                <?php echo Helper::e($sup['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php echo display_field_error('supplier_id', $formErrors); ?>
                </div>
            </div>
            
            <hr>
            <h4>Other Details</h4>
             <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="weight_grams" class="form-label">Weight (grams)</label>
                    <input type="number" class="form-control <?php echo !empty($formErrors['weight_grams']) ? 'is-invalid' : ''; ?>"
                           id="weight_grams" name="weight_grams" value="<?php echo Helper::e($f_weight_grams); ?>">
                    <?php echo display_field_error('weight_grams', $formErrors); ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="estimated_value" class="form-label">Estimated Value (€)</label>
                    <input type="number" step="0.01" class="form-control <?php echo !empty($formErrors['estimated_value']) ? 'is-invalid' : ''; ?>"
                           id="estimated_value" name="estimated_value" value="<?php echo Helper::e($f_estimated_value); ?>">
                    <?php echo display_field_error('estimated_value', $formErrors); ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="rating" class="form-label">Rating (0-5)</label>
                    <input type="number" min="0" max="5" class="form-control <?php echo !empty($formErrors['rating']) ? 'is-invalid' : ''; ?>"
                           id="rating" name="rating" value="<?php echo Helper::e($f_rating); ?>">
                    <?php echo display_field_error('rating', $formErrors); ?>
                </div>
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Additional Notes</label>
                <textarea class="form-control <?php echo !empty($formErrors['notes']) ? 'is-invalid' : ''; ?>"
                          id="notes" name="notes" rows="3"><?php echo Helper::e($f_notes); ?></textarea>
                <?php echo display_field_error('notes', $formErrors); ?>
            </div>

        </div> <!-- {/* Fin col-md-8 */} -->

        <div class="col-md-4">
            <h4>Images</h4>
            <div class="mb-3">
                <label for="article_images" class="form-label">Add Images</label>
                <input type="file" class="form-control <?php echo !empty($formErrors['article_images']) ? 'is-invalid' : ''; ?>" 
                       id="article_images" name="article_images[]" multiple accept="image/jpeg, image/png, image/gif, image/webp">
                <small class="form-text text-muted">You can select multiple images. Max 5MB per image.</small>
                <?php echo display_field_error('article_images', $formErrors); ?>
            </div>
            
            <?php if ($isEditMode && !empty($articleImages)): ?>
                <p>Current Images:</p>
                <div class="row row-cols-2 g-2 mb-3">
                <?php foreach ($articleImages as $img): ?>
                    <div class="col">
                        <div class="card">
                             <img src="<?php echo Helper::e($articleImagePath . $img['image_path']); ?>" class="card-img-top" alt="<?php echo Helper::e($img['caption'] ?? 'Article image'); ?>" style="height: 120px; object-fit: cover;">
                             <div class="card-body p-1 text-center">
                                 <small class="text-muted"><?php echo Helper::e($img['caption'] ?? basename($img['image_path'])); ?></small>
                                 <div class="form-check form-check-inline">
                                     <input class="form-check-input" type="radio" name="primary_image_id" value="<?php echo $img['id']; ?>" id="primary_<?php echo $img['id']; ?>" <?php echo $img['is_primary'] ? 'checked' : ''; ?>>
                                     <label class="form-check-label" for="primary_<?php echo $img['id']; ?>"><small>Primary</small></label>
                                 </div>
                                 <div class="form-check form-check-inline">
                                     <input class="form-check-input" type="checkbox" name="delete_image_ids[]" value="<?php echo $img['id']; ?>" id="delete_<?php echo $img['id']; ?>">
                                     <label class="form-check-label text-danger" for="delete_<?php echo $img['id']; ?>"><small>Delete</small></label>
                                 </div>
                             </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <hr class="my-4">
            <h4>Associated Articles</h4>
            <div class="mb-3" style="max-height: 300px; overflow-y: auto;">
                <?php if (!empty($allArticlesForAssociation)): ?>
                    <?php foreach ($allArticlesForAssociation as $assocArticle): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="associated_article_ids[]" 
                                   value="<?php echo Helper::e($assocArticle['id']); ?>" 
                                   id="assoc_<?php echo Helper::e($assocArticle['id']); ?>"
                                   <?php echo in_array($assocArticle['id'], $f_selected_associated_ids) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="assoc_<?php echo Helper::e($assocArticle['id']); ?>">
                                <?php echo Helper::e($assocArticle['name']); ?> (<?php echo Helper::e($assocArticle['article_ref'] ?? 'No Ref'); ?>)
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No other articles available to associate.</p>
                <?php endif; ?>
                 <?php echo display_field_error('associated_article_ids', $formErrors); ?>
            </div>

            <hr class="my-4">
            <h4>Suitable Event Types</h4>
            <div class="mb-3" style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; padding: 10px;">
                <?php if (!empty($allEventTypesForForm)): ?>
                    <?php foreach ($allEventTypesForForm as $et): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="suitable_event_type_ids[]" 
                                   value="<?php echo Helper::e($et['id']); ?>" 
                                   id="suitable_et_<?php echo Helper::e($et['id']); ?>"
                                   <?php echo in_array($et['id'], $selectedSuitableEventTypeIds) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="suitable_et_<?php echo Helper::e($et['id']); ?>">
                                <?php echo Helper::e($et['name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No event types defined to select from.</p>
                <?php endif; ?>
                 <?php echo display_field_error('suitable_event_type_ids', $formErrors); // La fonction display_field_error doit exister ?>
            </div>
			
        </div> <!-- {/* Fin col-md-4 */} -->
    </div> <!-- {/* Fin row principale */} -->


    <hr>
    <div class="mt-3">
        <button type="submit" class="btn btn-primary">
            <i class="bi <?php echo $isEditMode ? 'bi-check-circle-fill' : 'bi-plus-circle-fill'; ?>"></i>
            <?php echo $isEditMode ? 'Update Article' : 'Create Article'; ?>
        </button>
        <a href="<?php echo APP_URL; ?>/articles" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Cancel
        </a>
    </div>
</form>