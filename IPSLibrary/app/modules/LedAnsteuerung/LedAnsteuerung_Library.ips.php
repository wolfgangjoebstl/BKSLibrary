<?

 //
 // LW12 LED Controller Library (LW12_Library) - V 1.0 BETA
 // ==============================================================
 //
 // Autor: Matthias Ziolek / 02.09.2014
 //
 // Dieses Skript beinhaltet alle Funktionen die für den Betrieb des LW12 LED Controllers nötig sind.
 //
 // Zur Nutzung des Controller muss dieses Skript an beliebiger Stelle im IPS angelegt werden und die IPS ObjektId ins "LW12_Create_Modul" eingetragen werden.
 // Die Library muss nur EINMAL im IPS vorhanden sein und kann von allen LW12 Controllern verwendet werden.
 //



SetValue($_IPS['VARIABLE'], $_IPS['VALUE']);


function LW12_WriteCommand($command, $responseLength) {

	   $IP = LW12_getIp();
	   $Port = LW12_getPort();

		error_reporting(E_ALL);

		/* Create a TCP/IP socket. */
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($socket === false) {
		    echo "socket_create() failed: reason: " .
		         socket_strerror(socket_last_error()) . "\n";
		}


		echo "Attempting to connect to '$IP' on port '$Port'...\n";
		$result = socket_connect($socket, $IP, $Port);
		if ($result === false) {
		    echo "socket_connect() failed.\nReason: ($result) " .
		          socket_strerror(socket_last_error($socket)) . "\n";
		}

		$in = hex2bin($command);
		$out = '';

		echo "Sending LW12 command...\n";
		socket_write($socket, $in, strlen($in));
		echo "OK.\n";

		if ($responseLength > 0 ) {
			echo "Reading response:\n\n";
			$out = socket_read($socket, $responseLength);
			}

		socket_close($socket);

		return bin2hex($out);
	}

function LW12_WriteCommand2($variableId,$command, $responseLength) {

	   $IP = LW12_getIp2($variableId);
	   $Port = LW12_getPort2($variableId);

		error_reporting(E_ALL);

		/* Create a TCP/IP socket. */
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($socket === false) {
		    echo "socket_create() failed: reason: " .
		         socket_strerror(socket_last_error()) . "\n";
		}


		//echo "Attempting to connect to '$IP' on port '$Port'...\n";
		$result = socket_connect($socket, $IP, $Port);
		if ($result === false) {
		    echo "socket_connect() failed.\nReason: ($result) " .
		          socket_strerror(socket_last_error($socket)) . "\n";
		}

		$in = hex2bin($command);
		$out = '';

		//echo "Sending LW12 command...\n";
		socket_write($socket, $in, strlen($in));
		//echo "OK.\n";

		if ($responseLength > 0 ) {
			echo "Reading response:\n\n";
			$out = socket_read($socket, $responseLength);
			}

		socket_close($socket);

		return bin2hex($out);
	}

function LW12_getIP() {
   $objektId = @IPS_GetObjectIDByName("!IP",  IPS_GetParent($_IPS['VARIABLE']));
		if ($objektId === false) $objektId = @IPS_GetObjectIDByName("!IP",  IPS_GetParent(IPS_GetParent($_IPS['VARIABLE'])));

	return GetValue($objektId);
	}

function LW12_getIP2($variableId) {
   $objektId = @IPS_GetObjectIDByName("!IP",  IPS_GetParent($variableId));
		if ($objektId === false) $objektId = @IPS_GetObjectIDByName("!IP",  IPS_GetParent(IPS_GetParent($variableId)));

	return GetValue($objektId);
	}
	
function LW12_getPort() {
	$objektId = @IPS_GetObjectIDByName("!TCP-Port",  IPS_GetParent($_IPS['VARIABLE']));
		if ($objektId === false) $objektId = @IPS_GetObjectIDByName("!TCP-Port",  IPS_GetParent(IPS_GetParent($_IPS['VARIABLE'])));

	return GetValue($objektId);
	}

