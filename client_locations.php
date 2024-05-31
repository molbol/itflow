<?php

// Default Column Sortby Filter
$sort = "location_name";
$order = "ASC";

require_once "inc_all_client.php";

// Tags Filter
if (isset($_GET['tags']) && is_array($_GET['tags']) && !empty($_GET['tags'])) {
    // Sanitize each element of the status array
    $sanitizedTags = array();
    foreach ($_GET['tags'] as $tag) {
        // Escape each status to prevent SQL injection
        $sanitizedTags[] = "'" . intval($tag) . "'";
    }

    // Convert the sanitized tags into a comma-separated string
    $sanitizedTagsString = implode(",", $sanitizedTags);
    $tag_query = "AND tags.tag_id IN ($sanitizedTagsString)";
}

//Rebuild URL
$url_query_strings_sort = http_build_query($get_copy);

$sql = mysqli_query(
    $mysqli,
    "SELECT SQL_CALC_FOUND_ROWS locations.*, GROUP_CONCAT(tag_name) FROM locations 
    LEFT JOIN location_tags ON location_tags.location_id = locations.location_id
    LEFT JOIN tags ON tags.tag_id = location_tags.tag_id
    WHERE location_client_id = $client_id
    $tag_query
    AND location_$archive_query
    AND (location_name LIKE '%$q%' OR location_description LIKE '%$q%' OR location_address LIKE '%$q%' OR location_phone LIKE '%$phone_query%' OR tag_name LIKE '%$q%') 
    GROUP BY location_id
    ORDER BY location_primary DESC, $sort $order LIMIT $record_from, $record_to"
);

$num_rows = mysqli_fetch_row(mysqli_query($mysqli, "SELECT FOUND_ROWS()"));

?>

