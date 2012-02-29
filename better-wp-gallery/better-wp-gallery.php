<?php
/*
Plugin Name: Better WP Gallery
Plugin URI: http://github.com/ajgon/wp-plugins/tree/master/better-wp-gallery
Description: Simple plugin - when you set <em><strong>imagetag</em></strong> or <em><strong>imageicon</em></strong> to empty string in [gallery] shorttag it will remove whole tag (without content), avoiding HTML errors. Also with <strong><em>galleryclass</em></strong> parameter to set additional classes for gallery container and with <em><strong>excludepostimages</em></strong> to include only raw gallery items attached to specified post.
Author: Igor Rzegocki
Version: 0.42
Author URI: http://www.rzegocki.pl/
*/
remove_shortcode('gallery');
add_shortcode('gallery', 'better_wp_gallery_shortcode');

/**
 * The Gallery shortcode.
 *
 * This implements the functionality of the Gallery Shortcode for displaying
 * WordPress images on a post.
 *
 * Some minor improvements implemented to improve flexibility
 *
 * @since 2.5.0
 *
 * @param array $attr Attributes of the shortcode.
 * @return string HTML content to display gallery.
 */
function better_wp_gallery_shortcode($attr) {
	global $post;

	static $instance = 0;
	$instance++;

	// Allow plugins/themes to override the default gallery template.
	$output = apply_filters('post_gallery', '', $attr);
	if ( $output != '' )
		return $output;

	// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
	if ( isset( $attr['orderby'] ) ) {
		$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
		if ( !$attr['orderby'] )
			unset( $attr['orderby'] );
	}

	extract(shortcode_atts(array(
		'order'             => 'ASC',
		'orderby'           => 'menu_order ID',
		'id'                => $post->ID,
		'itemtag'           => 'dl',
		'icontag'           => 'dt',
		'captiontag'        => 'dd',
        'galleryclass'      => '',
        'excludepostimages' => false,
		'columns'           => 3,
		'size'              => 'thumbnail',
		'include'           => '',
		'exclude'           => ''
	), $attr));

	$id = intval($id);
	if ( 'RAND' == $order )
		$orderby = 'none';
        
    if ( $excludepostimages ) {
        preg_match_all('/wp-image-([0-9]+)/', $post->post_content, $matches);
        $__exclude = $matches[1];
        $__exclude[] = get_post_meta( $post->ID, '_thumbnail_id', true );
        $exclude = implode(',', array($exclude, implode(',', $__exclude)));
    }

	if ( !empty($include) ) {
		$include = preg_replace( '/[^0-9,]+/', '', $include );
		$_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );

		$attachments = array();
		foreach ( $_attachments as $key => $val ) {
			$attachments[$val->ID] = $_attachments[$key];
		}
	} elseif ( !empty($exclude) ) {
		$exclude = preg_replace( '/[^0-9,]+/', '', $exclude );
		$attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	} else {
		$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	}

	if ( empty($attachments) )
		return '';

	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $att_id => $attachment )
			$output .= wp_get_attachment_link($att_id, $size, true) . "\n";
		return $output;
	}

	$itemtag = tag_escape($itemtag);
	$captiontag = tag_escape($captiontag);
	$columns = intval($columns);
	$itemwidth = $columns > 0 ? floor(100/$columns) : 100;
	$float = is_rtl() ? 'right' : 'left';

	$selector = "gallery-{$instance}";

	$gallery_style = $gallery_div = '';
	if ( apply_filters( 'use_default_gallery_style', true ) )
		$gallery_style = "
		<style type='text/css'>
			#{$selector} {
				margin: auto;
			}
			#{$selector} .gallery-item {
				float: {$float};
				margin-top: 10px;
				text-align: center;
				width: {$itemwidth}%;
			}
			#{$selector} img {
				border: 2px solid #cfcfcf;
			}
			#{$selector} .gallery-caption {
				margin-left: 0;
			}
		</style>
		<!-- see gallery_shortcode() in wp-includes/media.php -->";
	$size_class = sanitize_html_class( $size );
	$gallery_div = "<div id='$selector' class='gallery galleryid-{$id} gallery-columns-{$columns} gallery-size-{$size_class} {$galleryclass}'>";
	$output = apply_filters( 'gallery_style', $gallery_style . "\n\t\t" . $gallery_div );

	$i = 0;
	foreach ( $attachments as $id => $attachment ) {
		$link = isset($attr['link']) && 'file' == $attr['link'] ? wp_get_attachment_link($id, $size, false, false) : wp_get_attachment_link($id, $size, true, false);

		$output .= ( empty($itemtag) ? '' : "<{$itemtag} class='gallery-item'>");
		$output .= "
			" . ( empty($icontag) ? '' : "<{$icontag} class='gallery-icon'>" ) ."
				$link
			" . ( empty($icontag) ? '' : "</{$icontag}>" );
		if ( $captiontag && trim($attachment->post_excerpt) ) {
			$output .= "
				<{$captiontag} class='wp-caption-text gallery-caption'>
				" . wptexturize($attachment->post_excerpt) . "
				</{$captiontag}>";
		}
		$output .= ( empty($itemtag) ? '' : "</{$itemtag}>" );
		if ( $columns > 0 && ++$i % $columns == 0 )
			$output .= '<br style="clear: both" />';
	}

	$output .= "
			<br style='clear: both;' />
		</div>\n";

	return $output;
}
?>
