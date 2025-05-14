<?php use App\Utils\Helper; ?>

<h1><?php echo Helper::e($pageTitle ?? 'Dashboard'); ?></h1>
<p><?php echo Helper::e($welcomeMessage ?? 'Welcome!'); ?></p>

<p>This is the main dashboard of your Dressing Manager. From here you can navigate to manage your wardrobe items and related entities.</p>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Manage Articles</h5>
                <p class="card-text">View, add, edit, and organize your clothing, accessories, and jewelry.</p>
                <a href="<?php echo APP_URL; ?>/articles" class="btn btn-primary">Go to Articles</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Manage Brands</h5>
                <p class="card-text">Maintain the list of brands for your items.</p>
                <a href="<?php echo APP_URL; ?>/brands" class="btn btn-secondary">Go to Brands</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Other Entities</h5>
                <p class="card-text">Manage colors, materials, categories, and more.</p>
                <a href="#" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown">Manage Data</a>
                 <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/colors">Colors</a></li>
                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/materials">Materials</a></li>
                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/categories_types">Categories/Types</a></li>
                    <!-- Add more links as needed -->
                </ul>
            </div>
        </div>
    </div>
</div>