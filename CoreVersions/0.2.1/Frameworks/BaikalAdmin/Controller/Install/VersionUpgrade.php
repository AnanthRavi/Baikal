<?php
#################################################################
#  Copyright notice
#
#  (c) 2012 Jérôme Schneider <mail@jeromeschneider.fr>
#  All rights reserved
#
#  http://baikal.codr.fr
#
#  This script is part of the Baïkal Server project. The Baïkal
#  Server project is free software; you can redistribute it
#  and/or modify it under the terms of the GNU General Public
#  License as published by the Free Software Foundation; either
#  version 2 of the License, or (at your option) any later version.
#
#  The GNU General Public License can be found at
#  http://www.gnu.org/copyleft/gpl.html.
#
#  This script is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  This copyright notice MUST APPEAR in all copies of the script!
#################################################################

namespace BaikalAdmin\Controller\Install;

class VersionUpgrade extends \Flake\Core\Controller {
	
	protected $aMessages = array();
	protected $oModel;
	protected $oForm;	# \Formal\Form
	
	protected $aErrors = array();
	protected $aSuccess = array();
	
	public function execute() {
	}

	public function render() {
		$sBigIcon = "glyph2x-magic";
		$sBaikalVersion = BAIKAL_VERSION;
		$sBaikalConfiguredVersion = BAIKAL_CONFIGURED_VERSION;
		
		if(BAIKAL_CONFIGURED_VERSION === BAIKAL_VERSION) {
			$sMessage = "Your system is configured to use version <strong>" . $sBaikalConfiguredVersion . "</strong>.<br />There's no upgrade to be done.";
		} else {
			$sMessage = "Upgrading Baïkal from version <strong>" . $sBaikalConfiguredVersion . "</strong> to version <strong>" . $sBaikalVersion . "</strong>";
		}
		
		$sHtml = <<<HTML
<header class="jumbotron subhead" id="overview">
	<h1><i class="{$sBigIcon}"></i>Baïkal upgrade wizard</h1>
	<p class="lead">{$sMessage}</p>
</header>
HTML;
		
		$bSuccess = $this->upgrade(BAIKAL_CONFIGURED_VERSION, BAIKAL_VERSION);
		
		if(!empty($this->aErrors)) {
			$sHtml .= "<h3>Errors</h3>" . implode("<br />\n", $this->aErrors);
		}
		
		if(!empty($this->aSuccess)) {
			$sHtml .= "<h3>Successful operations</h3>" . implode("<br />\n", $this->aSuccess);
		}
		
		if($bSuccess === FALSE) {
			$sHtml .= "<p><span class='label label-error'>Error</span> Baïkal has not been upgraded. See the section 'Errors' for details.</p>";
		} else {
			$sHtml .= "<p>Baïkal has been successfully upgraded. You may now <a class='btn btn-success' href='" . PROJECT_URI . "admin/'>Access the Baïkal admin</a></p>";
		}
		
		return $sHtml;
	}
	
	protected function upgrade($sVersionFrom, $sVersionTo) {
		
		if($sVersionFrom === "0.2.0" && $sVersionTo === "0.2.1") {
			
			$sOldDbFilePath = PROJECT_PATH_SPECIFIC . "Db/.ht.db.sqlite";
			
			if(PROJECT_SQLITE_FILE === $sOldDbFilePath) {
				$sNewDbFilePath = PROJECT_PATH_SPECIFIC . "Db/db.sqlite";
				
				# Move old db from Specific/Db/.ht.db.sqlite to Specific/Db/db.sqlite
				if(!file_exists($sNewDbFilePath)) {
					if(!is_writable(dirname($sNewDbFilePath))) {
						$this->aErrors[] = "DB file path '" . dirname($sNewDbFilePath) . "' is not writable";
						return FALSE;
					}
					
					if(!@copy($sOldDbFilePath, $sNewDbFilePath)) {
						$this->aErrors[] = "DB could not be copied from '" . $sOldDbFilePath . "' to '" . $sNewDbFilePath . "'.";
						return FALSE;
					}
					
					$this->aSuccess[] = "SQLite database has been renamed from '" . $sOldDbFilePath . "' to '" . $sNewDbFilePath . "'";
				}
			}
		}
		
		$this->updateConfiguredVersion($sVersionTo);
		return TRUE;
	}
	
	protected function updateConfiguredVersion($sVersionTo) {
		$oConfig = new \Baikal\Model\Config\System(PROJECT_PATH_SPECIFIC . "config.system.php");
		$oConfig->set("BAIKAL_CONFIGURED_VERSION", $sVersionTo);
		$oConfig->persist();
	}
}