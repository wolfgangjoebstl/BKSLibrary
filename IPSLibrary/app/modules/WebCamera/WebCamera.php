<?

/*  Weblinks Control, wenn es Buttons gibt, diese Routine als Action Script aufrufen */

if ($_IPS['SENDER']=="WebFront")
	{
	/* vom Webfront aus gestartet */

	SetValue($_IPS['VARIABLE'],$_IPS['VALUE']);
	
	}


?>