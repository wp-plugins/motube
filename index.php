<?php
/*
Plugin Name: MoTube
Plugin URI: http://www.blackbam.at/motube/
Description: This Plugin provides a smooth 3D video presentation platform with categories for the iPhone 4.
Version:0.1
Author: Robert Kirchner, David Stöckl
Author URI: http://www.blackbam.at/blog/
 */
 
/* Installation und Deinstallation */

// Callback wird bei der Aktiviertung ausgeführt
register_activation_hook(__FILE__,'motube_install');

// Callback wird bei der Deaktivierung ausgeführt
register_deactivation_hook(__FILE__,'motube_uninstall');

// Installation: Datenbanken erstellen, Updatetest
function motube_install() {
	/* Check the version */
	define('MOTUBE_URL',WP_PLUGIN_URL.'/motube/app/');
	
	require(ABSPATH . "wp-admin/includes/upgrade.php");
	global $wpdb;
	global $wp_version;
	global $wp_db_version;
	
	$motube_cat = $wpdb->prefix .'motube_cat';
	$motube_vid = $wpdb->prefix.'motube_vid';

/*
	if(version_compare($wp_version,"2.5","<")) {
		deactivate_plugins(basename(__FILE__));
		wp_die("This plugin is only tested with WordPress 2.5 or higher. It might work with other verions of WordPress, check the source-code and make your changes as needed.");
	}
*/

	/* Database table installation */

	$cur_version = "0.1";
	
	/** Create the video category table */
	if($wpdb->get_var("show tables like '$motube_cat'") != $motube_cat) {
		$sql= "CREATE TABLE ".$motube_cat." (
		`category` INT( 5 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`name` VARCHAR( 50 ) NOT NULL ,
		`parentcategory` INT( 5 ) NOT NULL DEFAULT '1'
		) ENGINE = MYISAM DEFAULT CHARSET=utf8;";
		dbDelta($sql);
		
		// Create initial category 1
		//$rows_affected = $wpdb->insert( $motube_cat, array( 'category' => NULL, 'name' => __("General","motube"), 'parentcategory' => 0 ) );

	}
	
	/** Create the video table */
	if($wpdb->get_var("show tables like '$motube_vid'") != $motube_vid) {
		$sql = "CREATE TABLE ".$motube_vid." (
		`id` INT( 7 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`category` INT( 6 ) NOT NULL DEFAULT '1',
		`titel` VARCHAR( 50 ) NULL ,
		`description` VARCHAR( 400 ) NULL ,
		`thumburl` VARCHAR( 200 ) NULL ,
		`videourl` VARCHAR( 200 ) NOT NULL
		) ENGINE = MYISAM DEFAULT CHARSET=utf8;";
		
		require_once(ABSPATH . "wp-admin/includes/upgrade.php");
		dbDelta($sql);
		
		add_option('motube_version', $cur_version);
	}
	
	
	$pre_version = get_option('motube_version');

	if($pre_version != $cur_version) {
		update_option('motube_version', $cur_version);
	}
	
}
 
// Uninstall: Delete database tables
function motube_uninstall() {
	define('MOTUBE_URL',WP_PLUGIN_URL.'motube/app/');
	
	require(ABSPATH . "wp-admin/includes/upgrade.php");
	global $wpdb;
	global $wp_version;
	global $wp_db_version;
	
	$sql = "DROP TABLE `motube_cat`";
	dbDelta($sql);
	
	$sql = "DROP TABLE `motube_vid`";
	dbDelta($sql);
}

/* Register query-variable for the motube category navigation */
add_filter('query_vars', 'mot_cat_var');

function mot_cat_var($qvars) {
    $qvars[] = '_motube_catvar';
    return $qvars;
}



/* The admin menu in WP */
add_action('admin_menu','motube_create_menu');

// One menu point for the videos, one for the category navigation
function motube_create_menu() {
	
	add_menu_page('Motube','Motube','author',__FILE__,'motube_video_administration',plugins_url('/app/img/icon.png',__FILE__));
	add_submenu_page(__FILE__,'Category Administration','Categories','author',__FILE__.'_category_administration','motube_category_administration');

}

