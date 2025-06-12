<?php

/**
 * Nuclia_Searchbox_Widget class file.
 *
 * @since   1.0.0
 *
 * @package kalyx\nuclia-search-for-wp
 */


/**
 * Class Nuclia_Searchbox_Widget
 *
 * @since 1.0.0
 */

namespace Kalyx\WPSWN;

class Nuclia_Searchbox_Widget extends \WP_Widget {

	public function __construct() {
		$widget_args = array(
			'classname'             => 'nuclia-searchbox-widget',
			'description' 			=> __( "Fully functional and customizable widget to embed Nuclia's search in seconds.", 'klx-nuclia-search-for-wp' ),
			'show_instance_in_rest' => true
		);

		parent::__construct(
			'nuclia-search',  // Base ID
			__('Nuclia search','klx-nuclia-search-for-wp'),  // Name
			$widget_args // arguments
		);
	}

	
	public $args = array(
		'before_title'  => '<h4 class="widgettitle">',
		'after_title'   => '</h4>',
		'before_widget' => '<div class="widget-wrap">',
		'after_widget'  => '</div>',
	);

	
	public function widget( $args, $instance ) {
		wp_enqueue_script('nuclia-widget', "https://cdn.nuclia.cloud/nuclia-video-widget.umd.js", array(), false, true );
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		if ( ! empty( $instance['kbid'] ) && ! empty( $instance['zone'] ) ) :
			echo '<nuclia-search-bar';
			echo ' knowledgebox="'.$instance['kbid'].'"'; // 
			echo ' zone="'.$instance['zone'].'"';
			echo ( ! empty( $instance['features'] ) && is_array( $instance['features'] ) ) ? ' features="'.implode(',',$instance['features']).'"' : '';
			echo '></nuclia-search-bar>';
			echo '<nuclia-search-results></nuclia-search-results>';
		else : 
			if ( current_user_can('edit_posts')) {
				echo sprintf(
					'<div style="color:red; border: 2px dotted red; padding: .5em;">%s</div>',
					__("Nuclia shortcode misconfigured. Please provide your zone and your kbid.", 'klx-nuclia-search-for-wp' )
				);
			} else {
				echo '';
			}
		endif;

		echo $args['after_widget'];
	}


	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( '', 'klx-nuclia-search-for-wp' );
		$zone  = ! empty( $instance['zone'] ) ? $instance['zone'] : 'europe-1';
		$kbid  = ! empty( $instance['kbid'] ) ? $instance['kbid'] : esc_html__( '', 'klx-nuclia-search-for-wp' );
		$features  = ! empty( $instance['features'] ) ? $instance['features'] : array( 'navigateToLink' );

		// available features
		$widget_search_features = array( 
			"navigateToLink" => __("Navigate to links : clicking on a result will open the original page rather than rendering it in the viewer." , 'klx-nuclia-search-for-wp' ),
			"permalink" => __("Permalinks : add extra parameters in URL allowing direct opening of a resource or search results." , 'klx-nuclia-search-for-wp' ),
			"suggestions" => __("Suggestions : suggest results while typing search query." , 'klx-nuclia-search-for-wp' ),
			//"suggestLabels" => __("Suggest labels" , 'klx-nuclia-search-for-wp' ),
			//"filter" => __("Filter" , 'klx-nuclia-search-for-wp' ),
			//"relations" => __("Relations" , 'klx-nuclia-search-for-wp' )
		);
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php echo esc_html__( 'Title:', 'klx-nuclia-search-for-wp' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'zone' ) ); ?>"><?php echo esc_html__( 'Zone:', 'klx-nuclia-search-for-wp' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'zone' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'zone' ) ); ?>" >
				<option value="europe-1" <?php selected( esc_attr( $zone ),'europe-1'); ?> >europe-1</option>
            </select>
		</p>
        <p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'kbid' ) ); ?>"><?php echo esc_html__( 'Knowledgebox ID:', 'klx-nuclia-search-for-wp' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'kbid' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'kbid' ) ); ?>" type="text" value="<?php echo esc_attr( $kbid ); ?>">
		</p>
        <p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'features' ) ); ?>"><?php echo esc_html__( 'Features:', 'klx-nuclia-search-for-wp' ); ?></label><br>
        	<?php foreach( $widget_search_features as $key => $label ) : ?>
            <input class="checkbox" type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'features' ).$key ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'features' ) ); ?>[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $features ), 1 ); ?>> <?php echo $label; ?><br />
            <?php endforeach; ?>
		</p>
		<?php
	}


	public function update( $new_instance, $old_instance ) {		
		$instance          = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['zone']  = ( ! empty( $new_instance['zone'] ) ) ? sanitize_title( $new_instance['zone'] ) : 'europe-1';
		$instance['kbid']  = ( ! empty( $new_instance['kbid'] ) ) ? sanitize_title( $new_instance['kbid'] ) : '';
		$instance['features']  = ( ! empty( $new_instance['features'] ) ) ? array_filter( $new_instance['features'], 'sanitize_title' ) : array();
		return $instance;
	}

}


/**
 * Register Nuclia Widget
 *
 * @since 1.0.0
 *
 */
function register_nuclia_widget() {
	\register_widget( __NAMESPACE__.'\Nuclia_Searchbox_Widget' );
}
\add_action( 'widgets_init', __NAMESPACE__.'\register_nuclia_widget' );
	