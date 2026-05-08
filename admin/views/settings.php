<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$notice = '';

if ( isset( $_POST['hlc_save'] ) ) {
	check_admin_referer( 'hlc_settings' );

	$access = new HLC_Access();

	$access->save_allowed_user_ids( array_map( 'intval', (array) ( $_POST['hlc_user_ids'] ?? array() ) ) );

	update_option( HLC_Access::OPT_LOGO_GW, max( 0, (int) ( $_POST['hlc_logo_gw'] ?? 0 ) ) );
	update_option( HLC_Access::OPT_LOGO_GM, max( 0, (int) ( $_POST['hlc_logo_gm'] ?? 0 ) ) );
	update_option( HLC_Access::OPT_LOGO_SUB, max( 0, (int) ( $_POST['hlc_logo_sub'] ?? 0 ) ) );

	$match_gw = sanitize_textarea_field( wp_unslash( $_POST['hlc_match_gw'] ?? '' ) );
	$match_gm = sanitize_textarea_field( wp_unslash( $_POST['hlc_match_gm'] ?? '' ) );
	update_option( HLC_Access::OPT_MATCH_GW, $match_gw );
	update_option( HLC_Access::OPT_MATCH_GM, $match_gm );

	$match_sub = sanitize_textarea_field( wp_unslash( $_POST['hlc_match_sub'] ?? '' ) );
	update_option( HLC_Access::OPT_MATCH_SUB, $match_sub );

	update_option( HLC_Access::OPT_SIGNATURE, max( 0, (int) ( $_POST['hlc_signature'] ?? 0 ) ) );

	$type_map = array();
	if ( ! empty( $_POST['hlc_type_map'] ) && is_array( $_POST['hlc_type_map'] ) ) {
		foreach ( $_POST['hlc_type_map'] as $tid => $which ) {
			$type_map[ (int) $tid ] = sanitize_key( (string) $which );
		}
	}
	$access->save_type_logo_map( $type_map );

	update_option( HLC_Access::OPT_EMAIL_SUBJECT, sanitize_text_field( wp_unslash( $_POST['hlc_email_subject'] ?? '' ) ) );
	update_option( HLC_Access::OPT_EMAIL_BODY, sanitize_textarea_field( wp_unslash( $_POST['hlc_email_body'] ?? '' ) ) );

	update_option( HLC_Access::OPT_LIMIT_BUCKETS, isset( $_POST['hlc_limit_buckets'] ) ? '1' : '0' );
	update_option( HLC_Access::OPT_DENIAL_MESSAGE, sanitize_textarea_field( wp_unslash( $_POST['hlc_denial_message'] ?? '' ) ) );

	$notice = '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
}

$access      = new HLC_Access();
$allowed_ids = $access->get_allowed_user_ids();
$logo_gw     = $access->get_logo_grant_writing_id();
$logo_gm     = $access->get_logo_grant_mgmt_id();
$logo_sub    = $access->get_logo_subaward_id();
$match_gw    = (string) get_option( HLC_Access::OPT_MATCH_GW, HLC_Access::DEFAULT_MATCH_GW );
$match_gm    = (string) get_option( HLC_Access::OPT_MATCH_GM, HLC_Access::DEFAULT_MATCH_GM );
$match_sub   = (string) get_option( HLC_Access::OPT_MATCH_SUB, HLC_Access::DEFAULT_MATCH_SUB );
$signature   = $access->get_signature_attachment_id();
$type_map    = $access->get_type_logo_map();

global $wpdb;
$event_types = $wpdb->get_results(
	"SELECT event_type_id AS id, event_type_name AS name FROM {$wpdb->prefix}event_type WHERE event_type_status = 1 ORDER BY event_type_name ASC"
);

$pick_users = array();
foreach ( $allowed_ids as $uid ) {
	$u = get_userdata( $uid );
	if ( $u ) {
		$pick_users[] = array(
			'id'    => $uid,
			'name'  => $u->display_name,
			'email' => $u->user_email,
		);
	}
}

