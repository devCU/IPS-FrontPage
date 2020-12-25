<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                            https://www.devcu.com/donate
 *
 * @brief		Post Model
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.5x
 * @subpackage	FrontPage
 * @version     4.5.4 Build 205010
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated    22 DEC 2020
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
 * Post Model
 */
class _Comment extends \IPS\Content\Comment implements \IPS\Content\EditHistory, \IPS\Content\Hideable, \IPS\Content\Shareable, \IPS\Content\Searchable, \IPS\Content\Embeddable
{
	use \IPS\Content\Reactable, \IPS\Content\Reportable;
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	[Content\Comment]	Item Class
	 */
	public static $itemClass = NULL;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'frontpage_database_comments';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'comment_';
	
	/**
	 * @brief	Application
	 */
	public static $application = 'frontpage';

	/**
	 * @brief	Title
	 */
	public static $title = 'content_comment';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'item'				=> 'record_id',
		'author'			=> 'user',
		'author_name'		=> 'author',
		'content'			=> 'post',
		'date'				=> 'date',
		'ip_address'		=> 'ip_address',
		'edit_time'			=> 'edit_date',
		'edit_show'			=> 'edit_show',
		'edit_member_name'	=> 'edit_member_name',
		'edit_member_id'    => 'edit_member_id',
		'edit_reason'		=> 'edit_reason',
		'approved'			=> 'approved',
	#	'first'				=> 'new_topic'
	);
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'comment';
	
	/**
	 * @brief	[Content\Comment]	Comment Template
	 */
	public static $commentTemplate = array( array( 'display', 'frontpage', 'database' ), 'commentContainer' );

	/**
	 * @brief	Database ID
	 */
	public static $customDatabaseId = NULL;
	
	/**
	 * @brief Store generated search index titles for efficiency
	 */
	protected static $searchIndexTitles = array();

	/**
	 * Load Record
	 *
	 * @see		\IPS\Db::build
	 * @param	int|string	$id					ID
	 * @param	string		$idField			The database column that the $id parameter pertains to (NULL will use static::$databaseColumnId)
	 * @param	mixed		$extraWhereClause	Additional where clause(s) (see \IPS\Db::build for details) - if used will cause multiton store to be skipped and a query always ran
	 * @return	static
	 * @throws	\InvalidArgumentException
	 * @throws	\OutOfRangeException
	 */
	public static function load( $id, $idField=NULL, $extraWhereClause=NULL )
	{
		if ( static::commentWhere() !== NULL )
		{
			if( $extraWhereClause === NULL )
			{
				$extraWhereClause = array( static::commentWhere() );
			}
			else
			{
				$extraWhereClause[] = static::commentWhere();
			}
		}

		return parent::load( $id, $idField, $extraWhereClause );
	}

	/**
	 * Post count for member
	 *
	 * @param	\IPS\Member	$member	The memner
	 * @return	int
	 */
	public static function memberPostCount( \IPS\Member $member )
	{
		return \IPS\Db::i()->select( 'COUNT(*)', static::$databaseTable, array( 'comment_database_id=? AND comment_user=?', static::$customDatabaseId, $member->member_id ) )->first();
	}
	
	/**
	 * Return custom where for SQL delete
	 *
	 * @param   int     $id     Content item to delete from
	 * @return array
	 */
	public static function deleteWhereSql( $id )
	{
		return array( array( static::$databasePrefix . static::$databaseColumnMap['item'] . '=?', $id ), array( static::$databasePrefix . 'database_id=?', static::$customDatabaseId ) );
	}
	
	/**
	 * Get items with permisison check
	 *
	 * @param	array		$where				Where clause
	 * @param	string		$order				MySQL ORDER BY clause (NULL to order by date)
	 * @param	int|array	$limit				Limit clause
	 * @param	string		$permissionKey		A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index
	 * @param	mixed		$includeHiddenItems	Include hidden comments? NULL to detect if currently logged in member has permission, -1 to return public content only, TRUE to return unapproved content and FALSE to only return unapproved content the viewing member submitted
	 * @param	int			$queryFlags			Select bitwise flags
	 * @param	\IPS\Member	$member				The member (NULL to use currently logged in member)
	 * @param	bool		$joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinComments		If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinReviews		If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$countOnly				If true will return the count
	 * @param	array|null	$joins					Additional arbitrary joins for the query
	 * @return	array|NULL|\IPS\Content\Comment		If $limit is 1, will return \IPS\Content\Comment or NULL for no results. For any other number, will return an array.
	 */
	public static function getItemsWithPermission( $where=array(), $order=NULL, $limit=10, $permissionKey='read', $includeHiddenItems=\IPS\Content\Hideable::FILTER_AUTOMATIC, $queryFlags=0, \IPS\Member $member=NULL, $joinContainer=FALSE, $joinComments=FALSE, $joinReviews=FALSE, $countOnly=FALSE, $joins=NULL )
	{
		$class = '\IPS\frontpage\Records' . static::$customDatabaseId;
		$where = $class::getItemsWithPermissionWhere( $where, $permissionKey, $member, $joinContainer );
		
		return parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenItems, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins );
	}
	
	/**
	 * Get HTML for search result display
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$authorData		Basic data about the author. Only includes columns returned by \IPS\Member::columnsForPhoto()
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @param	array		$reputationData	Array of people who have given reputation and the reputation they gave
	 * @param	int|NULL	$reviewRating	If this is a review, the rating
	 * @param	bool		$iPostedIn		If the user has posted in the item
	 * @param	string		$view			'expanded' or 'condensed'
	 * @param	bool		$asItem	Displaying results as items?
	 * @param	bool		$canIgnoreComments	Can ignore comments in the result stream? Activity stream can, but search results cannot.
	 * @param	array		$template	Optional custom template
	 * @param	array		$reactions	Reaction Data
	 * @return	string
	 */
	public static function searchResult( array $indexData, array $authorData, array $itemData, array $containerData = NULL, array $reputationData, $reviewRating, $iPostedIn, $view, $asItem, $canIgnoreComments=FALSE, $template=NULL, $reactions=array() )
	{
		/* Ensure that the comment title is formatted correctly */
		try
		{
			$databases  = \IPS\frontpage\Databases::databases();
			
			if ( ! isset( static::$searchIndexTitles[ $itemData['primary_id_field'] ] ) )
			{
				$fields     = '\IPS\frontpage\Fields' .  static::$customDatabaseId;
				$titleField = $databases[ static::$customDatabaseId ]->field_title;
				
				static::$searchIndexTitles[ $itemData['primary_id_field'] ] = $fields::load( $databases[ static::$customDatabaseId ]->field_title )->displayValue( $itemData['field_' . $databases[ static::$customDatabaseId ]->field_title ] );
			}

			$itemData['field_' . $databases[ static::$customDatabaseId ]->field_title ] = static::$searchIndexTitles[ $itemData['primary_id_field'] ];
		}
		catch ( \Exception $ex ) { }

		return parent::searchResult( $indexData, $authorData, $itemData, $containerData, $reputationData, $reviewRating, $iPostedIn, $view, $asItem, $canIgnoreComments, $template, $reactions );
	}
	
	/**
	 * Do stuff after creating (abstracted as comments and reviews need to do different things)
	 *
	 * @return	void
	 */
	public function postCreate()
	{
		$this->database_id = static::$customDatabaseId;
		$this->save();
		
		$item = $this->item();
		if ( $item->hidden() OR ( \IPS\frontpage\Databases::load( static::$customDatabaseId )->_comment_bump & \IPS\frontpage\Databases::BUMP_ON_COMMENT ) )
		{
			parent::postCreate();
		}
		else
		{
			/* No bump, so don't update the record's last_action stuff */
			if ( isset( $item::$databaseColumnMap['num_comments'] ) )
			{
				$item->resyncCommentCounts();
				$item->save();
			}
				
			if ( $item->containerWrapper() AND $item->container()->_comments !== NULL )
			{
				$item->container()->_comments = ( $item->container()->_comments + 1 );
				$item->container()->save();
			}
		}
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		$template = static::$commentTemplate[1];
		static::$commentTemplate[0][0] = $this->item()->database()->template_display;

		return \IPS\frontpage\Theme::i()->getTemplate( static::$commentTemplate[0][0], static::$commentTemplate[0][1], static::$commentTemplate[0][2] )->$template( $this->item(), $this );
	}
	
	/**
	 * Get URL for doing stuff
	 *
	 * @param	string|NULL		$action		Action
	 * @return	\IPS\Http\Url
	 */
	public function url( $action='find' )
	{
		$url = parent::url( $action );
		
		if ( $action !== NULL )
		{
			$url = $url->setQueryString( 'd', static::$customDatabaseId );
		}

		$url = $url->setQueryString( 'tab', 'comments' );
		
		return $url;
	}
	
	/**
	 * Get attachment IDs
	 *
	 * @return	array
	 */
	public function attachmentIds()
	{
		$item = $this->item();
		$idColumn = $item::$databaseColumnId;
		$commentIdColumn = static::$databaseColumnId;
		return array( $this->item()->$idColumn, $this->$commentIdColumn, static::$customDatabaseId . '-comment' ); 
	}

	/**
	 * Addition where needed for fetching comments
	 *
	 * @return	array|NULL
	 */
	public static function commentWhere()
	{
		return array( 'comment_database_id=?', static::$customDatabaseId );
	}
	
	/**
	 * Reaction Type
	 *
	 * @return	string
	 */
	public static function reactionType()
	{
		$databaseId = static::$customDatabaseId;
		return "comment_id_{$databaseId}";
	}

	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string
	 */
	public function embedContent( $params )
	{
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'embed.css', 'frontpage', 'front' ) );
		return \IPS\Theme::i()->getTemplate( 'global', 'frontpage' )->embedRecordComment( $this, $this->item(), $this->url()->setQueryString( $params ) );
	}
}