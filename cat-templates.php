<?php
/*
Plugin Name: Category Templates
Plugin URI: https://github.com/c3mdigital/Category-Templates
Description: Gives you the ability to apply different templates to your posts either individually, or by category.
Version: 3.0
Author: Aaron Gloege
Contributor: Chris Olbekson
Author URI: http://www.aarongloege.com/
Contributor URI: http://c3mdigital.com

===============================================================================

Copyright 2009  Aaron Gloege  (contact@aarongloege.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

===============================================================================
*/

function cat_temp_menus() {
    add_submenu_page('themes.php','Category Templates', 'Category Templates', 'activate_plugins', 'themes.php?page=cat_temp_options_page', 'cat_temp_options_page');
    if (function_exists('add_meta_box')) {
        add_meta_box('cat_template_select','Post Template','cat_temp_meta','post','side','low');
    }
}

function cat_temp_meta() {
	global $wpdb, $post_ID;
	
	$templates = cat_temp_get_page_templates();
	$current = cat_temp_post_template($post_ID);

	$out = '<select name="cat_temp_template" style="width:100%">';
	$out .= '<option value="none"';
	if ($post_ID == 0 || !$current) $out .= ' selected="selected"';
	$out .= '>No Template</option>';
	$out .= '<option value="/single.php"';
	if ($current == "/single.php") $out .= ' selected="selected"';
	$out .= '>Default Template</option>';
	foreach ($templates as $template => $file) {
		$out .= '<option value="'.$file.'"';
	if ($current == $file) $out .= ' selected="selected"';
	$out .= '>'.$template.'</option>';
	}
	$out .= "</select>";
	$out .= "<p>Choosing a template here will override any templates assigned to this post's categories.</p>";
	echo $out;
}

function cat_temp_post_submit($post_ID) {
  global $wpdb;
  if ($_POST['cat_temp_template']) {
		$templates =  (get_option("cat_temp_post"));
		if ($_POST['cat_temp_template'] != 'none') {
			$templates[$post_ID] = $_POST['cat_temp_template'];
		} else {
			if ($templates[$post_ID]) {
				unset($templates[$post_ID]);
			}
		}
		update_option("cat_temp_post", ($templates));
  }
}

function cat_temp_post_template($ID) {
	$templates =  (get_option("cat_temp_post"));
	return $templates[$ID];
}

function cat_temp($template) {
	global $wp_query;
	$post_obj = $wp_query->get_queried_object();
	
	if (is_single()) {
		$data = cat_temp_get_data();
		$categories = get_the_category($post_obj->ID);
	} else if (is_category()) {
		$data = cat_temp_get_data(true);
		$categories[0] = &get_category($post_obj->cat_ID);
	}
	$temp_data;
	
	// Get templates for post categories
	foreach((array)$categories as $category) {
		if ($data[$category->term_id]['template'] != '0') {
			$temp_data[$data[$category->term_id]['template']] = $data[$category->term_id]['priority']+($category->term_id/80000);
		}
	}
	
	// Get templates for parent categories
	foreach((array)$data as $key => $cat) {
		if ($cat['all'] == "all" && $cat['template'] != "0") {
			$id = (is_single()) ? (int)$cat['id'] : $key;
			$descendants = get_term_children($id, 'category');
			if ($descendants && in_category($descendants)) {
				$temp_data[$cat['template']] = $cat['priority']+($cat['id']/80000);
			}
		}
	}
	
	//print_r($temp_data);
	
	// Sort templates by priotiry, and return the one with the highest priority
	if (is_array($temp_data)) {
		asort($temp_data);
		$template = array_shift(array_keys($temp_data));
	}
	
	// If currrent post has a template, use that one instead
	if (is_single()) {
		$overRule = cat_temp_post_template($post_obj->ID);
		if ($overRule) $template = $overRule;
	}
	// Return template path
	if (!empty($template)) {
		if (file_exists(TEMPLATEPATH.$template)) {
			include(TEMPLATEPATH.$template);
			exit;
		}
	}
}

function cat_temp_is_cat($cat, $_post = null) {
	if (in_category($cat, $_post)) {
		return true;
	} else {
		$descendants = get_term_children((int)$cat, 'category');
		if ($descendants && in_category($descendants, $_post)) return true;
	}
	return false;
}