?>
<div class="wrap">
	<h1>Hostlinks Certificate Generator</h1>
	<?php echo $notice; ?>

	<p>Publish a page with shortcode <code>[hostlinks_certificate_generator]</code>. Only users you add below can use the tool (administrators always can).</p>

	<form method="post" id="hlc-settings-form">
		<?php wp_nonce_field( 'hlc_settings' ); ?>

		<h2 class="title">Allowed users</h2>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="hlc-user-search">Add users</label></th>
				<td>
					<input type="search" id="hlc-user-search" class="regular-text" placeholder="Search by name or email…" autocomplete="off" />
					<div id="hlc-user-suggest" style="margin-top:8px;"></div>
					<ul id="hlc-user-picked" style="list-style:disc;margin-left:1.5rem;">
						<?php foreach ( $pick_users as $pu ) : ?>
						<li data-id="<?php echo (int) $pu['id']; ?>">
							<?php echo esc_html( $pu['name'] . ' — ' . $pu['email'] ); ?>
							<button type="button" class="button-link hlc-remove-user" style="color:#b32d2e;">Remove</button>
							<input type="hidden" name="hlc_user_ids[]" value="<?php echo (int) $pu['id']; ?>" />
						</li>
						<?php endforeach; ?>
					</ul>
				</td>
			</tr>
		</table>

		<h2 class="title">Logos</h2>
		<p class="description">Pick one image from the Media Library for each workshop class. PDFs and the live preview use Grant Writing, Grant Management, or Subaward based on the event type (see matching rules below). If Subaward is not set, the Grant Management logo is used.</p>
		<table class="form-table">
			<tr>
				<th scope="row">Grant Writing logo</th>
				<td>
					<input type="hidden" id="hlc_logo_gw" name="hlc_logo_gw" value="<?php echo (int) $logo_gw; ?>" />
					<div id="hlc_logo_gw_preview">
						<?php
						if ( $logo_gw ) {
							echo wp_get_attachment_image( $logo_gw, 'medium' );
						}
						?>
					</div>
					<button type="button" class="button hlc-pick-media" data-target="hlc_logo_gw" data-preview="hlc_logo_gw_preview">Choose image</button>
					<button type="button" class="button hlc-clear-media" data-target="hlc_logo_gw" data-preview="hlc_logo_gw_preview">Clear</button>
				</td>
			</tr>
			<tr>
				<th scope="row">Grant Management logo</th>
				<td>
					<input type="hidden" id="hlc_logo_gm" name="hlc_logo_gm" value="<?php echo (int) $logo_gm; ?>" />
					<div id="hlc_logo_gm_preview">
						<?php
						if ( $logo_gm ) {
							echo wp_get_attachment_image( $logo_gm, 'medium' );
						}
						?>
					</div>
					<button type="button" class="button hlc-pick-media" data-target="hlc_logo_gm" data-preview="hlc_logo_gm_preview">Choose image</button>
					<button type="button" class="button hlc-clear-media" data-target="hlc_logo_gm" data-preview="hlc_logo_gm_preview">Clear</button>
				</td>
			</tr>
			<tr>
				<th scope="row">Subaward logo</th>
				<td>
					<input type="hidden" id="hlc_logo_sub" name="hlc_logo_sub" value="<?php echo (int) $logo_sub; ?>" />
					<div id="hlc_logo_sub_preview">
						<?php
						if ( $logo_sub ) {
							echo wp_get_attachment_image( $logo_sub, 'medium' );
						}
						?>
					</div>
					<button type="button" class="button hlc-pick-media" data-target="hlc_logo_sub" data-preview="hlc_logo_sub_preview">Choose image</button>
					<button type="button" class="button hlc-clear-media" data-target="hlc_logo_sub" data-preview="hlc_logo_sub_preview">Clear</button>
				</td>
			</tr>
		</table>

		<h2 class="title">Type name matching</h2>
		<p class="description">Grant Management / Grant Writing lines: one substring per line (case-insensitive). They distinguish Grant Management from Grant Writing when the event is <strong>not</strong> a Subaward class. The <strong>Subaward hints</strong> field is evaluated first and selects Subaward wording, hours, and the Subaward logo.</p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="hlc_match_gm">Grant Management / Subaward</label></th>
				<td>
					<textarea name="hlc_match_gm" id="hlc_match_gm" rows="4" class="large-text"><?php echo esc_textarea( $match_gm ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="hlc_match_gw">Grant Writing</label></th>
				<td>
					<textarea name="hlc_match_gw" id="hlc_match_gw" rows="4" class="large-text"><?php echo esc_textarea( $match_gw ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="hlc_match_sub">Subaward type hints</label></th>
				<td>
					<textarea name="hlc_match_sub" id="hlc_match_sub" rows="3" class="large-text"><?php echo esc_textarea( $match_sub ); ?></textarea>
					<p class="description">One substring per line. If the Hostlinks event <strong>type name</strong> matches, certificates use Subaward wording and 8 contact hours. Checked before Grant Writing / Grant Management.</p>
				</td>
			</tr>
		</table>

		<h2 class="title">CEO signature (optional)</h2>
		<p class="description">Defaults to the bundled Becky Helm signature from Grant Certify Pro if not set.</p>
		<table class="form-table">
			<tr>
				<th scope="row">Signature image</th>
				<td>
					<input type="hidden" id="hlc_signature" name="hlc_signature" value="<?php echo (int) $signature; ?>" />
					<div id="hlc_signature_preview"><?php echo $signature ? wp_get_attachment_image( $signature, 'medium' ) : ''; ?></div>
					<button type="button" class="button hlc-pick-media" data-target="hlc_signature" data-preview="hlc_signature_preview">Choose image</button>
					<button type="button" class="button hlc-clear-media" data-target="hlc_signature" data-preview="hlc_signature_preview">Clear</button>
				</td>
			</tr>
		</table>

		<h2 class="title">Per event type override</h2>
		<table class="widefat striped" style="max-width:720px;">
			<thead>
				<tr>
					<th>Event type</th>
					<th>Logo</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $event_types as $et ) : ?>
				<tr>
					<td><?php echo esc_html( $et->name ); ?></td>
					<td>
						<select name="hlc_type_map[<?php echo (int) $et->id; ?>]">
							<?php
							$cur = $type_map[ (int) $et->id ] ?? '';
							?>
							<option value="">— Auto (use matching rules) —</option>
							<option value="gw" <?php selected( $cur, 'gw' ); ?>>Grant Writing logo</option>
							<option value="gm" <?php selected( $cur, 'gm' ); ?>>Grant Management logo</option>
						</select>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2 class="title">Email templates</h2>
		<p class="description">Placeholders: <code>{participant_name}</code>, <code>{event_title}</code>, <code>{event_dates}</code>, <code>{event_type}</code>, <code>{site_name}</code></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="hlc_email_subject">Subject</label></th>
				<td>
					<input type="text" id="hlc_email_subject" name="hlc_email_subject" class="large-text"
						value="<?php echo esc_attr( $access->get_email_subject() ); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="hlc_email_body">Body</label></th>
				<td>
					<textarea name="hlc_email_body" id="hlc_email_body" rows="6" class="large-text"><?php echo esc_textarea( $access->get_email_body() ); ?></textarea>
				</td>
			</tr>
		</table>

		<h2 class="title">Access</h2>
		<table class="form-table">
			<tr>
				<th scope="row">Event list scope</th>
				<td>
					<label>
						<input type="checkbox" name="hlc_limit_buckets" value="1" <?php checked( $access->limit_to_marketing_ops_buckets() ); ?> <?php disabled( ! class_exists( 'HMO_Access_Service' ) ); ?> />
						Limit events to those visible in Hostlinks Marketing Ops (buckets) for each user
					</label>
					<?php if ( ! class_exists( 'HMO_Access_Service' ) ) : ?>
						<p class="description">Install and activate <strong>Hostlinks Marketing Ops</strong> to enable bucket scoping.</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="hlc_denial_message">Shortcode denial message</label></th>
				<td>
					<textarea name="hlc_denial_message" id="hlc_denial_message" rows="3" class="large-text"><?php echo esc_textarea( (string) get_option( HLC_Access::OPT_DENIAL_MESSAGE, '' ) ); ?></textarea>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" name="hlc_save" class="button button-primary">Save settings</button>
		</p>
	</form>
</div>
