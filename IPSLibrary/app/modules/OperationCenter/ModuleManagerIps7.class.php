<?

	/**@defgroup ipsmodulemanager IPSModuleManager
	 * @{
	 *
	 * Der IPSModuleManager bildet das Herzstück des IPSLibrary Installers. Er beinhaltet diverse Konfigurations Möglichkeiten, die
	 * man in der Datei IPSModuleManager.ini verändern kann (Ablagerort: IPSLibrary.install.InitializationFile).
	 *
	 * @file          IPSModuleManager.class.php
	 * @author        Andreas Brauneis fork Wolfgang Jöbstl
	 *
	 */

    IPSUtils_Include ('AllgemeineDefinitionen.inc.php', 'IPSLibrary');
	IPSUtils_Include ("IPSInstaller.inc.php",                  "IPSLibrary::install::IPSInstaller");
	IPSUtils_Include ("IPSFileVersionHandler.class.php",   	  "IPSLibrary::install::IPSModuleManager::IPSVersionHandler");
	IPSUtils_Include ("IPSScriptHandler.class.php",            "IPSLibrary::install::IPSModuleManager::IPSScriptHandler");
	IPSUtils_Include ("IPSFileHandler.class.php",              "IPSLibrary::install::IPSModuleManager::IPSFileHandler");
	IPSUtils_Include ("IPSBackupHandler.class.php",            "IPSLibrary::install::IPSModuleManager::IPSBackupHandler");
	IPSUtils_Include ("IPSLogHandler.class.php",               "IPSLibrary::install::IPSModuleManager::IPSLogHandler");
	IPSUtils_Include ("IPSIniConfigHandler.class.php",         "IPSLibrary::app::core::IPSConfigHandler");

	/**
	 * @class IPSModuleManager
	 *
	 * Klasse zur Installation neuer Module und zum Update bestehender Module
	 *
	 * @author Andreas Brauneis
	 * @version
	 *  Version 2.5.1, 05.01.2012<br/>
	 *  Version 2.5.3, 29.10.2012  Adapted Version Handling<br/>
	 */
	class ModuleManagerIPS7{

 		const DOWNLOADLISTFILE_PATH            = "IPSLibrary/install/DownloadListFiles/";
		const DOWNLOADLISTFILE_SUFFIX          = '_FileList.ini';
 		const INSTALLATIONSCRIPT_PATH          = "IPSLibrary/install/InstallationScripts/";
		const INSTALLATIONSCRIPT_SUFFIX        = '_Installation.ips.php';
		const DEINSTALLATIONSCRIPT_SUFFIX      = '_Deinstallation.ips.php';
		const INITIALIZATIONFILE_PATH          = "IPSLibrary/install/InitializationFiles/";
		const INITIALIZATIONDEFAULTFILE_PATH   = "IPSLibrary/install/InitializationFiles/Default/";
		const INITIALIZATIONFILE_SUFFIX        = '.ini';

		private $moduleName="";
		private $sourceRepository="";
		private $versionHandler;
		private $scriptHandler;
		private $backupHandler;
		private $fileHandler;
		private $logHandler;
		private $fileConfigHandler;
		private $managerConfigHandler;
		private $moduleConfigHandler;

		/**
		 * @public
		 *
		 * Initialisierung des ModuleManagers
		 *
		 * @param string $moduleName Name des Modules
		 * @param string $sourceRepository Pfad/Url zum SourceRepository, das zum Download der Module verwendet werden soll
		 * @param string $logDirectory Verzeichnis das zum Loggen verwendet werden soll 
		 * @param string $silentMode bei TRUE werden Meldungen nicht mit ECHO gelogged
		 */
		public function __construct($moduleName='', $sourceRepository='', $logDirectory='', $silentMode=false) {
			global $_IPS;
			$_IPS['ABORT_ON_ERROR'] = true;
			$_IPS['MODULEMANAGER']  = $this;

			if ($moduleName=='') {
				$moduleName = 'IPSModuleManager';
			}
			$this->moduleName           = $moduleName;

			// Create ConfigHandler for ModuleManager INI File
			$this->managerConfigHandler = new IPSIniConfigHandler($this->GetModuleInitializationFile('IPSModuleManager'));

			$this->sourceRepository = $sourceRepository;
			if ($this->sourceRepository=='') {
				$this->sourceRepository = $this->managerConfigHandler->GetValue(IPSConfigHandler::SOURCEREPOSITORY, '');
			}
			$this->sourceRepository = IPSFileHandler::AddTrailingPathDelimiter($this->sourceRepository);

			// Create Log Handler
			if ($logDirectory=='') {
				if (function_exists('IPS_GetLogDir')) {
					$logDirectory = $this->managerConfigHandler->GetValueDef(IPSConfigHandler::LOGDIRECTORY, '', IPS_GetLogDir());
				} else {
					$logDirectory = $this->managerConfigHandler->GetValueDef(IPSConfigHandler::LOGDIRECTORY, '', IPS_GetKernelDir().'logs/');
				}
			}
			$this->logHandler = new IPSLogHandler(get_class($this), $logDirectory, $moduleName, true, $silentMode);
		   
			// Create Version Handler
			$this->versionHandler   = new  FileVersionHandlerIPS7($moduleName);

			// Create Script Handler
			$libraryBasePath           = 'Program';
			$this->scriptHandler       = new IPSScriptHandler($libraryBasePath);

			// Create File Handler
			$this->fileHandler         = new IPSFileHandler();

			// Create Backup Handler
			$backupDirectory           = $this->managerConfigHandler->GetValueDef('BackupLoadDirectory', '', IPS_GetKernelDir().'backup/IPSLibrary_Load/');
			$this->backupHandler       = new IPSBackupHandler($backupDirectory);

			// ConfigHandler for Module Filelist File
			$localDownloadIniFile      = $this->GetModuleDownloadListFile(IPS_GetKernelDir().'scripts/');
			if (!file_exists($localDownloadIniFile)) {
				$repositoryDownloadIniFile = $this->GetModuleDownloadListFile($this->sourceRepository);
				$this->logHandler->Log('Module Download Ini File doesnt exists -> Load Ini File "'.$repositoryDownloadIniFile.'"');
				$this->fileHandler->LoadFiles(array($repositoryDownloadIniFile), array($localDownloadIniFile));
			}
			$this->fileConfigHandler = new IPSIniConfigHandler($this->GetModuleDownloadListFile(IPS_GetKernelDir().'scripts/'));

			// ConfigHandler for Module INI File
			$moduleIniFile = $this->GetModuleInitializationFile($moduleName);
			if (!file_exists($moduleIniFile)) {
				$moduleLocalDefaultIniFile      = $this->GetModuleDefaultInitializationFile($moduleName, IPS_GetKernelDir().'scripts/');
				$moduleRepositoryDefaultIniFile = $this->GetModuleDefaultInitializationFile($moduleName, $this->sourceRepository);
				$this->logHandler->Log('Module Ini File doesnt exists -> Load Default Ini File "'.$moduleLocalDefaultIniFile.'"');
				$this->fileHandler->LoadFiles(array($moduleRepositoryDefaultIniFile), array($moduleLocalDefaultIniFile));
				$this->fileHandler->CreateScriptsFromDefault(array($moduleLocalDefaultIniFile));
			}
			$this->moduleConfigHandler  = new IPSIniConfigHandler($moduleIniFile);

			// Increase PHP Timeout for current Session
			$timeLimit = $this->managerConfigHandler->GetValueIntDef('TimeLimit', '', '300'); /*5 Minuten*/
			//set_time_limit($timeLimit);
			global $_IPS;
			$_IPS['PROXY'] = $this->managerConfigHandler->GetValueDef('Proxy', '', ''); /*Proxy Settings*/
		}

        public function getSourceRepository()
            {
            return($this->sourceRepository);
            }

		/**
       * @public
		 *
		 * Liefert aktuellen Versions Handler
		 *
	    * @return IPSVersionHandler aktuellen Versions Handler
		 */
		public function VersionHandler() {
		   return $this->versionHandler;
		}

		/**
       * @public
		 *
		 * Liefert aktuellen ConfigHandler für ModuleManager
		 *
	    * @return IPSConfigHandler aktuellen Config Handler
		 */
		public function ManagerConfigHandler() {
		   return $this->managerConfigHandler;
		}

		/**
       * @public
		 *
		 * Liefert aktuellen ConfigHandler für Module
		 *
	    * @return IPSConfigHandler aktuellen Config Handler
		 */
		public function ModuleConfigHandler() {
		   return $this->moduleConfigHandler;
		}

		/**
       * @public
		 *
		 * Liefert aktuellen LogHandler des ModuleManagers
		 *
	    * @return IPSLogHandler aktueller Log Handler
		 */
		public function LogHandler() {
		   return $this->logHandler;
		}

		/**
		 * @public
		 *
		 * Liefert den Wert eines übergebenen Parameters, es wird zuerst im ConfigHandler des aktuellen
		 * Modules gesucht, wird er dort nicht gefunden erfolgt die Suche im ModuleManager Config Handler.
		 *
		 * @param string $key Name des Parameters
		 * @param string $section Name der Parameter Gruppe, kann auch leer sein
		 * @return string liefert den Wert des übergebenen Parameters
		 * @throws ConfigurationException wenn der betroffene Parameter nicht gefunden wurde
		 */
		public function GetConfigValue($key, $section=null) {
			if ($this->moduleConfigHandler->ExistsValue($key, $section)) {
				$result = $this->moduleConfigHandler->GetValue($key, $section);
			} else {
				$result = $this->managerConfigHandler->GetValue($key, $section);
			}
			if ($result == 'true') {
				return true;
			} elseif ($result == 'false') {
				return false;
			} else {
				return $result;
			}
		}

		/**
		 * @public
		 *
		 * Liefert den integer Wert eines übergebenen Parameters, es wird zuerst im ConfigHandler des aktuellen
		 * Modules gesucht, wird er dort nicht gefunden erfolgt die Suche im ModuleManager Config Handler.
		 *
		 * @param string $key Name des Parameters
		 * @param string $section Name der Parameter Gruppe, kann auch leer sein
		 * @return integer liefert den Wert des übergebenen Parameters
		 * @throws ConfigurationException wenn der betroffene Parameter nicht gefunden wurde
		 */
		public function GetConfigValueInt ($key, $section=null) {
		   return (int)$this->GetConfigValue($key, $section);
		}

		/**
		 * @public
		 *
		 * Liefert den boolean Wert eines übergebenen Parameters, es wird zuerst im ConfigHandler des aktuellen
		 * Modules gesucht, wird er dort nicht gefunden erfolgt die Suche im ModuleManager Config Handler.
		 *
		 * @param string $key Name des Parameters
		 * @param string $section Name der Parameter Gruppe, kann auch leer sein
		 * @return boolean liefert den Wert des übergebenen Parameters
		 * @throws ConfigurationException wenn der betroffene Parameter nicht gefunden wurde
		 */
		public function GetConfigValueBool ($key, $section=null) {
			return (boolean)$this->GetConfigValue($key, $section);
		}

		/**
		 * @public
		 *
		 * Liefert den float Wert eines übergebenen Parameters, es wird zuerst im ConfigHandler des aktuellen
		 * Modules gesucht, wird er dort nicht gefunden erfolgt die Suche im ModuleManager Config Handler.
		 *
		 * @param string $key Name des Parameters
		 * @param string $section Name der Parameter Gruppe, kann auch leer sein
		 * @return float liefert den Wert des übergebenen Parameters
		 * @throws ConfigurationException wenn der betroffene Parameter nicht gefunden wurde
		 */
		public function GetConfigValueFloat ($key, $section=null) {
		   return (float)$this->GetConfigValue($key, $section);
		}

		/**
		 * @public
		 *
		 * Liefert den Wert eines übergebenen Parameters, es wird zuerst im ConfigHandler des aktuellen
		 * Modules gesucht, wird er dort nicht gefunden erfolgt die Suche im ModuleManager Config Handler.
		 * Ist er dort auch nicht definiert, wird der Default Wert retouniert.
		 *
		 * @param string $key Name des Parameters
		 * @param string $section Name der Parameter Gruppe, kann auch leer sein
		 * @param string $defaultValue Default Wert wenn Parameter nicht gefunden wurde
		 * @return string liefert den Wert des übergebenen Parameters
		 */
		public function GetConfigValueDef($key, $section=null, $defaultValue="") {
			if ($this->moduleConfigHandler->ExistsValue($key, $section)) {
				return $this->moduleConfigHandler->GetValue($key, $section);
			} elseif ($this->managerConfigHandler->ExistsValue($key, $section)) {
				return $this->managerConfigHandler->GetValue($key, $section);
			} else {
				return $defaultValue;
			}
		}

		/**
		 * @public
		 *
		 * Liefert den integer Wert eines übergebenen Parameters, es wird zuerst im ConfigHandler des aktuellen
		 * Modules gesucht, wird er dort nicht gefunden erfolgt die Suche im ModuleManager Config Handler.
		 * Ist er dort auch nicht definiert, wird der Default Wert retouniert.
		 *
		 * @param string $key Name des Parameters
		 * @param string $section Name der Parameter Gruppe, kann auch leer sein
		 * @param string $defaultValue Default Wert wenn Parameter nicht gefunden wurde
		 * @return integer liefert den Wert des übergebenen Parameters
		 */
		public function GetConfigValueIntDef($key, $section=null, $defaultValue="") {
			return (int)$this->GetConfigValueDef($key, $section, $defaultValue);
		}

		/**
		 * @public
		 *
		 * Liefert den boolean Wert eines übergebenen Parameters, es wird zuerst im ConfigHandler des aktuellen
		 * Modules gesucht, wird er dort nicht gefunden erfolgt die Suche im ModuleManager Config Handler.
		 * Ist er dort auch nicht definiert, wird der Default Wert retouniert.
		 *
		 * @param string $key Name des Parameters
		 * @param string $section Name der Parameter Gruppe, kann auch leer sein
		 * @param string $defaultValue Default Wert wenn Parameter nicht gefunden wurde
		 * @return boolean liefert den Wert des übergebenen Parameters
		 */
		public function GetConfigValueBoolDef ($key, $section=null, $defaultValue="") {
			return (boolean)$this->GetConfigValueDef($key, $section, $defaultValue);
		}

		/**
		 * @public
		 *
		 * Liefert den float Wert eines übergebenen Parameters, es wird zuerst im ConfigHandler des aktuellen
		 * Modules gesucht, wird er dort nicht gefunden erfolgt die Suche im ModuleManager Config Handler.
		 * Ist er dort auch nicht definiert, wird der Default Wert retouniert.
		 *
		 * @param string $key Name des Parameters
		 * @param string $section Name der Parameter Gruppe, kann auch leer sein
		 * @param string $defaultValue Default Wert wenn Parameter nicht gefunden wurde
		 * @return float liefert den Wert des übergebenen Parameters
		 */
		public function GetConfigValueFloatDef ($key, $section=null, $defaultValue="") {
		   return (float)$this->GetConfigValueDef($key, $section, $defaultValue);
		}

		/**
		 * @public
		 *
		 * Liefert die ensprechenden Pfad im IPSLibrary Objektbaum
		 *
		 * @param string $type Zweig im Objektbaum ('app','config' oder 'data')
		 * @return int Pfad der Kategorie
		 */
		public function GetModuleCategoryPath($type='app') {
		   if ($type<>'app' and $type<>'config' and $type<>'data') {
		      throw new Exception('Unknown Category Type '.$type);
		   }
			$namespace  = $this->fileConfigHandler->GetValue(IPSConfigHandler::MODULENAMESPACE);
			$namespace  = str_replace('::app::','::'.$type.'::',$namespace);
		   $path       = 'Program.'.str_replace('::','.',$namespace);

			return $path;
		}

		/**
		 * @public
		 *
		 * Liefert die ensprechende ID im IPSLibrary Objektbaum
		 *
		 * @param string $type Zweig im Objektbaum ('app','config' oder 'data')
		 * @param boolean $createNonExisting Anlegen des Baumes, falls nicht vorhanden
		 * @return int ID der Kategorie
		 */
		public function GetModuleCategoryID($type='app', $createNonExisting=true) {
		   $path       = $this->GetModuleCategoryPath($type);
			$categoryID = IPSUtil_ObjectIDByPath($path, true);
			
			if ($categoryID===false and $createNonExisting) {
			   $categoryID = CreateCategoryPath($path);
			}

			return $categoryID;
		}

		/**
		 * @public
		 *
		 * Liefert Namen des aktuellen LogFiles
		 *
		 * @return string Name des LogFiles
		 */
		public function GetLogFileName() {
		   return $this->logHandler->GetLogFileName();
		}

		/**
		 * @public
		 *
		 * Liefert ein Array aller installierten Module
		 *
		 * Aufbau:
		 * array('Module1' => Version,
		 *       'Module2' => Version,
		 *       ...
		 *       'ModuleX' => Version)
		 *
		 * @return string Array der Installierten Module
		 */
		public function GetInstalledModules() {
			$resultList = $this->versionHandler->GetInstalledModules();

			return $resultList;
		}

		/**
		 * @public
		 *
		 * Liefert die ID des Objectes, mit dem das Modul konfiguriert werden kann.
		 * Falls kein Objekt gefunden wird, dann liefert die Funktion FALSE zurück.
		 *
		 * @return integer ID des Objectes
		 */
		public function GetConfigurationObjectID() {
			$configList       = $this->GetScriptList('DefaultFiles', 'Config', IPS_GetKernelDir().'scripts/');
			if (count($configList)==0) {
			   return false;
			}
			$configDefaultFile = $configList[0];
			$configFile = IPSFileHandler::GetUserFilenameByDefaultFilename($configDefaultFile);
			$scriptPath = $this->scriptHandler->GetScriptPathByFileName($configFile);
			$scriptName = $this->scriptHandler->GetScriptNameByFileName($configFile);
			$scriptID   = IPSUtil_ObjectIDByPath($scriptPath.'.'.$scriptName,true);
			return $scriptID;
		}

		/**
		 * @public
		 *
		 * Liefert ein Array mit Informationen zu dem Module zurück
		 *
		 * @return string[] Infos zu Modul
		 */
		public function GetModuleInfos($moduleName='') {
			if ($moduleName=='') {
				$moduleName = $this->moduleName;
			}
			$infos = $this->versionHandler->GetModuleInfos($moduleName);
			if ($this->versionHandler->IsModuleInstalled($moduleName)) {
				$versionHandler = new IPSFileVersionHandler($moduleName);
				$infos['Installed']      = 'Yes';
				$infos['CurrentVersion'] = $versionHandler->GetScriptVersion();
				$infos['State']          = $versionHandler->GetModuleState();
				$infos['LastRepository'] = $versionHandler->GetModuleRepository();
			} else {
				$infos['Installed']      = 'No';
				$infos['CurrentVersion'] = '';
				$infos['State']          = '';
				$infos['LastRepository'] = '';
			}

			return $infos;
		}

		
		 /**
		 * @private
		 *
		 * @return string liefert Installation Filename des Modules
		 */
		private function GetModuleInstallationScript() {
			$path = IPS_GetKernelDir().'scripts/'.$this::INSTALLATIONSCRIPT_PATH;
			$file = $this->moduleName.$this::INSTALLATIONSCRIPT_SUFFIX;
			return $path.$file;
		}

		/**
		 * @private
		 *
		 * @return string liefert Installation Filename des Modules
		 */
		private function GetModuleDeinstallationScript() {
			$path = IPS_GetKernelDir().'scripts/'.$this::INSTALLATIONSCRIPT_PATH;
			$file = $this->moduleName.$this::DEINSTALLATIONSCRIPT_SUFFIX;
			return $path.$file;
		}

		/**
		 * @private
		 *
		 * @param string $baseDirectory Pfad/Url des Basis Directories
		 * @return string liefert DownloadList Filename des Modules
		 */
		private function GetModuleDownloadListFile($baseDirectory) {
			$path = $baseDirectory.$this::DOWNLOADLISTFILE_PATH;
			$file = $this->moduleName.$this::DOWNLOADLISTFILE_SUFFIX;
			return $path.$file;
		}

		/**
		 * @private
		 *
		 * @param string $module Name des Modules
		 * @return string liefert Initialization Filename des Modules
		 */
		private function GetModuleInitializationFile($moduleName) {
			$path = IPS_GetKernelDir().'scripts/'.$this::INITIALIZATIONFILE_PATH;
			$file = $moduleName.$this::INITIALIZATIONFILE_SUFFIX;
			return $path.$file;
		}

		/**
		 * @private
		 *
		 * @param string $module Name des Modules
		 * @param string $baseDirectory Pfad/Url des Basis Directories
		 * @return string liefert Default Initialization Filename des Modules
		 */
		private function GetModuleDefaultInitializationFile($moduleName, $baseDirectory) {
			$path = $baseDirectory.$this::INITIALIZATIONDEFAULTFILE_PATH;
			$file = $moduleName.$this::INITIALIZATIONFILE_SUFFIX;
			return $path.$file;
		}

		/**
		 * @private
		 *
		 * Liefert die ScriptListe für einen übergebenen FileType
		 *
		 * @param string $fileKey Type des Files (ScriptList, DefaultList, ExampleList, ...)
		 * @param string $fileTypeSection Filetype Section (app, config, webfront ...)
		 * @param string $baseDirectory Basis Verzeichnis für die Generierung der Filenamen
		 * @return array[] Liste mit Filenamen
		 */
		private function GetScriptList($fileKey, $fileTypeSection, $baseDirectory) {
			if ($fileKey=='DownloadFiles') {
				return array($this->GetModuleDownloadListFile($baseDirectory));
			}
		
			$resultList = array();
			$scriptList = $this->fileConfigHandler->GetValueDef($fileKey, $fileTypeSection, array());
			
			foreach ($scriptList as $idx => $script) {
				if ($script=='') {
					continue;
				}
				$script = str_replace('\\', '/', $script);
				
				if ($fileKey=='DefaultFiles') {
					$script   = 'Default/'.$script;
				} elseif ($fileKey=='ExampleFiles') {
					$script   = 'Examples/'.$script;
				} else {
				}

				switch ($fileTypeSection) {
					case 'App':
						$namespace = $this->fileConfigHandler->GetValue(IPSConfigHandler::MODULENAMESPACE);
						$fullScriptName   = $baseDirectory.'::'.$namespace.'::'.$script;
						break;
					case 'Config':
						$namespace = $this->fileConfigHandler->GetValue(IPSConfigHandler::MODULENAMESPACE);
						$namespace = str_replace('IPSLibrary::app', 'IPSLibrary::config', $namespace);
						$fullScriptName   = $baseDirectory.'::'.$namespace.'::'.$script;
						break;
					case 'WebFront':
						if ($baseDirectory==IPS_GetKernelDir().'scripts/') {
                            $ipsOps=new ipsOps();
                            if ($ipsOps->ipsVersion7check()) $fullScriptName   = IPS_GetKernelDir().'user/'.$this->moduleName.'/'.$script;				// jw, change 
							else $fullScriptName   = IPS_GetKernelDir().'webfront/user/'.$this->moduleName.'/'.$script;
                            echo $fullScriptName."\n";
						} else {
							$fullScriptName   = $baseDirectory.'/IPSLibrary/webfront/'.$this->moduleName.'/'.$script;
                            echo "$baseDirectory results into $fullScriptName \n";
						}
						break;
					case 'Install':
						if ($fileKey=='DefaultFiles' or $fileKey=='ExampleFiles') {
							$fullScriptName   = $baseDirectory.'/IPSLibrary/install/InitializationFiles/'.$script;
						} else {
							$fullScriptName   = $baseDirectory.'/IPSLibrary/install/InstallationScripts/'.$script;
						}
						break;
					default:
						die('Unknown fileTypeSection '.$fileTypeSection);
				}
				$fullScriptName   = str_replace('::', '/', $fullScriptName);
				$fullScriptName   = str_replace('//', '/', $fullScriptName);
				$fullScriptName   = str_replace('\\\\', '\\', $fullScriptName);
				$fullScriptName   = str_replace('\\192.168', '\\\\192.168', $fullScriptName);

				$resultList[] = $fullScriptName;
			}
			return $resultList;
		}

        private function GetScriptListWebfront($fileKey, $fileTypeSection, $baseDirectory) {
			if ($fileKey=='DownloadFiles') {
                echo "DownloadFiles behandlen.\n";
				return array($this->GetModuleDownloadListFile($baseDirectory));
			}
		
            echo $this->GetModuleDownloadListFile(IPS_GetKernelDir().'scripts/')."\n";  // ini File des Moduls

			$resultList = array();
			$scriptList = $this->fileConfigHandler->GetValueDef($fileKey, $fileTypeSection, array());           // filekey zB ScriptFiles  fileTypeSection zB Webfront
			//print_R($scriptList);

			foreach ($scriptList as $idx => $script) {
				if ($script=='') {
					continue;
				}
				$script = str_replace('\\', '/', $script);
				
				if ($fileKey=='DefaultFiles') {
					$script   = 'Default/'.$script;
				} elseif ($fileKey=='ExampleFiles') {
					$script   = 'Examples/'.$script;
				} else {
				}

				switch ($fileTypeSection) {
					case 'App':
						$namespace = $this->fileConfigHandler->GetValue(IPSConfigHandler::MODULENAMESPACE);
						$fullScriptName   = $baseDirectory.'::'.$namespace.'::'.$script;
						break;
					case 'Config':
						$namespace = $this->fileConfigHandler->GetValue(IPSConfigHandler::MODULENAMESPACE);
						$namespace = str_replace('IPSLibrary::app', 'IPSLibrary::config', $namespace);
						$fullScriptName   = $baseDirectory.'::'.$namespace.'::'.$script;
						break;
					case 'WebFront':
						if ($baseDirectory==IPS_GetKernelDir().'scripts/') {
                            $ipsOps=new ipsOps();
                            if ($ipsOps->ipsVersion7check()) $fullScriptName   = IPS_GetKernelDir().'user/'.$this->moduleName.'/'.$script;				// jw, change 
							else $fullScriptName   = IPS_GetKernelDir().'webfront/user/'.$this->moduleName.'/'.$script;
                            echo $fullScriptName."\n";
						} else {
							$fullScriptName   = $baseDirectory.'/IPSLibrary/webfront/'.$this->moduleName.'/'.$script;
						}
						break;
					case 'Install':
						if ($fileKey=='DefaultFiles' or $fileKey=='ExampleFiles') {
							$fullScriptName   = $baseDirectory.'/IPSLibrary/install/InitializationFiles/'.$script;
						} else {
							$fullScriptName   = $baseDirectory.'/IPSLibrary/install/InstallationScripts/'.$script;
						}
						break;
					default:
						die('Unknown fileTypeSection '.$fileTypeSection);
				}
				$fullScriptName   = str_replace('::', '/', $fullScriptName);
				$fullScriptName   = str_replace('//', '/', $fullScriptName);
				$fullScriptName   = str_replace('\\\\', '\\', $fullScriptName);
				$fullScriptName   = str_replace('\\192.168', '\\\\192.168', $fullScriptName);

				$resultList[] = $fullScriptName;
			}
			return $resultList;
		}
		/**
		 * @public
		 *
		 * Registriert eine List von Files in IP-Symcon anhand des Filenames
		 *
		 * @param string $fileKey Type des Files (ScriptList, DefaultList, ExampleList, ...)
		 * @param string $fileTypeSection Filetype Section (app, config, webfront ...)
		 * @param string $fileList Liste mit Filenamen
		 */
		private function RegisterModuleFiles($fileKey, $fileTypeSection, $fileList) {
			$registerDefaultFiles = $this->GetConfigValueBoolDef('RegisterDefaultFiles', '', '');
			$registerExampleFiles = $this->GetConfigValueBoolDef('RegisterExampleFiles', '', '');
			$registerInstallFiles = $this->GetConfigValueBoolDef('RegisterInstallFiles', '', '');

			if ($fileKey=='DefaultFiles') {
				$this->scriptHandler->RegisterUserScriptsListByDefaultFilename($fileList);
			}

			if ((!$registerDefaultFiles and $fileKey=='DefaultFiles') or
				(!$registerExampleFiles and $fileKey=='ExampleFiles')) {
				return;
			}

			if (!$registerInstallFiles and
			    ($this->moduleName=='IPSModuleManager' or $fileTypeSection=='Install')) {
				return;
			}

			$this->scriptHandler->RegisterScriptListByFilename($fileList);
		}

		/**
		 * @public
		 *
		 * Lädt eine Liste von Dateien anhand des Filetypes von einem Source Repository
		 *
		 * @param string $fileKey Type des Files (ScriptList, DefaultList, ExampleList, ...)
		 * @param string $fileTypeSection Filetype Section (app, config, webfront ...)
		 * @param string $sourceRepository Pfad/Url zum Source Repository, das zum Laden verwendet werden soll
		 * @param boolean $overwriteUserFiles bestehende User Files mit Default überschreiben
		 */
		private function LoadModuleFiles($fileKey, $fileTypeSection, $sourceRepository, $overwriteUserFiles=false) {
			$localList       = $this->GetScriptList($fileKey, $fileTypeSection, IPS_GetKernelDir().'scripts/');
			$repositoryList  = $this->GetScriptList($fileKey, $fileTypeSection, $sourceRepository);
			$backupList      = $this->GetScriptList($fileKey, $fileTypeSection, $this->backupHandler->GetBackupDirectory());

			$this->backupHandler->CreateBackup($localList, $backupList);

			$this->fileHandler->LoadFiles($repositoryList, $localList);
			if ($fileKey=='DefaultFiles') {
				$this->fileHandler->CreateScriptsFromDefault($localList, $overwriteUserFiles);
			}
			$this->RegisterModuleFiles($fileKey, $fileTypeSection, $localList);
		}


		public function LoadModuleFilesWebfront($sourceRepository='', $overwriteUserFiles=false) {
            $fileKey='ScriptFiles';  
            $fileTypeSection='WebFront';
			if ($sourceRepository=='') {
				$sourceRepository = $this->sourceRepository;
			}
			$sourceRepository = IPSFileHandler::AddTrailingPathDelimiter($sourceRepository);

			$this->LoadModuleFiles('DownloadFiles','Install',  $sourceRepository, $overwriteUserFiles);
			$this->fileConfigHandler = new IPSIniConfigHandler($this->GetModuleDownloadListFile(IPS_GetKernelDir().'scripts/'));

			$newVersion = $this->fileConfigHandler->GetValueDef(IPSConfigHandler::SCRIPTVERSION, null, 
			                                                    $this->fileConfigHandler->GetValue(IPSConfigHandler::SCRIPTVERSION));
			$this->versionHandler->SetVersionLoading($newVersion);
			$this->versionHandler->SetModuleRepository($sourceRepository);

			$localList       = $this->GetScriptList($fileKey, $fileTypeSection, IPS_GetKernelDir().'scripts/');
			$repositoryList  = $this->GetScriptList($fileKey, $fileTypeSection, $sourceRepository);
			$backupList      = $this->GetScriptList($fileKey, $fileTypeSection, $this->backupHandler->GetBackupDirectory());

			//$this->backupHandler->CreateBackup($localList, $backupList);
            print_r($repositoryList); 
			$this->fileHandler->LoadFiles($repositoryList, $localList);
			if ($fileKey=='DefaultFiles') {
				$this->fileHandler->CreateScriptsFromDefault($localList, $overwriteUserFiles);
			}
            print_R($localList);
			$this->RegisterModuleFiles($fileKey, $fileTypeSection, $localList);
		}

		/**
		 * @public
		 *
		 * Lädt alle zugehörigen Files des Modules von einem Source Repository
		 *
		 * @param string $sourceRepository Pfad/Url zum Source Repository, das zum Laden verwendet werden soll
		 * @param boolean $overwriteUserFiles bestehende User Files mit Default überschreiben
		 */
		public function LoadModule($sourceRepository='', $overwriteUserFiles=false) {
			if ($sourceRepository=='') {
				$sourceRepository = $this->sourceRepository;
			}
			$sourceRepository = IPSFileHandler::AddTrailingPathDelimiter($sourceRepository);

			$this->LoadModuleFiles('DownloadFiles','Install',  $sourceRepository, $overwriteUserFiles);
			$this->fileConfigHandler = new IPSIniConfigHandler($this->GetModuleDownloadListFile(IPS_GetKernelDir().'scripts/'));

			$newVersion = $this->fileConfigHandler->GetValueDef(IPSConfigHandler::SCRIPTVERSION, null, 
			                                                    $this->fileConfigHandler->GetValue(IPSConfigHandler::SCRIPTVERSION));
			$this->versionHandler->SetVersionLoading($newVersion);
			$this->versionHandler->SetModuleRepository($sourceRepository);

			$this->LoadModuleFiles('InstallFiles', 'Install',  $sourceRepository, $overwriteUserFiles);
			$this->LoadModuleFiles('DefaultFiles', 'Install',  $sourceRepository, $overwriteUserFiles);
			$this->LoadModuleFiles('ExampleFiles', 'Install',  $sourceRepository, $overwriteUserFiles);

			$this->LoadModuleFiles('ScriptFiles',  'App',      $sourceRepository, $overwriteUserFiles);
			$this->LoadModuleFiles('DefaultFiles', 'App',      $sourceRepository, $overwriteUserFiles);

			$this->LoadModuleFiles('ScriptFiles',  'Config',   $sourceRepository, $overwriteUserFiles);
			$this->LoadModuleFiles('DefaultFiles', 'Config',   $sourceRepository, $overwriteUserFiles);
			$this->LoadModuleFiles('ExampleFiles', 'Config',   $sourceRepository, $overwriteUserFiles);

			$this->LoadModuleFiles('ScriptFiles',  'WebFront', $sourceRepository, $overwriteUserFiles);
			$this->LoadModuleFiles('ExampleFiles', 'WebFront', $sourceRepository, $overwriteUserFiles);

			$this->versionHandler->SetVersionLoaded($newVersion);
		}

		/**
		 * @public
		 *
		 * Installiert ein Module,
		 *
		 * @param string $forceInstallation wenn true, wird auch eine Installation ausgeführt, wenn sich die Version des Modules nicht geändert hat
		 */
		public function InstallModule($forceInstallation = true) {
			$newVersion = $this->fileConfigHandler->GetValueDef(IPSConfigHandler::INSTALLVERSION, null, 
			                                                    $this->fileConfigHandler->GetValue(IPSConfigHandler::SCRIPTVERSION));
			if (!$this->versionHandler->IsVersionInstalled($newVersion) or $forceInstallation) {
				$this->versionHandler->SetVersionInstalling($newVersion);
				$moduleManager = $this;
				$file =  $this->GetModuleInstallationScript();
				include $file;
			} else {
				$this->logHandler->Debug('Module '.$this->moduleName.' is already at installed Version '.$newVersion);
			}
			$this->versionHandler->SetVersionInstalled($newVersion);
		}

		/**
		 * @public
		 *
		 * Update des aktuellen Modules auf die neueste Version. Es erfolgt zuerst ein Download des Modules,
		 * sollte sich die Version des Modules verändert haben, wird autom. auch das Installations Script
		 * ausgeführt.
		 *
		 * @param string $sourceRepository Pfad/Url zum Source Repository, das zum Speichern verwendet werden soll
		 */
		public function UpdateModule($sourceRepository='') {
			if ($sourceRepository=='') {
				$sourceRepository = $this->sourceRepository;
			}
			$sourceRepository = IPSFileHandler::AddTrailingPathDelimiter($sourceRepository);
			$this->LoadModule($sourceRepository);
			$this->InstallModule(false /*dont force Installation*/);
		}

		/**
		 * @public
		 *
		 * Update aller installierter Module
		 *
		 */
		public function UpdateAllModules() {
			$moduleList = $this->versionHandler->GetKnownUpdates();
			foreach ($moduleList as $idx=>$module) {
				$moduleInfos = $this->versionHandler->GetModuleInfos($module);
				$repository = $moduleInfos['Repository'];

				$moduleManager = new IPSModuleManager($module, $repository);
				$moduleManager->UpdateModule();
			}
		}

		/**
		 * @public
		 *
		 * Der Aufruf der Funktion versucht eine bereits installierte ModuleVersion zu reparieren.
		 *
		 */
		public function RepairModule() {
			$this->InstallModule(true /*ForceInstallation*/);
		}

		/**
		 * @public
		 *
		 * Exportiert eine Liste von Dateien anhand des Filetypes
		 *
		 * @param string $fileKey Type des Files (ScriptList, DefaultList, ExampleList, ...)
		 * @param string $fileTypeSection Filetype Section (app, config, webfront ...)
		 * @param string $sourceRepository Pfad/Url zum Source Repository, das zum Speichern verwendet werden soll
		 */
		private function DeleteModuleFiles($fileKey, $fileTypeSection) {
			$backupDirectory = $this->managerConfigHandler->GetValueDef('DeployBackupDirectory', '', IPS_GetKernelDir().'backup/IPSLibrary_Delete/');
			$backupHandler   = new IPSBackupHandler($backupDirectory);

			$localList       = $this->GetScriptList($fileKey, $fileTypeSection, IPS_GetKernelDir().'scripts/');
			$backupList      = $this->GetScriptList($fileKey, $fileTypeSection, $backupHandler->GetBackupDirectory());

			$this->logHandler->Log('Delete Files with Key='.$fileKey.' and Section='.$fileTypeSection);
			foreach ($localList as $idx=>$file) {
				if ($fileKey=='DefaultFiles') {
					$userFile   = IPSFileHandler::GetUserFilenameByDefaultFilename($file);
					$backupFile = IPSFileHandler::GetUserFilenameByDefaultFilename($backupList[$idx]);
					$this->backupHandler->CreateBackupFromFile($userFile, $backupFile);
					$this->scriptHandler->UnregisterScriptByFilename($userFile);
					$this->fileHandler->DeleteFile($userFile);
				}
				$this->backupHandler->CreateBackupFromFile($file, $backupList[$idx]);
				$this->scriptHandler->UnregisterScriptByFilename($file);
				$this->fileHandler->DeleteFile($file);
				$this->fileHandler->DeleteEmptyDirectories($file);
			}
		}

		private function DeleteModuleObjects($path, $exclusiveSwitch=true) {
		   if ($path <> '' and $exclusiveSwitch) {
				$categoryID = IPSUtil_ObjectIDByPath($path, true);
				if ($categoryID===false) {
					$this->logHandler->Debug('Path '.$path.' not found...');
					return;
				}
				$this->logHandler->Log('Delete Objects in Category='.$path.', ID='.$categoryID);

				DeleteObject($categoryID);
			}
		}

		private function DeleteWFCItems($wfcItemPrefix, $exclusiveSwitch=true) {
			if ($wfcItemPrefix <> '' and $exclusiveSwitch) {
				$wfcConfigID = $this->GetConfigValueIntDef('ID', 'WFC10', GetWFCIdDefault());
				$this->logHandler->Log('Delete WFC Items with Prefix='.$wfcItemPrefix);
				DeleteWFCItems($wfcConfigID, $wfcItemPrefix);
				ReloadAllWebFronts();
		   }
		}

		/**
		 * @public
		 *
		 * Löscht ein Module aus IP-Symcon
		 *
		 * Es werden folgende Komponenten gelöscht
		 *   - Alle WebFront Seiten, die autom. generiert wurden
		 *   - Alle Mobile Interface Einträge
		 *   - Alle Variablen und Scripte in IPS
		 *   - Alle zugehörigen Dateien
		 *
		 */
		public function DeleteModule() {
			$this->versionHandler->SetVersionDeleting();

			if ($this->moduleName=='IPSModuleManager') {
				throw new Exception('Deinstallation of IPSModuleManager currenty NOT supported !!!');
				
				$this->DeleteModuleObjects('Program.IPSLibrary.install');
				$this->DeleteModuleObjects('Program.IPSLibrary.app.core.IPSUtils');
				$this->DeleteModuleObjects('Program.IPSLibrary.app.core.IPSConfigHandler');
			} else {
				if ($this->moduleConfigHandler->GetValueDef('TabItem', 'WFC10', '') <> '') {
					$this->DeleteWFCItems($this->moduleConfigHandler->GetValueDef('TabPaneItem', 'WFC10', '').$this->moduleConfigHandler->GetValueDef('TabItem', 'WFC10', ''));
				}
				for ($idx=1;$idx<=10;$idx++) {
					if ($this->moduleConfigHandler->GetValueDef('TabItem'.$idx, 'WFC10', '') <> '') {
						$this->DeleteWFCItems($this->moduleConfigHandler->GetValueDef('TabPaneItem', 'WFC10', '').$this->moduleConfigHandler->GetValueDef('TabItem'.$idx, 'WFC10', ''));
					}
				}
				$this->DeleteWFCItems($this->moduleConfigHandler->GetValueDef('TabPaneItem', 'WFC10', ''),
				                      $this->moduleConfigHandler->GetValueBoolDef('TabPaneExclusive', 'WFC10', false));

				$namespace  = $this->fileConfigHandler->GetValue(IPSConfigHandler::MODULENAMESPACE);
				$this->DeleteModuleObjects($this->GetModuleCategoryPath('app'));
				$this->DeleteModuleObjects($this->GetModuleCategoryPath('data'));
				$this->DeleteModuleObjects($this->GetModuleCategoryPath('config'));
				$this->DeleteModuleObjects($this->moduleConfigHandler->GetValueDef('Path', 'WFC10', ''));
				$this->DeleteModuleObjects($this->moduleConfigHandler->GetValueDef('Path', 'Mobile', ''),
				                           $this->moduleConfigHandler->GetValueBoolDef('PathExclusive', 'Mobile', false));
				$this->DeleteModuleObjects($this->moduleConfigHandler->GetValueDef('Path', 'Mobile', '').'.'.$this->moduleConfigHandler->GetValueDef('Name', 'Mobile', ''));
				for ($idx=1;$idx<=10;$idx++) {
					$this->DeleteModuleObjects($this->moduleConfigHandler->GetValueDef('Path', 'Mobile', '').'.'.$this->moduleConfigHandler->GetValueDef('Name'.$idx, 'Mobile', ''));
				}
			}

			$deinstallationScriptName = $this->GetModuleDeinstallationScript();
			if (file_exists($deinstallationScriptName)) {
				$this->logHandler->Log('Execute Deinstallation Script '.$deinstallationScriptName);
				include_once $deinstallationScriptName;
			}

			$this->DeleteModuleFiles('DefaultFiles', 'App');
			$this->DeleteModuleFiles('ScriptFiles',  'App');
			$this->DeleteModuleFiles('ScriptFiles',  'Config');
			$this->DeleteModuleFiles('DefaultFiles', 'Config');
			$this->DeleteModuleFiles('ExampleFiles', 'Config');
			$this->DeleteModuleFiles('ScriptFiles',  'WebFront');
			$this->DeleteModuleFiles('ExampleFiles', 'WebFront');
			$this->DeleteModuleFiles('InstallFiles', 'Install');
			$this->DeleteModuleFiles('DefaultFiles', 'Install');
			$this->DeleteModuleFiles('ExampleFiles', 'Install');
			$this->DeleteModuleFiles('DownloadFiles','Install');

			$this->versionHandler->DeleteModule();
		}



		/**
		 * @public
		 *
		 * Exportiert eine Liste von Dateien anhand des Filetypes
		 *
		 * @param string $fileKey Type des Files (ScriptList, DefaultList, ExampleList, ...)
		 * @param string $fileTypeSection Filetype Section (app, config, webfront ...)
		 * @param string $sourceRepository Pfad/Url zum Source Repository, das zum Speichern verwendet werden soll
		 */
		private function DeployModuleFiles($fileKey, $fileTypeSection, $sourceRepository) {
			$backupDirectory = $this->managerConfigHandler->GetValueDef('DeployBackupDirectory', '', IPS_GetKernelDir().'backup/IPSLibrary_Deploy/');
			$backupHandler   = new IPSBackupHandler($backupDirectory);
				
			$localList       = $this->GetScriptList($fileKey, $fileTypeSection, IPS_GetKernelDir().'scripts/');		 
			$repositoryList  = $this->GetScriptList($fileKey, $fileTypeSection, $sourceRepository);
			$backupList      = $this->GetScriptList($fileKey, $fileTypeSection, $backupHandler->GetBackupDirectory());

			$this->backupHandler->CreateBackup($repositoryList, $backupList);
			$this->fileHandler->FilterEqualFiles($localList, $repositoryList);
			$this->fileHandler->WriteFiles($localList, $repositoryList);
		}

        /* Testfunktion für Verständinis Webfront Behandlung
         */
        private function DeployModuleFilesWebFront($fileKey, $sourceRepository) {
			$backupDirectory = $this->managerConfigHandler->GetValueDef('DeployBackupDirectory', '', IPS_GetKernelDir().'backup/IPSLibrary_Deploy/');
			$backupHandler   = new IPSBackupHandler($backupDirectory);
			
			$fileTypeSection='WebFront';
			echo "DeployModuleFilesWebFront $fileKey $fileTypeSection $sourceRepository \n";
			$localList       = $this->GetScriptListWebfront($fileKey, $fileTypeSection, IPS_GetKernelDir().'scripts/');	
            print_r($localList);
			$repositoryList  = $this->GetScriptList($fileKey, $fileTypeSection, $sourceRepository);
			$backupList      = $this->GetScriptList($fileKey, $fileTypeSection, $backupHandler->GetBackupDirectory());
			//$this->backupHandler->CreateBackup($repositoryList, $backupList);
            print_R($repositoryList);
            $this->fileHandler->FilterEqualFiles($localList, $repositoryList);
			
            /* $sourceOut      = array();
			$destinationOut = array();
            foreach ($localList as $idx=>$sourceScript) {
				$sourceFile          = $localList[$idx];
				$destinationFile     = $repositoryList[$idx];
				echo "compare $sourceFile with $destinationFile :";
				$addFileToList = true;
				if (!file_exists($destinationFile)) {
                    echo "destination does not exists";
					$addFileToList = true;
				} else {
					$sourceContent      = file_get_contents($sourceFile);
					$destinationContent = file_get_contents($destinationFile);
					$sourceContent      = str_replace(chr(13), '',$sourceContent);
					$destinationContent = str_replace(chr(13), '',$destinationContent);
					if ($sourceContent == $destinationContent) {
						$addFileToList = false;
					}
				}
				if ($addFileToList) {
					$sourceOut[]      = $sourceFile;
					$destinationOut[] = $destinationFile;
				};
                echo "\n";
			}  */
            print_r($localList);
            print_R($repositoryList);
		}

		/**
		 * @public
		 *
		 * Exportiert einkomplettes Module zu einem Ziel Verzeichnis
		 *
		 * @param string $sourceRepository Pfad/Url zum Source Repository, das zum Speichern verwendet werden soll
		 * @param string $changeText Text der für die ChangeList verwendet werden soll
		 * @param boolean $installationRequired Installation durch Änderung notwendig
		 */
		public function DeployModule($sourceRepository='', $changeText='', $installationRequired=false) {
			if ($sourceRepository=='') {
				$sourceRepository = $this->sourceRepository;
			}
			$sourceRepository = IPSFileHandler::AddTrailingPathDelimiter($sourceRepository);

			$this->logHandler->Log('Start Deploy of Module "'.$this->moduleName.'"');
			if ($changeText<>'') {
				$this->versionHandler->IncreaseModuleVersion($changeText, $installationRequired);
			}
			
			$this->DeployModuleFiles('DownloadFiles','Install',  $sourceRepository);

			$this->DeployModuleFiles('DefaultFiles', 'App',      $sourceRepository);
			$this->DeployModuleFiles('ScriptFiles',  'App',      $sourceRepository);

			$this->DeployModuleFiles('ScriptFiles',  'Config',   $sourceRepository);
			$this->DeployModuleFiles('DefaultFiles', 'Config',   $sourceRepository);
			$this->DeployModuleFiles('ExampleFiles', 'Config',   $sourceRepository);

			$this->DeployModuleFiles('DownloadFiles','Install',  $sourceRepository);
			$this->DeployModuleFiles('InstallFiles', 'Install',  $sourceRepository);
			$this->DeployModuleFiles('DefaultFiles', 'Install',  $sourceRepository);
			$this->DeployModuleFiles('ExampleFiles', 'Install',  $sourceRepository);

			$this->DeployModuleFiles('ScriptFiles',  'WebFront', $sourceRepository);
			$this->DeployModuleFiles('ExampleFiles', 'WebFront', $sourceRepository);

			$this->logHandler->Log('Finished Deploy of Module "'.$this->moduleName.'"');
		}

        public function DeployModuleWebfront($sourceRepository='', $changeText='', $installationRequired=false) {
            echo "DeployModuleWebfront($sourceRepository \n";
			if ($sourceRepository=='') { $sourceRepository = $this->sourceRepository; }
			$sourceRepository = IPSFileHandler::AddTrailingPathDelimiter($sourceRepository);
			$this->DeployModuleFilesWebfront('ScriptFiles',  $sourceRepository);
			//$this->DeployModuleFiles('ExampleFiles', 'WebFront', $sourceRepository);
		}

		/**
		 * @public
		 *
		 * Exportiert alle installierten Module zu einem Ziel Verzeichnis
		 *
		 * @param string $sourceRepository Pfad/Url zum Source Repository, das zum Speichern verwendet werden soll
		 */
		public function DeployAllModules($sourceRepository='') {
			if ($sourceRepository=='') {
				$sourceRepository = $this->sourceRepository;
			}
			$moduleList = $this->versionHandler->GetInstalledModules();
			foreach ($moduleList as $module=>$version) {
				$moduleManager = new IPSModuleManager($module, $sourceRepository);
				$moduleManager->DeployModule();
			}
		}

	}


	class FileVersionHandlerIPS7 extends IPSFileVersionHandler {
		
        // muss eigene Variablen definieren
        private $fileNameAvailableModules;      
		private $fileNameInstalledModules;
		private $fileNameKnownModules;
		private $fileNameKnownRepositories;
		private $fileNameRepositoryVersions;
		private $fileNameChangeList;
		private $fileNameRequiredModules;
		private $fileNameDownloadList;

		/**
		 * @public
		 *
		 * Initialisierung des IPSFileVersionHandler
		 *
		 * @param string $moduleName Name des Modules
		 */
		public function __construct($moduleName) {
			if ($moduleName=="") {
				die("ModuleName must have a Value!");
			}
			parent::__construct($moduleName);
			$this->fileNameInstalledModules      = IPS_GetKernelDir().'scripts/'.$this::FILE_INSTALLED_MODULES;
			$this->fileNameAvailableModules      = IPS_GetKernelDir().'scripts/'.$this::FILE_AVAILABLE_MODULES;
			$this->fileNameKnownModules          = IPS_GetKernelDir().'scripts/'.$this::FILE_KNOWN_MODULES;
			$this->fileNameKnownRepositories     = IPS_GetKernelDir().'scripts/'.$this::FILE_KNOWN_REPOSITORIES;
			$this->fileNameKnownUserRepositories = IPS_GetKernelDir().'scripts/'.$this::FILE_KNOWN_USERREPOSITORIES;
			$this->fileNameRepositoryVersions    = IPS_GetKernelDir().'scripts/'.$this::FILE_REPOSITORY_VERSIONS;
			$this->fileNameChangeList            = IPS_GetKernelDir().'scripts/'.$this::FILE_CHANGELIST;
			$this->fileNameRequiredModules       = IPS_GetKernelDir().'scripts/'.$this::FILE_REQUIRED_MODULES;
			$this->fileNameDownloadList          = IPS_GetKernelDir().'scripts/'.$this::FILE_DOWNLOADLIST_PATH.$moduleName.$this::FILE_DOWNLOADLIST_SUFFIX;

			$this->ReloadVersionData();
		}

        	
		private function LoadFileRequiredModules() {
			if (file_exists($this->fileNameRequiredModules)) {
				$this->requiredModules = parse_ini_file($this->fileNameRequiredModules, true);
			}
		}

		private function LoadFileChangeList() {
			if (file_exists($this->fileNameChangeList)) {
				$this->changeList = parse_ini_file($this->fileNameChangeList, true);
			}
		}

		private function LoadFileKnownModules() {
			if (file_exists($this->fileNameKnownModules)) {
				$this->knownModules = parse_ini_file($this->fileNameKnownModules, true);
			}
		}

		private function LoadFileKnownRepositories() {
			if (!file_exists($this->fileNameKnownRepositories)) {
				die($this->fileNameKnownRepositories.' does NOT exist!');
			} elseif (file_exists($this->fileNameKnownUserRepositories)) {
				$this->knownRepositories = parse_ini_file($this->fileNameKnownUserRepositories, true);
			} else {
				$this->knownRepositories = parse_ini_file($this->fileNameKnownRepositories, true);
			}
		}

		private function LoadFileRepositoryVersions() {
			if (file_exists($this->fileNameRepositoryVersions)) {
				$this->repositoryVersions = parse_ini_file($this->fileNameRepositoryVersions, true);
			}
		}

		private function LoadFileInstalledModules() {
			if (file_exists($this->fileNameInstalledModules)) {
				$fileContent = file_get_contents($this->fileNameInstalledModules);
				$lines = explode(PHP_EOL, $fileContent);
				foreach ($lines as $line) {
					$content = explode('=', $line);
					if (count($content)>0) {
						$this->installedModules[$content[0]] = $content[1];
					}
				}
			} else {
				$this->installedModules = array();
			}
		}

		private function WriteFileInstalledModules() {
			$fileContent = '';
			foreach ($this->installedModules as $moduleName=>$moduleVersion) {
				if ($fileContent <> '') {
					$fileContent .= PHP_EOL;
				}
				$fileContent .= $moduleName.'='.$moduleVersion;
			}
			file_put_contents($this->fileNameInstalledModules, $fileContent);
		}

		/**
		 * Overwrite BuildKnownModules
		 *
		 * Erzeugt das File KnownModules, hier ohne echo Ausgabe
		 */
		public function BuildKnownModules($debug=false) {
			$knownRepositories    = $this->GetKnownRepositories();
			$knownModules         = array();
			$repositoryVersions   = array();
			$changeList           = array();
			$requiredModules      = array();
			foreach ($knownRepositories as $repositoryIdx=>$repository) {
				if ($debug) echo 'Process Repsoitory '.$repository.PHP_EOL;
				$fileHandler         = new IPSFileHandler();
				$repository = IPSFileHandler::AddTrailingPathDelimiter($repository);
				$localAvailableModuleList      = sys_get_temp_dir().'/AvailableModules.ini';
				$repositoryAvailableModuleList = $repository.'IPSLibrary/config/AvailableModules.ini';
				$fileHandler->CopyFiles(array($repositoryAvailableModuleList), array($localAvailableModuleList));

				$availableModules = parse_ini_file($localAvailableModuleList, true);
				foreach ($availableModules as $moduleName=>$moduleData) {
					$moduleProperties  = explode('|',$moduleData);
					$modulePath        = $moduleProperties[0];
					$moduleDescription = '';
					if (array_key_exists(1, $moduleProperties)) {
						$moduleDescription = $moduleProperties[1];
					}
					
					$localDownloadIniFile      = sys_get_temp_dir().'/DownloadListfile.ini';
					$repositoryDownloadIniFile = $repository.'IPSLibrary/install/DownloadListFiles/'.$moduleName.'_FileList.ini';
					$result = $fileHandler->CopyFiles(array($repositoryDownloadIniFile), array($localDownloadIniFile), false);
					if ($result===false) {
						echo '   '.$moduleName.'could NOT be found in '.$repository.PHP_EOL;
					} else {
						if ($debug) echo '   Processing '.$moduleName.' in '.$repository.PHP_EOL;
						$configHandler    = new IPSIniConfigHandler($localDownloadIniFile);
						$availableVersion = $configHandler->GetValue(IPSConfigHandler::SCRIPTVERSION);
						$changeListModule = $configHandler->GetValueDef(IPSConfigHandler::CHANGELIST, null, array());
						$requiredModulesOfModule = $configHandler->GetValueDef(IPSConfigHandler::REQUIREDMODULES, null, array());

						$replaceModule = false;
						if (!array_key_exists($moduleName, $knownModules)) {
							$replaceModule = true;
						} elseif ($versionHandler->CompareVersionsNewer($knownModules[$moduleName]['Version'], $availableVersion)) {
							$replaceModule = true;
						} elseif ($versionHandler->CompareVersionsEqual($knownModules[$moduleName]['Version'], $availableVersion)
								  and $versionHandler->IsModuleInstalled($moduleName)) {
							$versionHandler   = new IPSFileVersionHandler($moduleName);
							if ($versionHandler->GetModuleRepository()==$repository) {
								$replaceModule = true;
							}
						} else {
						}

						if ($replaceModule) {
							$knownModules[$moduleName]['Version']     = $availableVersion;
							$knownModules[$moduleName]['Repository']  = $repository;
							$knownModules[$moduleName]['Description'] = $moduleDescription;
							$knownModules[$moduleName]['Path']        = $modulePath;
							if ($this->IsModuleInstalled($moduleName)) {
								$versionHandler   = new IPSFileVersionHandler($moduleName);
							}
							$knownModules[$moduleName]['LastRepository'] = $versionHandler->GetModuleRepository();
							$changeList[$moduleName] = $changeListModule;
							$requiredModules[$moduleName] = $requiredModulesOfModule;
						}
						$repositoryVersions[$moduleName][$repository] = $availableVersion;
					}
				}

			}

			$fileContent = '';
			foreach ($knownModules as $moduleName=>$moduleData) {
				$fileContent .= '['.$moduleName.']'.PHP_EOL;
				foreach ($moduleData as $property=>$value) {
					// "//192.168..." not handled correct in case of usage ""
					if ($property=='Repository') {
						$fileContent .= $property.'='.$value.''.PHP_EOL;
					} else {
						$fileContent .= $property.'="'.$value.'"'.PHP_EOL;
					}
				}
			}
			file_put_contents($this->fileNameKnownModules, $fileContent);

			$fileContent = '';
			foreach ($repositoryVersions as $moduleName=>$moduleData) {
				$fileContent .= '['.$moduleName.']'.PHP_EOL;
				foreach ($moduleData as $property=>$value) {
					$fileContent .= $property.'="'.$value.'"'.PHP_EOL;
				}
			}
			file_put_contents($this->fileNameRepositoryVersions, $fileContent);

			$fileContent = '';
			foreach ($changeList as $moduleName=>$moduleData) {
				$fileContent .= '['.$moduleName.']'.PHP_EOL;
				foreach ($moduleData as $property=>$value) {
					$fileContent .= $property.'="'.$value.'"'.PHP_EOL;
				}
			}
			file_put_contents($this->fileNameChangeList, $fileContent);

			$fileContent = '';
			foreach ($requiredModules as $moduleName=>$moduleData) {
				$fileContent .= '['.$moduleName.']'.PHP_EOL;
				foreach ($moduleData as $property=>$value) {
					$fileContent .= $property.'="'.$value.'"'.PHP_EOL;
				}
			}
			file_put_contents($this->fileNameRequiredModules, $fileContent);

			$this->LoadFileKnownModules();
			$this->LoadFileRepositoryVersions();
			$this->LoadFileChangeList();
			$this->LoadFileRequiredModules();
		}

    }

	/** @}*/
?>