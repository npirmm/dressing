<?php // src/Views/articles/index.php
use App\Utils\Helper;

// Récupérer les filtres actuels depuis GET pour les réafficher dans les champs
$filterNameRef = $_GET['filter_name_ref'] ?? '';
$filterCategory = $_GET['filter_category_id'] ?? ''; // Utiliser l'ID de category_type
$filterBrand = $_GET['filter_brand_id'] ?? '';
$filterStatus = $_GET['filter_status_id'] ?? '';
$filterSeason = $_GET['filter_season'] ?? '';
$filterCondition = $_GET['filter_condition'] ?? '';

// Récupérer les données pour les listes déroulantes des filtres (besoin de les passer depuis le contrôleur)
// $allCategoriesForFilter, $allBrandsForFilter, $allStatusesForFilter, $allSeasonOptions, $allConditionOptions

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

<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-funnel-fill"></i> Filters & Search
    </div>
    <div class="card-body">
        <form action="<?php echo APP_URL; ?>/articles" method="GET" class="row g-3">
            <!-- {/* Conserver les paramètres de tri actuels lors du filtrage */} -->
            <?php if ($currentSortColumn !== 'a.name' || $currentSortOrder !== 'asc'): // Ne pas ajouter si c'est le défaut implicite ?>
                <input type="hidden" name="sort" value="<?php echo Helper::e($currentSortColumn); ?>">
                <input type="hidden" name="order" value="<?php echo Helper::e($currentSortOrder); ?>">
            <?php endif; ?>

            <div class="col-md-4">
                <label for="filter_name_ref" class="form-label">Name / Ref / Description</label>
                <input type="text" class="form-control form-control-sm" id="filter_name_ref" name="filter_name_ref" value="<?php echo Helper::e($filterNameRef); ?>">
            </div>
            <div class="col-md-3">
                <label for="filter_category_id" class="form-label">Category/Type</label>
                <select class="form-select form-select-sm" id="filter_category_id" name="filter_category_id">
                    <option value="">All</option>
                    <?php foreach ($allCategoriesForFilter ?? [] as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($filterCategory == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo Helper::e(ucfirst($cat['category']) . ' - ' . $cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter_brand_id" class="form-label">Brand</label>
                <select class="form-select form-select-sm" id="filter_brand_id" name="filter_brand_id">
                    <option value="">All</option>
                     <?php foreach ($allBrandsForFilter ?? [] as $brand): ?>
                        <option value="<?php echo $brand['id']; ?>" <?php echo ($filterBrand == $brand['id']) ? 'selected' : ''; ?>>
                            <?php echo Helper::e($brand['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
             <div class="col-md-2">
                <label for="filter_status_id" class="form-label">Status</label>
                <select class="form-select form-select-sm" id="filter_status_id" name="filter_status_id">
                    <option value="">All</option>
                     <?php foreach ($allStatusesForFilter ?? [] as $status): ?>
                        <option value="<?php echo $status['id']; ?>" <?php echo ($filterStatus == $status['id']) ? 'selected' : ''; ?>>
                            <?php echo Helper::e($status['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter_season" class="form-label">Season</label>
                <select class="form-select form-select-sm" id="filter_season" name="filter_season">
                    <option value="">All</option>
                     <?php foreach ($allSeasonOptionsForFilter ?? [] as $season): ?>
                        <option value="<?php echo $season; ?>" <?php echo ($filterSeason == $season) ? 'selected' : ''; ?>>
                            <?php echo Helper::e(ucfirst($season)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter_condition" class="form-label">Condition</label>
                <select class="form-select form-select-sm" id="filter_condition" name="filter_condition">
                    <option value="">All</option>
                     <?php foreach ($allConditionOptionsForFilter ?? [] as $cond): ?>
                        <option value="<?php echo $cond; ?>" <?php echo ($filterCondition == $cond) ? 'selected' : ''; ?>>
                            <?php echo Helper::e(ucfirst($cond)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- {/* Ajoutez d'autres filtres : Couleur, Matière, Lieu de stockage, etc. */} -->
            <div class="col-md-auto align-self-end">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Filter</button>
                <a href="<?php echo APP_URL; ?>/articles" class="btn btn-secondary btn-sm"><i class="bi bi-x-lg"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($articles) && !empty(array_filter(compact('filterNameRef', 'filterCategory', 'filterBrand', 'filterStatus', 'filterSeason', 'filterCondition')))): ?>
    <div class="alert alert-warning">No articles found matching your filter criteria. <a href="<?php echo APP_URL; ?>/articles">Reset filters?</a></div>
<?php elseif (empty($articles)): ?>
    <div class="alert alert-info">No articles found. <a href="<?php echo APP_URL; ?>/articles/create">Add one?</a></div>
<?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <small class="text-muted">Showing <?php echo count($articles); ?> of <?php echo $totalArticles ?? count($articles); ?> articles.</small>
        <!-- {/* Pagination (à venir) */} -->
    </div>
	<table class="table table-striped table-hover table-sm" id="articlesTable">
		<thead class="table-dark">
			<tr>
				<th>Img</th>
				<th><?php echo Helper::generateSortLink('a.article_ref', 'Ref', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
				<th><?php echo Helper::generateSortLink('a.name', 'Name', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
				<th><?php echo Helper::generateSortLink('ct.name', 'Category', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
				<th><?php echo Helper::generateSortLink('b.name', 'Brand', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
				
				<th>Color</th>
				<th><?php echo Helper::generateSortLink('st.name', 'Status', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
				<th><?php echo Helper::generateSortLink('a.updated_at', 'Last Upd.', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
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
                    <!-- <td><?php echo Helper::e($item['name']); ?></td> -->
					<td>
						<a href="<?php echo APP_URL . '/articles/show/' . $item['id']; ?>">
							<?php echo Helper::e($item['name']); ?>
						</a>
					</td>					
                    <td><?php echo Helper::e($item['category_type_name'] ?? 'N/A'); ?></td>
                    <td><?php echo Helper::e($item['brand_name'] ?? 'N/A'); ?></td>
                    <!-- <td><?php echo Helper::e($item['size'] ?? 'N/A'); ?></td> Plus besoin -->
                    <td>
                        <?php if (!empty($item['primary_color_hex'])): ?>
                            <span style="display:inline-block; width: 20px; height:20px; background-color:<?php echo Helper::e($item['primary_color_hex']); ?>; border:1px solid #ccc; border-radius: 50%; vertical-align: middle;"></span>
                        <?php else: echo 'N/A'; endif; ?>
                    </td>
                    <td><?php echo Helper::e($item['status_name'] ?? 'N/A'); ?></td>
					<td><?php echo $item['updated_at'] ? Helper::e(date('Y-m-d', strtotime($item['updated_at']))) : 'N/A'; ?></td>
					<td>
						<a href="<?php echo APP_URL; ?>/articles/show/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-info" title="View Details"><i class="bi bi-eye"></i></a>
						<a href="<?php echo APP_URL; ?>/articles/edit/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Article"><i class="bi bi-pencil-square"></i></a>
						<a href="<?php echo APP_URL; ?>/articles/log_event/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-success" title="Log Event / Change Status">
							<i class="bi bi-calendar-event"></i> Log
						</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <!-- {/* Pagination (à venir) */} -->
     <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if (isset($paginationLinks)): ?>
                <?php echo $paginationLinks; ?>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>