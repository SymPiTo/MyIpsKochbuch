<?
/**
 * Title: Status Werte eines Raspberry Pi auslesen
  *
 * author PiTo
 * 
 * GITHUB = <https://github.com/SymPiTo/MySymApps/MyRaspStat/>
 * 
 * Version:1.0.2019.08.22
 */
//Class: MyRaspberryPi
class MyRaspberryPi extends IPSModule
{
    /* 
    _______________________________________________________________________ 
     Section: Internal Modul Funtions
     Die folgenden Funktionen sind Standard Funktionen zur Modul Erstellung.
    _______________________________________________________________________ 
     */
            
    /* ------------------------------------------------------------ 
    Function: Create  
    Create() wird einmalig beim Erstellen einer neuen Instanz und 
    neu laden der Modulesausgeführt. Vorhandene Variable werden nicht veändert, auch nicht 
    eingetragene Werte (Properties).
    Variable können hier nicht verwendet werden nur statische Werte.
    Überschreibt die interne IPS_Create(§id)  Funktion
   
     CONFIG-VARIABLE:
      FS20RSU_ID   -   ID des FS20RSU Modules (selektierbar).
     
    STANDARD-AKTIONEN:
      FSSC_Position    -   Position (integer)

    ------------------------------------------------------------- */
    public function Create()
    {
	    //Never delete this line!
        parent::Create();
 
        // Variable aus dem Instanz Formular registrieren (zugänglich zu machen)
        // Aufruf dieser Form Variable mit  $this->ReadPropertyFloat("IDENTNAME")
        $this->RegisterPropertyInteger("UpdateInterval", 30000);
        $this->RegisterPropertyBoolean("Modul_Active", false);
        $this->RegisterPropertyString("IPAddress");
        
        
        //Float Variable anlegen
        $this->RegisterVariableFloat("ID_cpuFreq", "CPU frequnecy","", 0);
        $this->RegisterVariableFloat("ID_MemTotal", "Memory total","", 0);
        $this->RegisterVariableFloat("ID_MemFree", "Memory free","", 0);
        $this->RegisterVariableFloat("ID_SD_boot_used", "SD Card Boot used","", 0);
        $this->RegisterVariableFloat("ID_SD_root_used", "SD Card Root used","", 0);
        $this->RegisterVariableFloat("ID_Swap_used", "Swap used","", 0);
        
         //Integer Variable anlegen
        //integer RegisterVariableInteger ( string $Ident, string $Name, string $Profil, integer $Position )
        //Aufruf dieser Variable mit $this->GetIDForIdent("IDENTNAME")
        //$this->RegisterVariableInteger("FSSC_Position", "Position", "Rollo.Position");
      
        //Boolean Variable anlegen
        //integer RegisterVariableBoolean ( string $Ident, string $Name, string $Profil, integer $Position )
        // Aufruf dieser Variable mit $this->GetIDForIdent("IDENTNAME")
        //$this->RegisterVariableBoolean("FSSC_Mode", "Mode");
        
        //String Variable anlegen
        //RegisterVariableString ($Ident,  $Name, $Profil, $Position )
        //Aufruf dieser Variable mit $this->GetIDForIdent("IDENTNAME")
        $this->RegisterVariableString("ID_CPU_Volt", "CPU Voötage");
        $this->RegisterVariableString("ID_http", "Port http");
        $this->RegisterVariableString("ID_https", "Port https");
        $this->RegisterVariableString("ID_RPI_monitor", "Port RPI Monitor");
        $this->RegisterVariableString("ID_ssh", "Port Telnet/ssh");
        $this->RegisterVariableString("ID_symcon", "Port symcon");
        $this->RegisterVariableString("ID_wss", "Port WebSocketServer");
        $this->RegisterVariableString("ID_scal_Gov", "scaling govenor");
        $this->RegisterVariableString("ID_CPU_Temp", "CPU Temperature");
        $this->RegisterVariableString("ID_upgrade", "Files upgradable");
        $this->RegisterVariableString("ID_UpTime", "Start Time");
        $this->RegisterVariableString("ID_CPU_load1", "CPU load 1 min");
        $this->RegisterVariableString("ID_CPU_load5", "CPU load 5 min");
        $this->RegisterVariableString("ID_CPU_load15", "CPU load 15 min");
        $this->RegisterVariableString("ID_packages", "update for packages");



        // Aktiviert die Standardaktion der Statusvariable zur Bedienbarkeit im Webfront
        //$this->EnableAction("IDENTNAME");
        
        //IPS_SetVariableCustomProfile(§this->GetIDForIdent("Mode"), "Rollo.Mode");
        
        //anlegen eines Timers
        //$this->RegisterTimer("TimerName", 0, "FSSC_reset($_IPS["TARGET">]);");
            


    }
   /* ------------------------------------------------------------ 
     Function: ApplyChanges 
      ApplyChanges() Wird ausgeführt, wenn auf der Konfigurationsseite "Übernehmen" gedrückt wird 
      und nach dem unittelbaren Erstellen der Instanz.
     
    SYSTEM-VARIABLE:
        InstanceID - $this->InstanceID.

    EVENTS:
        SwitchTimeEvent".$this->InstanceID   -   Wochenplan (Mo-Fr und Sa-So)
        SunRiseEvent".$this->InstanceID       -   cyclice Time Event jeden Tag at SunRise
    ------------------------------------------------------------- */
    public function ApplyChanges()
    {
	    //Never delete this line!
        parent::ApplyChanges();
       
    }
    
