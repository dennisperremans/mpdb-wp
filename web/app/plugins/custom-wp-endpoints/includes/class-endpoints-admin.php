<?php
/**
 * -----------------------------------------------------------------------------
 *  Class: CWE_Endpoints_Admin
 * -----------------------------------------------------------------------------
 *  ▸ Renders the Endpoints builder UI (DataTables + AJAX CRUD)
 *  ▸ Adds a Settings sub‑page (REST namespace, default posts_per_page)
 *  ▸ Registers all admin‑side assets
 *  ▸ Houses every wp‑ajax callback needed by the JS layer
 *
 *  @since 1.1.0
 * -----------------------------------------------------------------------------
 */
class CWE_Endpoints_Admin {

	/** Single option row that stores all settings */
	const OPTION_KEY = 'cwe_settings';

	/* ────────────────────────────────────────────────────────────────────────── */
	/*  Boot                                                                    */
	/* ────────────────────────────────────────────────────────────────────────── */

	public function __construct() {
		add_action( 'admin_menu',            [ $this, 'register_admin_pages' ] );
		add_action( 'admin_init',            [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// AJAX – CRUD + query‑builder.
		add_action( 'wp_ajax_cwe_add_endpoint',    [ $this, 'ajax_add_endpoint' ] );
		add_action( 'wp_ajax_cwe_get_endpoint',    [ $this, 'ajax_get_endpoint' ] );
		add_action( 'wp_ajax_cwe_delete_endpoint', [ $this, 'ajax_delete_endpoint' ] );
		add_action( 'wp_ajax_cwe_edit_endpoint',   [ $this, 'ajax_edit_endpoint' ] );
		add_action( 'wp_ajax_cwe_save_query',      [ $this, 'ajax_save_query' ] );
	}

	/* ────────────────────────────────────────────────────────────────────────── */
	/*  Assets                                                                  */
	/* ────────────────────────────────────────────────────────────────────────── */

	/**
	 * Enqueue CSS/JS only on our plugin pages.
	 */
	public function enqueue_assets( $hook ) {

		if ( strpos( $hook, 'custom-wp-endpoints' ) === false ) {
			return; // bail on all other admin pages.
		}

		// ── Core plugin stylesheet
		wp_enqueue_style( 'cwe-admin', CWE_URL . 'assets/css/admin.css', [], CWE_VERSION );

		// ── WP–bundled jQuery‑UI Dialog (modal for Query builder)
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-dialog' );

		// ── DataTables (CDN)
		wp_enqueue_style( 'datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css' );
		wp_enqueue_script( 'datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', [ 'jquery' ], null, true );

		// ── Plugin admin JS (depends on DT + Dialog)
		wp_enqueue_script( 'cwe-admin', CWE_URL . 'assets/js/admin.js', [ 'jquery', 'datatables-js', 'jquery-ui-dialog' ], CWE_VERSION, true );

		// ── Localize runtime vars
		$settings   = get_option( self::OPTION_KEY, [] );
		$namespace  = ! empty( $settings['rest_namespace'] ) ? $settings['rest_namespace'] : 'custom/v1';

		wp_localize_script( 'cwe-admin', 'CWE', [
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'cwe-nonce' ),
			'siteUrl'       => get_site_url(),
			'restNamespace' => $namespace,
		] );
	}

	/* ────────────────────────────────────────────────────────────────────────── */
	/*  Menu & Settings API                                                     */
	/* ────────────────────────────────────────────────────────────────────────── */

	/**
	 * Top‑level menu (Endpoints) + Settings submenu.
	 */
	public function register_admin_pages() {

		add_menu_page(
			'Custom Endpoints',            // page‑title
			'Custom Endpoints',            // menu‑title
			'manage_options',
			'custom-wp-endpoints',         // slug
			[ $this, 'render_endpoints_page' ],
			'dashicons-rest-api',          // icon
			80                            // position
		);

		add_submenu_page(
			'custom-wp-endpoints',
			'Settings', 'Settings',
			'manage_options',
			'custom-wp-endpoints-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Register settings & fields using the Settings API.
	 */
	public function register_settings() {

		// main option row
		register_setting( self::OPTION_KEY, self::OPTION_KEY, [ $this, 'sanitize_settings' ] );

		add_settings_section( 'cwe_general', 'General Settings', '__return_false', self::OPTION_KEY );

		add_settings_field( 'rest_namespace', 'REST Namespace', [ $this, 'field_namespace' ], self::OPTION_KEY, 'cwe_general' );
		add_settings_field( 'default_ppp',    'Default posts_per_page', [ $this, 'field_ppp' ], self::OPTION_KEY, 'cwe_general' );
	}

	/** Sanitize callback */
	public function sanitize_settings( $raw ) {
		return [
			'rest_namespace' => sanitize_title( $raw['rest_namespace'] ?? 'custom/v1' ),
			'default_ppp'    => (int) ( $raw['default_ppp'] ?? -1 ),
		];
	}

	/* ----- field callbacks ----- */
	public function field_namespace() {
		$val = esc_attr( get_option( self::OPTION_KEY )[ 'rest_namespace' ] ?? 'custom/v1' );
		echo "<input type='text' class='regular-text' name='".self::OPTION_KEY."[rest_namespace]' value='{$val}' />";
		echo '<p class="description">Used in /wp-json/&lt;namespace&gt;/slug (e.g. <code>custom/v1</code>).</p>';
	}
	public function field_ppp() {
		$val = esc_attr( get_option( self::OPTION_KEY )[ 'default_ppp' ] ?? -1 );
		echo "<input type='number' class='small-text' name='".self::OPTION_KEY."[default_ppp]' value='{$val}' />";
		echo '<p class="description">When an endpoint has no posts_per_page param (‑1 = all).</p>';
	}

	/* ────────────────────────────────────────────────────────────────────────── */
	/*  Page renderers                                                          */
	/* ────────────────────────────────────────────────────────────────────────── */

	public function render_endpoints_page() { $this->render_endpoints_table(); }

	public function render_settings_page() { ?>
		<div class="wrap"><h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
					settings_fields( self::OPTION_KEY );
					do_settings_sections( self::OPTION_KEY );
					submit_button();
				?>
			</form>
		</div><?php
	}

	/** Endpoints DataTable markup */
	private function render_endpoints_table() {
		global $wpdb;
		$table  = $wpdb->prefix . CWE_Endpoints_DB::TABLE_NAME;
		$rows   = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A );
		$types  = get_post_types( [ 'public' => true, 'show_ui' => true ], 'objects' );
		$ns     = get_option( self::OPTION_KEY )[ 'rest_namespace' ] ?? 'custom/v1';
		?>
		<div class="wrap cwe-wrap">
			<h1 class="wp-heading-inline">Custom Endpoints</h1>

			<h2 class="cwe-section-title">Create a new endpoint</h2>
			<div class="cwe-form">
				<label for="cwe-post-type">Choose post type:</label>
				<select id="cwe-post-type" class="cwe-select">
					<?php foreach ( $types as $slug => $o ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $o->labels->singular_name ); ?></option>
					<?php endforeach; ?>
				</select>
				<button id="cwe-add-btn" class="button button-primary">Add Endpoint</button>
			</div>

			<hr>

			<h2 class="cwe-section-title">Existing endpoints</h2>
			<table id="cwe-endpoint-table" class="display">
				<thead><tr><th>Slug</th><th>Post Type</th><th>Endpoint URL</th><th>Actions</th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $r ) :
					$url = home_url( "/wp-json/{$ns}/{$r['endpoint_slug']}" ); ?>
				<tr data-id="<?php echo esc_attr( $r['id'] ); ?>">
					<td><strong><?php echo esc_html( $r['endpoint_slug'] ); ?></strong></td>
					<td><?php echo esc_html( $r['post_type'] ); ?></td>
					<td><a href="<?php echo esc_url( $url ); ?>" target="_blank"><?php echo esc_html( $url ); ?></a></td>
					<td>
						<button class="button query-endpoint">Query</button>
						<button class="button view-endpoint">View</button>
						<button class="button edit-endpoint">Edit</button>
						<button class="button delete-endpoint">Delete</button>
					</td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div><?php
	}

	/* ────────────────────────────────────────────────────────────────────────── */
	/*  AJAX: add / get / delete / edit / save_query                            */
	/* ────────────────────────────────────────────────────────────────────────── */

	/** Add endpoint */
	public function ajax_add_endpoint() {
		check_ajax_referer( 'cwe-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'No permission' ], 403 );

		$post_type = sanitize_key( $_POST['post_type'] ?? '' );
		if ( ! $post_type || ! get_post_type_object( $post_type ) ) wp_send_json_error( [ 'message' => 'Invalid post type' ], 400 );

		$slug = sanitize_title( $post_type . '-' . wp_generate_password( 6, false ) );

		global $wpdb; $table = $wpdb->prefix . CWE_Endpoints_DB::TABLE_NAME;
		$wpdb->insert( $table, [
			'endpoint_slug' => $slug,
			'post_type'     => $post_type,
			'query_args'    => wp_json_encode( [] ),
		], [ '%s', '%s', '%s' ] );

		wp_send_json_success( [ 'id' => $wpdb->insert_id, 'endpoint_slug' => $slug, 'post_type' => $post_type ], 201 );
	}

	/** Get (single) endpoint */
	public function ajax_get_endpoint() {
		check_ajax_referer( 'cwe-nonce', 'nonce' );
		$id = absint( $_POST['id'] ?? 0 ); if ( ! $id ) wp_send_json_error( [ 'message' => 'Invalid ID' ], 400 );

		global $wpdb; $t = $wpdb->prefix . CWE_Endpoints_DB::TABLE_NAME;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE id=%d", $id ), ARRAY_A );
		if ( ! $row ) wp_send_json_error( [ 'message' => 'Not found' ], 404 );

		$row['query_args'] = json_decode( $row['query_args'], true );
		wp_send_json_success( $row );
	}

	/** Delete endpoint */
	public function ajax_delete_endpoint() {
		check_ajax_referer( 'cwe-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'No permission' ], 403 );
		$id = absint( $_POST['id'] ?? 0 ); if ( ! $id ) wp_send_json_error( [ 'message' => 'Invalid ID' ], 400 );

		global $wpdb; $t = $wpdb->prefix . CWE_Endpoints_DB::TABLE_NAME;
		$wpdb->delete( $t, [ 'id' => $id ], [ '%d' ] );
		wp_send_json_success( [ 'id' => $id ] );
	}

	/** Edit slug */
	public function ajax_edit_endpoint() {
		check_ajax_referer( 'cwe-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'No permission' ], 403 );

		$id   = absint( $_POST['id'] ?? 0 );
		$slug = sanitize_title( $_POST['endpoint_slug'] ?? '' );
		if ( ! $id || ! $slug ) wp_send_json_error( [ 'message' => 'Missing data' ], 400 );

		global $wpdb; $t = $wpdb->prefix . CWE_Endpoints_DB::TABLE_NAME;
		$wpdb->update( $t, [ 'endpoint_slug' => $slug ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
		wp_send_json_success( [ 'id' => $id, 'endpoint_slug' => $slug ] );
	}

	/** Save Query (key/value pairs) */
	public function ajax_save_query() {
		check_ajax_referer( 'cwe-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'No permission' ], 403 );

		$id   = absint( $_POST['id'] ?? 0 );
		$json = wp_unslash( $_POST['query_args'] ?? '' );
		if ( ! $id || ! $json ) wp_send_json_error( [ 'message' => 'Missing data' ], 400 );

		$decoded = json_decode( $json, true );
		if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) wp_send_json_error( [ 'message' => 'Invalid JSON' ], 400 );

		global $wpdb; $t = $wpdb->prefix . CWE_Endpoints_DB::TABLE_NAME;
		$wpdb->update( $t, [ 'query_args' => wp_json_encode( $decoded ) ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
		wp_send_json_success( [ 'id' => $id ] );
	}
}
