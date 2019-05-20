<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		FrontPage Database Records API
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

namespace IPS\frontpage\api;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Database Records API
 */
class _records extends \IPS\Content\Api\ItemController
{
	/**
	 * Class
	 */
	protected $class = NULL;
	
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
	 * GET /frontpage/records/{database_id}
	 * Get list of records
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, only records the authorized user can view will be included
	 * @param		int		$database			Database ID
	 * @apiparam	string	categories			Comma-delimited list of category IDs
	 * @apiparam	string	authors				Comma-delimited list of member IDs - if provided, only records started by those members are returned
	 * @apiparam	int		locked				If 1, only records which are locked are returned, if 0 only unlocked
	 * @apiparam	int		hidden				If 1, only records which are hidden are returned, if 0 only not hidden
	 * @apiparam	int		pinned				If 1, only records which are pinned are returned, if 0 only not pinned
	 * @apiparam	int		featured			If 1, only records which are featured are returned, if 0 only not featured
	 * @apiparam	string	sortBy				What to sort by. Can be 'date' for creation date, 'title' or leave unspecified for ID
	 * @apiparam	string	sortDir				Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page				Page number
	 * @apiparam	int		perPage				Number of results per page - defaults to 25
	 * @throws		2T306/1	INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @return		\IPS\Api\PaginatedResponse<IPS\frontpage\Records>
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
			$this->class = 'IPS\frontpage\Records' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T306/1', 404 );
		}	
		
		/* Where clause */
		$where = array();
		
		/* Return */
		return $this->_list( $where, 'categories' );
	}
	
	/**
	 * GET /frontpage/records/{database_id}/{record_id}
	 * View details about a specific record
	 *
	 * @param		int		$database		Database ID Number
	 * @param		int		$record			Record ID Number
	 * @throws		2T306/2	INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T306/3	INVALID_ID			The record ID does not exist or the authorized user does not have permission to view it
	 * @return		\IPS\frontpage\Records
	 */
	public function GETitem( $database, $record )
	{
		/* Load database */
		try
		{
			$database = \IPS\frontpage\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			
			$this->class = 'IPS\frontpage\Records' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T306/2', 404 );
		}
		
		/* Return */
		try
		{
			$className = $this->class;
			$record = $className::load( $record );
			if ( $this->member and !$record->can( 'read', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			
			return new \IPS\Api\Response( 200, $record->apiOutput( $this->member ) );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2T306/3', 404 );
		}
	}
	
	/**
	 * POST /frontpage/records/{database_id}
	 * Create a record
	 *
	 * @note	For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, locked will only be honoured if the authentictaed user has permission to lock records).
	 * @param		int					$database			Database ID Number
	 * @reqapiparam	int					category			The ID number of the category the record should be created in. If the database does not use categories, this is not required
	 * @reqapiparam	int					author				The ID number of the member creating the record (0 for guest) Required for requests made using an API Key or the Client Credentials Grant Type. For requests using an OAuth Access Token for a particular member, that member will always be the author
	 * @reqapiparam	object				fields				Field values. Keys should be the field ID, and the value should be the value. For requests using an OAuth Access Token for a particular member, values will be sanatised where necessary. For requests made using an API Key or the Client Credentials Grant Type values will be saved unchanged.
	 * @apiparam	string				prefix				Prefix tag
	 * @apiparam	string				tags				Comma-separated list of tags (do not include prefix)
	 * @apiparam	datetime			date				The date/time that should be used for the record date. If not provided, will use the current date/time. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	string				ip_address			The IP address that should be stored for the record. If not provided, will use the IP address from the API request. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	int					locked				1/0 indicating if the record should be locked
	 * @apiparam	int					hidden				0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	int					pinned				1/0 indicating if the record should be pinned
	 * @apiparam	int					featured			1/0 indicating if the record should be featured
	 * @throws		2T306/4				INVALID_DATABASE	The database ID does not exist
	 * @throws		1T306/5				NO_CATEGORY			The category ID does not exist
	 * @throws		1T306/6				NO_AUTHOR			The author ID does not exist
	 * @throws		2T306/G				NO_PERMISSION		The authorized user does not have permission to create a record in that category
	 * @return		\IPS\frontpage\Records
	 */
	public function POSTindex( $database )
	{
		/* Load database */
		try
		{
			$database = \IPS\frontpage\Databases::load( $database );
			$this->class = 'IPS\frontpage\Records' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T306/4', 404 );
		}
				
		/* Get category */
		try
		{
			$categoryClass = 'IPS\frontpage\Categories' . $database->id;

			if ( $database->use_categories )
			{
				$category = $categoryClass::load( \IPS\Request::i()->category );
			}
			else
			{
				$category = $categoryClass::load( $database->default_category );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'NO_CATEGORY', '1T306/5', 400 );
		}
		
		/* Get author */
		if ( $this->member )
		{
			if ( !$category->can( 'add', $this->member ) )
			{
				throw new \IPS\Api\Exception( 'NO_PERMISSION', '2T306/G', 403 );
			}
			$author = $this->member;
		}
		else
		{
			if ( \IPS\Request::i()->author )
			{
				$author = \IPS\Member::load( \IPS\Request::i()->author );
				if ( !$author->member_id )
				{
					throw new \IPS\Api\Exception( 'NO_AUTHOR', '1T306/6', 400 );
				}
			}
			else
			{
				$author = new \IPS\Member;
			}
		}

		$record = $this->_create( $category, $author );

		/* Sync Topic */
		$class = $this->class;
		if ( !$class::$skipTopicCreation and \IPS\Application::appIsEnabled('forums') and $record->_forum_record and $record->_forum_forum and ! $record->hidden() and ! $record->record_future_date )
		{
			try
			{
				$record->syncTopic();
			}
			catch( \Exception $ex ) { }
		}

		/* Do it */
		return new \IPS\Api\Response( 201, $record->apiOutput( $this->member ) );
	}
	
	/**
	 * POST /frontpage/records/{database_id}/{record_id}
	 * Edit a record
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, locked will only be honoured if the authentictaed user has permission to lock topics).
	 * @param		int					$database		Database ID Number
	 * @param		int					$record			Record ID Number
	 * @apiparam	int					category		The ID number of the category the record should be created in. If the database does not use categories, this is not required
	 * @apiparam	int					author			The ID number of the member creating the record (0 for guest). Ignored for requests using an OAuth Access Token for a particular member.
	 * @reqapiparam	object				fields				Field values. Keys should be the field ID, and the value should be the value. For requests using an OAuth Access Token for a particular member, values will be sanatised where necessary. For requests made using an API Key or the Client Credentials Grant Type values will be saved unchanged.
	 * @apiparam	string				prefix			Prefix tag
	 * @apiparam	string				tags			Comma-separated list of tags (do not include prefix)
	 * @apiparam	datetime			date			The date/time that should be used for the record date. If not provided, will use the current date/time. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	string				ip_address		The IP address that should be stored for the record. If not provided, will use the IP address from the API request. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	int					locked			1/0 indicating if the record should be locked
	 * @apiparam	int					hidden			0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	int					pinned			1/0 indicating if the record should be pinned
	 * @apiparam	int					featured		1/0 indicating if the record should be featured
	 * @throws		2T306/9				INVALID_DATABASE	The database ID does not exist
	 * @throws		2T306/6				INVALID_ID		The record ID is invalid or the authorized user does not have permission to view it
	 * @throws		1T306/7				NO_CATEGORY		The category ID does not exist or the authorized user does not have permission to post in it
	 * @throws		1T306/8				NO_AUTHOR		The author ID does not exist
	 * @throws		2T306/H				NO_PERMISSION	The authorized user does not have permission to edit the record
	 * @return		\IPS\frontpage\Records
	 */
	public function POSTitem( $database, $record )
	{
		/* Load database */
		try
		{
			$database = \IPS\frontpage\Databases::load( $database );
			$this->class = 'IPS\frontpage\Records' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T306/9', 404 );
		}
		
		/* Load record */
		try
		{
			$className = $this->class;
			$record = $className::load( $record );
			if ( $this->member and !$record->can( 'read', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2T306/6', 404 );
		}
		if ( $this->member and !$record->canEdit( $this->member ) )
		{
			throw new \IPS\Api\Exception( 'NO_PERMISSION', '2T306/H', 403 );
		}
			
		/* New category */
		if ( $database->use_categories and isset( \IPS\Request::i()->category ) and \IPS\Request::i()->category != $record->category_id and ( !$this->member or $record->canMove( $this->member ) ) )
		{
			try
			{
				$categoryClass = 'IPS\frontpage\Categories' . $database->id;

				$newCategory = $categoryClass::load( \IPS\Request::i()->category );
				if ( $this->member and !$newCategory->can( 'add', $this->member ) )
				{
					throw new \OutOfRangeException;
				}
				
				$record->move( $newCategory );
			}
			catch ( \OutOfRangeException $e )
			{
				throw new \IPS\Api\Exception( 'NO_CATEGORY', '1T306/7', 400 );
			}
		}
		
		/* New author */
		if ( !$this->member and isset( \IPS\Request::i()->author ) )
		{				
			try
			{
				$member = \IPS\Member::load( \IPS\Request::i()->author );
				if ( !$member->member_id )
				{
					throw new \OutOfRangeException;
				}
				
				$record->changeAuthor( $member );
			}
			catch ( \OutOfRangeException $e )
			{
				throw new \IPS\Api\Exception( 'NO_AUTHOR', '1T306/8', 400 );
			}
		}
		
		/* Everything else */
		$this->_createOrUpdate( $record, 'edit' );
		
		/* Save and return */
		$record->save();

		/* Sync Topic */
		$class = $this->class;
		if ( !$class::$skipTopicCreation and \IPS\Application::appIsEnabled('forums') and $record->_forum_record and $record->_forum_forum and ! $record->hidden() and ! $record->record_future_date )
		{
			try
			{
				$record->syncTopic();
			}
			catch( \Exception $ex ) { }
		}

		return new \IPS\Api\Response( 200, $record->apiOutput( $this->member ) );
	}

	/**
	 * Create or update record
	 *
	 * @param	\IPS\Content\Item	$item	The item
	 * @param	string				$type	add or edit
	 * @return	\IPS\Content\Item
	 */
	protected function _createOrUpdate( \IPS\Content\Item $item, $type='add' )
	{
		/* Set field values */
		if ( isset( \IPS\Request::i()->fields ) )
		{
			$fieldsClass = str_replace( 'Records', 'Fields', \get_class( $item ) );
			foreach ( $fieldsClass::data() as $key => $field )
			{
				if ( isset( \IPS\Request::i()->fields[ $field->id ] ) )
				{
					if ( !$this->member or $field->can( $type, $this->member ) )
					{
						$key = "field_{$field->_id}";
						
						$value = \IPS\Request::i()->fields[ $field->id ];
						if ( $field->type === 'Editor' and $this->member )
						{
							$value = \IPS\Text\Parser::parseStatic( $value, TRUE, NULL, $this->member, 'frontpage_Records' );
						}
						
						$item->$key = $value;
					}
				}
			}
		}
		
		/* Pass up */
		return parent::_createOrUpdate( $item, $type );
	}
	
	/**
	 * GET /frontpage/records/{database_id}/{record_id}/comments
	 * Get comments on a record
	 *
	 * @param		int					$database		Database ID Number
	 * @param		int					$record			Record ID Number
	 * @apiparam	int		hidden		If 1, only comments which are hidden are returned, if 0 only not hidden
	 * @apiparam	string	sortDir		Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @throws		2T306/C		INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T306/D		INVALID_ID	The entry ID does not exist or the authorized user does not have permission to view it
	 * @return		\IPS\Api\PaginatedResponse<IPS\frontpage\Records\Comment>
	 */
	public function GETitem_comments( $database, $record )
	{
		/* Load database */
		try
		{
			$database = \IPS\frontpage\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			
			$this->class = 'IPS\frontpage\Records' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T306/C', 404 );
		}
		
		/* Return */
		try
		{
			return $this->_comments( $record, 'IPS\frontpage\Records\Comment' . $database->id );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2T306/D', 404 );
		}
	}
	
	/**
	 * GET /frontpage/records/{database_id}/{record_id}/reviews
	 * Get reviews on a record
	 *
	 * @param		int					$database		Database ID Number
	 * @param		int					$record			Record ID Number
	 * @apiparam	int		hidden		If 1, only comments which are hidden are returned, if 0 only not hidden
	 * @apiparam	string	sortDir		Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @throws		2T306/E		INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T306/F		INVALID_ID	The entry ID does not exist or the authorized user does not have permission to view it
	 * @return		\IPS\Api\PaginatedResponse<IPS\frontpage\Records\Review>
	 */
	public function GETitem_reviews( $database, $record )
	{
		/* Load database */
		try
		{
			$database = \IPS\frontpage\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			
			$this->class = 'IPS\frontpage\Records' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T306/E', 404 );
		}
		
		/* Return */
		try
		{
			return $this->_comments( $record, 'IPS\frontpage\Records\Review' . $database->id );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2T306/F', 404 );
		}
	}
		
	/**
	 * DELETE /frontpage/records/{database_id}/{record_id}
	 * Delete an entry
	 *
	 * @param		int			$database		Database ID Number
	 * @param		int			$record			Record ID Number
	 * @throws		2T306/A		INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T306/B		INVALID_ID			The entry ID does not exist
	 * @throws		2T306/I		NO_PERMISSION		The authorized user does not have permission to delete the record.
	 * @return		void
	 */
	public function DELETEitem( $database, $record )
	{
		/* Load database */
		try
		{
			$database = \IPS\frontpage\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			
			$this->class = 'IPS\frontpage\Records' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T306/A', 404 );
		}
		
		/* Load record */
		try
		{
			$className = $this->class;
			$record = $className::load( $record );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2T306/B', 404 );
		}
		if ( $this->member and !$record->canDelete( $this->member ) )
		{
			throw new \IPS\Api\Exception( 'NO_PERMISSION', '2T306/I', 404 );
		}
		
		/* Delete and return */
		$record->delete();
		return new \IPS\Api\Response( 200, NULL );
	}
}