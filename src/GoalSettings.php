<?php declare( strict_types=1 );

namespace Merkushin\Inkmeter;

use Merkushin\Wpal\Service\Options;
use Merkushin\Wpal\ServiceFactory;

defined( 'ABSPATH' ) || exit;

/**
 * The site's writing goal, and the first day of its week.
 */
class GoalSettings {
	public const OPTION = 'inkmeter_goal';

	/**
	 * @var Options
	 */
	private $options;

	public function __construct() {
		$this->options = ServiceFactory::create_options();
	}

	/**
	 * The daily goal until another is chosen.
	 */
	public function goal(): Goal {
		return Goal::from_array( $this->options->get_option( self::OPTION, null ) );
	}

	public function save( Goal $goal ): void {
		// Autoloaded: the panel reads it on every visit to the Posts screen.
		$this->options->update_option( self::OPTION, $goal->to_array(), true );
	}

	/**
	 * WordPress' Settings → General → Week Starts On: 0 (Sunday) to 6.
	 */
	public function week_start(): int {
		$value = $this->options->get_option( 'start_of_week', 1 );

		return is_numeric( $value ) ? ( (int) $value % 7 + 7 ) % 7 : 1;
	}
}