/** Video administration panel for the admin interface  */
function motube_video_administration() {
	
	require_once(ABSPATH . "wp-admin/includes/upgrade.php");
	
	global $wpdb;
	$motube_vid = $wpdb->prefix.'motube_vid';
	$motube_cat= $wpdb->prefix.'motube_cat';

	// add new video to database
	if(isset($_POST['saveit']) && $_POST['saveit'] == 'yes') {
		$sql="INSERT INTO ".$motube_vid." (
		`id` ,
		`category` ,
		`titel` ,
		`description` ,
		`thumburl` ,
		`videourl`
		)
		VALUES (
		NULL ,".$_POST['motube_category'].", '".$_POST['motube_titel']."', '".$_POST['motube_description']."', '".$_POST['motube_thumburl']."', '".$_POST['motube_videourl']."'
		);";
		
		dbDelta($sql);
		
		print "<br /><div class='updated'><p>".__('Saved','motube')."!</p></div>";
		
	// delete video from database
	} else if(isset($_POST['deletevid']) && $_POST['deletevid'] == 'yes') {
			$wpdb->query("DELETE FROM $motube_vid WHERE id = ".$_POST['vid2del']);
			print "<br /><div class='updated'><p>".__('The video has been successfully deleted','motube')."!</p></div>";
	
	// show the administration interface for videos
	} else { 
		$cats2chose = $wpdb->get_results("SELECT * FROM $motube_cat");
		
		if(isset($_POST['vid_cat']) && $_POST['vid_cat'] != "") {
			$vids2chose = $wpdb->get_results("SELECT * FROM $motube_vid WHERE category = ".$_POST['vid_cat']);
		} else {
			$vids2chose = $wpdb->get_results("SELECT * FROM $motube_vid WHERE category = 0");
		}
	?>
	<div class='wrap'><h2><?php _e('Add Videos','motube'); ?></h2>
		<form method=post><table width=100%>
			<table>
				<tr>
                <td>
                    <label for="motube_category">
                        Category:
                    </label>
                </td>
				<td>
                    <select name="motube_category" style="min-width:150px;">
                    	<option value="0">Top-Level</option>
						<?php foreach($cats2chose as $ct) { ?>
						<option value="<?php echo $ct->category; ?>"><?php echo $ct->name; ?></option>
						<?php } ?>
					</select>
				</td>
				</tr>
				<tr>
                <td>
                    <label for="motube_titel">
                        Titel of the video:
                    </label>
                </td>
                <td>
                    <input type="text" value="" name="motube_titel" />
                </td>
				</tr>
				<tr>
                <td>
                    <label for="motube_description">
                        Description:
                    </label>
                </td>
                <td>
                    <textarea name="motube_description" cols="20" rows="5"></textarea>
                </td>
				</tr>
				<tr>
                <td>
                    <label for="motube_videourl">
                        Video URL*:
                    </label>
                </td>
                <td>
                    <input type="text" value="" name="motube_videourl" />
                </td>
				</tr>
				<tr>
                <td>
                    <label for="motube_thumburl">
                        Thumbnail URL:
                    </label>
                </td>
                <td>
                    <input type="text" value="" name="motube_thumburl" />
                </td>
				</tr>
			</table>
			<input type="hidden" name="saveit" value="yes">
			<input type="submit" value="Add video" />
		</form>
	</div>
	<br/>
	<br/>
	<div class="wrap"><h2>Manage Videos</h2>
		<div>
			<form method="post">
		        <select name="vid_cat" style="min-width:150px;">
	            	<option value="0">Top-Level</option>
					<?php foreach($cats2chose as $ct) { ?>
					<option value="<?php echo $ct->category; ?>"><?php echo $ct->name; ?></option>
					<?php } ?>
				</select>
			<input type="submit" value="Renew" />
			</form>
		</div>
		<h3>Category: <?php if(isset($_POST['vid_cat']) && $_POST['vid_cat'] != "") { echo get_motube_category_name($_POST['vid_cat']); } else { echo "Top-Level Category";} ?></h3>
		<form method="post">
			<table>
				<tr>
					<th style="width:200px;">Titel</th>
					<th style="width:200px;">Description</th>
					<th style="width:76px;">Video</th>
					<th style="width:76px;" >Delete</th>
				</tr>
				<?php foreach($vids2chose as $vd) { ?>
				<tr>
					<td><?php echo $vd->titel; ?></td>
					<td><?php echo $vd->description; ?></td>
					<td><a href="<?php echo $vd->videourl; ?>"><img src="<?php echo $vd->thumburl; ?>" width="56" height="56" alt="missing thumb"/></a></td>
					<td><input type="radio" name="vid2del" value="<?php echo $vd->id; ?>" /></td>
				</tr>
				<?php } ?>
			</table>
			<input type="hidden" name="deletevid" value="yes">
			<input type="submit" value="Delete video" />
		</form>
	</div>
	<?php }
	
}

