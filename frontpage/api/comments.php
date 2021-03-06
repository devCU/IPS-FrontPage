<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.devcu.com/donate
 *
 * @brief		FrontPage Database Comments API
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
 * @brief	FrontPage Database Comments API
 */
class _comments extends \IPS\Content\Api\CommentController
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
	 * GET /frontpage/comments/{database_id}
	 * Get list of comments
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, only comments the authorized user can view will be included
	 * @param		int		$database			Database ID
	 * @apiparam	string	categories			Comma-delimited list of category IDs
	 * @apiparam	string	authors				Comma-delimited list of member IDs - if provided, only topics started by those members are returned
	 * @apiparam	int		locked				If 1, only comments from events which are locked are returned, if 0 only unlocked
	 * @apiparam	int		hidden				If 1, only comments which are hidden are returned, if 0 only not hidden
	 * @apiparam	int		featured			If 1, only comments from  events which are featured are returned, if 0 only not featured
	 * @apiparam	string	sortBy				What to sort by. Can be 'date', 'title' or leave unspecified for ID
	 * @apiparam	string	sortDir				Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page				Page number
	 * @apiparam	int		perPage				Number of results per page - defaults to 25
	 * @throws		2T311/1	INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @return		\IPS\Api\PaginatedResponse<IPS\frontpage\Records\Comment>
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
			$this->class = 'IPS\frontpage\Records\Comment' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T311/1', 404 );
		}	
		
		/* Return */
		return $this->_list( array( array( 'comment_database_id=?', $database->id ) ), 'categories' );
	}
	
	/**
	 * GET /frontpage/comments/{database_id}/{id}
	 * View information about a specific comment
	 *
	 * @param		int		$database			Database ID
	 * @param		int		$comment			Comment ID
	 * @throws		2T311/1	INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T311/3	INVALID_ID	The comment ID does not exist or the authorized user does not have permission to view it
	 * @return		\IPS\frontpage\Records\Comment
	 */
	public function GETitem( $database, $comment )
	{
		/* Load database */
		try
		{
			$database = \IPS\frontpage\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T311/2', 404 );
		}	
		
		/* Return */
		try
		{
			$class = 'IPS\frontpage\Records\Comment' . $database->id;
			if ( $this->member )
			{
				$object = $class::loadAndCheckPerms( $comment, $this->member );
			}
			else
			{
				$object = $class::load( $comment );
			}
			
			return new \IPS\Api\Response( 200, $object->apiOutput( $this->member ) );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2T311/3', 404 );
		}
	}
	
	/**
	 * POST /frontpage/comments/{database_id}
	 * Create a comment
	 *
	 * @note	For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, hidden will only be honoured if the authenticated user has permission to hide content).
	 * @param		int			$database			Database ID
	 * @reqapiparam	int			record				The ID number of the record the comment is for
	 * @reqapiparam	int			author				The ID number of the member making the comment (0 for guest). Required for requests made using an API Key or the Client Credentials Grant Type. For requests using an OAuth Access Token for a particular member, that member will always be the author
	 * @apiparam	string		author_name			If author is 0, the guest name that should be used
	 * @reqapiparam	string		content				The comment content as HTML (e.g. "<p>This is a comment.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type. 
	 * @apiparam	datetime	date				The date/time that should be used for the comment date. If not provided, will use the current date/time. Ignored for requests using an OAuth Access Token for a particular member
	 * @apiparam	string		ip_address			The IP address that should be stored for the comment. If not provided, will use the IP address from the API request. Ignored for requests using an OAuth Access Token for a particular member
	 * @apiparam	int			hidden				0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @throws		2T311/4		INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T311/5		INVALID_ID			The comment ID does not exist
	 * @throws		1T311/6		NO_AUTHOR			The author ID does not exist
	 * @throws		1T311/7		NO_CONTENT			No content was supplied
	 * @throws		2T311/D		NO_PERMISSION		The authorized user does not have permission to comment on that record
	 * @return		\IPS\frontpage\Records\Comment
	 */
	public function POSTindex( $database )
	{
		/* Load database */
		try
		{
			$database = \IPS\frontpage\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			$this->class = 'IPS\frontpage\Records\Comment' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T311/4', 404 );
		}	
		
		/* Get record */
		try
		{
			$recordClass = 'IPS\frontpage\Records' . $database->id;
			$record = $recordClass::load( \IPS\Request::i()->record );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2T311/5', 403 );
		}
		
		/* Get author */
		if ( $this->member )
		{
			if ( !$record->canComment( $this->member ) )
			{
				throw new \IPS\Api\Exception( 'NO_PERMISSION', '2T311/D', 403 );
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
					throw new \IPS\Api\Exception( 'NO_AUTHOR', '1T311/6', 404 );
				}
			}
			else
			{
				$author = new \IPS\Member;
				$author->name = \IPS\Request::i()->author_name;
			}
		}
		
		/* Check we have a post */
		if ( !\IPS\Request::i()->content )
		{
			throw new \IPS\Api\Exception( 'NO_CONTENT', '1T311/7', 403 );
		}
		
		/* Do it */
		return $this->_create( $record, $author );
	}
	
	/**
	 * POST /frontpage/comments/{database_id}/{comment_id}
	 * Edit a comment
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, hidden will only be honoured if the authenticated user has permission to hide content).
	 * @param		int			$database		Database ID
	 * @param		int			$comment		Comment ID
	 * @apiparam	int			author			The ID number of the member making the comment (0 for guest). Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	string		author_name		If author is 0, the guest name that should be used
	 * @apiparam	string		content			The comment content as HTML (e.g. "<p>This is a comment.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type. 
	 * @apiparam	int			hidden				1/0 indicating if the topic should be hidden
	 * @throws		2T311/7		INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T311/8		INVALID_ID			The comment ID does not exist or the authorized user does not have permission to view it
	 * @throws		1T311/9		NO_AUTHOR			The author ID does not exist
	 * @throws		2T311/E		NO_PERMISSION		The authorized user does not have permission to edit the comment
	 * @return		\IPS\frontpage\Records\Comment
	 */
	public function POSTitem( $database, $comment )
	{
		/* Load database */
		try
		{
			$database = \IPS\frontpage\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			$this->class = 'IPS\frontpage\Records\Comment' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T311/7', 404 );
		}	
		
		/* Do it */
		try
		{
			/* Load */
			$className = $this->class;
			$comment = $className::load( $comment );
			if ( $this->member and !$comment->canView( $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			if ( $this->member and !$comment->canEdit( $this->member ) )
			{
				throw new \IPS\Api\Exception( 'NO_PERMISSION', '2T311/E', 403 );
			}
						
			/* Do it */
			try
			{
				return $this->_edit( $comment );
			}
			catch ( \InvalidArgumentException $e )
			{
				throw new \IPS\Api\Exception( 'NO_AUTHOR', '1T311/9', 400 );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2T311/8', 404 );
		}
	}
		
	/**
	 * DELETE /frontpage/comments/{database_id}/{comment_id}
	 * Deletes a comment
	 *
	 * @param		int			$database			Database ID
	 * @param		int			$comment			Comment ID
	 * @throws		2T311/A		INVALID_DATABASE	The database ID does not exist or the authorized user does not have permission to view it
	 * @throws		2T311/B		INVALID_ID			The comment ID does not exist
	 * @throws		2T311/F		NO_PERMISSION		The authorized user does not have permission to delete the comment
	 * @return		void
	 */
	public function DELETEitem( $database, $comment )
	{
		/* Load database */
		try
		{
			$database = \IPS\frontpage\Databases::load( $database );
			if ( $this->member and !$database->can( 'view', $this->member ) )
			{
				throw new \OutOfRangeException;
			}
			$this->class = 'IPS\frontpage\Records\Comment' . $database->id;
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_DATABASE', '2T311/A', 404 );
		}	
		
		/* Do it */
		try
		{			
			$class = $this->class;
			$object = $class::load( $comment );
			if ( $this->member and !$object->canDelete( $this->member ) )
			{
				throw new \IPS\Api\Exception( 'NO_PERMISSION', '2T311/F', 403 );
			}
			$object->delete();
			
			return new \IPS\Api\Response( 200, NULL );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2T311/B', 404 );
		}
	}
}