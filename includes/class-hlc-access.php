<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allowlist + bucket scoping option + option getters.
 */
class HLC_Access {

	const OPT_USERS          = 'hlc_allowed_users';
	const OPT_LOGO_GW        = 'hlc_logo_grant_writing';
	const OPT_LOGO_GM        = 'hlc_logo_grant_mgmt';
	const OPT_LOGO_SUB       = 'hlc_logo_subaward';
	const OPT_MATCH_GW       = 'hlc_match_grant_writing';
	const OPT_MATCH_GM       = 'hlc_match_grant_mgmt';
	const OPT_TYPE_MAP       = 'hlc_type_logo_map';
	const OPT_EMAIL_SUBJECT  = 'hlc_email_subject';
	const OPT_EMAIL_BODY     = 'hlc_email_body';
	const OPT_LIMIT_BUCKETS  = 'hlc_limit_to_buckets';
	const OPT_DENIAL_MESSAGE = 'hlc_denial_message';
	const OPT_MATCH_SUB      = 'hlc_match_subaward';
	const OPT_SIGNATURE      = 'hlc_signature_attachment';

	const DEFAULT_DENIAL = "You don't have access to this tool. Please contact your site administrator if you believe this is an error.";

	const DEFAULT_MATCH_GW = "grant writing\n";

	const DEFAULT_MATCH_GM = "grant management\n";

	const DEFAULT_MATCH_SUB = "subaward\nsub-award\nsub award\n";

	const DEFAULT_EMAIL_SUBJECT = 'Your certificate: {event_title}';

	const DEFAULT_EMAIL_BODY = "Hello,\n\nAttached is your certificate for {event_title} ({event_dates}).\n\n— {site_name}\n";

	public function current_user_can_use_generator(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$uid = get_current_user_id();
		return $uid > 0 && in_array( $uid, $this->get_allowed_user_ids(), true );
	}

	public function get_allowed_user_ids(): array {
		$raw = get_option( self::OPT_USERS, array() );
		return array_values( array_unique( array_filter( array_map( 'intval', (array) $raw ) ) ) );
	}

	public function save_allowed_user_ids( array $ids ): void {
		$clean = array_values( array_unique( array_filter(
			array_map( 'intval', $ids ),
			fn( $id ) => $id > 0
		) ) );
		update_option( self::OPT_USERS, $clean );
	}

	public function limit_to_marketing_ops_buckets(): bool {
		if ( ! class_exists( 'HMO_Access_Service' ) ) {
			return false;
		}
		return (string) get_option( self::OPT_LIMIT_BUCKETS, '1' ) === '1';
	}

	public function get_logo_grant_writing_id(): int {
		return max( 0, (int) get_option( self::OPT_LOGO_GW, 0 ) );
	}

	public function get_logo_grant_mgmt_id(): int {
		return max( 0, (int) get_option( self::OPT_LOGO_GM, 0 ) );
	}

	public function get_logo_subaward_id(): int {
		return max( 0, (int) get_option( self::OPT_LOGO_SUB, 0 ) );
	}

	/**
	 * Logo for PDF/preview from workshop variant (three class types).
	 */
	public function get_logo_attachment_id_for_variant( string $variant ): int {
		switch ( $variant ) {
			case HLC_Certificate_Data::VARIANT_SUBAWARD:
				$id = $this->get_logo_subaward_id();
				return $id > 0 ? $id : $this->get_logo_grant_mgmt_id();
			case HLC_Certificate_Data::VARIANT_GRANT_MANAGEMENT:
				return $this->get_logo_grant_mgmt_id();
			default:
				return $this->get_logo_grant_writing_id();
		}
	}

	public function get_match_grant_writing_lines(): array {
		return $this->parse_match_lines( (string) get_option( self::OPT_MATCH_GW, self::DEFAULT_MATCH_GW ) );
	}

	public function get_match_grant_mgmt_lines(): array {
		return $this->parse_match_lines( (string) get_option( self::OPT_MATCH_GM, self::DEFAULT_MATCH_GM ) );
	}

	public function get_match_subaward_lines(): array {
		return $this->parse_match_lines( (string) get_option( self::OPT_MATCH_SUB, self::DEFAULT_MATCH_SUB ) );
	}