/** Category administration interface */
function motube_category_administration() {
		
	require_once(ABSPATH . "wp-admin/includes/upgrade.php");
	
	global $wpdb;
	$motube_cat= $wpdb->prefix.'motube_cat';
	$motube_vid = $wpdb->prefix.'motube_vid';
	
	// add a new category to the database
	if(isset($_POST['savecat']) && $_POST['savecat'] == 'yes') {
		$sql="INSERT INTO ".$motube_cat." (
		`category`,
		`name` ,
		`parentcategory`
		)
		VALUES (
		NULL , '".$_POST['motube_categoryname']."', ".$_POST['motube_parentcategory']."
		);
		";
		
		dbDelta($sql);
		
		print "<br /><div class='updated'><p>".__('Saved','motube')."!</p></div>";
	// delete a category from the database, if there are no videos in it
	} else if(isset($_POST['deletecat']) && $_POST ['deletecat'] == 'yes') {
		$count_vids = $wpdb->query("SELECT * FROM $motube_vid WHERE category = ".$_POST['cat2del']);
		
		if($count_vids == 0) {
			$wpdb->query("DELETE FROM $motube_cat WHERE category = ".$_POST['cat2del']);
			print "<br /><div class='updated'><p>".__('The category has been successfully deleted','motube')."!</p></div>";
		} else {
			print "<br /><div class='error'><p>".__('The category could not be deleted. Remove all videos before','motube')."!</p></div>";
		}
		
	// show the category administration interface
	} else { 
	
		$cats2chose = $wpdb->get_results("SELECT * FROM $motube_cat");
	
	?>
	<div class='wrap'><h2><?php _e('Add Categories','motube'); ?></h2>
		<form method=post><table width=100%>
			<table>
				<tr>
                <td>
                    <label for="motube_categoryname">
                        Category Name:
                    </label>
                </td>
                <td>
                    <input type="text" value="" name="motube_categoryname" />
                </td>
				</tr>
				<tr>
                <td>
                    <label for="motube_parentcategory">
                        Parent Category:
                    </label>
                </td>
				<td>
                    <select name="motube_parentcategory" style="min-width:150px;">
                    	<option value="0">Top-Level-Category</option>
						<?php foreach($cats2chose as $ct) { ?>
						<option value="<?php echo $ct->category; ?>"><?php echo $ct->name; ?></option>
						<?php } ?>
					</select>
                </td>
				</tr>
			</table>
			<input type="hidden" name="savecat" value="yes">
			<input type="submit" value="Add new category" />
		</form>
		<br/><br/>
	</div>
	<div class="wrap"><h2>Delete Categories</h2>
		<form method=post>
            <select name="cat2del" width="150">
				<?php foreach($cats2chose as $ct) { ?>
				<option value="<?php echo $ct->category; ?>"><?php echo $ct->name; ?></option>
				<?php } ?>
			</select>
		<p>Note: You have to delete all videos from a category before you delete it.</p>
			<input type="hidden" name="deletecat" value="yes">
			<input type="submit" value="Delete Category" />
		</form>
	</div>
	<?php }
}

// returns the name of the current category by the given id
function get_motube_category_name($catid) {
	global $wpdb;
	$motube_cat= $wpdb->prefix.'motube_cat';
	
	$res = $wpdb->get_row("SELECT * FROM $motube_cat WHERE category = $catid");
	return $res->name;
}

// returns the name of a motube category by the given id
function get_parent_category_id($current_motube_cat) {
	
	global $wpdb;
	$motube_cat= $wpdb->prefix.'motube_cat';
	
	$res = $wpdb->get_row("SELECT * FROM $motube_cat WHERE category = $current_motube_cat");
	return $res->parentcategory;
}

