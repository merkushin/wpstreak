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
?>
<div class="streakfire-panel notice <?php echo esc_attr( $accent_class ); ?>">
	<div class="streakfire-panel__lead">
		<div class="streakfire-panel__eyebrow"><?php esc_html_e( 'Writing momentum', 'streakfire' ); ?></div>
		<h2 class="streakfire-panel__title"><?php esc_html_e( 'Protect the streak. Build the habit.', 'streakfire' ); ?></h2>
		<p class="streakfire-panel__description">
			<?php
			if ( 'start' === $status_key ) {
				esc_html_e( 'Start your next streak', 'streakfire' );
			} elseif ( 'on_fire' === $status_key ) {
				esc_html_e( 'You are on fire today', 'streakfire' );
			} else {
				esc_html_e( 'You are still alive, publish today to keep it going', 'streakfire' );
			}
			?>
		</p>
		<div class="streakfire-panel__stats">
			<div class="streakfire-panel__stat">
				<span class="streakfire-panel__stat-label"><?php esc_html_e( 'Current run', 'streakfire' ); ?></span>
				<span class="streakfire-panel__stat-value">
					<?php
					/* translators: %s: Number of days. */
					echo esc_html( sprintf( _n( '%s day', '%s days', $streak, 'streakfire' ), $streak_label ) );
					?>
				</span>
			</div>
			<div class="streakfire-panel__stat">
				<span class="streakfire-panel__stat-label"><?php esc_html_e( 'Last published', 'streakfire' ); ?></span>
				<span class="streakfire-panel__stat-value"><?php echo esc_html( $last_post_label ?? __( 'No published posts yet', 'streakfire' ) ); ?></span>
			</div>
			<div class="streakfire-panel__stat">
				<span class="streakfire-panel__stat-label"><?php esc_html_e( 'Next milestone', 'streakfire' ); ?></span>
				<span class="streakfire-panel__stat-value">
					<?php
					/* translators: %s: Number of days. */
					echo esc_html( sprintf( _n( '%s day', '%s days', $next_milestone, 'streakfire' ), $next_milestone_label ) );
					?>
				</span>
			</div>
		</div>
	</div>
	<div class="streakfire-panel__meta">
		<div>
			<div class="streakfire-panel__score">
				<span class="streakfire-panel__score-value"><?php echo esc_html( $streak_label ); ?></span>
				<span class="streakfire-panel__score-unit">
					<?php
					/* translators: Unit shown after the large streak number, e.g. "12 days". */
					echo esc_html( _nx( 'day', 'days', $streak, 'streak counter unit', 'streakfire' ) );
					?>
				</span>
			</div>
			<div class="streakfire-panel__status">
				<span class="streakfire-panel__status-dot"></span>
				<span><?php echo esc_html( $is_active_today ? __( 'Published today', 'streakfire' ) : __( 'Needs a post today', 'streakfire' ) ); ?></span>
			</div>
		</div>
		<div>
			<div class="streakfire-panel__progress-copy">
				<span><?php esc_html_e( 'Milestone progress', 'streakfire' ); ?></span>
				<span>
					<?php
					/* translators: %s: Progress towards the next milestone, a number from 0 to 100. */
					echo esc_html( sprintf( __( '%s%%', 'streakfire' ), $progress_label ) );
					?>
				</span>
			</div>
			<div class="streakfire-panel__progress-track">
				<div class="streakfire-panel__progress-bar" style="width:<?php echo esc_attr( (string) $progress ); ?>%;"></div>
			</div>
		</div>
	</div>
</div>