   /* ------------------------------------------------------------ 
      Function: RequestAction  
      RequestAction() Wird ausgeführt, wenn auf der Webfront eine Variable
      geschaltet oder verändert wird. Es werden die System Variable des betätigten
      Elementes übergeben.
      Ausgaben über echo werden an die Visualisierung zurückgeleitet
     
   
    SYSTEM-VARIABLE:
      $this->GetIDForIdent($Ident)     -   ID der von WebFront geschalteten Variable
      $Value                           -   Wert der von Webfront geänderten Variable

   STANDARD-AKTIONEN:
      FSSC_Position    -   Slider für Position
      UpDown           -   Switch für up / Down
      Mode             -   Switch für Automatik/Manual
     ------------------------------------------------------------- */
    public function RequestAction($Ident, $Value) {
         switch($Ident) {
            case "UpDown":
                SetValue($this->GetIDForIdent($Ident), $Value);
                if(getvalue($this->GetIDForIdent($Ident))){
                    $this->SetRolloDown();  
                }
                else{
                    $this->SetRolloUp();
                }
                break;
             case "Mode":
                $this->SetMode($Value);  
                break;
            default:
                throw new Exception("Invalid Ident");
        }
 
    }

  /* ______________________________________________________________________________________________________________________
     Section: Public Funtions
     Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
     Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wie folgt zur Verfügung gestellt:
    
     FSSC_XYFunktion($Instance_id, ... );
     ________________________________________________________________________________________________________________________ */
    //-----------------------------------------------------------------------------
    /* Function: update
    ...............................................................................
    Beschreibung:
      liest Statuswerte als Json des Raspberry aus und schreibt Werte in die Variable
    ...............................................................................
    Parameters: 
        none
    ...............................................................................
    Returns:    
        none
    ------------------------------------------------------------------------------  */
    public function update(){
      $ip = $this->ReadPropertyFloat("IPAddress");
      $data = file_get_contents("http://".$ip.":8888/dynamic.json"); 
      $data = json_decode($data); 
      SetValue($this->ReadPropertyFloat("ID_cpuFreq"), $data['cpu_frequency']); 
      SetValue($this->ReadPropertyFloat("ID_MemTotal"), $data['memory_available']);
      SetValue($this->ReadPropertyFloat("ID_MemFree"), $data['memory_free']);
      SetValue($this->ReadPropertyFloat("ID_SD_boot_used"), $data['sdcard_boot_used']);
      SetValue($this->ReadPropertyFloat("ID_SD_root_used"), $data['sdcard_root_used']);
      SetValue($this->ReadPropertyFloat("ID_Swap_used"), $data['swap_used']);
      SetValue($this->ReadPropertyFloat("ID_CPU_Volt"), $data['cpu_voltage']);
      SetValue($this->ReadPropertyFloat("ID_http"), $data['http']);
      SetValue($this->ReadPropertyFloat("ID_https"), $data['https']);
      SetValue($this->ReadPropertyFloat("ID_RPI_monitor"), $data['rpimonitor']);
      SetValue($this->ReadPropertyFloat("ID_ssh"), $data['ssh']);
      SetValue($this->ReadPropertyFloat("ID_symcon"), $data['symcon']);
      SetValue($this->ReadPropertyFloat("ID_wss"), $data['websocketserver']);
      SetValue($this->ReadPropertyFloat("ID_scal_Gov"), $data['scaling_governor']);
      SetValue($this->ReadPropertyFloat("ID_CPU_Temp"), $data['soc_temp']);
      SetValue($this->ReadPropertyFloat("ID_upgrade"), $data['upgrade']);
      SetValue($this->ReadPropertyFloat("ID_UpTime"), $data['uptime']);
      SetValue($this->ReadPropertyFloat("ID_CPU_load1"), $data['load1']);
      SetValue($this->ReadPropertyFloat("ID_CPU_load5"), $data['load5']);
      SetValue($this->ReadPropertyFloat("ID_CPU_load15"), $data['load15']);
      SetValue($this->ReadPropertyFloat("ID_packages"), $data['packages']);
    }  

 
 