// returns a result array of all the motube categories
function get_motube_cats($current_motube_parrent_cat) {
	global $wpdb;
	$motube_cat= $wpdb->prefix.'motube_cat';
	
	$result = $wpdb->get_results("SELECT * FROM $motube_cat WHERE parentcategory = $current_motube_parrent_cat");
	return $result;
}

// returns a result array of all the motube vids from the chosen category
function get_motube_vids($current_motube_cat) {
	global $wpdb;
	$motube_vid= $wpdb->prefix.'motube_vid';
	
	$result = $wpdb->get_results("SELECT * FROM $motube_vid WHERE category = $current_motube_cat");
	return $result;
}

/* This ist the most important function: It returns the whole motube-application for the motube-theme. This is what the user gets to see. */
function get_motube() { 

define('MOTUBE_URL',WP_PLUGIN_URL.'/motube/app/');

?>
<!DOCTYPE html>
<html lang="en">
    <head>
    	<!-- Meta Tags -->
        <meta name="viewport" content="user-scalable=0" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-touch-fullscreen" content="yes" />
		
        <title>MoTube</title>
		
		<!-- CSS -->
		<link rel="stylesheet" href="<?php echo MOTUBE_URL ?>/css/motube.css" type="text/css" charset="utf-8" />
		
		<!-- Scripts -->
        <script src="<?php echo MOTUBE_URL ?>/js/mObb.js" type="text/javascript" charset="utf-8">
        </script>
        <script type="text/javascript" charset="utf-8">
            
			// the initialization of the mObb object, which holds the 3D-navigation
            window.addEventListener('load', mObb.init, true);
            
			// defines the center position of the video-wheel in dependence of the current position and sets the timeout for a smooth transition
            function initScreen(){
                setTimeout("window.scrollTo(0,1);", 100);
                
                switch (window.orientation) {
                    case 0:
                        document.getElementById('shape').style.top = 370 + "px";
                        break;
                        
                    case -90:
                        document.getElementById('shape').style.top = 250 + "px";
                        break;
                        
                    case 90:
                        document.getElementById('shape').style.top = 250 + "px";
                        break;
                        
                    case 180:
                        document.getElementById('shape').style.top = 370 + "px";
                        break;
                }
                
            }
			
            // If the orientation of the iPhone changes, we have to change the position of the center of our video-wheel
            function updateOrientation(){
                initScreen();
            }
        </script>
    </head>
    <body onload="initScreen()" onorientationchange="updateOrientation()">
        <div id="container">
            <div id="sidebar">
                <a href="?motube_catvar=1"><img src="<?php echo MOTUBE_URL ?>img/logo.jpg" width="296" height="161" alt="MoTube Logo" /></a>
				<div id="navigation">
                    <ul>
						<?php
						global $wp_query;
						
						$motube_catvar = $_GET['motube_catvar'];
						
						$options = array();
						$options['options']['min_range'] = 1;
						$options['options']['max_range'] = 999999;
						
						if(filter_var($motube_catvar,FILTER_VALIDATE_INT,$options)) {
							$mocucat = $motube_catvar;
						} else {
							$mocucat = 0;
						}
						
						/* Get all the categories */
						$mocat = get_motube_cats($mocucat);
						
						foreach($mocat as $ct) {
							echo '<li><a href="?motube_catvar='.$ct->category.'">'.$ct->name.'</a></li>';
						}
						
						/* The top back link goes to the home page, any other is one directory up */
						if(get_parent_category_id($mocucat) === 0) {
							echo '<li><a id="directory_up" href="'.get_bloginfo('url').'">&nbsp;</a></li>';
						} else {
							echo '<li><a id="directory_up" href="?motube_catvar='.get_parent_category_id($mocucat).'">&nbsp;</a></li>';
						}
						?>
                    </ul>
                </div>
            </div>
			<?php 
			/* Get all the Videos of this category */
			?>
            <div id="stage">
                <ul id="shape" class="ring">
                    	
						<?php
							/* Get all the videos  */
							$vids = get_motube_vids($mocucat);
							
							foreach($vids as $vid) {
								echo '<li class="plane">';
								echo '<a href="'.$vid->videourl.'"><img src="'.$vid->thumburl.'"></a><h3>'.$vid->titel.'</h3><p>'.$vid->description.'</p></li>';
							}
						?>
				</ul>
            </div>
        </div>
    </body>
</html> <?php
}

add_shortcode('motube','get_motube');
?>