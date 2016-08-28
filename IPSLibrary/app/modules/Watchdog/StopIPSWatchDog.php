<?

 // Parent-ID der Kategorie ermitteln
$parentID = IPS_GetObject($IPS_SELF);
$parentID = $parentID['ParentID'];

// ID der Skripte ermitteln
$IWDSendMessageScID = IPS_GetScriptIDByName("IWDSendMessage", $parentID);

 IPS_RunScriptEx($IWDSendMessageScID, Array('state' =>  'stop'));

?>