function cat_temp_get_page_templates($str = "Template Name") {
	$themes = get_themes();
	$theme = get_current_theme();
	$templates = $themes[$theme]['Template Files'];
	$page_templates = array();
	
	if (is_array($templates)) {
		foreach((array)$templates as $template) {
			if (!file_exists($template)) $template = WP_CONTENT_DIR.$template;
			$template_data = implode('', file($template));
		
			$name = '';
			if (preg_match('|'.$str.':(.*)$|mi', $template_data, $name))
				$name = $name[1];
		
			if (!empty($name)) {
				$page_templates[trim($name)] = str_replace($themes[$theme]['Template Dir'], "", $template);// basename( $template );
			}
		}
	}
	return $page_templates;
}

function cat_temp_cats($item, $current, $archive=false) {
	if ($archive) {
		$templates = cat_temp_get_page_templates('Archive Template');
		$default = '/archive.php';
	} else {
		$templates = cat_temp_get_page_templates();
		$default = '/single.php';
	}
		
	$out = '<select title="Template" class="ct_template" name="data';
	if ($archive) $out .= '[archive]';
	$out .= '['.$item.'][template]">';
	$out .= '<option value="0"';
	if ($current == "0") $out .= ' selected="selected"';
	$out .= '>No Template</option>';
	$out .= '<option value="'.$default.'"';
	if ($current == $default) $out .= ' selected="selected"';
	$out .= '>Default Template</option>';
	foreach ($templates as $template => $file) {
		$out .= '<option value="'.$file.'"';
	if ($current == $file) $out .= ' selected="selected"';
	$out .= '>'.$template.'</option>';
	}
	$out .= "</select>";
	return $out;
}

function cat_temp_categories($child = 0) {
	$data = array(
		"hide_empty" => false,
		"child_of" => $child,
		"pad_count" => false,
	);
	$categories = get_categories($data);
	
	$list = array();
	foreach ((array)$categories as $cat) {
		if ($cat->parent == $child) {
			$list[] = array(
				"name" => $cat->name,
				"id" => $cat->cat_ID,
				"count" => cat_temp_getALLposts($cat->cat_ID),
				"acount" => $cat->category_count,
				"child" => cat_temp_categories($cat->cat_ID),
			);
		}
	}
	return $list;
}

function cat_temp_li_fun($data) {
	$out = "<ul class=\"cat-temp\">";
	foreach ((array)$data as $root) {
		$out .= "<li>".$root['name']." (".$root['count'].")</li>";
		if (count($root['child']) > 0) {
			$out .= cat_temp_li_fun($root['child']);
		}
		//$out .= "</li>";
	}
	$out .= "</ul>";
	return $out;
}

function cat_temp_priority($item, $current, $archive) {
	$pri = array("Lowest","Low","Medium","High","Highest");
	$out = '<select class="ct_priority" title="Template Priority" name="data';
	if ($archive) $out .= '[archive]';
	$out .= '['.$item.'][priority]">';
	$t = 0;
	for ($i = 10; $i >= 1; $i = $i-2) {
		$out .= '<option value="'.$i.'"';
		if (intval($current) == $i) $out .= ' selected="selected"';
		$out .= '>'.$pri[$t].'</option>';
		$t++;
	}
	$out .= "</select>";
	return $out;
}

function cat_temp_getALLposts($ID) {
	$td = array(
		'numberposts' => -1,
		'category' => $ID,
	);
	return count(get_posts($td));

}

function cat_temp_admin_head() {
	echo '<link type="text/css" rel="stylesheet" media="all" href="'.plugins_url('category-templates/admin.css').'" />';
	echo '<script type="text/javascript" src="'.plugins_url('category-templates/admin.js').'"></script>';
}

function cat_temp_get_data($archive=false, $id=false) {
	$t = (!$archive) ?  (get_option('cat_temp_data')) :  (get_option('cat_arch_data'));
	return (!$id) ? $t : $t[$id];
}

function cat_temp_update($data) {
	$archive = $data['archive'];
	unset($data['archive']);
	update_option('cat_temp_data',  ($data));
	update_option('cat_arch_data',  ($archive));
}

function cat_temp_delete() {
	delete_option('cat_temp_data');
	delete_option('cat_temp_post');
	delete_option('cat_arch_data');
}

function cat_temp_sub_cats($id, $data, $archive=false) {
	$out .= ' <input type="checkbox"';
	if ($data == "all") $out .= ' checked="checked"';
	$out .= ' name="data';
	if ($archive) $out .= "[archive]";
	$out .= '['.$id.'][all]" value="all" title="Apply to sub-categories" /> <small>Apply to sub-categories</small>';
	return $out;
}

