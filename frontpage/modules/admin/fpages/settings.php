<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       FrontPage General Settings Controller
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4+
 * @subpackage	FrontPage
 * @version     1.0.0 RC
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     22 MAY 2019
 *
 *                    GNU General Public License v3.0
 *    This program is free software: you can redistribute it and/or modify       
 *    it under the terms of the GNU General Public License as published by       
 *    the Free Software Foundation, either version 3 of the License, or          
 *    (at your option) any later version.                                        
 *                                                                               
 *    This program is distributed in the hope that it will be useful,            
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of             
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *                                                                               
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see http://www.gnu.org/licenses/
 */

namespace IPS\frontpage\modules\admin\fpages;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Settings
 */
class _settings extends \IPS\Dispatcher\Controller
{
	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'settings_manage' );

		$form = $this->_manageSettings();

		if ( $values = $form->values( TRUE ) )
		{
			$this->saveSettingsForm( $form, $values );

			/* Clear guest content caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__frontpage_settings' );
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('head_frontpage_settings');
		\IPS\Output::i()->output = $form;
	}

	/**
	 * Settings
	 *
	 * @return	void
	 */
	protected function _manageSettings()
	{

		$form = new \IPS\Helpers\Form;
		$form->addTab('head_frontpage_settings');
		$form->addHeader('head_frontpage_settings');
		$form->addSeparator();
		$form->add( new \IPS\Helpers\Form\YesNo( 'frontpage_enable', \IPS\Settings::i()->frontpage_enable, FALSE, array( 'togglesOn' => array( 'frontpage_groups_id' ) ), NULL, NULL, NULL, 'frontpage_enable') );
		$form->add( new \IPS\Helpers\Form\Select( 'frontpage_groups', \IPS\Settings::i()->frontpage_groups == 'all' ? "all" : explode( ',' , \IPS\Settings::i()->frontpage_groups ), FALSE, array( 'options' => \IPS\Member\Group::groups(TRUE, TRUE), 'parse' => 'normal', 'multiple' => TRUE, 'unlimited' => 'all', 'unlimitedLang' => 'all_groups' ), NULL, NULL, NULL, 'frontpage_groups_id' ) );


		$form->addTab('head_frontpage_settings_custom');
		$form->addHeader('head_frontpage_settings_custom');
		$form->addSeparator();
		$form->add( new \IPS\Helpers\Form\Text( 'frontpage_application_name', \IPS\Settings::i()->frontpage_application_name, FALSE, array(), NULL, NULL, NULL, 'frontpage_application_name' ) );

		/* Save values - Nexus values refactored */
		if ( $values = $form->values() )
		{

			$form->saveAsSettings( $values );

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=frontpage&module=dashboard&controller=settings' ), 'saved' );
		}

		return $form;
	}

}