	public function get_signature_attachment_id(): int {
		return max( 0, (int) get_option( self::OPT_SIGNATURE, 0 ) );
	}

	/**
	 * @return array<int,string> event_type_id => 'gw'|'gm'
	 */
	public function get_type_logo_map(): array {
		$raw = get_option( self::OPT_TYPE_MAP, array() );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $type_id => $which ) {
			$id = (int) $type_id;
			if ( $id <= 0 ) {
				continue;
			}
			$which = strtolower( (string) $which );
			if ( in_array( $which, array( 'gw', 'gm' ), true ) ) {
				$out[ $id ] = $which;
			}
		}
		return $out;
	}

	public function save_type_logo_map( array $map ): void {
		$clean = array();
		foreach ( $map as $type_id => $which ) {
			$id = (int) $type_id;
			if ( $id <= 0 ) {
				continue;
			}
			$w = strtolower( (string) $which );
			if ( in_array( $w, array( 'gw', 'gm' ), true ) ) {
				$clean[ $id ] = $w;
			}
		}
		update_option( self::OPT_TYPE_MAP, $clean );
	}

	public function get_email_subject(): string {
		$s = (string) get_option( self::OPT_EMAIL_SUBJECT, self::DEFAULT_EMAIL_SUBJECT );
		return $s !== '' ? $s : self::DEFAULT_EMAIL_SUBJECT;
	}

	public function get_email_body(): string {
		$s = (string) get_option( self::OPT_EMAIL_BODY, self::DEFAULT_EMAIL_BODY );
		return $s !== '' ? $s : self::DEFAULT_EMAIL_BODY;
	}

	public function get_denial_message_html(): string {
		$msg = (string) get_option( self::OPT_DENIAL_MESSAGE, '' );
		if ( $msg === '' ) {
			$msg = self::DEFAULT_DENIAL;
		}
		return '<div class="hlc-access-denied hostlinks-access-denied"><p>' . wp_kses_post( $msg ) . '</p></div>';
	}

	/**
	 * When Marketing Ops is active and bucket limiting is on, enforce per-event access.
	 */
	public function current_user_can_access_event( int $event_id ): bool {
		if ( $event_id <= 0 ) {
			return false;
		}
		if ( ! $this->limit_to_marketing_ops_buckets() ) {
			return true;
		}
		if ( ! class_exists( 'HMO_Access_Service' ) ) {
			return true;
		}
		$hmo = new HMO_Access_Service();
		return $hmo->can_view_event( $event_id );
	}

	/**
	 * Resolve which logo attachment to use: 'gw' or 'gm'.
	 *
	 * @param object $event Row with eve_type, event_type_name.
	 */
	public function resolve_logo_key( object $event ): string {
		$type_id = isset( $event->eve_type ) ? (int) $event->eve_type : 0;
		$map     = $this->get_type_logo_map();
		if ( $type_id && isset( $map[ $type_id ] ) ) {
			return $map[ $type_id ];
		}

		$name = isset( $event->event_type_name ) ? mb_strtolower( (string) $event->event_type_name, 'UTF-8' ) : '';

		foreach ( $this->get_match_grant_mgmt_lines() as $sub ) {
			if ( $sub !== '' && str_contains( $name, $sub ) ) {
				return 'gm';
			}
		}
		foreach ( $this->get_match_grant_writing_lines() as $sub ) {
			if ( $sub !== '' && str_contains( $name, $sub ) ) {
				return 'gw';
			}
		}

		return 'gw';
	}

	public function get_logo_attachment_id_for_key( string $key ): int {
		return $key === 'gm' ? $this->get_logo_grant_mgmt_id() : $this->get_logo_grant_writing_id();
	}

	/**
	 * @return string[] lowercased trimmed non-empty substrings
	 */
	private function parse_match_lines( string $text ): array {
		$lines = array_map( 'trim', explode( "\n", $text ) );
		$out   = array();
		foreach ( $lines as $line ) {
			$line = mb_strtolower( $line, 'UTF-8' );
			if ( $line !== '' ) {
				$out[] = $line;
			}
		}
		return array_values( array_unique( $out ) );
	}
}