function cat_temp_templates($id, $archive) {
	if ($archive) {
		$title = "Archive";
		$class = " class=\"noborder\"";
		$class2 = " noborder";
	} else {
		$title = "Posts";
	}
	$data = cat_temp_get_data($archive, $id);

	
	$out .= "<td class=\"r$class2\" >$title:</td>";
	$out .= "<td $class><div>".cat_temp_cats($id, $data['template'], $archive);
	$out .= cat_temp_sub_cats($id, $data['all'], $archive).'</div></td>';
	$out .= "<td $class><div>".cat_temp_priority($id, $data['priority'], $archive)."</div></td>";
	return $out;
}

function cat_temp_td_fun($data, $padding = 5) {
	$out = "";
	foreach ((array)$data as $root) {
		$out .= '<tr>';
		$out .= '<td class="c" rowspan="2">'.$root['id'];
		$out .= '<input type="hidden" name="data['.$root['id'].'][id]" value="'.$root['id'].'" />';
		$out .= '</td>';
		$out .= '<td class="wide" style="padding-left:'.$padding.'px;" rowspan="2">'.$root['name'].'</td>';
		$out .= cat_temp_templates($root['id'], true);
		$out .= '<td rowspan="2">'.$root['acount'].' ('.$root['count'].')</td>';
		$out .= "</tr><tr>";
		$out .= cat_temp_templates($root['id'], false);
		$out .= '</tr>';
		
		if (count($root['child']) > 0) {
			$out .= cat_temp_td_fun($root['child'], $padding+10);
		}
	}
	return $out;
}

function cat_temp_options_page() {
$_GET['lang'] = 'all';
?>

<div class="wrap cat-template">
  <div class="icon32" id="icon-themes"><br/>
  </div>
  <h2>Settings for Category Templates</h2>
  <?php
if ($_POST['update_theme']) {
	cat_temp_update($_POST['data']);
	echo '<div id="message" class="updated"><p>Theme Settings Updated</p></div>';
}
	?>
  <p>Configure the options below to apply templates to your categories, and their posts.</p>
  <form method="post" action="<?php echo $_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING'];?>">
    <?php
        // New way of setting the fields, for WP 2.7 and newer
        if(function_exists('settings_fields')){
            settings_fields('cat-temp-options');
        } else {
            wp_nonce_field('update-options');
            ?>
    <input type="hidden" name="action" value="update" />
    <?php
        }
    ?>
    <table>
      <tr>
        <td style="vertical-align:top" width="70%"><table width="100%" class="widefat" id="cat_temps">
            <thead>
              <tr>
                <th width="2%" class="c">ID</th>
                <th><?php _e('Category');?></th>
                <th colspan="2"><?php _e('Template');?></th>
                <th width="4%"><?php _e('Priority');?></th>
                <th><?php _e("Post's");?></th>
              </tr>
            </thead>
            <tbody>
              <?php echo cat_temp_td_fun(cat_temp_categories());?>
            </tbody>
          </table></td>
        <td style="vertical-align:top"><table width="100%" class="widefat">
            <thead>
              <tr>
                <th>Instructions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><p>To specify a template for a certain category, select the template from the appropriate drop down box. The templates listed are the standard WordPress page templates.</p>
                  <p>If not already done, you must add the following codes to the top of your template pages inside your <strong>current theme</strong> for a template to appear here.</p>
                  <p>Post Templates:<br />
                  <code>&lt;?php /* Template Name: Blog Template */ ?&gt;</code> </p>
                  <p>Archive Templates:<br />
                  <code>&lt;?php /* Archive Template: Blog Template */ ?&gt;</code></p>
                  <p>Checking the <strong>apply to sub-categories</strong> checkbox will apply the selected template to all posts within the category <em>and</em> sub-categories. Templates will only apply to sub-categories that have no templates assigned, or if its of higher priority.</p>
                <p>Choosing a priority will tell the plugin which template to favor if more than one template is returned. If the priorities are the same, the category with the lowest ID will have its template applied.</p></td>
              </tr>
            </tbody>
          </table></td>
      </tr>
    </table>
    <p class="submit">
      <input type="submit" name="update_theme" value="<?php _e('Save Changes') ?>" />
    </p>
  </form>
</div>
<?php

}

add_action('admin_head', 'cat_temp_admin_head');
add_action('admin_menu', 'cat_temp_menus');
add_action('save_post', 'cat_temp_post_submit');
add_filter('template_redirect', 'cat_temp');

?>
