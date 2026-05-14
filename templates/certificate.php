<?php
/**
 * Dompdf: fixed US Letter landscape (11in × 8.5in).
 * Use explicit point dimensions rather than nested 100% heights; Dompdf counts
 * borders and padding inconsistently and can otherwise create a second page.
 *
 * @var string $participant_name
 * @var string $agency
 * @var string $logo_attr        safe data-URI for the organisation logo img src
 * @var string $seal_attr        safe data-URI for the seal img src
 * @var string $workshop_body
 * @var string $program_line
 * @var string $date_long
 * @var string $event_details
 * @var string $certificate_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<style>
		@font-face {
			font-family: 'AlexBrush';
			src: url('<?php echo HLC_PLUGIN_DIR; ?>assets/fonts/AlexBrush-Regular.ttf') format('truetype');
			font-weight: normal;
			font-style: normal;
		}
		@page {
			size: letter landscape;
			margin: 0;
		}
		* { margin: 0; padding: 0; }
		html, body {
			background: #ffffff;
		}
		body {
			font-family: DejaVu Sans, sans-serif;
			color: #2b2f44;
		}

		/* ── outer page shell ── */
		.page {
			width: 780pt;
			height: 600pt;
			page-break-inside: avoid;
			page-break-after: avoid;
		}
		.paper {
			background: #ffffff;
			border: 3px solid #9b2335;
			width: 748pt;
			height: 568pt;
			padding: 8px;
			margin: 5pt auto 0 auto;
		}
	.paper-inner {
		border: 1px solid rgba(155, 35, 53, 0.45);
		padding: 8px;
		width: 726pt;
		height: 546pt;
		position: relative;
		overflow: hidden;
	}
		.paper-core {
			border: 1px solid rgba(181, 140, 60, 0.5);
			padding: 24px 28px 8px 28px;
			width: 668pt;
			height: 510pt;
		}

		/* ── two-row layout table (fills paper-core) ── */
		.cert-layout {
			width: 100%;
			height: 510pt;
			border-collapse: collapse;
			table-layout: fixed;
		}
		/* content row — grows to fill remaining space */
		.cert-content {
			vertical-align: top;
			text-align: center;
			padding: 0;
		}
		/* footer row — fixed height, content sits at bottom */
		.cert-foot {
			vertical-align: bottom;
			height: 138pt;
			padding: 0;
		}

	/* ── header / content ── */
	.hdr-logo {
		max-height: 140px;
		width: auto;
		max-width: 480px;
		margin: 0 auto 8px auto;
		display: block;
	}
	.watermark {
		position: absolute;
		bottom: 4pt;
		right: 0;
		height: 500pt;
		width: auto;
		opacity: 0.25;
	}
		.presented {
			font-size: 10px;
			text-transform: uppercase;
			letter-spacing: 0.32em;
			color: rgba(26, 39, 68, 0.82);
			margin: 14px 0 8px 0;
		}
	.name {
		font-family: 'AlexBrush', DejaVu Serif, serif;
		font-size: 46px;
		line-height: 1;
		color: #7a1524;
		margin: 0 0 -50px 0;
		font-weight: 400;
	}
	.name-line {
		height: 1px;
		width: 72%;
		margin: 2px auto 16px auto;
			background-color: rgba(155, 35, 53, 0.4);
		}
		.agency {
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.24em;
			color: #1a2744;
			margin: 8px 0 0 0;
		}
		.body-text {
			font-family: DejaVu Serif, serif;
			font-size: 19px;
			font-style: italic;
			line-height: 1.45;
			color: rgba(43, 47, 68, 0.88);
			margin: 0 auto 0 auto;
			max-width: 88%;
		}
	.event-details-inline {
		font-family: DejaVu Serif, serif;
		font-size: 14px;
		font-weight: 700;
		line-height: 1.3;
		color: #1a2744;
		margin: 10px auto 0 auto;
	}
	.program {
		font-size: 10px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.28em;
		color: #9b2335;
		margin: 12px 0 0 0;
	}

		/* ── footer ── */
		.footer-table {
			width: 100%;
			border-collapse: collapse;
			table-layout: fixed;
		}
		.footer-table .ft-top {
			vertical-align: bottom;
			height: 58px;
			padding: 0 8px;
		}
		.footer-table .ft-mid {
			vertical-align: top;
			padding: 0 8px;
		}
		.footer-table .ft-bot {
			vertical-align: top;
			padding: 4px 8px 0 8px;
		}
		.ft-date {
			font-family: DejaVu Serif, serif;
			font-size: 16px;
			line-height: 1.25;
			font-weight: 700;
			color: #1a2744;
		}
		.ft-rule {
			border-top: 1px solid rgba(43, 47, 68, 0.35);
			height: 1px;
			margin: 6px 0 0 0;
		}
		.ft-label {
			font-size: 7px;
			text-transform: uppercase;
			letter-spacing: 0.24em;
			color: rgba(26, 39, 68, 0.72);
			margin: 4px 0 0 0;
		}
		.seal-img {
			width: 122px;
			height: 122px;
			display: block;
			margin: 0 auto;
		}
		.sig-script {
			font-family: 'AlexBrush', 'DejaVu Serif', serif;
			font-size: 29px;
			color: #1a2744;
			margin: 0 33px -13px 0;
			padding: 0;
			text-align: right;
			line-height: 1;
		}
		.sig-name {
			font-size: 10px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.22em;
			color: #1a2744;
			margin: 4px 0 0 0;
		}
		.sig-title {
			font-size: 7px;
			text-transform: uppercase;
			letter-spacing: 0.2em;
			color: rgba(26, 39, 68, 0.72);
			margin: 2px 0 0 0;
		}
		.meta-cert {
			text-align: center;
			font-size: 7px;
			text-transform: uppercase;
			letter-spacing: 0.24em;
			color: rgba(26, 39, 68, 0.55);
			margin: 6px 0 0 0;
		}
	</style>
