//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class frontpage_hook_DisplaySettingsTab extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return array_merge_recursive( array (
  'settings' => 
  array (
    0 => 
    array (
      'selector' => '#elSettingsTabs > div.ipsColumns.ipsColumns_collapsePhone.ipsColumns_bothSpacing > div.ipsColumn.ipsColumn_wide > div.ipsSideMenu > ul.ipsSideMenu_list',
      'type' => 'add_inside_end',
      'content' => '{template="accountDisplaySettings" group="system" location="front" app="frontpage" params="\'tab\', $tab"}',
    ),
  ),
  'settingsOverview' => 
  array (
    0 => 
    array (
      'selector' => 'div.ipsColumns.ipsColumns_collapsePhone > div.ipsColumn.ipsColumn_wide.ipsAreaBackground_light > div.ipsPad > ul.ipsList.ipsList_reset.ipsType_medium',
      'type' => 'add_inside_end',
      'content' => '{template="accountDisplaySettings" group="system" location="front" app="frontpage" params="\'overview\'"}',
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */


}