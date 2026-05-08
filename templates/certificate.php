<?php
/**
 * Dompdf: fixed US Letter landscape (11in × 8.5in). Avoid SVG <text> (crashes Dompdf).
 *
 * @var string $participant_name
 * @var string $agency
 * @var string $logo_attr   safe attribute value for img src (data URI)
 * @var string $signature_attr
 * @var string $seal_attr   safe attribute value for img src (data URI)
 * @var string $workshop_body
 * @var string $program_line
 * @var string $date_long
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
			margin: 0.32in;
		}
		* { box-sizing: border-box; }
		html, body {
			margin: 0;
			padding: 0;
			width: 100%;
			height: 100%;
			background: #ffffff;
		}
		body {
			font-family: DejaVu Sans, sans-serif;
			color: #2b2f44;
		}
		.page {
			width: 10.36in;
			height: 7.86in;
			max-height: 7.86in;
			overflow: hidden;
			margin: 0 auto;
			position: relative;
		}
		.paper {
			background: #ffffff;
			border: 3px solid #9b2335;
			height: 100%;
			padding: 8px;
		}
		.paper-inner {
			border: 1px solid rgba(155, 35, 53, 0.45);
			padding: 8px;
			height: 100%;
		}
		.paper-core {
			border: 1px solid rgba(181, 140, 60, 0.5);
			padding: 18px 28px 36px 28px;
			text-align: center;
			position: relative;
			height: 100%;
		}
		.scroll {
			max-height: 4.35in;
			overflow: hidden;
		}
		.hdr-logo {
			max-height: 64px;
			width: auto;
			max-width: 280px;
			margin: 0 auto 4px auto;
			display: block;
		}
		.hdr-sub {
			font-size: 8px;
			font-weight: 700;
			letter-spacing: 0.32em;
			text-transform: uppercase;
			color: #1a2744;
			margin: 0;
		}
		.hdr-line {
			display: inline-block;
			width: 32px;
			border-top: 1px solid rgba(155, 35, 53, 0.55);
			margin: 0 6px;
			vertical-align: middle;
		}
		.presented {
			font-size: 10px;
			text-transform: uppercase;
			letter-spacing: 0.32em;
			color: rgba(26, 39, 68, 0.82);
			margin: 16px 0 0 0;
		}
		.name {
			font-family: DejaVu Serif, serif;
			font-size: 34px;
			line-height: 1.05;
			color: #7a1524;
			margin: 10px 0 0 0;
			font-weight: 700;
		}
		.name-line {
			height: 1px;
			width: 72%;
			margin: 8px auto 0 auto;
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
			font-size: 14px;
			font-style: italic;
			line-height: 1.45;
			color: rgba(43, 47, 68, 0.88);
			margin: 14px auto 0 auto;
			max-width: 92%;
		}
		.program {
			font-size: 9px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.28em;
			color: #9b2335;
			margin: 12px 0 0 0;
		}
		.footer-wrap {
			position: absolute;
			left: 28px;
			right: 28px;
			bottom: 34px;
		}
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
			font-size: 18px;
			font-weight: 700;
			color: #1a2744;
			margin: 0;
			padding: 0;
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
			width: 76px;
			height: 76px;
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
			position: absolute;
			left: 28px;
			right: 28px;
			bottom: 10px;
			text-align: center;
			font-size: 7px;
			text-transform: uppercase;
			letter-spacing: 0.24em;
			color: rgba(26, 39, 68, 0.55);
		}
	</style>
</head>
<body>
<div class="page">
	<div class="paper">
		<div class="paper-inner">
			<div class="paper-core">
				<div class="scroll">
					<?php if ( $logo_attr !== '' ) : ?>
						<img class="hdr-logo" src="<?php echo $logo_attr; ?>" alt="" />
					<?php endif; ?>
					<p class="hdr-sub">
						<span class="hdr-line"></span>
						Office of Professional Development &amp; Training
						<span class="hdr-line"></span>
					</p>

					<p class="presented">This Certificate is Proudly Presented to</p>
					<p class="name"><?php echo esc_html( $participant_name ); ?></p>
					<div class="name-line"></div>
					<?php if ( $agency !== '' ) : ?>
						<p class="agency"><?php echo esc_html( $agency ); ?></p>
					<?php endif; ?>

					<p class="body-text"><?php echo esc_html( $workshop_body ); ?></p>
					<p class="program"><?php echo esc_html( $program_line ); ?></p>
				</div>

				<div class="footer-wrap">
					<table class="footer-table">
						<tr>
							<td class="ft-top" style="width: 36%;">
								<p class="ft-date"><?php echo esc_html( $date_long ); ?></p>
							</td>
							<td class="ft-top" style="width: 28%;">
			<?php if ( isset( $seal_attr ) && $seal_attr !== '' ) : ?>
				<img class="seal-img" src="<?php echo $seal_attr; ?>" alt="" />
			<?php else : ?>
				<div style="height: 76px;"></div>
			<?php endif; ?>
							</td>
							<td class="ft-top" style="width: 36%;">
							<p class="sig-script">Becky Helm</p>
							</td>
						</tr>
						<tr>
							<td class="ft-mid"><div class="ft-rule"></div></td>
							<td class="ft-mid"></td>
							<td class="ft-mid"><div class="ft-rule"></div></td>
						</tr>
						<tr>
							<td class="ft-bot">
								<p class="ft-label">Date of Completion</p>
							</td>
							<td class="ft-bot"></td>
							<td class="ft-bot">
								<p class="sig-name">Becky Helm</p>
								<p class="sig-title">Chief Executive Officer</p>
							</td>
						</tr>
					</table>
				</div>

				<div class="meta-cert">Certificate No. <?php echo esc_html( $certificate_id ); ?></div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
