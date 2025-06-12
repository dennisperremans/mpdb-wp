<?php
/**
 * Nuclia_API class file.
 *
 * @since   1.0.0
 *
 * @package kalyx\nuclia-search-for-wp
 */

/**
 * Class Nuclia_API
 *
 * @since 1.0.0
 */
class Nuclia_API {

	/**
	 * The Nuclia_Settings instance.
	 *
	 * @since  1.0.0
	 *
	 * @var Nuclia_Settings
	 */
	private $settings;
	
	/**
	 * The NucliaDB API end point
	 *
	 * @since  1.0.0
	 *
	 * @var Nuclia_Settings
	 */
	private $endpoint;
	
	/**
	 * Nuclia_API constructor.
	 *
	 * @since  1.0.0
	 *
	 * @param Nuclia_Settings $settings The Nuclia_Settings instance.
	 */
	public function __construct( Nuclia_Settings $settings ) {
		$this->settings = $settings;
		$this->endpoint = sprintf( 'https://%1s.nuclia.cloud/api/v1/kb/%2s/',
							$this->settings->get_zone(),
							$this->settings->get_kbid()
						  );
	}
	
	/**
	 * Create a resource
	 *
	 * @since  1.0.0
	 *
	 * @param int $post_id ID of the post.
	 *
	 * @param array() $body Content to send for indexation.
	 */	 
	public function create_resource( $post_id, $body ) {
		
		$uri = $this->endpoint.'resources';
		
		$args = array(
			'method' => 'POST',
			'headers' => array(
				'Content-type' => 'application/json',
				'X-STF-Serviceaccount' => 'Bearer ' .$this->settings->get_token()
			),
			'body' => $body
		);
		
		nuclia_log( 'endpoint : '. $uri );
		//nuclia_log( print_r( $args, true ) );
		
		$response = wp_remote_request( $uri, $args );
		$response_code = wp_remote_retrieve_response_code( $response ); // int or empty string
		
		nuclia_log( 'code : '.$response_code );
		
		if ( !is_wp_error( $response ) ) {
			$api_response = json_decode( wp_remote_retrieve_body( $response ), true );
			// successfull response
			if ( $response_code === 201 ) {
				$rid = $api_response['uuid'];
				$seqid = $api_response['seqid'];
				update_post_meta( $post_id, 'nuclia_rid', $rid );
				update_post_meta( $post_id, 'nuclia_seqid', $seqid );
				
				nuclia_log( 'nuclia success : '.print_r($api_response, true) );

			}
			// Validation error
			else {
				nuclia_log( 'nuclia error : '.print_r($api_response, true) );

			};
		} else {
			nuclia_log( 'connexion error: '.print_r($response,true) );
		};
		
	}
	
	/**
	 * Modify a resource
	 *
	 * @since  1.0.0
	 *
	 * @param int $post_id ID of the post.
	 *
	 * @param string  $rid  Resource ID in nucliaDB
	 * @param array() $body The content to index.
	 */	 
	public function modify_resource( $post_id, $rid, $body ) {
		
		$uri = $this->endpoint.'resource/'.$rid;
		$args = array(
			'method' => 'PATCH',
			'headers' => array(
				'Content-type' => 'application/json',
				'X-STF-Serviceaccount' => 'Bearer ' .$this->settings->get_token()
			),
			'body' => $body
		);
		
		nuclia_log( $uri );
		nuclia_log( print_r( $args, true ) );
		
		$response = wp_remote_request( $uri, $args );
		$response_code = wp_remote_retrieve_response_code( $response ); // int or empty string
		nuclia_log( 'code : '.$response_code );
		if ( !is_wp_error( $response ) ) {
			$api_response = json_decode( wp_remote_retrieve_body( $response ), true );
			// successfull response
			if ( $response_code === 200 ) {
				$seqid = $api_response['seqid'];
				update_post_meta( $post_id, 'nuclia_seqid', $seqid );
				nuclia_log( 'nuclia success : '.print_r($api_response, true) );
			}
			// Validation error
			else {
				nuclia_log( 'nuclia error : '.print_r($api_response, true) );

			};
		} else {
			nuclia_log( 'connexion error: '.print_r($response,true) );
		};
		
		
	}
	
	/**
	 * Delete a resource
	 *
	 * @since  1.0.0
	 *
	 * @param int $post_id ID of the post.
	 *
	 * @param string  $rid  Resource ID in nucliaDB
	 */	 
	public function delete_resource( $post_id, $rid ) {
		
		$uri = $this->endpoint.'resource/'.$rid;
		
		$args = array(
			'method' => 'DELETE',
			'headers' => array(
				'X-STF-Serviceaccount' => 'Bearer ' .$this->settings->get_token()
			),
			'body' => ''
		);
		
		nuclia_log( $uri );
		nuclia_log( print_r( $args, true ) );
		
		$response = wp_remote_request( $uri, $args );
		$response_code = wp_remote_retrieve_response_code( $response ); // int or empty string
		nuclia_log( 'code : '.$response_code );
		
		if ( !is_wp_error( $response ) ) {
			$api_response = json_decode( wp_remote_retrieve_body( $response ), true );
			// successfull response
			if ( $response_code === 204 ) {
				delete_post_meta( $post_id, 'nuclia_rid' );
				delete_post_meta( $post_id, 'nuclia_seqid' );
				nuclia_log( 'nuclia success : '.print_r($api_response, true) );
			}
			// Validation error
			else {
				nuclia_log( 'nuclia error : '.print_r($api_response, true) );

			};
		} else {
			nuclia_log( 'connexion error: '.print_r($response,true) );
		};
		
	}
	
	/**
	 * Get all indexed resources from nucliaDB
	 *
	 * @since  1.1.0
	 */	 
	public function get_resources() {
		$uri = $this->endpoint.'resources';
		$args = array(
			'method' => 'GET',
			'headers' => array(
				'Content-type' => 'application/json',
			)
		);
		// default query parameters
		$page = 0;
		// $size = 20; unused default nuclia resources number per page
		//
		$last = false;
		// ids of indexed posts
		$indexed_posts_ids = array();
		while ( !$last ) :
			$uri .= '?page='.$page;
			nuclia_log( $uri );
			$response = wp_remote_request( $uri, $args );
			$response_code = wp_remote_retrieve_response_code( $response ); // int or empty string
			if ( !is_wp_error( $response ) ) {
				$api_response = json_decode( wp_remote_retrieve_body( $response ), true );
				// successfull response
				if ( $response_code === 200 ) {
					$pagination = $api_response['pagination'];
					$resources = $api_response['resources'];
					$post_ids = wp_list_pluck( $resources, 'slug' );
					foreach( $post_ids as $post_id ) $indexed_posts_ids[] = $post_id;
					if ( !empty($pagination['last']) ) {
						$last = true;
					} else {
						$page++;
					}
					//nuclia_log( 'nuclia success : '.print_r($api_response, true) );
				}
				// Validation error
				else {
					nuclia_log( 'nuclia error : '.print_r($api_response, true) );
					$last = true;
	
				};
			} else {
				nuclia_log( 'connexion error: '.print_r($response,true) );
				$last = true;
			};
		endwhile;
		
		update_option('nuclia_indexed_posts_ids',$indexed_posts_ids);

	}
}
