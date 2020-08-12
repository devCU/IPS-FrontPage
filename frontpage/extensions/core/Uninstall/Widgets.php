<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief		Uninstall callback
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4.10 FINAL
 * @subpackage	FrontPage
 * @version     1.0.5 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     12 AUG 2020
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

namespace IPS\frontpage\extensions\core\Uninstall;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Uninstall callback
 */
class _Widgets
{
	/**
	 * Code to execute before the application has been uninstalled
	 *
	 * @param	string	$application	Application directory
	 * @return	array
	 */
	public function preUninstall( $application )
	{
	}

	/**
	 * Code to execute after the application has been uninstalled
	 *
	 * @param	string	$application	Application directory
	 * @return	array
	 */
	public function postUninstall( $application )
	{
	}

	/**
	 * Code to execute when other applications are uninstalled
	 *
	 * @param	string	$application	Application directory
	 * @return	void
	 * @deprecated	This is here for backwards-compatibility - all new code should go in onOtherUninstall
	 */
	public function onOtherAppUninstall( $application )
	{
		return $this->onOtherUninstall( $application );
	}

	/**
	 * Code to execute when other applications or plugins are uninstalled
	 *
	 * @param	string	$application	Application directory
	 * @param	int		$plugin			Plugin ID
	 * @return	void
	 */
	public function onOtherUninstall( $application=NULL, $plugin=NULL )
	{
		/* clean up widget areas table */
		foreach ( \IPS\Db::i()->select( '*', 'frontpage_fpage_widget_areas' ) as $row )
		{
			$data = json_decode( $row['area_widgets'], true );
			$deleted = false;
			foreach ( $data as $key => $widget )
			{
				if( $application !== NULL )
				{
					if ( isset( $widget['app'] ) and $widget['app'] == $application )
					{
						$deleted = true;
						unset( $data[$key] );
					}
				}

				if( $plugin !== NULL )
				{
					if ( isset( $widget['plugin'] ) and $widget['plugin'] == $plugin )
					{
						$deleted = true;
						unset( $data[$key] );
					}
				}
			}
			
			if ( $deleted === true )
			{
				\IPS\Db::i()->update( 'frontpage_fpage_widget_areas', array( 'area_widgets' => json_encode( $data ) ), array( 'area_fpage_id=? AND area_area=?', $row['area_fpage_id'], $row['area_area'] ) );
			}
		}
	}
}