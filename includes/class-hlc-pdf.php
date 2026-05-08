<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

class HLC_PDF {

	public const BUNDLED_LOGO              = 'grant-writing-usa-logo.png';
	public const LOGO_GRANT_WRITING        = 'logo-grant-writing-usa.png';
	public const LOGO_GRANT_MANAGEMENT     = 'logo-grant-management-usa.png';
	public const BUNDLED_SIGNATURE         = 'becky-helm-signature.png';
	public const SEAL_GRANT_WRITING        = 'seal-grant-writing-usa.png';
	public const SEAL_GRANT_MANAGEMENT     = 'seal-grant-management-usa.png';
	public const SEAL_GW_SVG               = 'seal-grant-writing-usa.svg';
	public const SEAL_GM_SVG               = 'seal-grant-management-usa.svg';

	/** @var HLC_Access */
	private $access;

	/** @var HLC_Bridge */
	private $bridge;

	public function __construct( HLC_Access $access, HLC_Bridge $bridge ) {
		$this->access = $access;
		$this->bridge = $bridge;
	}

	/**
	 * @return array{0:string,1:string} binary, filename
	 */
	public function build_certificate_pdf( int $event_id, string $participant_name, string $agency = '' ): array {
		$participant_name = $this->sanitize_participant_name( $participant_name );
		$agency           = $this->sanitize_agency( $agency );
		$event            = $this->bridge->get_event_with_type( $event_id );

		if ( ! $event ) {
			throw new RuntimeException( 'Event not found.' );
		}

		if ( ! $this->access->current_user_can_access_event( $event_id ) ) {
			throw new RuntimeException( 'You cannot generate a certificate for this event.' );
		}

		$variant = HLC_Certificate_Data::resolve_variant( $event, $this->access );
		$copy    = HLC_Certificate_Data::workshop_copy()[ $variant ];
		$logo_id  = $this->access->get_logo_attachment_id_for_variant( $variant );
		$logo_uri = $this->attachment_or_bundle_data_uri( $logo_id, $this->bundled_logo_filename( $variant ) );

		$sig_id          = $this->access->get_signature_attachment_id();
		$signature_uri   = $this->attachment_or_bundle_data_uri( $sig_id, self::BUNDLED_SIGNATURE );
		$logo_attr       = $logo_uri !== '' ? htmlspecialchars( $logo_uri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) : '';
		$signature_attr  = $signature_uri !== '' ? htmlspecialchars( $signature_uri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) : '';

		$seal_svg = $this->seal_svg_for_pdf( $variant );

		$date_long = HLC_Certificate_Data::format_completion_date_long( $event );
		$date_key  = $this->event_date_key( $event );

		$certificate_id = HLC_Certificate_Data::build_certificate_id( $participant_name, $variant, $date_key );

		$program_line = $copy['title'] . ' · ' . $copy['hours'];

		ob_start();
		$workshop_body = $copy['body'];
		include HLC_PLUGIN_DIR . 'templates/certificate.php';
		$html = (string) ob_get_clean();

		if ( ! class_exists( \Dompdf\Dompdf::class ) ) {
			throw new RuntimeException( 'PDF library is not installed. Run composer install in the plugin directory.' );
		}

		$options = new Options();
		$options->set( 'isRemoteEnabled', false );
		$options->set( 'defaultFont', 'DejaVu Sans' );

		$dompdf = new Dompdf( $options );
		$dompdf->loadHtml( $html );
		$dompdf->setPaper( 'letter', 'landscape' );
		$dompdf->render();

		$binary = $dompdf->output();

		$safe_name = sanitize_file_name( $participant_name );
		$safe_name = $safe_name !== '' ? $safe_name : 'participant';
		$variant_tag = 'GrantWriting';
		if ( HLC_Certificate_Data::VARIANT_GRANT_MANAGEMENT === $variant ) {
			$variant_tag = 'GrantManagement';
		} elseif ( HLC_Certificate_Data::VARIANT_SUBAWARD === $variant ) {
			$variant_tag = 'Subaward';
		}
		$filename = 'GrantWritingUSA_' . $variant_tag . '_' . $safe_name . '.pdf';

		return array( $binary, $filename );
	}

