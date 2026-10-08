<?php
/**
 * Writing streak panel shown above the Posts list.
 *
 * @var string $accent_class
 * @var string $status
 * @var int    $streak
 * @var string $day_label
 * @var string $last_post_label
 * @var int    $next_milestone
 * @var bool   $is_active_today
 * @var int    $progress
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wpstreak-panel notice <?php echo esc_attr( $accent_class ); ?>">
	<div class="wpstreak-panel__lead">
		<div class="wpstreak-panel__eyebrow">Writing momentum</div>
		<h2 class="wpstreak-panel__title">Protect the streak. Build the habit.</h2>
		<p class="wpstreak-panel__description"><?php echo esc_html( $status ); ?></p>
		<div class="wpstreak-panel__stats">
			<div class="wpstreak-panel__stat">
				<span class="wpstreak-panel__stat-label">Current run</span>
				<span class="wpstreak-panel__stat-value"><?php echo esc_html( $streak . ' ' . $day_label ); ?></span>
			</div>
			<div class="wpstreak-panel__stat">
				<span class="wpstreak-panel__stat-label">Last published</span>
				<span class="wpstreak-panel__stat-value"><?php echo esc_html( $last_post_label ); ?></span>
			</div>
			<div class="wpstreak-panel__stat">
				<span class="wpstreak-panel__stat-label">Next milestone</span>
				<span class="wpstreak-panel__stat-value"><?php echo esc_html( $next_milestone . ' days' ); ?></span>
			</div>
		</div>
	</div>
	<div class="wpstreak-panel__meta">
		<div>
			<div class="wpstreak-panel__score">
				<span class="wpstreak-panel__score-value"><?php echo esc_html( (string) $streak ); ?></span>
				<span class="wpstreak-panel__score-unit">days</span>
			</div>
			<div class="wpstreak-panel__status">
				<span class="wpstreak-panel__status-dot"></span>
				<span><?php echo esc_html( $is_active_today ? 'Published today' : 'Needs a post today' ); ?></span>
			</div>
		</div>
		<div>
			<div class="wpstreak-panel__progress-copy">
				<span>Milestone progress</span>
				<span><?php echo esc_html( $progress . '%' ); ?></span>
			</div>
			<div class="wpstreak-panel__progress-track">
				<div class="wpstreak-panel__progress-bar" style="width:<?php echo esc_attr( (string) $progress ); ?>%;"></div>
			</div>
		</div>
	</div>
</div>
