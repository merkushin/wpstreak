<?php
/**
 * Writing streak panel shown above the Posts list.
 *
 * @var string      $accent_class
 * @var string      $status_key           One of 'start', 'on_fire', 'alive'.
 * @var int         $streak
 * @var string      $streak_label         $streak formatted for the locale.
 * @var string|null $last_post_label      Day of the last published post, formatted for the site; null if none.
 * @var int         $next_milestone
 * @var string      $next_milestone_label $next_milestone formatted for the locale.
 * @var bool        $is_active_today
 * @var int         $progress             Milestone progress, 0-100.
 * @var string      $progress_label       $progress formatted for the locale.
 */

defined( 'ABSPATH' ) || exit;

$statuses = [
	'start' => __( 'Start your next streak', 'wpstreak' ),
	'on_fire' => __( 'You are on fire today', 'wpstreak' ),
	'alive' => __( 'You are still alive, publish today to keep it going', 'wpstreak' ),
];
?>
<div class="wpstreak-panel notice <?php echo esc_attr( $accent_class ); ?>">
	<div class="wpstreak-panel__lead">
		<div class="wpstreak-panel__eyebrow"><?php esc_html_e( 'Writing momentum', 'wpstreak' ); ?></div>
		<h2 class="wpstreak-panel__title"><?php esc_html_e( 'Protect the streak. Build the habit.', 'wpstreak' ); ?></h2>
		<p class="wpstreak-panel__description"><?php echo esc_html( $statuses[ $status_key ] ); ?></p>
		<div class="wpstreak-panel__stats">
			<div class="wpstreak-panel__stat">
				<span class="wpstreak-panel__stat-label"><?php esc_html_e( 'Current run', 'wpstreak' ); ?></span>
				<span class="wpstreak-panel__stat-value">
					<?php
					/* translators: %s: Number of days. */
					echo esc_html( sprintf( _n( '%s day', '%s days', $streak, 'wpstreak' ), $streak_label ) );
					?>
				</span>
			</div>
			<div class="wpstreak-panel__stat">
				<span class="wpstreak-panel__stat-label"><?php esc_html_e( 'Last published', 'wpstreak' ); ?></span>
				<span class="wpstreak-panel__stat-value"><?php echo esc_html( $last_post_label ?? __( 'No published posts yet', 'wpstreak' ) ); ?></span>
			</div>
			<div class="wpstreak-panel__stat">
				<span class="wpstreak-panel__stat-label"><?php esc_html_e( 'Next milestone', 'wpstreak' ); ?></span>
				<span class="wpstreak-panel__stat-value">
					<?php
					/* translators: %s: Number of days. */
					echo esc_html( sprintf( _n( '%s day', '%s days', $next_milestone, 'wpstreak' ), $next_milestone_label ) );
					?>
				</span>
			</div>
		</div>
	</div>
	<div class="wpstreak-panel__meta">
		<div>
			<div class="wpstreak-panel__score">
				<span class="wpstreak-panel__score-value"><?php echo esc_html( $streak_label ); ?></span>
				<span class="wpstreak-panel__score-unit">
					<?php
					/* translators: Unit shown after the large streak number, e.g. "12 days". */
					echo esc_html( _nx( 'day', 'days', $streak, 'streak counter unit', 'wpstreak' ) );
					?>
				</span>
			</div>
			<div class="wpstreak-panel__status">
				<span class="wpstreak-panel__status-dot"></span>
				<span><?php echo esc_html( $is_active_today ? __( 'Published today', 'wpstreak' ) : __( 'Needs a post today', 'wpstreak' ) ); ?></span>
			</div>
		</div>
		<div>
			<div class="wpstreak-panel__progress-copy">
				<span><?php esc_html_e( 'Milestone progress', 'wpstreak' ); ?></span>
				<span>
					<?php
					/* translators: %s: Progress towards the next milestone, a number from 0 to 100. */
					echo esc_html( sprintf( __( '%s%%', 'wpstreak' ), $progress_label ) );
					?>
				</span>
			</div>
			<div class="wpstreak-panel__progress-track">
				<div class="wpstreak-panel__progress-bar" style="width:<?php echo esc_attr( (string) $progress ); ?>%;"></div>
			</div>
		</div>
	</div>
</div>
