<?php declare( strict_types=1 );

// Minimal stand-ins for the WordPress template functions used by the views.
// Services are mocked through wpal's ServiceFactory; only global template tags live here.

function __( string $text, string $domain = 'default' ): string {
	return $text;
}

function _n( string $single, string $plural, int $number, string $domain = 'default' ): string {
	return 1 === $number ? $single : $plural;
}

function _nx( string $single, string $plural, int $number, string $context, string $domain = 'default' ): string {
	return 1 === $number ? $single : $plural;
}

function esc_html( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES );
}

function esc_attr( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES );
}

function esc_html_e( string $text, string $domain = 'default' ): void {
	echo esc_html( $text );
}

function checked( $checked, $current = true, bool $display = true ): string {
	$result = (string) $checked === (string) $current ? ' checked=\'checked\'' : '';
	if ( $display ) {
		echo $result; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed attribute string.
	}
	return $result;
}