	private function bundled_logo_filename( string $variant ): string {
		if ( HLC_Certificate_Data::VARIANT_GRANT_MANAGEMENT === $variant
			|| HLC_Certificate_Data::VARIANT_SUBAWARD === $variant ) {
			$file = self::LOGO_GRANT_MANAGEMENT;
		} else {
			$file = self::LOGO_GRANT_WRITING;
		}
		$path = HLC_PLUGIN_DIR . 'assets/img/' . $file;
		if ( is_readable( $path ) ) {
			return $file;
		}
		return self::BUNDLED_LOGO;
	}

	private function bundled_seal_filename( string $variant ): string {
		if ( HLC_Certificate_Data::VARIANT_GRANT_MANAGEMENT === $variant
			|| HLC_Certificate_Data::VARIANT_SUBAWARD === $variant ) {
			return self::SEAL_GRANT_MANAGEMENT;
		}
		return self::SEAL_GRANT_WRITING;
	}

	/**
	 * Return inline SVG markup for the seal, adapted for Dompdf fonts.
	 * Falls back to empty string if the SVG file is missing.
	 */
	private function seal_svg_for_pdf( string $variant ): string {
		$svg_file = ( HLC_Certificate_Data::VARIANT_GRANT_MANAGEMENT === $variant
			|| HLC_Certificate_Data::VARIANT_SUBAWARD === $variant )
			? self::SEAL_GM_SVG
			: self::SEAL_GW_SVG;

		$path = HLC_PLUGIN_DIR . 'assets/img/' . $svg_file;
		if ( ! is_readable( $path ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$svg = (string) file_get_contents( $path );
		if ( $svg === '' ) {
			return '';
		}

		// Set fixed pixel dimensions for Dompdf layout and swap to DejaVu Serif.
		$svg = preg_replace( '/<svg\b/', '<svg width="76" height="76"', $svg, 1 );
		$svg = str_replace(
			'font-family="Times New Roman,Georgia,serif"',
			'font-family="DejaVu Serif,serif"',
			$svg
		);

		return $svg;
	}

	private function event_date_key( object $event ): string {
		$start = isset( $event->eve_start ) ? (string) $event->eve_start : '';
		if ( $start === '' ) {
			return gmdate( 'Y-m-d' );
		}
		$ts = strtotime( $start );
		return $ts ? gmdate( 'Y-m-d', $ts ) : gmdate( 'Y-m-d' );
	}

	private function attachment_or_bundle_data_uri( int $attachment_id, string $bundled_filename ): string {
		if ( $attachment_id > 0 ) {
			$path = get_attached_file( $attachment_id );
			if ( $path && is_readable( $path ) ) {
				$uri = $this->file_to_data_uri( $path );
				if ( $uri !== '' ) {
					return $uri;
				}
			}
		}
		$bundle = HLC_PLUGIN_DIR . 'assets/img/' . $bundled_filename;
		if ( is_readable( $bundle ) ) {
			return $this->file_to_data_uri( $bundle );
		}
		return '';
	}

	private function file_to_data_uri( string $path ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = file_get_contents( $path );
		if ( $data === false ) {
			return '';
		}
		$mime = wp_check_filetype( $path )['type'] ?? '';
		if ( ! $mime ) {
			$mime = 'image/png';
		}

		return 'data:' . $mime . ';base64,' . base64_encode( $data );
	}

	public function sanitize_participant_name( string $name ): string {
		$name = wp_strip_all_tags( $name );
		$name = preg_replace( '/\s+/u', ' ', $name );
		$name = trim( (string) $name );
		if ( function_exists( 'mb_substr' ) ) {
			$name = mb_substr( $name, 0, 200, 'UTF-8' );
		} else {
			$name = substr( $name, 0, 200 );
		}
		return trim( $name );
	}

	public function sanitize_agency( string $agency ): string {
		$agency = wp_strip_all_tags( $agency );
		$agency = preg_replace( '/\s+/u', ' ', $agency );
		$agency = trim( (string) $agency );
		if ( function_exists( 'mb_substr' ) ) {
			$agency = mb_substr( $agency, 0, 300, 'UTF-8' );
		} else {
			$agency = substr( $agency, 0, 300 );
		}
		return trim( $agency );
	}

	/**
	 * @param array<string,string> $vars participant_name, event_title, event_dates, event_type, site_name
	 */
	public static function replace_placeholders( string $text, array $vars ): string {
		foreach ( $vars as $k => $v ) {
			$text = str_replace( '{' . $k . '}', $v, $text );
		}
		return $text;
	}
}
