<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HLC_REST {

	const NS = 'hl-cert/v1';

	/** @var HLC_Access */
	private $access;

	/** @var HLC_Bridge */
	private $bridge;

	/** @var HLC_PDF */
	private $pdf;

	public function __construct( HLC_Access $access, HLC_Bridge $bridge, HLC_PDF $pdf ) {
		$this->access = $access;
		$this->bridge = $bridge;
		$this->pdf    = $pdf;
	}

	public function register_routes(): void {
		register_rest_route(
			self::NS,
			'/events',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'permission_allowed_user' ),
				'callback'            => array( $this, 'get_events' ),
				'args'                => array(
					'year'  => array(
						'type'    => 'integer',
						'default' => (int) wp_date( 'Y' ),
						'minimum' => 1990,
						'maximum' => 2100,
					),
					'month' => array(
						'type'    => 'integer',
						'default' => 0,
						'minimum' => 0,
						'maximum' => 12,
					),
					'class' => array(
						'type'    => 'string',
						'default' => '',
						'enum'    => array( '', 'grant_writing', 'grant_management', 'subaward' ),
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/pdf',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( $this, 'permission_allowed_user' ),
				'callback'            => array( $this, 'post_pdf' ),
				'args'                => array(
					'event_id'          => array(
						'required' => true,
						'type'     => 'integer',
					),
					'participant_name'  => array(
						'required' => true,
						'type'     => 'string',
					),
					'action'            => array(
						'type'    => 'string',
						'default' => 'download',
						'enum'    => array( 'download', 'email' ),
					),
					'recipient_email'   => array(
						'type' => 'string',
					),
					'agency'            => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	public function permission_allowed_user(): bool {
		return $this->access->current_user_can_use_generator();
	}

	public function get_events( WP_REST_Request $request ): WP_REST_Response {
		$year  = (int) $request->get_param( 'year' );
		$month = (int) $request->get_param( 'month' );
		$class = (string) $request->get_param( 'class' );
		if ( $year < 1990 || $year > 2100 ) {
			$year = (int) wp_date( 'Y' );
		}
		if ( $month < 0 || $month > 12 ) {
			$month = 0;
		}
		$valid_classes = array( 'grant_writing', 'grant_management', 'subaward' );
		if ( ! in_array( $class, $valid_classes, true ) ) {
			$class = '';
		}

		$filters = array(
			'past_only'  => true,
			'year'       => $year,
			'month'      => $month,
			'order_desc' => true,
		);

		$rows = $this->bridge->get_events_for_access( $this->access, $filters );
		$out  = array();
		foreach ( $rows as $row ) {
			$variant = HLC_Certificate_Data::resolve_variant( $row, $this->access );
			if ( $class !== '' && $variant !== $class ) {
				continue;
			}
			$item                     = HLC_Bridge::event_to_rest_array( $row );
			$item['workshop_variant'] = $variant;
			$out[]                    = $item;
		}
		return new WP_REST_Response( array( 'events' => $out ) );
	}

	public function post_pdf( WP_REST_Request $request ): WP_REST_Response {
		$event_id = (int) $request->get_param( 'event_id' );
		$name     = (string) $request->get_param( 'participant_name' );
		$action   = (string) $request->get_param( 'action' );
		$agency   = (string) $request->get_param( 'agency' );

		if ( $name === '' ) {
			return new WP_REST_Response( array( 'message' => 'Participant name is required.' ), 400 );
		}

		try {
			list( $binary, $filename ) = $this->pdf->build_certificate_pdf( $event_id, $name, $agency );
		} catch ( RuntimeException $e ) {
			return new WP_REST_Response( array( 'message' => $e->getMessage() ), 400 );
		}

		if ( $action === 'email' ) {
			$to = sanitize_email( (string) $request->get_param( 'recipient_email' ) );
			if ( ! $to || ! is_email( $to ) ) {
				return new WP_REST_Response( array( 'message' => 'A valid recipient email is required.' ), 400 );
			}

			$event = $this->bridge->get_event_with_type( $event_id );
			if ( ! $event ) {
				return new WP_REST_Response( array( 'message' => 'Event not found.' ), 400 );
			}

			$title     = HLC_Bridge::event_to_rest_array( $event )['label'];
			$dates     = HLC_Bridge::format_event_dates( $event );
			$type_name = isset( $event->event_type_name ) ? (string) $event->event_type_name : '';

			$vars = array(
				'participant_name' => $this->pdf->sanitize_participant_name( $name ),
				'event_title'      => $title,
				'event_dates'      => $dates,
				'event_type'       => $type_name,
				'site_name'        => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			);

			$subject = HLC_PDF::replace_placeholders( $this->access->get_email_subject(), $vars );
			$body    = HLC_PDF::replace_placeholders( $this->access->get_email_body(), $vars );

			$upload_dir = wp_upload_dir();
			if ( ! empty( $upload_dir['error'] ) ) {
				return new WP_REST_Response( array( 'message' => 'Upload directory unavailable.' ), 500 );
			}

			$tmp = $upload_dir['basedir'] . '/hlc-cert-' . wp_generate_password( 8, false ) . '.pdf';
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			if ( file_put_contents( $tmp, $binary ) === false ) {
				return new WP_REST_Response( array( 'message' => 'Could not create temporary file.' ), 500 );
			}

			$sent = wp_mail(
				$to,
				$subject,
				$body,
				array( 'Content-Type: text/plain; charset=UTF-8' ),
				array( $tmp )
			);

			wp_delete_file( $tmp );

			if ( ! $sent ) {
				return new WP_REST_Response( array( 'message' => 'Email could not be sent.' ), 500 );
			}

			return new WP_REST_Response( array( 'success' => true, 'message' => 'Certificate emailed.' ) );
		}

		return new WP_REST_Response(
			array(
				'filename'   => $filename,
				'pdf_base64' => base64_encode( $binary ),
			)
		);
	}
}
