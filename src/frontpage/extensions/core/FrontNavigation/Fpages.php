<?php
/**
 * @brief		Front Navigation Extension: Frontpage
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4+
 * @subpackage	FrontPage
 * @version     1.0.0
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     20 MAY 2019
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

namespace IPS\frontpage\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Contents
 */
class _Fpages extends \IPS\core\FrontNavigation\FrontNavigationAbstract
{
	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle()
	{
		return \IPS\Member::loggedIn()->language()->addToStack('menu_content_fpage');
	}
	
	/**
	 * Allow multiple instances?
	 *
	 * @return	bool
	 */
	public static function allowMultiple()
	{
		return TRUE;
	}
	
	/**
	 * Get configuration fields
	 *
	 * @param	array	$existingConfiguration	The existing configuration, if editing an existing item
	 * @param	int		$id						The ID number of the existing item, if editing
	 * @return	array
	 */
	public static function configuration( $existingConfiguration, $id = NULL )
	{
		$fpages = array();
		foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'frontpage_fpages' ), 'IPS\frontpage\Fpages\Fpage' ) as $fpage )
		{
			$fpages[ $fpage->id ] = $fpage->full_path;
		}
		
		return array(
			new \IPS\Helpers\Form\Select( 'menu_content_fpage', isset( $existingConfiguration['menu_content_fpage'] ) ? $existingConfiguration['menu_content_fpage'] : NULL, NULL, array( 'options' => $contents ), NULL, NULL, NULL, 'menu_content_fpage' ),
			new \IPS\Helpers\Form\Radio( 'menu_title_fpage_type', isset( $existingConfiguration['menu_title_fpage_type'] ) ? $existingConfiguration['menu_title_fpage_type'] : 0, NULL, array( 'options' => array( 0 => 'menu_title_fpage_inherit', 1 => 'menu_title_fpage_custom' ), 'toggles' => array( 1 => array( 'menu_title_fpage' ) ) ), NULL, NULL, NULL, 'menu_title_fpage_type' ),
			new \IPS\Helpers\Form\Translatable( 'menu_title_fpage', NULL, NULL, array( 'app' => 'frontpage', 'key' => $id ? "frontpage_menu_title_{$id}" : NULL ), NULL, NULL, NULL, 'menu_title_fpage' ),
		);
	}
	
	/**
	 * Parse configuration fields
	 *
	 * @param	array	$configuration	The values received from the form
	 * @param	int		$id				The ID number of the existing item, if editing
	 * @return	array
	 */
	public static function parseConfiguration( $configuration, $id )
	{
		if ( $configuration['menu_title_fpage_type'] )
		{
			\IPS\Lang::saveCustom( 'frontpage', "frontpage_menu_title_{$id}", $configuration['menu_title_fpage'] );
		}
		else
		{
			\IPS\Lang::deleteCustom( 'frontpage', "frontpage_menu_title_{$id}" );
		}
		
		unset( $configuration['menu_title_fpage'] );
		
		return $configuration;
	}
		
	/**
	 * Can access?
	 *
	 * @return	bool
	 */
	public function canView()
	{
		if ( $this->permissions )
		{
			if ( $this->permissions != '*' )
			{
				return \IPS\Member::loggedIn()->inGroup( explode( ',', $this->permissions ) );
			}
			
			return TRUE;
		}
		
		/* Inherit from content */
		$store = \IPS\frontpage\Fpages\Fpage::getStore();

		if ( isset( $store[ $this->configuration['menu_content_fpage'] ] ) )
		{
			if ( $store[ $this->configuration['menu_content_fpage'] ]['perm'] != '*' )
			{
				return \IPS\Member::loggedIn()->inGroup( explode( ',', $store[ $this->configuration['menu_content_fpage'] ]['perm'] ) );
			}
			
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public function title()
	{
		if ( $this->configuration['menu_title_fpage_type'] )
		{
			return \IPS\Member::loggedIn()->language()->addToStack( "frontpage_menu_title_{$this->id}" );
		}
		else
		{
			return \IPS\Member::loggedIn()->language()->addToStack( "frontpage_fpage_{$this->configuration['menu_content_fpage']}" );
		}
	}
	
	/**
	 * Get Link
	 *
	 * @return	\IPS\Http\Url
	 */
	public function link()
	{
		$store = \IPS\frontpage\Fpages\Fpage::getStore();
		
		if ( isset( $store[ $this->configuration['menu_content_fpage'] ] ) )
		{
			return $store[ $this->configuration['menu_content_fpage'] ]['url'];
		}
		
		/* Fall back here */
		return \IPS\frontpage\Fpages\Fpage::load( $this->configuration['menu_content_content'] )->url();
	}
	
	/**
	 * Is Active?
	 *
	 * @return	bool
	 */
	public function active()
	{
		return ( \IPS\frontpage\Fpages\Fpage::$currentFpage and \IPS\frontpage\Fpages\Fpage::$currentFpage->id == $this->configuration['menu_content_fpage'] );
	}
	
	/**
	 * Children
	 *
	 * @param	bool	$noStore	If true, will skip datastore and get from DB (used for ACP preview)
	 * @return	array
	 */
	public function children( $noStore=FALSE )
	{
		return array();
	}
}