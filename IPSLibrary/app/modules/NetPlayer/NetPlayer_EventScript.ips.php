<?
	/**@addtogroup netplayer
	 * @{
	 *
	 * @file          NetPlayer_EventScript.ips.php
	 * @author        Andreas Brauneis
	 * @version
	 * Version 2.50.1, 31.01.2012<br/>
	 *
	 * Event Script, wird bei �nderung der aktuellen Titeldatei des MediaPlayers ausgel�st.
	 */

	include_once "NetPlayer.inc.php";


	if ($_IPS['SENDER'] == 'Variable') {
	   $value    = $_IPS['VALUE'];

		NetPlayer_RefreshTrackListValue();
	}


  /** @}*/
?>