<?php

/**
 * Represents the view for the public-facing component of the plugin.
 *
 * @package    PIB
 * @subpackage Views
 * @author     Phil Derksen <pderksen@gmail.com>, Nick Young <mycorpweb@gmail.com>
 */

	//Add Custom CSS
	function pib_add_custom_css() {
	    global $pib_options;

	    $custom_css = trim( $pib_options['custom_css'] );

	    echo "\n" .
		   '<style type="text/css">' . "\n" .
		   $custom_css . "\n" . //Put custom css
		   '</style>' . "\n";
	}
	add_action( 'wp_head', 'pib_add_custom_css' );

	// Function for rendering "Pin It" button base html.
	// HTML comes from Pinterest Widget Builder 7/10/2013.
	// http://business.pinterest.com/widget-builder/#do_pin_it_button
	// Sample HTML from widget builder:
	/*
	<a href="//pinterest.com/pin/create/button/?url=http%3A%2F%2Fwww.flickr.com%2Fphotos%2Fkentbrew%2F6851755809%2F&media=http%3A%2F%2Ffarm8.staticflickr.com%2F7027%2F6851755809_df5b2051c9_z.jpg&description=Next%20stop%3A%20Pinterest" data-pin-do="buttonPin" data-pin-config="above">
		<img src="//assets.pinterest.com/images/pidgets/pin_it_button.png" />
	</a>
	*/
	function pib_button_base( $post_url, $image_url, $description, $count_layout ) {
		global $pib_options;

		// Use updated backup button image URL from Pinterest.
		$btn_img_url = '//assets.pinterest.com/images/pidgets/pin_it_button.png';

		// Add "Pin It" title attribute.
		$inner_btn_html = '<img src="' . $btn_img_url . '" title="Pin It" />';

		// Set data attribute for button style.
		if ( $pib_options['button_style'] == 'image_selected' )
			$data_pin_do = 'buttonPin'; // image pre-selected
		else
			$data_pin_do = 'buttonBookmark'; // user selects image (default)

		// Set data attribute for count bubble style.
		if ( $count_layout == 'horizontal' )
			$data_pin_config = 'beside';
		elseif ( $count_layout == 'vertical' )
			$data_pin_config = 'above';
		else
			$data_pin_config = 'none';

		// Link href always needs all the parameters in it for the count bubble to work.
		// Pinterest points out to use protocol-agnostic URL for popup.
	    $link_href = '//pinterest.com/pin/create/button/' .
			'?url='         . rawurlencode( $post_url ) .
			'&media='       . rawurlencode( $image_url ) .
			'&description=' . rawurlencode( $description );

		// Full link html with data attributes.
		// Add rel="nobox" to prevent lightbox popup.
	    $link_html = '<a href="' . $link_href . '" ' .
			'data-pin-do="' . $data_pin_do . '" ' .
			'data-pin-config="' . $data_pin_config . '" ' .
			'rel="nobox">' .
			$inner_btn_html . '</a>';

	    return $link_html;
	}

	// Button HTML to render.
	function pib_button_html() {
	    global $pib_options;
		global $post;
	    $postID = $post->ID;

	    //Return nothing if sharing disabled on current post
		if ( get_post_meta( $postID, 'pib_sharing_disabled', 1 ) )
			return '';

	    //Set post url, image url and description from current post meta
		$post_url = get_post_meta( $postID, 'pib_url_of_webpage', true );
		$image_url = get_post_meta( $postID, 'pib_url_of_img', true );
		$description = get_post_meta( $postID, 'pib_description', true );


	    //Set post url to current post if still blank
	    if ( empty( $post_url ) )
			$post_url = get_permalink( $postID );

	    //Set image url to first image if still blank
	    if ( empty( $image_url ) ) {
		   //Get url of img and compare width and height
		   $output = preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches );
		   //$first_img = $matches [1] [0];
		   //$image_url = $first_img;
		   $image_url = $matches [1] [0];
	    }

	    //Set description to post title if still blank
	    if ( empty( $description ) ) { $description = get_the_title( $postID ); }

		$count_layout = $pib_options['count_layout'];

	    $base_btn = pib_button_base( $post_url, $image_url, $description, $count_layout );

	    // Don't wrap with div if using other sharing buttons or "remove div" is checked.
	    if ( (bool)$pib_options['remove_div'] )
			return $base_btn;
	    else
			return '<div class="pin-it-btn-wrapper">' . $base_btn . '</div>'; // Surround with div tag
	}


	//Render share bar on pages with regular content
	function pib_render_content( $content ) {
		global $pib_options;
		global $post;    
		$postID = $post->ID;

	    //Determine if button displayed on current page from main admin settings
	    if (
		   ( is_home() && ( (bool)$pib_options['post_page_types']['display_home_page'] ) ) ||
		   ( is_front_page() && ( (bool)$pib_options['post_page_types']['display_front_page'] ) ) ||
			( is_single() && ( (bool)$pib_options['post_page_types']['display_posts'] ) ) ||
		   ( is_page() && ( (bool)$pib_options['post_page_types']['display_pages'] ) && !is_front_page() ) ||
		
		   //archive pages besides categories (tag, author, date, search)
		   //http://codex.wordpress.org/Conditional_Tags
		   ( is_archive() && ( (bool)$pib_options['post_page_types']['display_archives'] ) && 
			  ( is_tag() || is_author() || is_date() || is_search() ) 
		   )
		  ) {
		   if ( (bool)$pib_options['post_page_placement']['display_above_content'] ) {
			  $content = pib_button_html() . $content;
		   }
		   if ( (bool)$pib_options['post_page_placement']['display_below_content'] ) {
			  $content .= pib_button_html();
		   }
	    }	

		return $content;
	}
	add_filter( 'the_content', 'pib_render_content' );

	//Render share bar on pages with excerpts if option checked

	function pib_render_content_excerpt( $content ) {
	    global $pib_options;
	    global $post;
		$postID = $post->ID;

	    if ( $pib_options['post_page_placement']['display_on_post_excerpts'] ) {
		   if (
			  ( is_home() && ( $pib_options['post_page_types']['display_home_page'] ) ) ||
			  ( is_front_page() && ( $pib_options['post_page_types']['display_front_page'] ) )           
			 ) {
			  if ( $pib_options['post_page_placement']['display_above_content'] ) {
				 $content = pib_button_html() . $content;
			  }
			  if ( $pib_options['post_page_placement']['display_below_content'] ) {
				 $content .= pib_button_html();
			  }
		   }   

		}

		return $content;
	}
	add_filter( 'the_excerpt', 'pib_render_content_excerpt' );