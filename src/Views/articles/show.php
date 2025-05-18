<?php
use App\Utils\Helper;
?>
<h1><?php echo Helper::e($pageTitle ?? 'Article Details'); ?></h1>

<?php if (isset($article) && $article): ?>
    <div class="row">
        <div class="col-md-8">
            <h3><?php echo Helper::e($article['name']); ?> <small class="text-muted">(<?php echo Helper::e($article['article_ref'] ?? 'No Ref'); ?>)</small></h3>
            <hr>
            <dl class="row">
                <dt class="col-sm-4">Category:</dt>
                <dd class="col-sm-8"><?php echo Helper::e($article['category_type_name'] ?? 'N/A'); ?> (<?php echo Helper::e($article['category_group'] ?? ''); ?>)</dd>
                
                <dt class="col-sm-4">Brand:</dt>
                <dd class="col-sm-8"><?php echo Helper::e($article['brand_name'] ?? 'N/A'); ?></dd>

                <dt class="col-sm-4">Primary Color:</dt>
                <dd class="col-sm-8">
                    <?php if(!empty($article['primary_color_name'])): ?>
                        <span style="display:inline-block; width: 1em; height:1em; background-color:<?php echo Helper::e($article['primary_color_hex'] ?? '#fff'); ?>; border:1px solid #ccc; vertical-align: middle;"></span>
                        <?php echo Helper::e($article['primary_color_name']); ?>
                    <?php else: echo 'N/A'; endif; ?>
                </dd>

                <?php if(!empty($article['secondary_color_name'])): ?>
                <dt class="col-sm-4">Secondary Color:</dt>
                <dd class="col-sm-8">
                    <span style="display:inline-block; width: 1em; height:1em; background-color:<?php echo Helper::e($article['secondary_color_hex'] ?? '#fff'); ?>; border:1px solid #ccc; vertical-align: middle;"></span>
                    <?php echo Helper::e($article['secondary_color_name']); ?>
                </dd>
                <?php endif; ?>

                <dt class="col-sm-4">Material:</dt>
                <dd class="col-sm-8"><?php echo Helper::e($article['material_name'] ?? 'N/A'); ?></dd>

                <dt class="col-sm-4">Size:</dt>
                <dd class="col-sm-8"><?php echo Helper::e($article['size'] ?? 'N/A'); ?></dd>

                <dt class="col-sm-4">Condition:</dt>
                <dd class="col-sm-8"><?php echo Helper::e(ucfirst($article['condition'])); ?></dd>

                <dt class="col-sm-4">Status:</dt>
                <dd class="col-sm-8 fw-bold"><?php echo Helper::e($article['status_name'] ?? 'N/A'); ?></dd>

                <dt class="col-sm-4">Storage Location:</dt>
                <dd class="col-sm-8"><?php echo Helper::e($article['storage_location_full_path'] ?? 'N/A'); ?></dd>

                <dt class="col-sm-4">Season:</dt>
                <dd class="col-sm-8"><?php echo Helper::e(ucfirst($article['season'] ?? 'N/A')); ?></dd>

                <dt class="col-sm-4">Weight (grams):</dt>
                <dd class="col-sm-8"><?php echo Helper::e($article['weight_grams'] ?? 'N/A'); ?> g</dd>
                
                <dt class="col-sm-4">Purchase Date:</dt>
                <dd class="col-sm-8"><?php echo $article['purchase_date'] ? Helper::e(date('d M Y', strtotime($article['purchase_date']))) : 'N/A'; ?></dd>

                <dt class="col-sm-4">Purchase Price:</dt>
                <dd class="col-sm-8"><?php echo $article['purchase_price'] ? '€' . Helper::e(number_format((float)$article['purchase_price'], 2, ',', ' ')) : 'N/A'; ?></dd>

                <dt class="col-sm-4">Supplier:</dt>
                <dd class="col-sm-8"><?php echo Helper::e($article['supplier_name'] ?? 'N/A'); ?></dd>

                <dt class="col-sm-4">Estimated Value:</dt>
                <dd class="col-sm-8"><?php echo $article['estimated_value'] ? '€' . Helper::e(number_format((float)$article['estimated_value'], 2, ',', ' ')) : 'N/A'; ?></dd>

                <dt class="col-sm-4">Rating:</dt>
                <dd class="col-sm-8">
                    <?php if($article['rating'] !== null): ?>
                        <?php for($i = 0; $i < 5; $i++): ?>
                            <i class="bi <?php echo ($i < $article['rating']) ? 'bi-star-fill text-warning' : 'bi-star text-muted'; ?>"></i>
                        <?php endfor; ?>
                    <?php else: echo 'N/A'; endif; ?>
                </dd>

                <dt class="col-sm-4">Times Worn:</dt>
                <dd class="col-sm-8"><?php echo Helper::e($article['times_worn']); ?></dd>

                <dt class="col-sm-4">Last Worn:</dt>
                <dd class="col-sm-8"><?php echo $article['last_worn_at'] ? Helper::e(date('d M Y H:i', strtotime($article['last_worn_at']))) : 'Never'; ?></dd>
            </dl>
            
            <?php if(!empty($article['description'])): ?>
                <h5>Description</h5>
                <p><?php echo nl2br(Helper::e($article['description'])); ?></p>
            <?php endif; ?>

            <?php if(!empty($article['notes'])): ?>
                <h5>Notes</h5>
                <p><?php echo nl2br(Helper::e($article['notes'])); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($article['associated_articles_details'])): ?>
                <h5 class="mt-3">Associated Articles:</h5>
                <ul>
                    <?php foreach ($article['associated_articles_details'] as $assoc): ?>
                        <li>
                            <a href="<?php echo APP_URL . '/articles/show/' . $assoc['id']; ?>">
                                <?php echo Helper::e($assoc['name']); ?> (<?php echo Helper::e($assoc['article_ref']); ?>)
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        </div>
        <div class="col-md-4">
            <h5>Images</h5>
            <?php if (!empty($article['images'])): ?>
                <?php foreach($article['images'] as $image): ?>
                    <div class="mb-2">
                        <img src="<?php echo Helper::e($articleImagePath . $image['image_path']); ?>" 
                             alt="<?php echo Helper::e($image['caption'] ?? $article['name']); ?>" class="img-fluid rounded border mb-1">
                        <?php if(!empty($image['caption'])): ?>
                            <small class="text-muted d-block"><?php echo Helper::e($image['caption']); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php elseif(!empty($article['primary_image_path'])): // Fallback si pas d'images dans la table mais une dans la requête principale ?>
                 <img src="<?php echo Helper::e($articleImagePath . $article['primary_image_path']); ?>" 
                             alt="<?php echo Helper::e($article['name']); ?>" class="img-fluid rounded border mb-1">
            <?php else: ?>
                <p class="text-muted">No images for this article.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4">
        <a href="<?php echo APP_URL; ?>/articles/edit/<?php echo Helper::e($article['id']); ?>" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>
        <a href="<?php echo APP_URL; ?>/articles" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Back to List</a>
        {/* Delete form viendra plus tard */}
    </div>
<?php else: ?>
    <div class="alert alert-warning">Article not found.</div>
    <a href="<?php echo APP_URL; ?>/articles" class="btn btn-secondary">Back to List</a>
<?php endif; ?>