<?

 //Fügen Sie hier Ihren Skriptquellcode ein


Include(IPS_GetKernelDir()."scripts\AllgemeineDefinitionen.inc.php");

$baseId  = IPSUtil_ObjectIDByPath('Program.BKSLibrary.data.Gartensteuerung.Nachrichtenverlauf-Garten');
//echo "BaseID :".$baseId."\n";

// Init

	$input = CreateVariableByName($baseId, "Nachricht_Garten_Input", 3);
	$zeile1 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile01", 3);
	$zeile2 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile02", 3);
	$zeile3 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile03", 3);
	$zeile4 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile04", 3);
	$zeile5 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile05", 3);
	$zeile6 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile06", 3);
	$zeile7 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile07", 3);
	$zeile8 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile08", 3);
	$zeile9 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile09", 3);
	$zeile10 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile10", 3);
	$zeile11 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile11", 3);
	$zeile12 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile12", 3);
	$zeile13 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile13", 3);
	$zeile14 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile14", 3);
	$zeile15 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile15", 3);
	$zeile16 = CreateVariableByName($baseId, "Nachricht_Garten_Zeile16", 3);

if ($_IPS['SENDER']=="Execute")
	{
	echo GetValue($zeile1)."\n";
	echo GetValue($zeile2)."\n";
	echo GetValue($zeile3)."\n";
	echo GetValue($zeile4)."\n";
	echo GetValue($zeile5)."\n";
	echo GetValue($zeile6)."\n";
	echo GetValue($zeile7)."\n";
	echo GetValue($zeile8)."\n";
	echo GetValue($zeile9)."\n";
	echo GetValue($zeile10)."\n";
	echo GetValue($zeile11)."\n";
	echo GetValue($zeile12)."\n";
	echo GetValue($zeile13)."\n";
	echo GetValue($zeile14)."\n";
	echo GetValue($zeile15)."\n";
	echo GetValue($zeile16)."\n";

	}
else
	{

	SetValue($zeile16,GetValue($zeile15));
	SetValue($zeile15,GetValue($zeile14));
	SetValue($zeile14,GetValue($zeile13));
	SetValue($zeile13,GetValue($zeile12));
	SetValue($zeile12,GetValue($zeile11));
	SetValue($zeile11,GetValue($zeile10));
	SetValue($zeile10,GetValue($zeile9));
	SetValue($zeile9,GetValue($zeile8));
	SetValue($zeile8,GetValue($zeile7));
	SetValue($zeile7,GetValue($zeile6));
	SetValue($zeile6,GetValue($zeile5));
	SetValue($zeile5,GetValue($zeile4));
	SetValue($zeile4,GetValue($zeile3));
	SetValue($zeile3,GetValue($zeile2));
	SetValue($zeile2,GetValue($zeile1));
	SetValue($zeile1,GetValue($input));
	}


?>
