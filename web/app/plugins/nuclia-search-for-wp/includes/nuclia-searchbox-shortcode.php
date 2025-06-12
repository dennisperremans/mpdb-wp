<?php
/**
 * Nuclia_Search_Shortcode file.
 *
 * @since   1.0.0
 *
 * @package kalyx\nuclia-search-for-wp
 *
 * Shortcode : [nuclia_searchbox zone="europe-1" kbid="XXX" features="navigateToLink,suggestions"]
 */

namespace Kalyx\WPSWN;

\add_shortcode( 'nuclia_searchbox', 'Kalyx\WPSWN\nuclia_searchbox' );
   
function nuclia_searchbox($atts) {
	
	extract(shortcode_atts(
	array(
		'zone' => '',
		'kbid' => '',
		'features' => 'navigateToLink'
	), $atts));

	// we need zone and kbid to display searchbox
	if ( empty($zone) || empty($kbid) ) :
		if ( current_user_can('edit_posts')) {
			return sprintf(
				'<div style="color:red; border: 2px dotted red; padding: .5em;">%s</div>',
				__("Nuclia shortcode misconfigured. Please provide your zone and your kbid.", 'klx-nuclia-search-for-wp' )
			);
		} else {
			return '';
		}
	endif;
	
	// sanitize atts
	$zone = sanitize_title( $zone );
	$kbid = sanitize_title( $kbid );
	
	// available features
	$nuclia_searchbox_features = array("navigateToLink","permalink","suggestions"/*,"suggestLabels","filter","relations"*/);
	
	$features = explode( ',', $features );
	$features = array_filter( $features, 'sanitize_title' );
	$features = array_intersect( $nuclia_searchbox_features, $features );
	$features = implode( ',', $features );
	
	// TODO : check if the searchbox is available : is_valid_credentials( $zone, $kbid ); 
	
	// enqueue script
	wp_enqueue_script('nuclia-widget', "https://cdn.nuclia.cloud/nuclia-video-widget.umd.js", array(), false, true );

	$searchbox = sprintf(
		'<nuclia-search-bar knowledgebox="%1s" zone="%2s" features="%3s"></nuclia-search-bar><nuclia-search-results></nuclia-search-results>',
		sanitize_title( $kbid ),
		sanitize_title( $zone ),
		$features
	);

	return $searchbox;
}
