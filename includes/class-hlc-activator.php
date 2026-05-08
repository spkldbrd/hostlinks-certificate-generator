<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HLC_Activator {

	public static function activate(): void {
		add_option( 'hlc_limit_to_buckets', '1' );
		flush_rewrite_rules();
	}
}
