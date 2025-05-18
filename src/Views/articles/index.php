<?php
use App\Utils\Helper;
$currentSortColumn = $_GET['sort'] ?? 'a.name'; // Doit correspondre aux clés dans $allowedSortColumns du modèle
$currentSortOrder = $_GET['order'] ?? 'asc';
$baseUrl = APP_URL . '/' . trim(str_replace(APP_URL, '', strtok($_SERVER['REQUEST_URI'], '?')), '/');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo Helper::e($pageTitle ?? 'Articles'); ?></h1>
    <a href="<?php echo APP_URL; ?>/articles/create" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add New Article
    </a>
</div>

<!-- Champs de recherche (placeholder pour l'instant) -->
<div class="row mb-3">
    <div class="col-md-4"><input type="text" id="articleNameSearch" class="form-control form-control-sm" placeholder="Search Name/Ref..."></div>
    <div class="col-md-3"><input type="text" id="articleCategorySearch" class="form-control form-control-sm" placeholder="Search Category..."></div>
    <div class="col-md-3"><input type="text" id="articleBrandSearch" class="form-control form-control-sm" placeholder="Search Brand..."></div>
    <div class="col-md-2"><input type="text" id="articleStatusSearch" class="form-control form-control-sm" placeholder="Search Status..."></div>
</div>

<?php if (empty($articles)): ?>
    <div class="alert alert-info">No articles found. <a href="<?php echo APP_URL; ?>/articles/create">Add one?</a></div>
<?php else: ?>
    <table class="table table-striped table-hover table-sm" id="articlesTable">
        <thead class="table-dark">
            <tr>
                <th>Img</th>
                <th><?php echo Helper::generateSortLink('a.article_ref', 'Ref', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('a.name', 'Name', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('ct.name', 'Category', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('b.name', 'Brand', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('a.size', 'Size', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th>Color</th>
                <th><?php echo Helper::generateSortLink('st.name', 'Status', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($articles as $item): ?>
                <tr>
                    <td>
                        <?php if (!empty($item['primary_image_path'])): ?>
                            <img src="<?php echo Helper::e($articleImagePath . $item['primary_image_path']); ?>"
                                 alt="<?php echo Helper::e($item['name']); ?>" style="width: 50px; height: 60px; object-fit: cover; border-radius: 3px;">
                        <?php else: ?>
                            <div style="width: 50px; height: 60px; background-color: #e9ecef; border-radius: 3px; display:flex; align-items:center; justify-content:center; font-size:0.8em; color:#6c757d;">No Img</div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo Helper::e($item['article_ref'] ?? 'N/A'); ?></td>
                    <td><?php echo Helper::e($item['name']); ?></td>
                    <td><?php echo Helper::e($item['category_type_name'] ?? 'N/A'); ?></td>
                    <td><?php echo Helper::e($item['brand_name'] ?? 'N/A'); ?></td>
                    <td><?php echo Helper::e($item['size'] ?? 'N/A'); ?></td>
                    <td>
                        <?php if (!empty($item['primary_color_hex'])): ?>
                            <span style="display:inline-block; width: 20px; height:20px; background-color:<?php echo Helper::e($item['primary_color_hex']); ?>; border:1px solid #ccc; border-radius: 50%; vertical-align: middle;"></span>
                        <?php else: echo 'N/A'; endif; ?>
                    </td>
                    <td><?php echo Helper::e($item['status_name'] ?? 'N/A'); ?></td>
                    <td>
                        <a href="<?php echo APP_URL; ?>/articles/edit/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                        <a href="<?php echo APP_URL; ?>/articles/show/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                        {/* Delete form viendra plus tard */}
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>