function LW12_getPort2($variableId) {
	$objektId = @IPS_GetObjectIDByName("!TCP-Port",  IPS_GetParent($variableId));
		if ($objektId === false) $objektId = @IPS_GetObjectIDByName("!TCP-Port",  IPS_GetParent(IPS_GetParent($variableId)));

	return GetValue($objektId);
	}

function LW12_setPowerOn() {
	LW12_WriteCommand('cc2333',0);
	}

function LW12_setPowerOn2($variableId) {
	LW12_WriteCommand2($variableId,'cc2333',0);
	}
	
function LW12_setPowerOff() {
	LW12_WriteCommand('cc2433',0);
	}

function LW12_setPowerOff2($variableId) {
	LW12_WriteCommand2($variableId,'cc2433',0);
	}


function LW12_getStatus() {
	return LW12_WriteCommand('ef0177',11);
	}

function LW12_refreshIPS() {

	$status = LW12_getStatus();

	//prüfen aus welcher Ebene die Funktion gestartet wird
	if (@IPS_GetObjectIDByName("Controller-Programme", IPS_GetParent($_IPS['VARIABLE'])) === false) {
	   // Wir befinden uns in einer der Dummy-Sub-Instanzen

	   } else {
	   // wir befinden und in der obersten INstanz

	   // Farb-Event deaktivieren, Wert setzen und Event wieder aktivieren
	   $varId = IPS_GetObjectIDByName("Farbauswahl (Rot-Grün-Blau)",IPS_GetParent($_IPS['VARIABLE']));
	   $eventId = IPS_GetChildrenIDs($varId)[0];
	   IPS_SetEventActive($eventId, false);
		SetValue($varId, hexdec(substr($status,12,6)));
	   IPS_SetEventActive($eventId, true);

	   // Programm-Event deaktivieren, Wert setzen und Event wieder aktivieren
	   $varId = IPS_GetObjectIDByName("Programm",IPS_GetObjectIDByName("Controller-Programme",IPS_GetParent($_IPS['VARIABLE'])));
	   $eventId = IPS_GetChildrenIDs($varId)[0];
	   IPS_SetEventActive($eventId, false);
		SetValue($varId, (hexdec(substr($status,6,2))-hexdec(24)));
		print ("Programm: ".(hexdec(substr($status,6,2))-hexdec(24))."\n");
	   IPS_SetEventActive($eventId, true);


	   // Programm-Speed-Event deaktivieren, Wert setzen und Event wieder aktivieren
	   $varId = IPS_GetObjectIDByName("Geschwindigkeit",IPS_GetObjectIDByName("Controller-Programme",IPS_GetParent($_IPS['VARIABLE'])));
	   $eventId = IPS_GetChildrenIDs($varId)[0];
	   IPS_SetEventActive($eventId, false);
		SetValue($varId, (hexdec(substr($status,10,2))-32)*(-1));
		print ("Programm-Speed: ".(hexdec(substr($status,10,2))-32)*(-1)."\n");
	   IPS_SetEventActive($eventId, true);

		// Automatisches Programm-Event deaktivieren, Wert setzen und Event wieder aktivieren

		}

	}

function LW12_setHexRGB($rgb) {
	$command = '56' . $rgb . 'aa';
	echo "\n".$command."\n";

	LW12_WriteCommand($command,0);
	}

function LW12_setDecRGB2($variableID,$rgb) {
	$hexrgb = dechex($rgb);
	while (strlen($hexrgb) < 6) {
	   $hexrgb = '0'.$hexrgb;
	   }

	$command = '56' . $hexrgb . 'aa';
	//echo "\n".$command."\n";

	LW12_WriteCommand2($variableID,$command,0);
	}

function LW12_setDecRGB($rgb) {
	$hexrgb = dechex($rgb);
	while (strlen($hexrgb) < 6) {
	   $hexrgb = '0'.$hexrgb;
	   }

	$command = '56' . $hexrgb . 'aa';
	echo "\n".$command."\n";

	LW12_WriteCommand($command,0);
	}

