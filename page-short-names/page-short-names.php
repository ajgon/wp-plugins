<?php
/*
Plugin Name: Page Short Names
Plugin URI: http://github.com/ajgon/wp-plugins/tree/master/page-short-names
Description: A simple plugin which add "Short" Page names to use in menus.
Author: Igor Rzegocki
Version: 1.0
Author URI: http://www.rzegocki.pl/
*/

class Walker_PageShortNames extends Walker_Page {
	function start_el(&$output, $page, $depth, $args, $current_page) {
		if ( $depth )
			$indent = str_repeat("\t", $depth);
		else
			$indent = '';
			
		$args['page_short_names'] = isset($args['page_short_names']) ? $args['page_short_names'] : true;

		extract($args, EXTR_SKIP);
		$css_class = array('page_item', 'page-item-'.$page->ID);
		if ( !empty($current_page) ) {
			$_current_page = get_page( $current_page );
			_get_post_ancestors($_current_page);
			if ( isset($_current_page->ancestors) && in_array($page->ID, (array) $_current_page->ancestors) )
				$css_class[] = 'current_page_ancestor';
			if ( $page->ID == $current_page )
				$css_class[] = 'current_page_item';
			elseif ( $_current_page && $page->ID == $_current_page->post_parent )
				$css_class[] = 'current_page_parent';
		} elseif ( $page->ID == get_option('page_for_posts') ) {
			$css_class[] = 'current_page_parent';
		}

		$css_class = implode( ' ', apply_filters( 'page_css_class', $css_class, $page, $depth, $args, $current_page ) );

		$short_title = page_short_names_get_short_title($page);
		$output .= $indent . '<li class="' . $css_class . '"><a href="' . get_permalink($page->ID) . '">' . $link_before . apply_filters( 'the_title', ($page_short_names ? ($short_title ? $short_title :$page->post_title) : $page->post_title), $page->ID ) . $link_after . '</a>';

		if ( !empty($show_date) ) {
			if ( 'modified' == $show_date )
				$time = $page->post_modified;
			else
				$time = $page->post_date;

			$output .= " " . mysql2date($date_format, $time);
		}
	}
}

function page_short_names_switch_walker($args) {
	$args['walker'] = new Walker_PageShortNames;
	return $args;
}

function page_short_names_get_short_title($post) {
	$short_title = get_post_meta($post->ID, 'page-short-names', true);
	$short_title = $short_title ? ($short_title == $post->post_title ? false : ($post->post_status == 'auto-draft' ? false : $short_title)) : false;
	return $short_title;
}

function page_short_names_enter_title_here($title_text, $post) {
	if($post->post_type == 'page') {
		$short_title = page_short_names_get_short_title($post);
		return $title_text . '</label><input id="short-title" type="text" autocomplete="off" tabindex="1" size="30" name="post_short_title"' . ($short_title ? ' value="' . $short_title . '"' : '') .' /><label id="short-title-prompt-text" style="visibility: hidden;" class="hide-if-no-js" for="short-title">' . __('Enter short title here (leave blank to use normal title)');
	}
}

function page_short_names_save_post($id, $post) {
	if($post->post_type == 'page') {
		$short_title = (empty($_REQUEST['post_short_title']) ? $post->post_title : $_REQUEST['post_short_title']);
		if(get_post_meta($id, 'page-short-names', true)) {
			update_post_meta($id, 'page-short-names', $short_title);
		} else {
			add_post_meta($id, 'page-short-names', $short_title);
		}
	}
}

function page_short_names_init_plugin() {
	global $post;
	if($post && $post->post_type == 'page') {
		wp_enqueue_style('page-short-names', implode(DIRECTORY_SEPARATOR, array(plugin_dir_url(__FILE__), 'page-short-names.css')));
		wp_enqueue_script('page-short-names', implode(DIRECTORY_SEPARATOR, array(plugin_dir_url(__FILE__), 'page-short-names.js')));
		
		add_filter('enter_title_here', 'page_short_names_enter_title_here', 10, 3);
	}
}

add_action('wp_page_menu_args', 'page_short_names_switch_walker', 10, 2);
add_action('save_post', 'page_short_names_save_post', 10, 3);
add_action('admin_head', 'page_short_names_init_plugin');

?>
