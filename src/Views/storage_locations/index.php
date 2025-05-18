<?php
use App\Utils\Helper;
$currentSortColumn = $_GET['sort'] ?? 'room';
$currentSortOrder = $_GET['order'] ?? 'asc';
$baseUrl = APP_URL . '/' . trim(str_replace(APP_URL, '', strtok($_SERVER['REQUEST_URI'], '?')), '/');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo Helper::e($pageTitle ?? 'Storage Locations'); ?></h1>
    <a href="<?php echo APP_URL; ?>/storagelocations/create" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add New</a>
</div>

<div class="row mb-3">
    <div class="col-md-2"><input type="text" id="slRoomSearch" class="form-control form-control-sm" placeholder="Room..."></div>
    <div class="col-md-2"><input type="text" id="slAreaSearch" class="form-control form-control-sm" placeholder="Area..."></div>
    <div class="col-md-2"><input type="text" id="slShelfSearch" class="form-control form-control-sm" placeholder="Shelf/Rack..."></div>
    <div class="col-md-2"><input type="text" id="slLevelSearch" class="form-control form-control-sm" placeholder="Level/Section..."></div>
    <div class="col-md-2"><input type="text" id="slSpotSearch" class="form-control form-control-sm" placeholder="Spot/Box..."></div>
    <div class="col-md-2"><input type="text" id="slFullPathSearch" class="form-control form-control-sm" placeholder="Full Path..."></div>
</div>

<?php if (empty($storageLocations)): ?>
    <div class="alert alert-info">No locations found.</div>
<?php else: ?>
    <table class="table table-striped table-hover table-sm" id="storageLocationsTable"> <!-- {/* table-sm pour plus de compacité */} -->
        <thead class="table-dark">
            <tr>
                <th><?php echo Helper::generateSortLink('id', 'ID', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('room', 'Room', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('area', 'Area', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('shelf_or_rack', 'Shelf/Rack', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('level_or_section', 'Level/Sec.', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th><?php echo Helper::generateSortLink('specific_spot_or_box', 'Spot/Box', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th style="min-width: 250px;"><?php echo Helper::generateSortLink('full_location_path', 'Full Path', $currentSortColumn, $currentSortOrder, $baseUrl); ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($storageLocations as $item): ?>
                <tr>
                    <td><?php echo Helper::e($item['id']); ?></td>
                    <td><?php echo Helper::e($item['room']); ?></td>                  <!-- {/* col 1 */} -->
                    <td><?php echo Helper::e($item['area'] ?? ''); ?></td>             <!-- {/* col 2 */} -->
                    <td><?php echo Helper::e($item['shelf_or_rack'] ?? ''); ?></td>      <!-- {/* col 3 */} -->
                    <td><?php echo Helper::e($item['level_or_section'] ?? ''); ?></td>   <!-- {/* col 4 */} -->
                    <td><?php echo Helper::e($item['specific_spot_or_box'] ?? ''); ?></td><!-- {/* col 5 */} -->
                    <td><?php echo Helper::e($item['full_location_path'] ?? 'N/A'); ?></td> <!-- {/* col 6 */} -->
                    <td>
                        <!-- {/* ... boutons d'action ... */} -->
                        <a href="<?php echo APP_URL; ?>/storagelocations/edit/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                        <a href="<?php echo APP_URL; ?>/storagelocations/show/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                        <form action="<?php echo APP_URL; ?>/storagelocations/delete/<?php echo $item['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this location?');">
                            <?php echo Helper::csrfInput(); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>