function LW12_PowerToggle2($variableID,$valueID) {
	   Switch ($valueID) {
		case true:
	   	LW12_setPowerOn2($variableID);
		break;
		default:
		   LW12_setPowerOff2($variableID);
		}
	}

function LW12_PowerToggle() {
	   Switch ($valueID) {
		case true:
	   	LW12_setPowerOn();
		break;
		default:
		   LW12_setPowerOff();
		}
	}

function LW12_ModeToggle() {
	   Switch ($_IPS['VALUE']) {
		case 0:
         IPS_SetHidden(IPS_GetObjectIDByName("Farbauswahl (Rot-Grün-Blau)",IPS_GetParent($_IPS['VARIABLE'])),false);
         IPS_SetHidden(IPS_GetObjectIDByName("Controller-Programme",IPS_GetParent($_IPS['VARIABLE'])),true);
         IPS_SetHidden(IPS_GetObjectIDByName("IPS-Programme",IPS_GetParent($_IPS['VARIABLE'])),true);
         if(GetValue(IPS_GetObjectIDByName("Automatisches Programm",IPS_GetObjectIDByName("Controller-Programme",IPS_GetParent($_IPS['VARIABLE'])))) == true) {
            LW12_setCtrlPrgOff();
            SetValue(IPS_GetObjectIDByName("Automatisches Programm",IPS_GetObjectIDByName("Controller-Programme",IPS_GetParent($_IPS['VARIABLE']))), false);
            LW12_setDecRGB(GetValue(IPS_GetObjectIDByName("Farbauswahl (Rot-Grün-Blau)",IPS_GetParent($_IPS['VARIABLE']))));
            }
		break;
		case 1:
         IPS_SetHidden(IPS_GetObjectIDByName("Farbauswahl (Rot-Grün-Blau)",IPS_GetParent($_IPS['VARIABLE'])),true);
         IPS_SetHidden(IPS_GetObjectIDByName("Controller-Programme",IPS_GetParent($_IPS['VARIABLE'])),false);
         IPS_SetHidden(IPS_GetObjectIDByName("IPS-Programme",IPS_GetParent($_IPS['VARIABLE'])),true);
		break;
		case 2:
         IPS_SetHidden(IPS_GetObjectIDByName("Farbauswahl (Rot-Grün-Blau)",IPS_GetParent($_IPS['VARIABLE'])),true);
         IPS_SetHidden(IPS_GetObjectIDByName("Controller-Programme",IPS_GetParent($_IPS['VARIABLE'])),true);
         IPS_SetHidden(IPS_GetObjectIDByName("IPS-Programme",IPS_GetParent($_IPS['VARIABLE'])),false);
		}
	}


function LW12_setCtrlPrg($prg) {
	// BB prg speed 44
	$prg = dechex(hexdec(24) + $prg);


   $speed = dechex(32-GetValue(IPS_GetObjectIDByName("Geschwindigkeit", IPS_GetParent($_IPS['VARIABLE']))));
   if (strlen($speed) < 2) $speed = "0".$speed;

	$command = 'BB'.$prg.$speed.'44';

	echo $command;
	LW12_WriteCommand($command,0);

	}

function LW12_setCtrlPrgSpeed($speed) {
	// BB prg spd 44
	$speed = dechex(32-$speed);
	if (strlen($speed) < 2) $speed = "0".$speed;

	$prg = dechex(GetValue(IPS_GetObjectIDByName("Programm", IPS_GetParent($_IPS['VARIABLE'])))+hexdec(24));

	$command = 'BB'.$prg.$speed.'44';

	LW12_WriteCommand($command,0);

	}



function LW12_setCtrlPrgOn() {
	// CC 21 33
	$command = 'CC2133';

	LW12_WriteCommand($command,0);

	}

function LW12_setCtrlPrgOff() {
	// CC 20 33
	$command = 'CC2033';

	LW12_WriteCommand($command,0);

	}

function LW12_CtrlPrgToggle() {
	   Switch ($_IPS['VALUE']) {
		case true:
	   	LW12_setCtrlPrgOn();
		break;
		default:
		   LW12_setCtrlPrgOff();
		}
	}

?>
