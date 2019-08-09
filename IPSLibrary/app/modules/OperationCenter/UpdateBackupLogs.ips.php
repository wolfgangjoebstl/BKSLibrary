<?

Include(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

IPSUtils_Include ("IPSModuleManagerGUI.inc.php", "IPSLibrary::app::modules::IPSModuleManagerGUI");
IPSUtils_Include ("IPSModuleManager.class.php","IPSLibrary::install::IPSModuleManager");

IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");
IPSUtils_Include ("SNMP_Library.class.php","IPSLibrary::app::modules::OperationCenter");
IPSUtils_Include ('IPSComponentLogger.class.php', 'IPSLibrary::app::core::IPSComponent::IPSComponentLogger');
IPSUtils_Include ('IPSComponentLogger_Configuration.inc.php', 'IPSLibrary::config::core::IPSComponent');

ini_set('memory_limit', '128M');

$subnet='10.255.255.255';
$BackupCenter=new BackupIpsymcon($subnet);

echo "Backup.csv updaten.\n";
$result=$BackupCenter->getBackupDirectoryStatus("update");
echo "SummaryofBackup.csv updaten.\n";  
$BackupCenter->updateSummaryofBackupFile(); 


?>