   /* _______________________________________________________________________
    * Section: Private Funtions
    * Die folgenden Funktionen sind nur zur internen Verwendung verfügbar
    *   Hilfsfunktionen
    * _______________________________________________________________________
    */  

    protected function SendToSplitter(string $payload)
		{						
			//an Splitter schicken
			$result = $this->SendDataToParent(json_encode(Array("DataID" => "{687E15E1-5C42-A35E-AD38-C4F1659B0DAA}", "Buffer" => $payload))); // Interface GUI
			return $result;
		}
		
        /* ----------------------------------------------------------------------------
         Function: GetIPSVersion
        ...............................................................................
        gibt die instalierte IPS Version zurück
        ...............................................................................
        Parameters: 
            none
        ..............................................................................
        Returns:   
            $ipsversion (floatint)
        ------------------------------------------------------------------------------- */
	protected function GetIPSVersion()
	{
		$ipsversion = floatval(IPS_GetKernelVersion());
		if ($ipsversion < 4.1) // 4.0
		{
			$ipsversion = 0;
		} elseif ($ipsversion >= 4.1 && $ipsversion < 4.2) // 4.1
		{
			$ipsversion = 1;
		} elseif ($ipsversion >= 4.2 && $ipsversion < 4.3) // 4.2
		{
			$ipsversion = 2;
		} elseif ($ipsversion >= 4.3 && $ipsversion < 4.4) // 4.3
		{
			$ipsversion = 3;
		} elseif ($ipsversion >= 4.4 && $ipsversion < 5) // 4.4
		{
			$ipsversion = 4;
		} else   // 5
		{
			$ipsversion = 5;
		}

		return $ipsversion;
	}

 
    /* --------------------------------------------------------------------------- 
    Function: RegisterEvent
    ...............................................................................
    legt einen Event an wenn nicht schon vorhanden
      Beispiel:
      ("Wochenplan", "SwitchTimeEvent".$this->InstanceID, 2, $this->InstanceID, 20);  
      ...............................................................................
    Parameters: 
      $Name        -   Name des Events
      $Ident       -   Ident Name des Events
      $Typ         -   Typ des Events (1=cyclic 2=Wochenplan)
      $Parent      -   ID des Parents
      $Position    -   Position der Instanz
    ...............................................................................
    Returns:    
        none
    -------------------------------------------------------------------------------*/
    private function RegisterEvent($Name, $Ident, $Typ, $Parent, $Position)
    {
            $eid = @$this->GetIDForIdent($Ident);
            if($eid === false) {
                    $eid = 0;
            } elseif(IPS_GetEvent($eid)["EventType"] <> $Typ) {
                    IPS_DeleteEvent($eid);
                    $eid = 0;
            }
            //we need to create one
            if ($eid == 0) {
                    $EventID = IPS_CreateEvent($Typ);
                    IPS_SetParent($EventID, $Parent);
                    IPS_SetIdent($EventID, $Ident);
                    IPS_SetName($EventID, $Name);
                    IPS_SetPosition($EventID, $Position);
                    IPS_SetEventActive($EventID, false);  
            }
    }
    
 
    /* ----------------------------------------------------------------------------------------------------- 
    Function: RegisterScheduleAction
    ...............................................................................
     *  Legt eine Aktion für den Event fest
     * Beispiel:
     * ("SwitchTimeEvent".$this->InstanceID), 1, "Down", 0xFF0040, "FSSC_SetRolloDown(\$_IPS["TARGET"]);");
    ...............................................................................
    Parameters: 
      $EventID
      $ActionID
      $Name
      $Color
      $Script
    .......................................................................................................
    Returns:    
        none
    -------------------------------------------------------------------------------------------------------- */
    private function RegisterScheduleAction($EventID, $ActionID, $Name, $Color, $Script)
    {
            IPS_SetEventScheduleAction($EventID, $ActionID, $Name, $Color, $Script);
    }



		
}