<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HLC_Shortcode {

	/** @var HLC_Access */
	private $access;

	public function __construct( HLC_Access $access ) {
		$this->access = $access;
	}

	public function register(): void {
		add_shortcode( 'hostlinks_certificate_generator', array( $this, 'render' ) );
	}

	public function render(): string {
		if ( ! is_user_logged_in() ) {
			return '<div class="hlc-shell"><p class="hlc-muted">Please log in to use the certificate generator.</p></div>';
		}

		if ( ! $this->access->current_user_can_use_generator() ) {
			return $this->access->get_denial_message_html();
		}

		$show_hl_bar = class_exists( 'Hostlinks_Toolbar' )
			&& class_exists( 'Hostlinks_Page_URLs' )
			&& (string) Hostlinks_Page_URLs::get_reports() !== '';

		$hlc_style_deps = array();
		if ( $show_hl_bar && wp_style_is( 'hostlinks-calendar', 'registered' ) ) {
			$hlc_style_deps[] = 'hostlinks-calendar';
		}

		wp_enqueue_style(
			'hlc-frontend',
			HLC_PLUGIN_URL . 'assets/css/frontend.css',
			$hlc_style_deps,
			HLC_VERSION
		);
		wp_enqueue_script(
			'hlc-frontend',
			HLC_PLUGIN_URL . 'assets/js/frontend.js',
			array(),
			HLC_VERSION,
			true
		);

		$logo_gw  = $this->access->get_logo_grant_writing_id();
		$logo_gm  = $this->access->get_logo_grant_mgmt_id();
		$logo_sub = $this->access->get_logo_subaward_id();
		$sig_id   = $this->access->get_signature_attachment_id();

		$bundle_logo = HLC_PLUGIN_URL . 'assets/img/' . HLC_PDF::BUNDLED_LOGO;
		$bundle_sig  = HLC_PLUGIN_URL . 'assets/img/' . HLC_PDF::BUNDLED_SIGNATURE;
		$default_gw  = is_readable( HLC_PLUGIN_DIR . 'assets/img/' . HLC_PDF::LOGO_GRANT_WRITING )
			? HLC_PLUGIN_URL . 'assets/img/' . HLC_PDF::LOGO_GRANT_WRITING
			: $bundle_logo;
		$default_gm  = is_readable( HLC_PLUGIN_DIR . 'assets/img/' . HLC_PDF::LOGO_GRANT_MANAGEMENT )
			? HLC_PLUGIN_URL . 'assets/img/' . HLC_PDF::LOGO_GRANT_MANAGEMENT
			: $bundle_logo;

		wp_localize_script(
			'hlc-frontend',
			'hlcData',
			array(
				'restUrl'      => esc_url_raw( rest_url( 'hl-cert/v1/' ) ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'workshopCopy' => HLC_Certificate_Data::workshop_copy_for_frontend(),
				'media'        => array(
					'logoGw'    => $logo_gw ? wp_get_attachment_image_url( $logo_gw, 'medium' ) : $default_gw,
					'logoGm'    => $logo_gm ? wp_get_attachment_image_url( $logo_gm, 'medium' ) : $default_gm,
					'logoSub'   => $logo_sub
						? wp_get_attachment_image_url( $logo_sub, 'medium' )
						: ( $logo_gm ? wp_get_attachment_image_url( $logo_gm, 'medium' ) : $default_gm ),
					'signature' => $sig_id ? wp_get_attachment_image_url( $sig_id, 'medium' ) : $bundle_sig,
					'sealGw'    => HLC_PLUGIN_URL . 'assets/img/seal-grant-writing-usa.png',
					'sealGm'    => HLC_PLUGIN_URL . 'assets/img/seal-grant-management-usa.png',
				),
			)
		);

		$hlc_sel_year = (int) wp_date( 'Y' );

		ob_start();
		if ( $show_hl_bar ) {
			echo '<div class="hostlinks-page"><div class="hostlinks-container">';
			Hostlinks_Toolbar::render_reports_style_actions_bar( 'certificates', 'hostlinks_reports' );
			echo '<hr class="hlc-after-toolbar" aria-hidden="true" />';
		}
		?>
		<div id="hlc-app" class="hlc-shell">
			<main class="hlc-grid">
				<aside class="hlc-aside">
					<p class="hlc-lead">Issue a certificate</p>
					<p class="hlc-muted">Pick the class type, the year, and optionally the month, choose an event, then download or email the PDF.</p>

					<div class="hlc-card">
						<select id="hlc-filter-class" class="hlc-input hlc-input--select" aria-label="Class type">
							<option value="grant_writing" selected>Grant Writing</option>
							<option value="grant_management">Grant Management</option>
							<option value="subaward">Subaward</option>
						</select>
						<select id="hlc-filter-year" class="hlc-input hlc-input--select" aria-label="Year (workshop date)" style="margin-top:10px;">
							<?php
							for ( $y = $hlc_sel_year; $y >= $hlc_sel_year - 15; $y-- ) :
								?>
							<option value="<?php echo (int) $y; ?>" <?php selected( $y, $hlc_sel_year ); ?>><?php echo (int) $y; ?></option>
								<?php
							endfor;
							?>
						</select>
						<select id="hlc-filter-month" class="hlc-input hlc-input--select" aria-label="Month" style="margin-top:10px;">
							<option value="0" selected>All months</option>
							<?php
							for ( $m = 1; $m <= 12; $m++ ) {
								echo '<option value="' . (int) $m . '">' . esc_html( date_i18n( 'F', mktime( 0, 0, 0, $m, 1, $hlc_sel_year ) ) ) . '</option>';
							}
							?>
						</select>
						<select id="hlc-event" class="hlc-input hlc-input--select" name="hlc-event" aria-label="Event" style="margin-top:10px;">
							<option value="">— Loading… —</option>
						</select>
						<div id="hlc-mini-meta" class="hlc-mini-meta" hidden>
							<span id="hlc-mini-type"></span>
							<span class="hlc-dot" aria-hidden="true"></span>
							<span id="hlc-mini-date"></span>
						</div>
					</div>

					<div class="hlc-card">
						<label class="hlc-label" for="hlc-participant">Attendee name</label>
						<input type="text" id="hlc-participant" class="hlc-input" autocomplete="name" placeholder="Jane A. Doe" required />

						<label class="hlc-label" for="hlc-agency" style="margin-top:14px;">Agency / organization</label>
						<input type="text" id="hlc-agency" class="hlc-input" autocomplete="organization" placeholder="Optional" />

						<label class="hlc-label" for="hlc-email" style="margin-top:14px;">Recipient email</label>
						<input type="email" id="hlc-email" class="hlc-input" autocomplete="email" placeholder="For Send email" />
					</div>

					<div class="hlc-actions">
						<button type="button" class="hlc-btn hlc-btn--primary" id="hlc-download">Print / Save PDF</button>
						<button type="button" class="hlc-btn hlc-btn--outline" id="hlc-email-send">Email PDF</button>
					</div>
					<p id="hlc-status" class="hlc-status" aria-live="polite"></p>
				</aside>

				<section class="hlc-preview-col" aria-label="Live preview">
					<div class="hlc-preview-meta">
						<span class="hlc-muted hlc-upper">Live preview</span>
						<span class="hlc-cert-num">Cert No. <span id="hlc-pr-cert-id" class="hlc-mono">—</span></span>
					</div>
					<div class="hlc-preview-wrap">
						<div id="hlc-cert-card" class="hlc-cert-paper">
							<div class="hlc-cert-border-outer">
								<div class="hlc-cert-border-mid">
								<div class="hlc-cert-inner">
								<div class="hlc-cert-header">
									<img id="hlc-pr-logo" class="hlc-cert-logo" src="" alt="" />
								</div>
								<img class="hlc-cert-watermark" src="<?php echo esc_url( HLC_PLUGIN_URL . 'assets/img/logo-pen-watermark.png' ); ?>" alt="" aria-hidden="true" />
								<div class="hlc-cert-scroll">
									<p class="hlc-cert-presented">This Certificate is Proudly Presented to</p>
									<p id="hlc-pr-name" class="hlc-cert-name">Recipient Name</p>
									<div class="hlc-cert-name-line"></div>
									<p id="hlc-pr-agency" class="hlc-cert-agency" hidden></p>
									<p id="hlc-pr-body" class="hlc-cert-body"></p>
									<p id="hlc-pr-program" class="hlc-cert-program"></p>
									<p id="hlc-pr-datelong" class="hlc-cert-event-details"></p>
								</div>
							<div class="hlc-cert-footer">
								<div class="hlc-cert-footer-row hlc-cert-footer-top">
									<div class="hlc-cert-ft-col hlc-cert-ft-w-date"></div>
									<div class="hlc-cert-ft-col hlc-cert-ft-w-seal"></div>
									<div class="hlc-cert-ft-col hlc-cert-ft-w-sig">
										<p class="hlc-cert-sig-script">Rebecca Helm</p>
									</div>
								</div>
								<div class="hlc-cert-footer-row hlc-cert-footer-rules">
									<div class="hlc-cert-ft-col hlc-cert-ft-w-date"></div>
									<div class="hlc-cert-ft-col hlc-cert-ft-w-seal"></div>
									<div class="hlc-cert-ft-col hlc-cert-ft-w-sig"><div class="hlc-cert-ft-rule"></div></div>
								</div>
								<div class="hlc-cert-footer-row hlc-cert-footer-labels">
									<div class="hlc-cert-ft-col hlc-cert-ft-w-date"></div>
									<div class="hlc-cert-ft-col hlc-cert-ft-w-seal"></div>
									<div class="hlc-cert-ft-col hlc-cert-ft-w-sig hlc-cert-ft-bot-sig">
										<p class="hlc-cert-sig-name">Rebecca Helm</p>
										<p class="hlc-cert-sig-title">Chief Executive Officer</p>
									</div>
								</div>
							</div>
							<!-- Seal absolutely positioned lower-left, aligned with sig area -->
							<img id="hlc-pr-seal" class="hlc-cert-seal-img hlc-cert-seal-left" src="" alt="" aria-hidden="true" />
								<div class="hlc-cert-meta" id="hlc-pr-meta-cert">Certificate of Completion No. —</div>
								</div>
								</div>
							</div>
						</div>
					</div>
				</section>
			</main>
		</div>
		<?php
		if ( $show_hl_bar ) {
			echo '</div></div>';
		}
		return (string) ob_get_clean();
	}
}
