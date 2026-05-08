<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HLC_Admin {

	/** @var HLC_Access */
	private $access;

	public function __construct( HLC_Access $access ) {
		$this->access = $access;
	}

	public function register_menu(): void {
		add_menu_page(
			'Certificate Generator',
			'HL Certificates',
			'manage_options',
			'hlc-settings',
			array( $this, 'render_settings_page' ),
			'dashicons-awards',
			57
		);
	}

	public function reorder_top_level_menu(): void {
		global $menu;
		if ( ! is_array( $menu ) ) {
			return;
		}

		$cert_item = null;
		foreach ( $menu as $key => $item ) {
			if ( isset( $item[2] ) && $item[2] === 'hlc-settings' ) {
				$cert_item = $item;
				unset( $menu[ $key ] );
				break;
			}
		}
		if ( ! $cert_item ) {
			return;
		}

		$menu = array_values( $menu );
		$insert_at = count( $menu );
		foreach ( $menu as $idx => $item ) {
			if ( isset( $item[2] ) && $item[2] === 'booking-menu' ) {
				$insert_at = $idx + 1;
				break;
			}
			if ( isset( $item[2] ) && $item[2] === 'hmo-dashboard' ) {
				$insert_at = $idx;
			}
		}

		array_splice( $menu, $insert_at, 0, array( $cert_item ) );
	}

	public function enqueue_admin( string $hook ): void {
		if ( $hook !== 'toplevel_page_hlc-settings' ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script(
			'hlc-admin',
			HLC_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			HLC_VERSION,
			true
		);
		wp_localize_script(
			'hlc-admin',
			'hlcAdmin',
			array(
				'nonce' => wp_create_nonce( 'hlc_admin' ),
			)
		);
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		include HLC_PLUGIN_DIR . 'admin/views/settings.php';
	}

	public function ajax_search_users(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}
		check_ajax_referer( 'hlc_admin' );

		$q = sanitize_text_field( wp_unslash( $_REQUEST['q'] ?? '' ) );
		if ( strlen( $q ) < 2 ) {
			wp_send_json_success( array() );
		}

		$users = new WP_User_Query( array(
			'search'         => '*' . $q . '*',
			'search_columns' => array( 'display_name', 'user_email', 'user_login' ),
			'number'         => 15,
			'fields'         => array( 'ID', 'display_name', 'user_email' ),
		) );

		$results = array();
		foreach ( $users->get_results() as $u ) {
			$results[] = array(
				'id'    => (int) $u->ID,
				'name'  => $u->display_name,
				'email' => $u->user_email,
			);
		}
		wp_send_json_success( $results );
	}
}
