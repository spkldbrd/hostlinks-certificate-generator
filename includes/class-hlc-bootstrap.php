<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HLC_Bootstrap {

	public static function init(): void {
		if ( ! defined( 'HOSTLINKS_VERSION' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'notice_hostlinks_missing' ) );
			return;
		}

		$access    = new HLC_Access();
		$bridge    = new HLC_Bridge();
		$pdf       = new HLC_PDF( $access, $bridge );
		$rest      = new HLC_REST( $access, $bridge, $pdf );
		$shortcode = new HLC_Shortcode( $access );
		$admin     = new HLC_Admin( $access );

		add_action( 'rest_api_init', array( $rest, 'register_routes' ) );
		add_action( 'init', array( $shortcode, 'register' ) );
		add_action( 'admin_menu', array( $admin, 'register_menu' ) );
		add_action( 'admin_menu', array( $admin, 'reorder_top_level_menu' ), 9999 );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_admin' ) );

		add_action( 'wp_ajax_hlc_search_users', array( $admin, 'ajax_search_users' ) );
	}

	public static function notice_hostlinks_missing(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>Hostlinks Certificate Generator</strong> requires the <strong>Hostlinks</strong> plugin to be active.</p></div>';
	}
}