<div class="card card-dark">
    <div class="card-header py-2">
        <h3 class="card-title mt-2"><i class="fa fa-fw fa-map-marker-alt mr-2"></i>Locations</h3>
        <div class="card-tools">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addLocationModal">
                    <i class="fas fa-plus mr-2"></i>New Location
                </button>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown"></button>
                <div class="dropdown-menu">
                    <a class="dropdown-item text-dark" href="#" data-toggle="modal" data-target="#importLocationModal">
                        <i class="fa fa-fw fa-upload mr-2"></i>Import
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-dark" href="#" data-toggle="modal" data-target="#exportLocationModal">
                        <i class="fa fa-fw fa-download mr-2"></i>Export
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form autocomplete="off">
            <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
            <input type="hidden" name="archived" value="<?php echo $archived; ?>">
            <div class="row">

                <div class="col-md-4">
                    <div class="input-group mb-3 mb-md-0">
                        <input type="search" class="form-control" name="q" value="<?php if (isset($q)) { echo stripslashes(nullable_htmlentities($q)); } ?>" placeholder="Search Locations">
                        <div class="input-group-append">
                            <button class="btn btn-dark"><i class="fa fa-search"></i></button>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <select onchange="this.form.submit()" class="form-control select2" name="tags[]" data-placeholder="- Select Tags -" multiple>

                                <?php $sql_tags = mysqli_query($mysqli, "SELECT * FROM tags WHERE tag_type = 2");
                                while ($row = mysqli_fetch_array($sql_tags)) {
                                    $tag_id = intval($row['tag_id']);
                                    $tag_name = nullable_htmlentities($row['tag_name']); ?>

                                    <option value="<?php echo $tag_id ?>" <?php if (isset($_GET['tags']) && is_array($_GET['tags']) && in_array($tag_id, $_GET['tags'])) { echo 'selected'; } ?>> <?php echo $tag_name ?> </option>

                                <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="float-right">
                        <?php if($archived == 1){ ?>
                        <a href="?client_id=<?php echo $client_id; ?>&archived=0" class="btn btn-primary"><i class="fa fa-fw fa-archive mr-2"></i>Archived</a>
                        <?php } else { ?>
                        <a href="?client_id=<?php echo $client_id; ?>&archived=1" class="btn btn-default"><i class="fa fa-fw fa-archive mr-2"></i>Archived</a>
                        <?php } ?>
                    </div>
                </div>

            </div>
        </form>
        <hr>
        <div class="table-responsive-sm">
            <table class="table table-striped table-borderless table-hover">
                <thead class="<?php if ($num_rows[0] == 0) { echo "d-none"; } ?>">
                <tr>
                    <th><a class="text-secondary" href="?<?php echo $url_query_strings_sort; ?>&sort=location_name&order=<?php echo $disp; ?>">Name</a></th>
                    <th><a class="text-secondary" href="?<?php echo $url_query_strings_sort; ?>&sort=location_address&order=<?php echo $disp; ?>">Address</a></th>
                    <th><a class="text-secondary" href="?<?php echo $url_query_strings_sort; ?>&sort=location_phone&order=<?php echo $disp; ?>">Phone</a></th>
                    <th><a class="text-secondary" href="?<?php echo $url_query_strings_sort; ?>&sort=location_hours&order=<?php echo $disp; ?>">Hours</a></th>
                    <th class="text-center">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php

                while ($row = mysqli_fetch_array($sql)) {
                    $location_id = intval($row['location_id']);
                    $location_name = nullable_htmlentities($row['location_name']);
                    $location_description = nullable_htmlentities($row['location_description']);
                    $location_country = nullable_htmlentities($row['location_country']);
                    $location_address = nullable_htmlentities($row['location_address']);
                    $location_city = nullable_htmlentities($row['location_city']);
                    $location_state = nullable_htmlentities($row['location_state']);
                    $location_zip = nullable_htmlentities($row['location_zip']);
                    $location_phone = formatPhoneNumber($row['location_phone']);
                    if (empty($location_phone)) {
                        $location_phone_display = "-";
                    } else {
                        $location_phone_display = $location_phone;
                    }
                    $location_hours = nullable_htmlentities($row['location_hours']);
                    if (empty($location_hours)) {
                        $location_hours_display = "-";
                    } else {
                        $location_hours_display = $location_hours;
                    }
                    $location_photo = nullable_htmlentities($row['location_photo']);
                    $location_notes = nullable_htmlentities($row['location_notes']);
                    $location_created_at = nullable_htmlentities($row['location_created_at']);
                    $location_contact_id = intval($row['location_contact_id']);
                    $location_primary = intval($row['location_primary']);
                    if ( $location_primary == 1 ) {
                        $location_primary_display = "<small class='text-success'><i class='fa fa-fw fa-check'></i> Primary</small>";
                    } else {
                        $location_primary_display = "";
                    }

                    // Tags

                    $location_tag_name_display_array = array();
                    $location_tag_id_array = array();
                    $sql_location_tags = mysqli_query($mysqli, "SELECT * FROM location_tags LEFT JOIN tags ON location_tags.tag_id = tags.tag_id WHERE location_tags.location_id = $location_id ORDER BY tag_name ASC");
                    while ($row = mysqli_fetch_array($sql_location_tags)) {

                        $location_tag_id = intval($row['tag_id']);
                        $location_tag_name = nullable_htmlentities($row['tag_name']);
                        $location_tag_color = nullable_htmlentities($row['tag_color']);
                        if (empty($location_tag_color)) {
                            $location_tag_color = "dark";
                        }
                        $location_tag_icon = nullable_htmlentities($row['tag_icon']);
                        if (empty($location_tag_icon)) {
                            $location_tag_icon = "tag";
                        }

                        $location_tag_id_array[] = $location_tag_id;
                        $location_tag_name_display_array[] = "<a href='client_locations.php?client_id=$client_id&q=$location_tag_name'><span class='badge text-light p-1 mr-1' style='background-color: $location_tag_color;'><i class='fa fa-fw fa-$location_tag_icon mr-2'></i>$location_tag_name</span></a>";
                    }
                    $location_tags_display = implode('', $location_tag_name_display_array);

                    ?>
                    <tr>
                        <td>
                            <a class="text-dark" href="#" data-toggle="modal" data-target="#editLocationModal<?php echo $location_id; ?>">
                                <div class="media">
                                    <i class="fa fa-fw fa-2x fa-map-marker-alt mr-3"></i>
                                    <div class="media-body">
                                        <div <?php if($location_primary) { echo "class='text-bold'"; } ?>><?php echo $location_name; ?></div>
                                        <div><small class="text-secondary"><?php echo $location_description; ?></small></div>
                                        <div><?php echo $location_primary_display; ?></div>
                                         <?php
                                        if (!empty($location_tags_display)) { ?>
                                            <div class="mt-1">
                                                <?php echo $location_tags_display; ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </a>
                        </td>
                        <td><a href="//maps.<?php echo $session_map_source; ?>.com?q=<?php echo "$location_address $location_zip"; ?>" target="_blank"><?php echo $location_address; ?><br><?php echo "$location_city $location_state $location_zip"; ?></a></td>
                        <td><?php echo $location_phone_display; ?></td>
                        <td><?php echo $location_hours_display; ?></td>
                        <td>
                            <div class="dropdown dropleft text-center">
                                <button class="btn btn-secondary btn-sm" type="button" data-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#editLocationModal<?php echo $location_id; ?>">
                                        <i class="fas fa-fw fa-edit mr-2"></i>Edit
                                    </a>
                                    <?php if ($session_user_role == 3 && $location_primary == 0) { ?>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger confirm-link" href="post.php?archive_location=<?php echo $location_id; ?>">
                                            <i class="fas fa-fw fa-archive mr-2"></i>Archive
                                        </a>
                                        <?php if ($config_destructive_deletes_enable) { ?>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger text-bold confirm-link" href="post.php?delete_location=<?php echo $location_id; ?>">
                                            <i class="fas fa-fw fa-trash mr-2"></i>Delete
                                        </a>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php require "client_location_edit_modal.php";
 ?>
                        </td>
                    </tr>

                <?php } ?>

                </tbody>
            </table>
        </div>
        <?php require_once "pagination.php";
 ?>
    </div>
</div>

<?php

require_once "client_location_add_modal.php";

require_once "client_location_import_modal.php";

require_once "client_location_export_modal.php";

require_once "footer.php";

