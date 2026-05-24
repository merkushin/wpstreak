<div class="wpstreak-panel notice <?= \esc_attr( $accent_class ) ?>">
  <div class="wpstreak-panel__lead">
    <div class="wpstreak-panel__eyebrow">Writing momentum</div>
    <h2 class="wpstreak-panel__title">Protect the streak. Build the habit.</h2>
    <p class="wpstreak-panel__description"><?= \esc_html( $status ) ?></p>
    <div class="wpstreak-panel__stats">
      <div class="wpstreak-panel__stat">
        <span class="wpstreak-panel__stat-label">Current run</span>
        <span class="wpstreak-panel__stat-value"><?= \esc_html( (string) $streak ) . ' ' . \esc_html( $day_label ) ?></span>
      </div>
      <div class="wpstreak-panel__stat">
        <span class="wpstreak-panel__stat-label">Last published</span>
        <span class="wpstreak-panel__stat-value"><?= \esc_html( $last_post_label ) ?></span>
      </div>
      <div class="wpstreak-panel__stat">
        <span class="wpstreak-panel__stat-label">Next milestone</span>
        <span class="wpstreak-panel__stat-value"><?= \esc_html( (string) $next_milestone ) ?> days</span>
      </div>
    </div>
  </div>
  <div class="wpstreak-panel__meta">
    <div>
      <div class="wpstreak-panel__score">
        <span class="wpstreak-panel__score-value"><?= \esc_html( (string) $streak ) ?></span>
        <span class="wpstreak-panel__score-unit">days</span>
      </div>
      <div class="wpstreak-panel__status">
        <span class="wpstreak-panel__status-dot"></span>
        <span><?= \esc_html( $is_active_today ? 'Published today' : 'Needs a post today' ) ?></span>
      </div>
    </div>
    <div>
      <div class="wpstreak-panel__progress-copy">
        <span>Milestone progress</span>
        <span><?= \esc_html( (string) $progress ) ?>%</span>
      </div>
      <div class="wpstreak-panel__progress-track">
        <div class="wpstreak-panel__progress-bar" style="width:<?= \esc_attr( (string) $progress ) ?>%;"></div>
      </div>
    </div>
  </div>
</div>
