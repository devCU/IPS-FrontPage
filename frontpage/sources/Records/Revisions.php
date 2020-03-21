<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		Revisions Model
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4+
 * @subpackage	FrontPage
 * @version     1.0.4 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     21 MAR 2020
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

namespace IPS\frontpage\Records;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Records Model
 */
class _Revisions extends \IPS\Patterns\ActiveRecord
{
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons = array();
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'frontpage_database_revisions';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'revision_';

	/**
	 * @brief	Unpacked data
	 */
	protected $_dataJson = NULL;
	
	/**
	 * Constructor - Create a blank object with default values
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		
		if ( $this->_new )
		{
			$this->member_id = \IPS\Member::loggedIn()->member_id;
			$this->date      = time();
		}
	}
	
	/**
	 * Get a value by key
	 * 
	 * @param   string $key	Key of value to return
	 * @return	mixed
	 */
	public function get( $key )
	{
		if ( $this->_dataJson === NULL )
		{
			$this->_dataJson = $this->data;
		}
		
		if ( isset( $this->_dataJson[ $key ] ) )
		{
			return $this->_dataJson[ $key ];
		}
		
		return NULL;
	}

	/**
	 *  Compute differences
	 *
	 * @param   int                 $databaseId     Database ID
	 * @param   \IPS\frontpage\Records    $record         Record
	 * @param   boolean             $justChanged    Get changed only
	 * @return array
	 */
	public function getDiffHtmlTables( $databaseId, $record, $justChanged=FALSE )
	{
		$fieldsClass  = 'IPS\frontpage\Fields' .  $databaseId;
		$customFields = $fieldsClass::data( 'view' );
		$conflicts    = array();

		/* Build up our data set */
		foreach( $customFields as $id => $field )
		{
			$key = 'field_' . $field->id;

			if( $justChanged === FALSE OR !\IPS\Login::compareHashes( md5( $record->$key ), md5( $this->get( $key ) ) ) )
			{
				$conflicts[] = array( 'original' => $this->get( $key ), 'current' => $record->$key, 'field' => $field );
			}
		}

		return $conflicts;
	}

	/**
	 * Set the "data" field
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set_data( $value )
	{
		$this->_data['data'] = ( \is_array( $value ) ? json_encode( $value ) : $value );
	}
	
	/**
	 * Get the "data" field
	 *
	 * @return array
	 */
	public function get_data()
	{
		return json_decode( $this->_data['data'], TRUE );
	}
	
	
}