<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                            https://www.devcu.com/donate
 *
 * @brief		Databases Model
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.5x
 * @subpackage	FrontPage
 * @version     4.5.4 Build 205010
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     22 DEC 2020
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

namespace IPS\frontpage;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Databases Model
 */
class _Databases extends \IPS\Node\Model implements \IPS\Node\Permissions
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons = array();

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected $caches = array( 'database_reciprocal_links', 'frontpage_databases' );
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'database_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Table
	 */
	public static $databaseTable = 'frontpage_databases';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields = array( 'database_key', 'database_fpage_id' );
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static $multitonMap	= array();
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static $databaseColumnOrder = 'id';
	
	/**
	 * @brief	[Node] Sortable?
	 */
	public static $nodeSortable = FALSE;
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = '';
	
	/**
	 * @brief	Have fetched all?
	 */
	protected static $gotAll = FALSE;
	
	/**
	 * @brief	The map of permission columns
	 */
	public static $permissionMap = array(
			'view' 				=> 'view',
			'read'				=> 2,
			'add'				=> 3,
			'edit'				=> 4,
			'reply'				=> 5,
			'review'            => 7,
			'rate'				=> 6
	);
	
	/**
	 * @brief	[Node] App for permission index
	 */
	public static $permApp = 'frontpage';
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static $permType = 'databases';
	
	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static $permissionLangPrefix = 'perm_content_';
		
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static $modalForms = FALSE;

	/**
	 * [Brief]	Bump on edit only
	 */
	const BUMP_ON_EDIT = 1;
	
	/**
	 * [Brief]	Bump on comment only
	 */
	const BUMP_ON_COMMENT = 2;
	
	/**
	 * [Brief]	Bump on edit only
	 */
	const CATEGORY_VIEW_CATEGORIES = 0;
	
	/**
	 * [Brief]	Bump on comment only
	 */
	const CATEGORY_VIEW_FEATURED = 1;

	/**
	 * [Brief] Database template groups
	 */
	public static $templateGroups = array(
		'categories' => 'category_index',
		'featured'   => 'category_articles',
		'listing'    => 'listing',
		'display'    => 'display',
		'form'       => 'form'
	);

	/**
	 * @brief	Bitwise values for database_options field
	 */
	public static $bitOptions = array(
		'options' => array(
			'options' => array(
				'comments'              => 1,   // Enable comments?
				'reviews'               => 2,   // Enable reviews?
				'comments_mod'          => 4,   // Enable comment moderation?
				'reviews_mod'           => 8,   // Enable reviews moderation?
			    'indefinite_own_edit'   => 16,  // Enable authors to indefinitely edit their own articles
			)
		)
	);
	
	/**
	 * @brief	Fpage title
	 */
	protected $fpageTitle = NULL;
	
	/**
	 * Return all databases
	 *
	 * @return	array
	 */
	public static function databases()
	{
		if ( ! static::$gotAll )
		{
			/* Avoid using SHOW TABLES LIKE / checkForTable() */
            try
            {
                foreach( static::getStore() as $db )
                {
                    $id = $db[ static::$databasePrefix . static::$databaseColumnId ];
                    static::$multitons[ $id ] = static::constructFromData( $db );
                }
            }
            catch( \Exception $e ) { }
				
			static::$gotAll = true;
		}
		
		return static::$multitons;
	}
	
	/**
	 * Returns database data from the store
	 *
	 * @return array
	 */
	public static function getStore()
	{
		if ( ! isset( \IPS\Data\Store::i()->frontpage_databases ) )
		{
			\IPS\Data\Store::i()->frontpage_databases = iterator_to_array(
				\IPS\Db::i()->select(
						static::$databaseTable . '.*, core_permission_index.perm_id, core_permission_index.perm_view, core_permission_index.perm_2, core_permission_index.perm_3, core_permission_index.perm_4, core_permission_index.perm_5, core_permission_index.perm_6, core_permission_index.perm_7',
						static::$databaseTable
					)->join(
							'core_permission_index',
							array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnId, static::$permApp, static::$permType )
					)
				->setKeyField('database_id')
			);
		}
		
		return \IPS\Data\Store::i()->frontpage_databases;
	}
	
	/**
	 * Can we promote stuff to Fpages?
	 *
	 * @return	boolean
	 */
	public static function canPromote()
	{
		return TRUE;
	}

	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param	array	$data							Row from database table
	 * @param	bool	$updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return	static
	 */
	public static function constructFromData( $data, $updateMultitonStoreIfExists = TRUE )
	{
		$obj = parent::constructFromData( $data, $updateMultitonStoreIfExists );
		$obj->preLoadWords();

		return $obj;
	}
	
		
	/**
	 * Can this database accept RSS imports? 
	 *
	 * @return boolean
	 */
	public function canImportRss(): bool
	{
		if ( ! $this->fpage_id )
		{
			return FALSE;
		}
		
		$fieldsClass = '\IPS\frontpage\Fields' . $this->id;
		
		try
		{
			if ( ! \in_array( mb_ucfirst( $fieldsClass::load( $this->field_title )->type ), array( 'Text', 'TextArea', 'Editor' ) ) )
			{
				return FALSE;
			}
			
			if ( ! \in_array( mb_ucfirst( $fieldsClass::load( $this->field_content )->type ), array( 'TextArea', 'Editor' ) ) )
			{
				return FALSE;
			}
		}
		catch( \Exception $e )
		{
			return FALSE;
		}
		
		return TRUE;
	}

	/**
	 * Return data for the ACP Menu
	 * 
	 * @return array
	 */
	public static function acpMenu()
	{
		$menu = array();

		foreach(
			\IPS\Db::i()->select( '*, core_sys_lang_words.word_custom as database_name, core_sys_lang_words2.word_custom as record_name', 'frontpage_databases', NULL, 'core_sys_lang_words.word_custom' )
				->join( 'core_sys_lang_words', "core_sys_lang_words.word_key=CONCAT( 'content_db_', frontpage_databases.database_id ) AND core_sys_lang_words.lang_id=" . \IPS\Member::loggedIn()->language()->id )
				->join( array( 'core_sys_lang_words', 'core_sys_lang_words2' ), "core_sys_lang_words2.word_key=CONCAT( 'content_db_lang_pu_', frontpage_databases.database_id ) AND core_sys_lang_words2.lang_id=" . \IPS\Member::loggedIn()->language()->id )
			as $row )
		{
			$menu[] = array(
				'id'             => $row['database_id'],
				'title'          => $row['database_name'],
				'record_name'	 => $row['record_name'],
				'use_categories' => $row['database_use_categories']
			);
		}

        return $menu;
	}

	/**
	 * Checks and fixes existing DB
	 *
	 * @param   int     $id     Database ID
	 * @return  int     $fixes  Number of fixes made (0 if none)
	 *
	 * @throws \OutOfRangeException
	 */
	public static function checkandFixDatabaseSchema( $id )
	{
		$fixes     = 0;
		$json      = json_decode( @file_get_contents( \IPS\ROOT_PATH . "/applications/frontpage/data/databaseschema.json" ), true );
		$table     = $json['frontpage_custom_database_1'];
		$tableName = 'frontpage_custom_database_' . $id;

		if ( ! \IPS\Db::i()->checkForTable( $tableName ) )
		{
			throw new \OutOfRangeException;
		}

		$schema		= \IPS\Db::i()->getTableDefinition( $tableName );
		$changes	= array();

		/* Colums */
		foreach( $table['columns'] as $key => $data )
		{
			if ( ! isset( $schema['columns'][ $key ] ) )
			{
				$changes[] = "ADD COLUMN " . \IPS\Db::i()->compileColumnDefinition( $data );
				$fixes++;
			}
		}

		/* Indexes */
		foreach( $table['indexes'] as $key => $data )
		{
			/* No index */
			if ( ! isset( $schema['indexes'][ $key ] ) )
			{
				$changes[] = \IPS\Db::i()->buildIndex( $tableName, $data );
				$fixes++;
			}
			else if ( implode( '.', $data['columns'] ) != implode( '.', $schema['indexes'][ $key ]['columns'] ) )
			{
				/* Check columns */
				if( $key == 'PRIMARY KEY' )
				{
					$changes[] = "DROP PRIMARY KEY";
				}
				else
				{
					$changes[] = "DROP KEY `" . \IPS\Db::i()->escape_string( $key ) . "`";
				}

				$changes[] =  \IPS\Db::i()->buildIndex( $tableName, $data );
				$fixes++;
			}
		}

		/* We collect all the changes so we can run one database query instead of, potentially, dozens */
		if( \count( $changes ) )
		{
			\IPS\Db::i()->query( "ALTER TABLE " . \IPS\Db::i()->prefix . $tableName . " " . implode( ', ', $changes ) );
		}

		return $fixes;
	}
	
	/**
	 * Create a new database
	 * 
	 * @param 	\IPS\frontpage\Databases 	$database		ID of database to create
	 * @return	void
	 */
	public static function createDatabase( $database )
	{
		$json  = json_decode( @file_get_contents( \IPS\ROOT_PATH . "/applications/frontpage/data/databaseschema.json" ), true );
		$table = $json['frontpage_custom_database_1'];
	
		$table['name'] = 'frontpage_custom_database_' . $database->id;
		
		foreach( $table['columns'] as $name => $data )
		{
			if ( mb_substr( $name, 0, 6 ) === 'field_' )
			{
				unset( $table['columns'][ $name ] );
			}
		}
		
		foreach( $table['indexes'] as $name => $data )
		{
			if ( mb_substr( $name, 0, 6 ) === 'field_' )
			{
				unset( $table['indexes'][ $name ] );
			}
		}
		
		try
		{
			if ( ! \IPS\Db::i()->checkForTable( $table['name'] ) )
			{
				\IPS\Db::i()->createTable( $table );
			}
		}
		catch( \IPS\Db\Exception $ex )
		{
			throw new \LogicException( $ex );
		}

		/* Populate default custom fields */
		$fieldsClass = 'IPS\frontpage\Fields' . $database->id;
		$fieldTitle   = array();
		$fieldContent = array();
		$catTitle     = array();
		$catDesc      = array();

		foreach( \IPS\Lang::languages() as $id => $lang )
		{
			/* Try to get the actual database noun if it has been created */
			try
			{
				$title = $lang->get( 'content_db_lang_pu_' . $database->id );
			}
			catch( \Exception $e )
			{
				$title = $lang->get('content_database_noun_pu');
			}

			$fieldTitle[ $id ]   = $lang->get('content_fields_is_title');
			$fieldContent[ $id ] = $lang->get('content_fields_is_content');
			$catTitle[ $id ]     = $title;
			$catDesc[ $id ]      = '';
		}

		/* Title */
		$titleField = new $fieldsClass;
		$titleField->saveForm( $titleField->formatFormValues( array(
			'field_title'			=> $fieldTitle,
			'field_type'			=> 'Text',
			'field_key'				=> 'titlefield_' . $database->id,
			'field_required'		=> 1,
			'field_user_editable'	=> 1,
			'field_display_listing'	=> 1,
			'field_display_display'	=> 1,
			'field_is_searchable'	=> 1,
			'field_max_length'		=> 255
	       ) ) );

		$database->field_title = $titleField->id;
		$perms = $titleField->permissions();

		\IPS\Db::i()->update( 'core_permission_index', array(
             'perm_view'	 => '*',
             'perm_2'		 => '*',
             'perm_3'        => '*'
         ), array( 'perm_id=?', $perms['perm_id']) );

		/* Content */
		$contentField = new $fieldsClass;
		$contentField->saveForm( $contentField->formatFormValues( array(
			'field_title'			=> $fieldContent,
			'field_type'			=> 'Editor',
			'field_key'				=> 'contentfield_' . $database->id,
			'field_required'		=> 1,
			'field_user_editable'	=> 1,
			'field_truncate'		=> 100,
			'field_topic_format'	=> '{value}',
			'field_display_listing'	=> 1,
			'field_display_display'	=> 1,
			'field_is_searchable'	=> 1
         ) ) );

		$database->field_content = $contentField->id;
		$perms = $contentField->permissions();

		\IPS\Db::i()->update( 'core_permission_index', array(
             'perm_view'	 => '*',
             'perm_2'		 => '*',
             'perm_3'        => '*'
         ), array( 'perm_id=?', $perms['perm_id']) );

		/* Create a category */
		$categoryClass = '\IPS\frontpage\Categories' . $database->id;
		$category = new $categoryClass;
		$category->database_id = $database->id;

		$category->saveForm( $category->formatFormValues( array(
             'category_name'		 => $catTitle,
             'category_description'  => $catDesc,
             'category_parent_id'    => 0,
             'category_has_perms'    => 0,
             'category_show_records' => 1
         ) ) );

		$perms = $category->permissions();

		\IPS\Db::i()->update( 'core_permission_index', array(
             'perm_view'	 => '*',
             'perm_2'		 => '*',
             'perm_3'        => '*'
         ), array( 'perm_id=?', $perms['perm_id']) );

		$database->options['comments'] = 1;
		$database->save();
	}

	/**
	 * @brief   Language strings pre-loaded
	 */
	protected $langLoaded = FALSE;

	/**
	 * Get database id
	 * 
	 * @return string
	 */
	public function get__id()
	{
		return $this->id;
	}

	/**
	 * Get comment bump
	 *
	 * @return int
	 */
	public function get__comment_bump()
	{
		if ( $this->comment_bump === 0 )
		{
			return static::BUMP_ON_EDIT;
		}
		else if ( $this->comment_bump === 1 )
		{
			return static::BUMP_ON_COMMENT;
		}
		else if ( $this->comment_bump === 2 )
		{
			return static::BUMP_ON_EDIT + static::BUMP_ON_COMMENT;
		}
	}
	
	/**
	 * Get database name
	 *
	 * @return string
	 */
	public function get__title()
	{
		return \IPS\Member::loggedIn()->language()->addToStack('content_db_' . $this->id);
	}
	
	/**
	 * Get database description
	 *
	 * @return string
	 */
	public function get__description()
	{
		return \IPS\Member::loggedIn()->language()->addToStack('content_db_' . $this->id . '_desc');
	}

	/**
	 * Get default category
	 *
	 * @return string
	 */
	public function get__default_category()
	{
		$categoryClass = '\IPS\frontpage\Categories' . $this->id;
		if ( $this->default_category )
		{
			try
			{
				$categoryClass::load( $this->default_category );
				return $this->default_category;
			}
			catch( \OutOfRangeException $e )
			{
				$this->default_category = NULL;
			}
		}

		if ( ! $this->default_category )
		{
			$roots = $categoryClass::roots( NULL );

			if ( ! \count( $roots ) )
			{
				/* Create a category */
				$category = new $categoryClass;
				$category->database_id = $this->id;

				$catTitle = array();
				$catDesc  = array();

				foreach( \IPS\Lang::languages() as $id => $lang )
				{
					$catTitle[ $id ] = $lang->get('content_database_noun_pu');
					$catDesc[ $id ]  = '';
				}

				$category->saveForm( $category->formatFormValues( array(
                  'category_name'		  => $catTitle,
                  'category_description'  => $catDesc,
                  'category_parent_id'    => 0,
                  'category_has_perms'    => 0,
                  'category_show_records' => 1
                ) ) );

				$perms = $category->permissions();

				\IPS\Db::i()->update( 'core_permission_index', array(
					'perm_view'	 => '*',
					'perm_2'	 => '*',
					'perm_3'     => '*'
				), array( 'perm_id=?', $perms['perm_id']) );

				$roots = $categoryClass::roots( NULL );
			}

			$category = array_shift( $roots );

			$this->default_category = $category->id;
			$this->save();

			/* Update records */
			\IPS\Db::i()->update( 'frontpage_custom_database_' . $this->id, array( 'category_id' => $category->id ), array( 'category_id=0' ) );
		}

		return $this->default_category;
	}

	/**
	 * Get fixed field data
	 * 
	 * @return array
	 */
	public function get_fixed_field_perms()
	{
		if ( ! \is_array( $this->_data['fixed_field_perms'] ) )
		{
			$this->_data['fixed_field_perms'] = json_decode( $this->_data['fixed_field_perms'], true );
		}
		
		if ( \is_array( $this->_data['fixed_field_perms'] ) )
		{
			return $this->_data['fixed_field_perms'];
		}
		
		return array();
	}

	/**
	 * Set the "fixed field" field
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set_fixed_field_perms( $value )
	{
		$this->_data['fixed_field_perms'] = ( \is_array( $value ) ? json_encode( $value ) : $value );
	}

	/**
	 * Get fixed field settings
	 *
	 * @return array
	 */
	public function get_fixed_field_settings()
	{
		if ( ! \is_array( $this->_data['fixed_field_settings'] ) )
		{
			$this->_data['fixed_field_settings'] = json_decode( $this->_data['fixed_field_settings'], true );
		}

		if ( \is_array( $this->_data['fixed_field_settings'] ) )
		{
			return $this->_data['fixed_field_settings'];
		}

		return array();
	}

	/**
	 * Set the "fixed field" settings field
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set_fixed_field_settings( $value )
	{
		$this->_data['fixed_field_settings'] = ( \is_array( $value ) ? json_encode( $value ) : $value );
	}

	/**
	 * Get feature settings, settings
	 *
	 * @return array
	 */
	public function get_featured_settings()
	{
		if ( ! \is_array( $this->_data['featured_settings'] ) )
		{
			$this->_data['featured_settings'] = json_decode( $this->_data['featured_settings'], true );
		}

		if ( \is_array( $this->_data['featured_settings'] ) )
		{
			return $this->_data['featured_settings'];
		}

		return array();
	}

	/**
	 * Set the "featured settings" field
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set_featured_settings( $value )
	{
		$this->_data['featured_settings'] = ( \is_array( $value ) ? json_encode( $value ) : $value );
	}
	
	/**
	 * Get the title of the fpage when using a database
	 *
	 * @return string
	 */
	public function fpageTitle()
	{
		if ( $this->fpageTitle === NULL )
		{
			if ( $this->use_as_fpage_title )
			{ 
				$this->fpageTitle = $this->_title;
			}
			else
			{
				try
				{
					$this->fpageTitle = \IPS\frontpage\Fpages\Fpage::load( $this->fpage_id )->getHtmlTitle();
				}
				catch( \Exception $e ) { }
			}
		}
		
		return $this->fpageTitle;
	}
	
	/**
	 * Check permissions
	 *
	 * @param	mixed								$permission						A key which has a value in static::$permissionMap['view'] matching a column ID in core_permission_index
	 * @param	\IPS\Member|\IPS\Member\Group|NULL	$member							The member or group to check (NULL for currently logged in member)
	 * @param	bool								$considerPostBeforeRegistering	If TRUE, and $member is a guest, will return TRUE if "Post Before Registering" feature is enabled
	 * @return	bool
	 * @throws	\OutOfBoundsException	If $permission does not exist in static::$permissionMap
	 */
	public function can( $permission, $member=NULL, $considerPostBeforeRegistering = TRUE )
	{
		/* If we're looking from the front, make sure the database fpage also passes */
		if ( $permission === 'view' and \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation === 'front' and $this->fpage_id )
		{
			try
			{
				return parent::can( 'view', $member, $considerPostBeforeRegistering ) AND \IPS\frontpage\Fpages\Fpage::load( $this->fpage_id )->can( 'view', $member, $considerPostBeforeRegistering );
			}
			catch( \OutOfRangeException $ex )
			{
				return parent::can( 'view', $member, $considerPostBeforeRegistering );
			}
		}

		return parent::can( $permission, $member, $considerPostBeforeRegistering );
	}

	/**
	 * Disabled permissions
	 * Allow node classes to define permissions that are unselectable in the permission matrix
	 *
	 * @return array	array( {group_id} => array( 'read', 'view', 'perm_7' );
	 * @throws UnderflowException (if guest group ID is invalid)
	 */
	public function disabledPermissions()
	{
		$disabled = array();

		try
		{
			$permissions = \IPS\frontpage\Fpages\Fpage::load( $this->fpage_id )->permissions();

			if( $permissions['perm_view'] != '*' )
			{
				$fpageViewPermissions = explode( ',', $permissions['perm_view'] );

				foreach ( \IPS\Member\Group::groups() as $group )
				{
					if ( ! \in_array( $group->g_id, $fpageViewPermissions ) )
					{
						$disabled[ $group->g_id ] = array( 'view', 2, 3, 4, 5, 6, 7 );
					}
				}
			}
		}
		catch( \OutOfRangeException $e ){}

		return $disabled;
	}

	/**
	 * Sets up and preloads some words
	 *
	 * @return void
	 */
	public function preLoadWords()
	{
		/* Skip this during installation / uninstallation as the words won't be loaded */
		if ( !\IPS\Dispatcher::hasInstance() or \IPS\Dispatcher::i()->controllerLocation === 'setup' OR ( \IPS\Dispatcher::i()->controllerLocation === 'admin' AND \IPS\Dispatcher::i()->module->key === 'applications' ) )
		{
			$this->langLoaded = TRUE;
			return;
		}
		 
		if ( ! $this->langLoaded )
		{
			if ( \IPS\Dispatcher::i()->controllerLocation === 'admin' )
			{
				/* Moderator tools */
				\IPS\Member::loggedIn()->language()->words['modperms__core_Content_frontpage_Records' . $this->id ] = $this->_title;
				\IPS\Member::loggedIn()->language()->words['frontpage' . $this->id ] = \IPS\Member::loggedIn()->language()->addToStack('categories');
				
				/* Editor Areas */
				\IPS\Member::loggedIn()->language()->words['editor__frontpage_Records' . $this->id ] = $this->_title;

				foreach( array( 'pin', 'unpin', 'feature', 'unfeature', 'edit', 'hide', 'unhide', 'view_hidden', 'future_publish', 'view_future', 'move', 'lock', 'unlock', 'reply_to_locked', 'delete', 'feature_comments', 'unfeature_comments', 'add_item_message', 'edit_item_message', 'delete_item_message' ) as $lang )
				{
					\IPS\Member::loggedIn()->language()->words['can_' . $lang . '_content_db_lang_sl_' . $this->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'can_' . $lang . '_record', FALSE, array( 'sprintf' => array( $this->recordWord( 1 ) ) ) );
					\IPS\Member::loggedIn()->language()->words['can_' . $lang . '_content_db_lang_su_' . $this->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'can_' . $lang . '_record', FALSE, array( 'sprintf' => array( $this->recordWord( 1, TRUE ) ) ) );

					if ( \in_array( $lang, array( 'edit', 'hide', 'unhide', 'view_hidden', 'delete' ) ) )
					{
						\IPS\Member::loggedIn()->language()->words['can_' . $lang . '_content_record_comments_title_' . $this->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'can_' . $lang . '_rcomment', FALSE, array( 'sprintf' => array( $this->recordWord( 1 ) ) ) );
						\IPS\Member::loggedIn()->language()->words['can_' . $lang . '_content_record_reviews_title_' . $this->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'can_' . $lang . '_rreview', FALSE, array( 'sprintf' => array( $this->recordWord( 1 ) ) ) );

					}
				}
			}

			$this->langLoaded = true;
		}
	}

	/**
	 * "Records" / "Record" word
	 *
	 * @param	int	    $number	Number
	 * @param   bool    $upper  ucfirst string
	 * @return	string
	 */
	public function recordWord( $number = 2, $upper = FALSE )
	{
		if ( \IPS\Application::appIsEnabled('frontpage') )
		{
			return \IPS\Member::loggedIn()->language()->recordWord( $number, $upper, $this->id );
		}
		else
		{
			/* If the Fpages app is disabled, just load a generic phrase */
			$key = "content_database_noun_" . ( $number > 1 ? "p" : "s" ) . ( $upper ? "u" : "l" );
			return \IPS\Member::loggedIn()->language()->addToStack( $key ); 
		}
	}
	
	/**
	 * [ActiveRecord] Save Record
	 *
	 * @return	void
	 */
	public function save()
	{
		/* If we are enabling search, we will need to index that content */
		$rebuildSearchIndex = ( !$this->_new AND isset( $this->changed['search'] ) AND $this->changed['search'] );
		$removeSearchIndex	= ( !$this->_new AND isset( $this->changed['search'] ) AND !$this->changed['search'] );

		parent::save();

		/* If this database isn't searchable, make sure its content is not in the search index */
		if( $removeSearchIndex )
		{
			\IPS\Content\Search\Index::i()->removeClassFromSearchIndex( 'IPS\frontpage\Records' . $this->id );
			\IPS\Content\Search\Index::i()->removeClassFromSearchIndex( 'IPS\frontpage\Records\Comment' . $this->id );
			\IPS\Content\Search\Index::i()->removeClassFromSearchIndex( 'IPS\frontpage\Records\Review' . $this->id );

			/* If there are any bg tasks to rebuild index, clear them */
			foreach( \IPS\Db::i()->select( '*', 'core_queue', array( '`key`=?', 'RebuildSearchIndex' ) ) as $queue )
			{
				$details = json_decode( $queue['data'], true );

				if( isset( $details['class'] ) AND $details['class'] == 'IPS\frontpage\Records' . $this->id )
				{
					\IPS\Db::i()->delete( 'core_queue', array( 'id=?', $queue['id'] ) );
				}
			}
		}
		elseif( $rebuildSearchIndex )
		{
			\IPS\Task::queue( 'core', 'RebuildSearchIndex', array( 'class' => 'IPS\frontpage\Records' . $this->id ), 5, TRUE );
		}
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		$fieldsClass = '\IPS\frontpage\Fields' . $this->id;

		$class = '\IPS\frontpage\Categories' . $this->id;
		foreach( $class::roots( NULL ) as $id => $cat )
		{
			$cat->delete();
		}

		foreach( $fieldsClass::roots( NULL ) as $id => $field )
		{
			$field->delete( TRUE );
		}
		
		/* Delete comments */
		\IPS\Db::i()->delete( 'frontpage_database_comments', array( 'comment_database_id=?', $this->id ) );

		/* Delete from view counter */
		\IPS\Db::i()->delete( 'core_view_updates', array( 'classname=? ', 'IPS\frontpage\Records' . $this->id ) );

		/* Delete records */
		\IPS\Db::i()->dropTable( 'frontpage_custom_database_' . $this->id, TRUE );
		
		/* Delete revisions */
		\IPS\Db::i()->delete( 'frontpage_database_revisions', array( 'revision_database_id=?', $this->id ) );
		
		/* Remove any reciprocal linking */
		\IPS\Db::i()->delete( 'frontpage_database_fields_reciprocal_map', array( 'map_foreign_database_id=? or map_origin_database_id=?', $this->id, $this->id ) );
		
		/* Delete notifications */
		$memberIds	= array();

		foreach( \IPS\Db::i()->select( '`member`', 'core_notifications', array( 'item_class=? ', 'IPS\frontpage\Records' . $this->id ) ) as $member )
		{
			$memberIds[ $member ]	= $member;
		}

		\IPS\Db::i()->delete( 'core_notifications', array( 'item_class=? ', 'IPS\frontpage\Records' . $this->id ) );
		\IPS\Db::i()->delete( 'core_follow', array( 'follow_app=? AND follow_area=?', 'frontpage', 'records' . $this->id ) );

		/* Remove promoted content */
		\IPS\Db::i()->delete( 'core_social_promote', array( 'promote_class=?', 'IPS\frontpage\Records' . $this->id ) );

		/* remove deletion log */
		\IPS\Db::i()->delete( 'core_deletion_log', array( 'dellog_content_class=?', 'IPS\frontpage\Records' . $this->id  ) );

		/* remove metadata */
		\IPS\Db::i()->delete( 'core_content_meta', array( "meta_class=? ", 'IPS\frontpage\Records' . $this->id  ) );

		foreach( $memberIds as $member )
		{
			\IPS\Member::load( $member )->recountNotifications();
		}

		/* Remove from search */
		\IPS\Content\Search\Index::i()->removeClassFromSearchIndex( 'IPS\frontpage\Records' . $this->id );
		\IPS\Content\Search\Index::i()->removeClassFromSearchIndex( 'IPS\frontpage\Records\Comment' . $this->id );
		\IPS\Content\Search\Index::i()->removeClassFromSearchIndex( 'IPS\frontpage\Records\Review' . $this->id );

		/* Delete custom languages */
		\IPS\Lang::deleteCustom( 'frontpage', "content_db_" . $this->id );
		\IPS\Lang::deleteCustom( 'frontpage', "content_db_" . $this->id . '_desc');
		\IPS\Lang::deleteCustom( 'frontpage', "content_db_lang_sl_" . $this->id );
		\IPS\Lang::deleteCustom( 'frontpage', "content_db_lang_pl_" . $this->id );
		\IPS\Lang::deleteCustom( 'frontpage', "content_db_lang_su_" . $this->id );
		\IPS\Lang::deleteCustom( 'frontpage', "content_db_lang_pu_" . $this->id );
		\IPS\Lang::deleteCustom( 'frontpage', "content_db_lang_ia_" . $this->id );
		\IPS\Lang::deleteCustom( 'frontpage', "content_db_lang_sl_" . $this->id . '_pl' );
		\IPS\Lang::deleteCustom( 'frontpage', "__indefart_content_db_lang_sl_" . $this->id );
		\IPS\Lang::deleteCustom( 'frontpage', "__defart_content_db_lang_sl_" . $this->id );
		\IPS\Lang::deleteCustom( 'frontpage', "frontpage_create_menu_records_" . $this->id );
		\IPS\Lang::deleteCustom( 'frontpage', "frontpage_records" . $this->id . '_pl' );
		\IPS\Lang::deleteCustom( 'frontpage', "module__frontpage_records" . $this->id );

		/* Unclaim attachments */
		\IPS\File::unclaimAttachments( 'frontpage_Records', NULL, NULL, $this->id );
		\IPS\File::unclaimAttachments( 'frontpage_Records' . $this->id );

		/* Remove widgets */
		$this->removeWidgets();

		/* Delete the database record */
		parent::delete();
	}

	/**
	 * Remove any database widgets
	 *
	 * @return void
	 */
	public function removeWidgets()
	{
		$databaseWidgets = array( 'Database', 'LatestArticles' );

		foreach ( \IPS\Db::i()->select( '*', 'frontpage_fpage_widget_areas' ) as $item )
		{
			$fpageBlocks   = json_decode( $item['area_widgets'], TRUE );
			$resaveBlock  = NULL;
			foreach( $fpageBlocks as $id => $fpageBlock )
			{
				if( $fpageBlock['app'] == 'frontpage' AND \in_array( $fpageBlock['key'], $databaseWidgets ) AND ! empty( $fpageBlock['configuration']['database'] ) )
				{
					if ( $fpageBlock['configuration']['database'] == $this->id )
					{
						$resaveBlock = $fpageBlocks;
						unset( $resaveBlock[ $id ] );
					}
				}
			}

			if ( $resaveBlock !== NULL )
			{
				\IPS\Db::i()->update( 'frontpage_fpage_widget_areas', array( 'area_widgets' => json_encode( $resaveBlock ) ), array( 'area_fpage_id=? and area_area=?', $this->id, $item['area_area'] ) );
			}
		}
	}

	/**
	 * Set the permission index permissions
	 *
	 * @param	array	$insert	Permission data to insert
	 * @return  void
	 */
	public function setPermissions( $insert )
	{
		parent::setPermissions( $insert );
		
		/* Clear cache */
		unset( \IPS\Data\Store::i()->frontpage_databases );
		
		/* Clone these permissions to all categories that do not have permissions */
		$class = '\IPS\frontpage\Categories' . $this->id;
		foreach( $class::roots( NULL ) as $category )
		{
			$this->setPermssionsRecursively( $category );
		}
	}
	
	/**
	 * Recursively set permissions
	 *
	 * @param	\IPS\frontpage\Categrories	$category		Category object
	 * @return	void
	 */
	protected function setPermssionsRecursively( $category )
	{
		if ( ! $category->has_perms )
		{
			$category->cloneDatabasePermissions();
		}
		
		foreach( $category->children() as $child )
		{
			$this->setPermssionsRecursively( $child );
		}
	}

	
	/**
	 * @brief	Number of categories
	 */
	protected $_numberOfCategories = NULL;
	
	/**
	 * Get the number of categories in this database
	 *
	 * @return  int
	 */
	public function numberOfCategories()
	{
		if ( $this->_numberOfCategories === NULL )
		{
			$this->_numberOfCategories = \IPS\Db::i()->select( 'count(*)', 'frontpage_database_categories', array( 'category_database_id=?', $this->_id ) )->first();
		}
		return $this->_numberOfCategories;
	}
	
	/**
	 * Determines if any fields from other databases are linking to items in this database via the Relational field
	 *
	 * @param	int		$databaseId		The ID of the database
	 * @return boolean
	 */
	public static function hasReciprocalLinking( $databaseId )
	{
		if ( isset( \IPS\Data\Store::i()->database_reciprocal_links ) )
		{
			$values = \IPS\Data\Store::i()->database_reciprocal_links;
		}
		else
		{
			$values = array();
			foreach( static::databases() as $database )
			{
				$fieldsClass = 'IPS\frontpage\Fields' . $database->_id;

				foreach( $fieldsClass::data() as $field )
				{
					if ( $field->type === 'Item' )
					{
						$extra = $field->extra;
						if ( ! empty( $extra['database'] ) )
						{
							$values[ $database->_id ][] = $extra;
						}
					}
				}

				\IPS\Data\Store::i()->database_reciprocal_links = $values;
			}
		}

		if ( \is_array( $values ) )
		{
			foreach( $values as $id => $fields )
			{
				foreach( $fields as $fieldid => $data )
				{
					if ( $data['database'] == $databaseId and $data['crosslink'] )
					{
						return TRUE;
					}
				}
			}
		}

		return FALSE;
	}
	
	/**
	 * Rebuild the reciprocal linking maps across all databases
	 *
	 * @return void
	 */
	public static function rebuildReciprocalLinkMaps()
	{
		/* Ensure the SPL are loaded from /frontpage/Application.php as this may be called by a task or upgrade module */
		\IPS\Application::load('frontpage');
		
		\IPS\Db::i()->delete( 'frontpage_database_fields_reciprocal_map' );
		
		foreach( static::databases() as $database )
		{
			$fieldsClass = 'IPS\frontpage\Fields' . $database->_id;
				
			foreach( $fieldsClass::data() as $field )
			{
				if ( $field->type === 'Item' )
				{
					\IPS\Task::queue( 'frontpage', 'RebuildReciprocalMaps', array( 'database' => $database->_id, 'field' => $field->id ), 2, array( 'field' ) );
				}
			}
		}
	}

	/**
	 * Returns database data from the store
	 *
	 * @return array
	 * @deprecated
	 */
	public static function getDatabaseDataFromStore()
	{
		return static::getStore();
	}
	
	/**
	 * Get output for API
	 *
	 * @param	\IPS\Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse			int					id				ID number
	 * @apiresponse			string				name			Name
	 * @apiresponse			bool					useCategories	If this database uses categories
	 * @clientapiresponse	[\IPS\frontpage\Fields]	fields			The fields
	 * @apiresponse			string				url				URL
	 * @clientapiresponse	object|null			permissions		Node permissions
	 */
	public function apiOutput( \IPS\Member $authorizedMember = NULL )
	{
		$return = array(
			'id'			=> $this->id,
			'name'			=> $this->_title,
			'useCategories'	=> (bool) $this->use_categories,
		);
		
		if ( $authorizedMember === NULL )
		{
			$return['fields'] = array();
			$fieldsClass = '\IPS\frontpage\Fields' . $this->id;
			foreach ( $fieldsClass::roots() as $field )
			{
				$return['fields'][] = $field->apiOutput( $authorizedMember );
			}
		}
		
		try
		{
			$fpagePath   = \IPS\frontpage\Fpages\Fpage::loadByDatabaseId( $this->id )->full_path;
			$return['url'] = (string) \IPS\Http\Url::internal( "app=frontpage&module=fpages&controller=fpage&path=" . $fpagePath, 'front', 'content_fpage_path' );
		}
		catch( \OutOfRangeException $ex )
		{
			$return['url'] = NULL;		
		}

		if( $authorizedMember === NULL )
		{
			$return['permissions']	= \in_array( 'IPS\Node\Permissions', class_implements( \get_class( $this ) ) ) ? $this->permissions() : NULL;
		}
		
		return $return;
	}
}