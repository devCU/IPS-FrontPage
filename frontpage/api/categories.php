<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.devcu.com/donate
 *
 * @brief		FrontPage Database Categories API
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.5x
 * @subpackage	FrontPage
 * @version     1.0.5 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     14 OCT 2020
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

namespace IPS\frontpage\api;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Pages Databases API
 */
class _categories extends \IPS\Node\Api\NodeController
{
	/**
	 * Class
	 */
	protected $class;
	
	/**
	 * Get endpoint data
	 *
	 * @param	array	$pathBits	The parts to the path called
	 * @param	string	$method		HTTP method verb
	 * @return	array
	 * @throws	\RuntimeException
	 */
	protected function _getEndpoint( $pathBits, $method = 'GET' )
	{
		if ( !\count( $pathBits ) )
		{
			throw new \RuntimeException;
		}
		
		$database = array_shift( $pathBits );
		if ( !\count( $pathBits ) )
		{
			return array( 'endpoint' => 'index', 'params' => array( $database ) );
		}
		
		$nextBit = array_shift( $pathBits );
		if ( \intval( $nextBit ) != 0 )
		{
			if ( \count( $pathBits ) )
			{
				return array( 'endpoint' => 'item_' . array_shift( $pathBits ), 'params' => array( $database, $nextBit ) );
			}
			else
			{				
				return array( 'endpoint' => 'item', 'params' => array( $database, $nextBit ) );
			}
		}
				
		throw new \RuntimeException;
	}

	/**
	 * GET /frontpage/categories/{database_id}
	 * Get list of database categories
	 *
	 * @param		int		$database			Database ID
	 * @return		\IPS\Api\PaginatedResponse<IPS\frontpage\Categories>
	 * @throws		2T306/1	INVALID_DATABASE							The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T306/2	DATABASE_DOES_NOT_USE_CATEGORIES		The database does not use categories
	 */
	public function GETindex( $database )
	{
		/* Load database */
		try
		{
			$database = \IPS\frontpage\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}	
			$this->class = 'IPS\frontpage\Categories' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T415/1', 404 );
		}	
		if ( !$database->use_categories )
		{
			throw new \IPS\Api\Exception( 'DATABASE_DOES_NOT_USE_CATEGORIES', '2T415/2', 404 );
		}
		
		/* Where clause */
		$where = array( 'category_database_id=?', $database->id );
		
		/* Return */
		return $this->_list( $where );
	}

	/**
	 * GET /frontpage/databases/{database_id}/{category_id}
	 * Get specific database
	 *
	 * @param		int		$id			ID Number
	 * @return		\IPS\frontpage\Categories
	 * @throws		2T306/3	INVALID_DATABASE		The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T306/4	INVALID_ID			The category ID does not exist or the authorized user does not have permission to view it
	 */
	public function GETitem( $database, $id )
	{
		/* Load database */
		try
		{
			$database = \IPS\frontpage\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}	
			$this->class = 'IPS\frontpage\Categories' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T415/3', 404 );
		}	
		
		/* Return */
		try
		{
			return $this->_view( $id );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2T415/4', 404 );
		}
	}
}