</head>
<body>
<div class="page">
	<div class="paper">
		<div class="paper-inner">
			<img class="watermark" src="<?php echo esc_url( HLC_PLUGIN_URL . 'assets/img/logo-pen-watermark.png' ); ?>" alt="" />
			<div class="paper-core">
				<table class="cert-layout" cellspacing="0" cellpadding="0">
					<tr>
						<td class="cert-content">
						<?php if ( $logo_attr !== '' ) : ?>
							<img class="hdr-logo" src="<?php echo $logo_attr; ?>" alt="" />
						<?php endif; ?>

						<p class="presented">This Certificate is Proudly Presented to</p>
							<p class="name"><?php echo esc_html( $participant_name ); ?></p>
							<div class="name-line"></div>
							<?php if ( $agency !== '' ) : ?>
								<p class="agency"><?php echo esc_html( $agency ); ?></p>
							<?php endif; ?>

						<p class="body-text"><?php echo esc_html( $workshop_body ); ?></p>
						<?php if ( $event_details !== '' ) : ?>
							<p class="event-details-inline"><?php echo nl2br( esc_html( $event_details ) ); ?></p>
						<?php endif; ?>
						<p class="program"><?php echo esc_html( $program_line ); ?></p>
					</td>
				</tr>
				<tr>
					<td class="cert-foot">
						<table class="footer-table" cellspacing="0" cellpadding="0">
							<tr>
								<td class="ft-top" style="width:36%; text-align:left; vertical-align:bottom;">
									<?php if ( isset( $seal_attr ) && $seal_attr !== '' ) : ?>
										<img class="seal-img" src="<?php echo $seal_attr; ?>" alt="" style="margin:0;" />
									<?php endif; ?>
								</td>
								<td class="ft-top" style="width:28%;"></td>
								<td class="ft-top" style="width:36%;">
									<p class="sig-script">Rebecca Helm</p>
								</td>
							</tr>
							<tr>
								<td class="ft-mid"></td>
								<td class="ft-mid"></td>
								<td class="ft-mid"><div class="ft-rule"></div></td>
							</tr>
							<tr>
								<td class="ft-bot"></td>
								<td class="ft-bot"></td>
								<td class="ft-bot">
									<p class="sig-name">Rebecca Helm</p>
									<p class="sig-title">Chief Executive Officer</p>
								</td>
							</tr>
						</table>
						<p class="meta-cert">Certificate of Completion No. <?php echo esc_html( $certificate_id ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
</div>
</body>
</html>
