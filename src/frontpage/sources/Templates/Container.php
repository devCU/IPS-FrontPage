<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       FrontPage Template Model
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4+
 * @subpackage	FrontPage
 * @version     1.0.0
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     04 MAY 2019
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

namespace IPS\frontpage\Templates;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Model
 */
class _Container extends \IPS\Patterns\ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons = array();
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'container_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Table
	 */
	public static $databaseTable = 'frontpage_containers';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields = array( 'container_key' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static $multitonMap	= array();
	
	/**
	 * @brief	Have fetched all?
	 */
	protected static $gotAll = FALSE;
	
	/**
	 * Return all containers
	 *
	 * @return	array
	 */
	public static function containers()
	{
		if ( ! static::$gotAll )
		{
			foreach( \IPS\Db::i()->select( '*', static::$databaseTable ) as $container )
			{
				static::$multitons[ $container['container_id'] ] = static::constructFromData( $container );
			}
			
			static::$gotAll = true;
		}
		
		return static::$multitons;
	}
	
	/**
	 * Get all containers by type
	 * 
	 * @param string $type		Type of container (template_block, page, etc)
	 * @return array	of Container objects
	 */
	public static function getByType( $type )
	{
		$return = array();
		static::containers();
		
		if ( $type === 'database' )
		{
			$type = 'dbtemplate';
		}
		
		foreach( static::$multitons as $id => $obj )
		{
			if ( $obj->type === $type )
			{
				$return[] = $obj;
			}
		}
		
		return $return;
	}
	
	/**
	 * Add a new container
	 *
	 * @param	array	$container	Template Data
	 * @return	object	\IPS\frontpage\Templates
	 */
	public static function add( $container )
	{
		$newContainer = new static;
		$newContainer->_new = TRUE;
		$newContainer->save();
	
		/* Create a unique key */
		if ( empty( $newContainer->key ) )
		{
			$newContainer->key = 'template__' . \IPS\Http\Url\Friendly::seoTitle( $newContainer->name ) . '.' . $newContainer->id;
			$newContainer->save();
		}
		
		return $newContainer;
	}
}