<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads Hostlinks event tables only (no direct Marketing Ops tables).
 */
class HLC_Bridge {

	/** @var string */
	private $events_table;

	/** @var string */
	private $type_table;

	public function __construct() {
		global $wpdb;
		$this->events_table = $wpdb->prefix . 'event_details_list';
		$this->type_table   = $wpdb->prefix . 'event_type';
	}

	/**
	 * @return object|null
	 */
	public function get_event_with_type( int $event_id ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT e.*, t.event_type_name, t.event_type_id AS type_table_id
				FROM {$this->events_table} e
				LEFT JOIN {$this->type_table} t ON e.eve_type = t.event_type_id
				WHERE e.eve_id = %d AND e.eve_status = 1",
				$event_id
			)
		);
		return $row;
	}

	/**
	 * Active events, optional eve_id IN filter and date filters.
	 *
	 * @param int[]|null $event_ids Pass null for no filter.
	 * @param array{
	 *   past_only?:bool,
	 *   year?:int,
	 *   month?:int,
	 *   order_desc?:bool
	 * } $filters Optional; month 1–12 or 0 for entire year when year is set.
	 * @return object[]
	 */
	public function get_events( ?array $event_ids = null, array $filters = array() ): array {
		global $wpdb;

		$where  = array( 'e.eve_status = 1' );
		$params = array();
		$event_date_sql = "CASE
			WHEN e.eve_end IS NOT NULL AND e.eve_end NOT IN ('', '0000-00-00', '0000-00-00 00:00:00')
			THEN e.eve_end
			ELSE e.eve_start
		END";

		if ( is_array( $event_ids ) ) {
			$event_ids = array_values( array_filter( array_map( 'intval', $event_ids ) ) );
			if ( $event_ids === array() ) {
				return array();
			}
			$ph      = implode( ',', array_fill( 0, count( $event_ids ), '%d' ) );
			$where[] = "e.eve_id IN ({$ph})";
			$params  = array_merge( $params, $event_ids );
		}

		if ( ! empty( $filters['past_only'] ) ) {
			$cutoff  = wp_date( 'Y-m-d' );
			$where[] = "DATE({$event_date_sql}) < %s";
			$params[] = $cutoff;
		}

		if ( ! empty( $filters['year'] ) ) {
			$y = (int) $filters['year'];
			if ( $y >= 1990 && $y <= 2100 ) {
				$where[]  = "YEAR({$event_date_sql}) = %d";
				$params[] = $y;
				$m        = isset( $filters['month'] ) ? (int) $filters['month'] : 0;
				if ( $m >= 1 && $m <= 12 ) {
					$where[]  = "MONTH({$event_date_sql}) = %d";
					$params[] = $m;
				}
			}
		}

		$order = ! empty( $filters['order_desc'] ) ? 'DESC' : 'ASC';

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT e.*, t.event_type_name, t.event_type_id AS type_table_id
			FROM {$this->events_table} e
			LEFT JOIN {$this->type_table} t ON e.eve_type = t.event_type_id
			WHERE {$where_sql}
			ORDER BY {$event_date_sql} {$order}";

		if ( $params !== array() ) {
			$sql = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * @param array<string,mixed> $filters Passed to get_events() (past_only, year, month, order_desc).
	 * @return object[]
	 */
	public function get_events_for_access( HLC_Access $access, array $filters = array() ): array {
		if ( ! $access->limit_to_marketing_ops_buckets() || ! class_exists( 'HMO_Access_Service' ) ) {
			return $this->get_events( null, $filters );
		}

		$hmo = new HMO_Access_Service();
		if ( $hmo->current_user_can_see_all_events() ) {
			return $this->get_events( null, $filters );
		}

		$allowed = $hmo->get_allowed_event_ids();
		if ( ! is_array( $allowed ) ) {
			return $this->get_events( null, $filters );
		}

		return $this->get_events( $allowed, $filters );
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function event_to_rest_array( object $event ): array {
		$location  = isset( $event->eve_location ) ? trim( (string) $event->eve_location ) : '';
		$type_name = isset( $event->event_type_name ) ? trim( (string) $event->event_type_name ) : '';
		$is_zoom   = isset( $event->eve_zoom )
			&& strtolower( trim( (string) $event->eve_zoom ) ) === 'yes';

		$label = self::build_event_label( $location, $type_name, $event, $is_zoom );

		$start_raw = isset( $event->eve_start ) ? (string) $event->eve_start : '';
		$end_raw   = isset( $event->eve_end ) ? (string) $event->eve_end : '';
		$key_raw   = $end_raw !== '' ? $end_raw : $start_raw;
		$date_key  = '';
		if ( $key_raw !== '' ) {
			$ts = strtotime( $key_raw );
			if ( $ts ) {
				$date_key = gmdate( 'Y-m-d', $ts );
			}
		}

		return array(
			'id'                   => (int) $event->eve_id,
			'label'                => $label,
			'start'                => $start_raw,
			'end'                  => $end_raw,
			'type_name'            => $type_name,
			'type_id'              => isset( $event->eve_type ) ? (int) $event->eve_type : 0,
			'date_label'           => self::format_event_dates( $event ),
			'location'             => $location,
			'event_details'        => self::format_event_details( $event ),
			'completion_date_long' => HLC_Certificate_Data::format_completion_date_long( $event ),
			'date_key'             => $date_key,
			'is_zoom'              => $is_zoom,
		);
	}

	/**
	 * Build a consistent dropdown label: "[ZOOM · ] Location — Class Type".
	 * Avoids duplicating the type when it already appears in the location string.
	 * Falls back to cvent_event_title only when location is empty.
	 */
	private static function build_event_label(
		string $location,
		string $type_name,
		object $event,
		bool $is_zoom
	): string {
		$base = $location;

		if ( $base === '' ) {
			$base = isset( $event->cvent_event_title )
				? trim( (string) $event->cvent_event_title )
				: '';
		}

		if ( $type_name !== '' ) {
			$type_present = stripos( $base, $type_name ) !== false;

			if ( ! $type_present ) {
				// Also detect when only an alias is present (e.g. "Grant Management"
				// covers an "Grant Management USA" event_type_name).
				$alias = preg_replace( '/\s+USA$/i', '', $type_name );
				if ( $alias !== $type_name && $alias !== '' && stripos( $base, $alias ) !== false ) {
					$type_present = true;
				}
			}

			if ( ! $type_present ) {
				$base = $base !== '' ? $base . ' — ' . $type_name : $type_name;
			}
		}

		if ( $is_zoom ) {
			$base = 'ZOOM · ' . $base;
		}

		return $base;
	}

	public static function format_event_dates( object $event ): string {
		$start = isset( $event->eve_start ) ? (string) $event->eve_start : '';
		$end   = isset( $event->eve_end ) ? (string) $event->eve_end : '';
		if ( $start === '' ) {
			return '';
		}
		$ts_start = strtotime( $start );
		if ( ! $ts_start ) {
			return $start;
		}
		$ts_end = ( $end !== '' && $end !== $start ) ? strtotime( $end ) : null;
		if ( $ts_end ) {
			if ( date( 'MY', $ts_start ) === date( 'MY', $ts_end ) ) {
				return date_i18n( 'M j', $ts_start ) . '–' . date_i18n( 'j, Y', $ts_end );
			}
			return date_i18n( 'M j', $ts_start ) . ' – ' . date_i18n( 'M j, Y', $ts_end );
		}
		return date_i18n( 'M j, Y', $ts_start );
	}

	public static function format_event_details( object $event ): string {
		$dates = self::format_event_dates( $event );
		$is_zoom = isset( $event->eve_zoom )
			&& strtolower( trim( (string) $event->eve_zoom ) ) === 'yes';
		$location = $is_zoom
			? 'Zoom webinar'
			: ( isset( $event->eve_location ) ? trim( (string) $event->eve_location ) : '' );

		$parts = array_values( array_filter( array( $dates, $location ), static fn( $part ) => $part !== '' ) );
		return implode( "\n", $parts );
	}
}
