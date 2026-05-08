<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Workshop variant + copy aligned with Grant Certify Pro reference design.
 */
class HLC_Certificate_Data {

	public const VARIANT_GRANT_WRITING      = 'grant_writing';
	public const VARIANT_GRANT_MANAGEMENT   = 'grant_management';
	public const VARIANT_SUBAWARD          = 'subaward';

	/**
	 * @return array<string, array{title:string,body:string,hours:string}>
	 */
	public static function workshop_copy(): array {
		return array(
			self::VARIANT_GRANT_WRITING => array(
				'title' => 'Grant Writing Workshop',
				'body'  => 'having successfully completed the Grant Writing USA two-day intensive workshop in the principles, practices, and craft of competitive federal and foundation grant writing.',
				'hours' => 'Sixteen (16) Contact Hours',
			),
			self::VARIANT_GRANT_MANAGEMENT => array(
				'title' => 'Grant Management Workshop',
				'body'  => 'having successfully completed the Grant Management USA two-day intensive workshop in post-award grant administration, compliance, and the Uniform Guidance (2 CFR 200).',
				'hours' => 'Sixteen (16) Contact Hours',
			),
			self::VARIANT_SUBAWARD => array(
				'title' => 'Grant Management USA Subaward Workshop',
				'body'  => 'having successfully completed the Grant Management USA Subaward Workshop, a one-day intensive program in subaward management, monitoring, risk assessment, and pass-through entity responsibilities under 2 CFR 200.',
				'hours' => 'Eight (8) Contact Hours',
			),
		);
	}

	/**
	 * @return array<string, array{title:string,body:string,hours:string}> keyed for JSON/JS
	 */
	public static function workshop_copy_for_frontend(): array {
		$raw = self::workshop_copy();
		return array(
			'grant_writing'      => $raw[ self::VARIANT_GRANT_WRITING ],
			'grant_management'   => $raw[ self::VARIANT_GRANT_MANAGEMENT ],
			'subaward'           => $raw[ self::VARIANT_SUBAWARD ],
		);
	}

	public static function resolve_variant( object $event, HLC_Access $access ): string {
		$name = isset( $event->event_type_name ) ? mb_strtolower( (string) $event->event_type_name, 'UTF-8' ) : '';
		foreach ( $access->get_match_subaward_lines() as $sub ) {
			if ( $sub !== '' && str_contains( $name, $sub ) ) {
				return self::VARIANT_SUBAWARD;
			}
		}
		return $access->resolve_logo_key( $event ) === 'gw' ? self::VARIANT_GRANT_WRITING : self::VARIANT_GRANT_MANAGEMENT;
	}

	/**
	 * @param object $event Hostlinks event row
	 */
	public static function format_completion_date_long( object $event ): string {
		$date = '';
		if ( ! empty( $event->eve_end ) ) {
			$date = (string) $event->eve_end;
		} elseif ( ! empty( $event->eve_start ) ) {
			$date = (string) $event->eve_start;
		}
		if ( $date === '' ) {
			return '';
		}
		$ts = strtotime( $date );
		if ( ! $ts ) {
			return $date;
		}
		return date_i18n( 'F j, Y', $ts );
	}

	/**
	 * Certificate number similar to Grant Certify Pro (GWU-YEAR-#####).
	 */
	public static function build_certificate_id( string $participant_name, string $variant, string $date_ymd ): string {
		$seed = $participant_name . '-' . $variant . '-' . $date_ymd;
		$hash = 0;
		$len  = strlen( $seed );
		for ( $i = 0; $i < $len; $i++ ) {
			$hash = ( ( $hash * 31 ) + ord( $seed[ $i ] ) ) & 0xFFFFFFFF;
		}
		$year = date( 'Y', strtotime( $date_ymd ) ?: time() );
		if ( ! preg_match( '/^\d{4}$/', (string) $year ) ) {
			$year = gmdate( 'Y' );
		}
		$suffix = str_pad( (string) ( $hash % 99999 ), 5, '0', STR_PAD_LEFT );

		return 'GWU-' . $year . '-' . $suffix;
	}
}
