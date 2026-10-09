// Screen Options checkbox for the streak panel. WordPress' utils script provides
// setUserSetting(), which saves the choice for the current user.
const toggle = document.getElementById( 'streakfire-panel-toggle' );
const panel = document.getElementById( 'streakfire-panel' );

if ( toggle && panel ) {
	toggle.addEventListener( 'change', () => {
		panel.hidden = ! toggle.checked;

		if ( typeof window.setUserSetting === 'function' ) {
			window.setUserSetting( 'streakfire_panel', toggle.checked ? 'on' : 'off' );
		}
	} );
}
