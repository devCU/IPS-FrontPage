<?php
/**
 * @brief		FrontPage Model
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4+
 * @subpackage	FrontPage
 * @version     1.0.4 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     20 MAR 2020
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

namespace IPS\frontpage\Fpages;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief Fpage Model
 */
class _Fpage extends \IPS\Node\Model implements \IPS\Node\Permissions
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected $caches = array( 'frontNavigation' );

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'frontpage_fpages';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'fpage_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields = array('fpage_seo_name', 'fpage_full_path');
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static $multitonMap	= array();
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static $databaseColumnParent = 'folder_id';
	
	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static $parentNodeColumnId = 'folder_id';
	
	/**
	 * @brief	[Node] Parent Node Class
	 */
	public static $parentNodeClass = 'IPS\frontpage\Fpages\Folder';
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static $databaseColumnOrder = 'seo_name';

	/**
	 * @brief	[Node] Automatically set position for new nodes
	 */
	public static $automaticPositionDetermination = FALSE;
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static $modalForms = TRUE;
	
	/**
	 * @brief	[Node] Title
	 */
	public static $nodeTitle = 'fpage';

	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 array(
	 'app'		=> 'core',				// The application key which holds the restrictrions
	 'module'	=> 'foo',				// The module key which holds the restrictions
	 'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 'add'			=> 'foo_add',
	 'edit'			=> 'foo_edit',
	 'permissions'	=> 'foo_perms',
	 'delete'		=> 'foo_delete'
	 ),
	 'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static $restrictions = array(
			'app'		=> 'frontpage',
			'module'	=> 'fpages',
			'prefix' 	=> 'fpage_'
	);
	
	/**
	 * @brief	[Node] App for permission index
	 */
	public static $permApp = 'frontpage';
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static $permType = 'fpages';
	
	/**
	 * @brief	The map of permission columns
	 */
	public static $permissionMap = array(
			'view' => 'view'
	);
	
	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static $permissionLangPrefix = 'perm_content_fpage_';
	
	/**
	 * @brief	[Fpage] Loaded fpages from paths
	 */
	protected static $loadedFpagesFromPath = array();
	
	/**
	 * @brief	[Fpage] Currently loaded fpage
	 */
	public static $currentFpage = NULL;

	/**
	 * @brief	[Fpage] Default fpage
	 */
	public static $defaultFpage = array();

	/**
	 * @brief	Pre save flag
	 */
	const PRE_SAVE  = 1;
	
	/**
	 * @brief	Post save flag
	 */
	const POST_SAVE = 2;

	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues()
	{
		$this->js_css_ids       = '';
		$this->content          = '';
		$this->meta_keywords    = '';
		$this->meta_description = '';
		$this->template         = '';
		$this->full_path        = '';
		$this->js_css_objects   = '';
	}

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}" as the key
	 */
	public static $titleLangPrefix = 'frontpage_fpage_';

	/**
	 * Load record based on a URL
	 *
	 * @param	\IPS\Http\Url	$url	URL to load from
	 * @return	\IPS\frontpage\Fpages\Fpage
	 * @throws	\InvalidArgumentException
	 * @throws	\OutOfRangeException
	 */
	public static function loadFromUrl( \IPS\Http\Url $url )
	{
		$qs = array_merge( $url->hiddenQueryString, $url->queryString );

		if ( isset( $qs['id'] ) )
		{
			if ( method_exists( \get_called_class(), 'loadAndCheckPerms' ) )
			{
				return static::loadAndCheckPerms( $qs['id'] );
			}
			else
			{
				return static::load( $qs['id'] );
			}
		}
		else if ( isset( $qs['path'] ) )
		{
			try
			{
				$return = static::load( $qs['path'], 'fpage_full_path' );
			}
			catch( \OutOfRangeException $ex )
			{
				$return = static::loadFromPath( $qs['path'] );
			}	
			
			if ( method_exists( $return, 'can' ) )
			{
				if ( !$return->can( 'view' ) )
				{
					throw new \OutOfRangeException;
				}
			}
			return $return;
		}
	
		throw new \InvalidArgumentException;
	}
	
	/**
	 * Get the fpage based on the database ID
	 * 
	 * @param int $databaseId
	 * @return	\IPS\frontpage\Fpages\Fpage object
	 * @throws  \OutOfRangeException
	 */
	public static function loadByDatabaseId( $databaseId )
	{
		return static::load( \IPS\frontpage\Databases::load( $databaseId )->fpage_id );
	}
	
	/**
	 * Resets a fpage path
	 *
	 * @param 	int 	$folderId	Folder ID to reset
	 * @return	void
	 */
	public static function resetPath( $folderId )
	{
		$path = $folderId ? \IPS\frontpage\Fpages\Folder::load( $folderId )->path : '';
	
		$children = static::getChildren( $folderId );
	
		foreach( $children as $id => $obj )
		{
			$obj->setFullPath( $path );
		}
	}
	
	/**
	 * Get all children of a specific folder.
	 *
	 * @param	INT 	$folderId		Folder ID to fetch children from
	 * @return	array
	 */
	public static function getChildren( $folderId=0 )
	{
		$children = array();
		foreach( \IPS\Db::i()->select( '*', static::$databaseTable, array( 'fpage_folder_id=?', \intval( $folderId ) ), 'fpage_seo_name ASC' ) as $child )
		{
			$children[ $child[ static::$databasePrefix . static::$databaseColumnId ] ] = static::load( $child[ static::$databasePrefix . static::$databaseColumnId ] );
		}
	
		return $children;
	}

	/**
	 * Returns a fpage object (or NULL) based on the path
	 * 
	 * @param	string	$path	Path /like/this/ok.html
	 * @return	NULL|\IPS\frontpage\Fpages\Fpage object
	 */
	public static function loadFromPath( $path )
	{
		$path = trim( $path, '/' );
		
		if ( ! array_key_exists( $path, static::$loadedFpagesFromPath ) )
		{
			static::$loadedFpagesFromPath[ $path ] = NULL;
			
			/* Try the simplest option */
			try
			{
				static::$loadedFpagesFromPath[ $path ] =  static::load( $path, 'fpage_full_path' );
			}
			catch ( \OutOfRangeException $e )
			{
				/* Nope - try a folder */
				try
				{
					if ( $path )
					{
						$class  = static::$parentNodeClass;
						$folder = $class::load( $path, 'folder_path' );
						
						static::$loadedFpagesFromPath[ $path ] = static::getDefaultFpage( $folder->id );
					}
					else
					{
						static::$loadedFpagesFromPath[ $path ] = static::getDefaultFpage( 0 );
					}
				}
				catch ( \OutOfRangeException $e )
				{
					/* May contain a database path */
					if ( \strstr( $path, '/' ) )
					{
						$bits = explode( '/', $path );
						$pathsToTry = array();
						
						while( \count( $bits ) )
						{
							$pathsToTry[] = implode( '/', $bits );
							
							array_pop($bits);
						}
						
						try
						{
							static::$loadedFpagesFromPath[ $path ] = static::constructFromData( \IPS\Db::i()->select( '*', 'frontpage_fpages', \IPS\Db::i()->in( 'fpage_full_path', $pathsToTry ), 'fpage_full_path DESC' )->first() );
						}
						catch( \UnderFlowException $e )
						{
							/* Last chance saloon */
							foreach( \IPS\Db::i()->select( '*', 'frontpage_fpages', array( '? LIKE CONCAT( fpage_full_path, \'%\')', $path ), 'fpage_full_path DESC' ) as $fpage )
							{
								if ( mb_stristr( $fpage['fpage_content'], '{database' ) )
								{
									static::$loadedFpagesFromPath[ $path ] = static::constructFromData( $fpage );
									break;
								}
							}
							
							/* Still here? It's possible this is a legacy URL that starts with "fpage" - last ditch effort */
							if ( static::$loadedFpagesFromPath[ $path ] === NULL AND mb_substr( $path, 0, 5 ) === 'fpage/' )
							{
								$pathWithoutFpage = str_replace( 'fpage/', '', $path );
								
								try
								{
									/* Pass back recursively so we don't have to duplicate all of the checks again */
									static::$loadedFpagesFromPath[ $path ] = static::loadFromPath( $pathWithoutFpage );
								}
								catch( \OutOfRangeException $e ) {}
							}
						}
					}
				}
			}
		}
		
		if ( static::$loadedFpagesFromPath[ $path ] === NULL )
		{
			throw new \OutOfRangeException;
		}

		return static::$loadedFpagesFromPath[ $path ];
	}

	/**
	 * Load from path history so we can 301 to the correct record.
	 *
	 * @param	string		$slug			Thing that lives in the garden and eats your plants
	 * @param	string|NULL	$queryString	Any query string to add to the end
	 * @return	\IPS\Http\Url
	 */
	public static function getUrlFromHistory( $slug, $queryString=NULL )
	{
		$slug = trim( $slug, '/' );
		
		try
		{
			$row = \IPS\Db::i()->select( '*', 'frontpage_url_store', array( 'store_type=? and store_path=?', 'fpage', $slug ) )->first();

			return static::load( $row['store_current_id'] )->url();
		}
		catch( \UnderflowException $ex )
		{
			/* Ok, perhaps this is a full URL with the fpage name at the beginning */
			foreach( \IPS\Db::i()->select( '*', 'frontpage_url_store', array( 'store_type=? and ? LIKE CONCAT( store_path, \'%\') OR store_path=?', 'fpage', $slug, $slug ) ) as $item )
			{
				$url = static::load( $item['store_current_id'] )->url();
				$url = $url->setPath( '/' . trim( str_replace( $item['store_path'], trim( $url->data['path'], '/' ), $slug ), '/' ) );
				if ( $queryString !== NULL )
				{
					$url = $url->setQueryString( $queryString );
				}
				return $url;
			}
			
			/* Still here? Ok, now we may have changed the folder name at some point, so lets look for that */
			foreach( \IPS\Db::i()->select( '*', 'frontpage_url_store', array( 'store_type=? and ? LIKE CONCAT( store_path, \'%\') OR store_path=?', 'folder', $slug, $slug ) ) as $item )
			{
				try
				{
					$folder = \IPS\frontpage\Fpages\Folder::load( $item['store_current_id'] );

					/* Attempt to build the new path */
					$newPath = str_replace( $item['store_path'], $folder->path, $slug );

					/* Do we have a fpage with this path? */
					try
					{
						return static::load( $newPath, 'fpage_full_path' )->url();
					}
					catch( \OutOfRangeException $ex )
					{
						/* This is not the path you are looking for */
					}

				}
				catch( \OutOfRangeException $ex )
				{
					/* This also is not the path you are looking for */
				}
			}
		}

		/* Still here? Consistent with AR pattern */
		throw new \OutOfRangeException();
	}
	
	/**
	 * Return the default fpage for this folder
	 *
	 * @param	INT 	$folderId		Folder ID to fetch children from
	 * @return	\IPS\frontpage\Fpages\Fpage
	 */
	public static function getDefaultFpage( $folderId=0 )
	{
		if ( ! isset( static::$defaultFpage[ $folderId ] ) )
		{
			/* Try the easiest method first */
			try
			{
				static::$defaultFpage[ $folderId ] = \IPS\frontpage\Fpages\Fpage::load( \IPS\Db::i()->select( 'fpage_id', static::$databaseTable, array( 'fpage_default=? AND fpage_folder_id=?', 1, \intval( $folderId ) ) )->first() );
			}
			catch( \Exception $ex )
			{
				throw new \OutOfRangeException;
			}

			/* Got a fpage called index? */
			if ( ! isset( static::$defaultFpage[ $folderId ] ) )
			{
				foreach( static::getChildren( $folderId ) as $id => $obj )
				{
					if ( \mb_substr( $obj->seo_name, 0, 5 ) === 'index' )
					{
						return $obj;
					}
				}

				reset( $children );

				/* Just return the first, then */
				static::$defaultFpage[ $folderId ] = array_shift( $children );
			}
		}

		return ( isset( static::$defaultFpage[ $folderId ] ) ) ? static::$defaultFpage[ $folderId ] : NULL;
	}
	
	/**
	 * Delete compiled versions
	 *
	 * @param 	int|array 	$ids	Integer ID or Array IDs to remove
	 * @return void
	 */
	public static function deleteCompiled( $ids )
	{
		if ( \is_numeric( $ids ) )
		{
			$ids = array( $ids );
		}
	
		foreach( $ids as $id )
		{
			$functionName = 'content_fpages_' .  $id;
			if ( isset( \IPS\Data\Store::i()->$functionName ) )
			{
				unset( \IPS\Data\Store::i()->$functionName );
			}
		}
	}

	/**
	 * Removes all include objects from all fpages
	 *
	 * @param   boolean     $url     				The URL to find and remove
	 * @param	NULL|int	$storageConfiguration	Delete the cached includes from an alternate storage configuration
	 * @note	This method is called by \IPS\frontpage\extensions\core\FileStorage\Fpages.php during a move, and in that process we want to remove resources
	 	from the old storage configuration, not the new one (which is what happens when the configuration id is not passed in to \IPS\File and a move is in progress)
	 * @return void
	 */
	static public function deleteCachedIncludes( $url=NULL, $storageConfiguration=NULL )
	{
		/* Remove them all */
		if ( $url === NULL )
		{
            /* Remove from DB */
            if ( \IPS\Db::i()->checkForTable( 'frontpage_fpages' ) )
            {
                \IPS\Db::i()->update( 'frontpage_fpages', array( 'fpage_js_css_objects' => NULL ) );
            }
			/* Remove from file system */
			\IPS\File::getClass( $storageConfiguration ?: 'frontpage_Fpages' )->deleteContainer('fpage_objects');
		}
		else
		{
			$bits = explode( '/', (string ) $url );
			$name = array_pop( $bits );

			/* Remove selectively */
			foreach( \IPS\Db::i()->select( '*', 'frontpage_fpages', array( "fpage_js_css_objects LIKE '%" . \IPS\Db::i()->escape_string( $name ) . "%'" ) ) as $row )
			{
				\IPS\Db::i()->update( 'frontpage_fpages', array( 'fpage_js_css_objects' => NULL ), array( 'fpage_id=?', $row['fpage_id'] ) );
			}
		}
	}

	/**
	 * Show a custom error fpage
	 *
	 * @param   string  $title          Title of the fpage
	 * @param	string	$message		language key for error message
	 * @param	mixed	$code			Error code
	 * @param	int		$httpStatusCode	HTTP Status Code
	 * @param	array	$httpHeaders	Additional HTTP Headers
	 * @return  void
	 */
	static public function errorFpage( $title, $message, $code, $httpStatusCode, $httpHeaders )
	{
		try
		{
			$fpage = static::load( \IPS\Settings::i()->frontpage_error_fpage );
			$content = $fpage->getHtmlContent();
			$content = str_replace( '{error_message}', $message, $content );
			$content = str_replace( '{error_code}', $code, $content );
			
			/* Fpages are compiled and cached, which we do not want for the error fpage as the {error_*} tags are saved with their text content */
			$functionName = 'content_fpages_' .  $fpage->id;
			if ( isset( \IPS\Data\Store::i()->$functionName ) )
			{
				unset( \IPS\Data\Store::i()->$functionName );
			}
			
			$fpage->output( $title, $httpStatusCode, $httpHeaders, $content );
		}
		catch( \Exception $ex )
		{
			\IPS\Output::i()->sidebar['enabled'] = FALSE;
			\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( $title, \IPS\Theme::i()->getTemplate( 'global', 'core' )->error( $title, $message, $code, NULL, \IPS\Member::loggedIn() ), array( 'app' => \IPS\Dispatcher::i()->application ? \IPS\Dispatcher::i()->application->directory : NULL, 'module' => \IPS\Dispatcher::i()->module ? \IPS\Dispatcher::i()->module->key : NULL, 'controller' => \IPS\Dispatcher::i()->controller ) ), $httpStatusCode, 'text/html', $httpHeaders, FALSE, FALSE );
		}
	}

	/**
	 * Form elements
	 *
	 * @param	object|null		$item	Fpage object or NULL
	 * @return	array
	 */
	static public function formElements( $item=NULL )
	{
		$return   = array();
		$fpageType = isset( \IPS\Request::i()->fpage_type ) ? \IPS\Request::i()->fpage_type : ( $item ? $item->type : 'html' );
		$return['tab_details'] = array( 'content_fpage_form_tab__details', NULL, NULL, 'ipsForm_horizontal' );

		$return['fpage_name'] = new \IPS\Helpers\Form\Translatable( 'fpage_name', NULL, TRUE, array( 'app' => 'frontpage', 'key' => ( $item and $item->id ) ? "frontpage_fpage_" . $item->id : NULL, 'maxLength' => 64 ), function( $val )
		{
			if ( empty( $val ) )
			{
				throw new \DomainException('form_required');
			}		
		}, NULL, NULL, 'fpage_name' );
		
		$return['fpage_seo_name'] = new \IPS\Helpers\Form\Text( 'fpage_seo_name', $item ? $item->seo_name : '', FALSE, array( 'maxLength' => 255 ), function( $val )
		{
			if ( empty( $val ) )
			{
				$val = \IPS\Http\Url\Friendly::seoTitle( $val );
			}
			
			/* We cannot have a fpage name the same as a folder name in this folder */
			try
			{
				$testFolder = \IPS\frontpage\Fpages\Folder::load( $val, 'folder_name' );
				
				/* Ok, we have a folder, but is it on the same tree as us ?*/
				if ( \intval( \IPS\Request::i()->fpage_folder_id ) == $testFolder->parent_id )
				{
					/* Yep, this will break designers' mode and may confuse the FURL engine so we cannot allow this */
					throw new \InvalidArgumentException('content_folder_name_furl_collision_fpages');
				}
			}
			catch ( \OutOfRangeException $e )
			{
				/* Nothing with the same name, so that's proper good that is */
			}
			
			/* If we hit here, we don't have an existing name so that's good */
			if ( ! \IPS\Request::i()->fpage_folder_id  and \IPS\frontpage\Fpages\Fpage::isFurlCollision( $val ) )
			{
				throw new \InvalidArgumentException('content_folder_name_furl_collision_fpages_app');
			}
				
			try
			{
				$test = \IPS\frontpage\Fpages\Fpage::load( $val, 'fpage_seo_name' );

				if ( isset( \IPS\Request::i()->id ) )
				{
					if ( $test->id == \IPS\Request::i()->id )
					{
						/* Just us.. */
						return TRUE;
					}
				}

				/* Not us */
				if ( \intval( \IPS\Request::i()->fpage_folder_id ) == $test->folder_id )
				{
					throw new \InvalidArgumentException( 'content_fpage_file_name_in_use' );
				}

			}
			catch ( \OutOfRangeException $e ) 
			{
				/* An exception means we don't have a match, so that is good */	
			}
		}, NULL, NULL, 'fpage_seo_name' );

		$return['fpage_folder_id'] = new \IPS\Helpers\Form\Node( 'fpage_folder_id', ( $item ? \intval( $item->folder_id ) : ( ( isset( \IPS\Request::i()->parent ) and \IPS\Request::i()->parent ) ? \IPS\Request::i()->parent : 0 ) ), FALSE, array(
				'class'         => 'IPS\frontpage\Fpages\Folder',
				'zeroVal'       => 'node_no_parent',
				'subnodes'		=> false
		), NULL, NULL, NULL, 'fpage_folder_id' );
	

		$return['fpage_ipb_wrapper'] = new \IPS\Helpers\Form\YesNo( 'fpage_ipb_wrapper', $item AND $item->id ? $item->ipb_wrapper : 1, TRUE, array(
				'togglesOn' => array( 'fpage_show_sidebar' ),
		        'togglesOff' => array( 'fpage_wrapper_template' )
		), NULL, NULL, NULL, 'fpage_ipb_wrapper' );

		$return['fpage_show_sidebar'] = new \IPS\Helpers\Form\YesNo( 'fpage_show_sidebar', $item ? $item->show_sidebar : TRUE, FALSE, array(), NULL, NULL, NULL, 'fpage_show_sidebar' );

		$wrapperTemplates = array( '_none_' => \IPS\Member::loggedIn()->language()->addToStack('frontpage_fpage_wrapper_template_none') );
		foreach( \IPS\frontpage\Templates::getTemplates( \IPS\frontpage\Templates::RETURN_FPAGE + \IPS\frontpage\Templates::RETURN_DATABASE_AND_IN_DEV ) as $id => $obj )
		{
			if ( $obj->isSuitableForCustomWrapper() )
			{
				$wrapperTemplates[ \IPS\frontpage\Templates::readableGroupName( $obj->group ) ][ $obj->group . '__' . $obj->title . '__' . $obj->key ] = \IPS\frontpage\Templates::readableGroupName( $obj->title );
			}
		}

		/* List of templates */
		$return['fpage_wrapper_template'] = new \IPS\Helpers\Form\Select( 'fpage_wrapper_template', ( $item ? $item->wrapper_template : NULL ), FALSE, array(
			         'options' => $wrapperTemplates
		), NULL, NULL, \IPS\Theme::i()->getTemplate( 'fpages', 'frontpage', 'admin' )->previewTemplateLink(), 'fpage_wrapper_template' );

		if ( \count( \IPS\Theme::themes() ) > 1 )
		{
			$themes = array( 0 => 'frontpage_fpage_theme_id_default' );
			foreach ( \IPS\Theme::themes() as $theme )
			{
				$themes[ $theme->id ] = $theme->_title;
			}

			$return['fpage_theme'] = new \IPS\Helpers\Form\Select( 'fpage_theme', $item ? $item->theme : 0, FALSE, array( 'options' => $themes ), NULL, NULL, NULL, 'fpage_theme' );
		}

		$builderTemplates = array();
		foreach( \IPS\frontpage\Templates::getTemplates( \IPS\frontpage\Templates::RETURN_FPAGE + \IPS\frontpage\Templates::RETURN_DATABASE_AND_IN_DEV ) as $id => $obj )
		{
			if ( $obj->isSuitableForBuilderWrapper() )
			{
				$builderTemplates[ \IPS\frontpage\Templates::readableGroupName( $obj->group ) ][ $obj->group . '__' . $obj->title . '__' . $obj->key ] = \IPS\frontpage\Templates::readableGroupName( $obj->title );
			}
		}

		$return['fpage_template'] = new \IPS\Helpers\Form\Select( 'fpage_template', ( $item and $item->template ) ? $item->template : FALSE, FALSE, array( 'options' => $builderTemplates ), NULL, NULL, NULL, 'fpage_template' );

		/* Fpage CSS and JS */
		$js  = \IPS\frontpage\Templates::getTemplates( \IPS\frontpage\Templates::RETURN_ONLY_JS + \IPS\frontpage\Templates::RETURN_DATABASE_ONLY );
		$css = \IPS\frontpage\Templates::getTemplates( \IPS\frontpage\Templates::RETURN_ONLY_CSS + \IPS\frontpage\Templates::RETURN_DATABASE_ONLY );

		if ( \count( $js ) OR \count( $css ) )
		{
			$return['tab_js_css'] = array( 'content_fpage_form_tab__includes', NULL, NULL, 'ipsForm_horizontal' );
			$return['msg_js_css'] = array( 'frontpage_fpage_includes_message', 'ipsMessage ipsMessage_info ipsFrontpageIncludesMessage' );

			if ( \count( $js ) )
			{
				$jsincludes = array();
				foreach( $js as $obj )
				{
					$jsincludes[ $obj->key ] = \IPS\frontpage\Templates::readableGroupName( $obj->group ) . '/' . \IPS\frontpage\Templates::readableGroupName( $obj->title );
				}
				ksort( $jsincludes );

				$return['fpage_includes_js'] = new \IPS\Helpers\Form\CheckboxSet( 'fpage_includes_js', $item ? $item->js_includes : FALSE, FALSE, array( 'options' => $jsincludes, 'multiple' => true ), NULL, NULL, NULL, 'fpage_includes_js' );
			}

			if ( \count( $css ) )
			{
				$cssincludes = array();
				foreach( $css as $obj )
				{
					$cssincludes[ $obj->key ] = \IPS\frontpage\Templates::readableGroupName( $obj->group ) . '/' . \IPS\frontpage\Templates::readableGroupName( $obj->title );
				}
				ksort( $cssincludes );
				
				$return['fpage_includes_css'] = new \IPS\Helpers\Form\CheckboxSet( 'fpage_includes_css', $item ? $item->css_includes : FALSE, FALSE, array( 'options' => $cssincludes, 'multiple' => true ), NULL, NULL, NULL, 'fpage_includes_css' );
			}
		}

		if ( $fpageType === 'html' )
		{
			$return['tab_content'] = array( 'content_fpage_form_tab__content', NULL, NULL, 'ipsForm_vertical' );
		
			$tags = array();

			if ( $item and $item->id == \IPS\Settings::i()->frontpage_error_fpage )
			{
				$tags['frontpage_error_fpage']['{error_message}'] = 'frontpage_error_fpage_message';
				$tags['frontpage_error_fpage']['{error_code}']    = 'frontpage_error_fpage_code';
			}

			foreach( \IPS\frontpage\Databases::roots( NULL, NULL ) as $id => $db )
			{
				if ( ! $db->fpage_id )
				{
					$tags['frontpage_tag_databases']['{database="' . $db->key . '"}'] = $db->_title;
				}
			}
			
			foreach( \IPS\frontpage\Blocks\Block::roots( NULL, NULL ) as $id => $block )
			{
				$tags['frontpage_tag_blocks']['{block="' . $block->key . '"}'] = $block->_title;
			}

			foreach( \IPS\Db::i()->select( '*', 'frontpage_fpages', NULL, 'fpage_full_path ASC', array( 0, 50 ) ) as $fpage )
			{
				$tags['frontpage_tag_fpages']['{fpageurl="' . $fpage['fpage_full_path'] . '"}' ] = \IPS\Member::loggedIn()->language()->addToStack( static::$titleLangPrefix . $fpage['fpage_id'] );
			}

			$return['fpage_content'] = new \IPS\Helpers\Form\Codemirror( 'fpage_content', $item ? htmlentities( $item->content, ENT_DISALLOWED, 'UTF-8', TRUE ) : NULL, FALSE, array( 'tags' => $tags, 'height' => 600 ), function( $val )
			{
				/* Test */
				try
				{
					\IPS\Theme::checkTemplateSyntax( $val );
				}
				catch( \LogicException $e )
				{
					throw new \LogicException('frontpage_fpage_error_bad_syntax');
				}
				
				/* New fpage? quick check to see if we added a DB tag, and if so, make sure it's not being used on another fpage. */
				if ( ! isset( \IPS\Request::i()->id ) )
				{
					preg_match( '#{database="([^"]+?)"#', $val, $matches );
					if ( isset( $matches[1] ) )
					{
						$database = NULL;

						if ( \is_numeric( $matches[1] ) )
						{
							try
							{
								$database = \IPS\frontpage\Databases::load( \intval( $matches[1] ) );
							}
							catch( \OutOfRangeException $ex ){}
						}

						if( $database === NULL )
						{
							try
							{
								$database = \IPS\frontpage\Databases::load( $matches[1], 'database_key' );
							}
							catch( \OutOfRangeException $ex ){}
						}

						if( $database === NULL )
						{
							throw new \LogicException('frontpage_err_db_does_not_exist');
						}
						elseif( $database->fpage_id )
						{
							throw new \LogicException('frontpage_err_db_in_use_other_page');
						}
					}
				}
			}, NULL, NULL, 'fpage_content' );
		}
	
		$return['tab_meta'] = array( 'content_fpage_form_tab__meta', NULL, NULL, 'ipsForm_horizontal' );
		$return['fpage_title'] = new \IPS\Helpers\Form\Text( 'fpage_title', $item ? $item->title : '', FALSE, array( 'maxLength' => 64 ), NULL, NULL, NULL, 'fpage_title' );
		$return['fpage_meta_keywords'] = new \IPS\Helpers\Form\TextArea( 'fpage_meta_keywords', $item ? $item->meta_keywords : '', FALSE, array(), NULL, NULL, NULL, 'fpage_meta_keywords' );
		$return['fpage_meta_description'] = new \IPS\Helpers\Form\TextArea( 'fpage_meta_description', $item ? $item->meta_description : '', FALSE, array(), NULL, NULL, NULL, 'fpage_meta_description' );

		\IPS\Output::i()->globalControllers[]  = 'frontpage.admin.fpages.form';
		\IPS\Output::i()->jsFiles  = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_fpages.js', 'frontpage' ) );
		
		return $return;
	}
	
	/**
	 * Create a new fpage from a form. Pretty much what the function says.
	 * 
	 * @param	array		 $values	Array of form values
	 * @param	string		 $fpageType	Type of fpage. 'html', 'builder' or 'editor'
	 * @return	\IPS\frontpage\Fpages\Fpage object
	 */
	static public function createFromForm( $values, $fpageType=NULL )
	{
		$fpage = new self;
		$fpage->type = $fpageType;
		$fpage->save();

		$fpage->saveForm( $fpage->formatFormValues( $values ), $fpageType );

		/* Set permissions */
		\IPS\Db::i()->update( 'core_permission_index', array( 'perm_view' => '*' ), array( 'app=? and perm_type=? and perm_type_id=?', 'frontpage', 'fpages', $fpage->id ) );
		
		return $fpage;
	}

	/**
	 * Ensure there aren't any collision issues when the CMS is the default app and folders such as "forums" are created when
	 * the forums app is installed.
	 *
	 * @param   string  $path   Path to check
	 * @return  boolean
	 */
	static public function isFurlCollision( $path )
	{
		$path   = trim( $path , '/');
		$bits   = explode( '/', $path );
		$folder = $bits[0];
		
		/* Ensure we cannot have a structure that starts with core/interface as we have this partial URL blacklisted in \IPS\Text\Parser::safeIframeRegexp() */
		if ( mb_substr( $path, 0, 15 ) == 'core/interface/' )
		{
			return TRUE;
		}
		
		/* Cannot have /fpage/ as it confuses SEO pagination */
		if ( mb_strstr( '/' . $path . '/', '/fpage/' ) )
		{
			return TRUE;
		}
		
		$defaultApplication = \IPS\Db::i()->select( 'app_directory', 'core_applications', 'app_default=1' )->first();

		foreach( \IPS\Application::applications() as $key => $app )
		{
			if ( $app->directory === 'frontpage' )
			{
				continue;
			}

			$furlDefinitionFile = \IPS\ROOT_PATH . "/applications/{$app->directory}/data/furl.json";
			if ( file_exists( $furlDefinitionFile ) )
			{
				$furlDefinition = json_decode( preg_replace( '/\/\*.+?\*\//s', '', file_get_contents( $furlDefinitionFile ) ), TRUE );

				if ( isset( $furlDefinition['topLevel'] ) )
				{
					if ( $furlDefinition['topLevel'] == $folder )
					{
						return TRUE;
					}

					if ( isset( $furlDefinition['fpages'] ) )
					{
						foreach( $furlDefinition['fpages'] as $name => $data )
						{
							if ( isset( $data['friendly'] ) )
							{
								$furlBits = explode( '/', $data['friendly'] );

								if ( $furlBits[0] == $folder )
								{
									return TRUE;
								}
							}
						}
					}
				}
			}
		}
		
		/* Still here? Some apps use very loose matching, like calendar looks for {id}-{?} which may conflict with a fpage with a filename of 123-foo */
		try
		{
			$url = \IPS\Http\Url::createFromString( \IPS\Http\Url::baseUrl() . $path );
		
			if ( $url and $url instanceof \IPS\Http\Url\Friendly and $url->seoTemplate !== 'content_fpage_path' )
			{
				return TRUE;
			}
		}
		catch( \Exception $ex )
		{
			/* If we get an error, then it cannot be a legitimate link */
		}

		return FALSE;
	}
	
	/**
	 * Delete all stored includes so they can be rebuilt on demand.
	 *
	 * @return	void
	 */
	public static function deleteCompiledIncludes()
	{
		\IPS\Db::i()->update( 'frontpage_fpages', array( 'fpage_js_css_objects' => NULL ) );
		\IPS\frontpage\Templates::deleteCompiledFiles();
	}
	
	/**
	 * Create a datastore object of fpage IDs and URLs.
	 *
	 * @return void
	 */
	public static function buildFpageUrlStore()
	{
		/* This fails because hooks are not installed when this is attempted to build via admin/install */
		if ( \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation === 'setup' )
		{
			return;
		}
		
		/* This also fails if we're installing via ACP */
		if ( \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation === 'admin' and isset( \IPS\Request::i()->do ) and \IPS\Request::i()->do == 'install' )
		{
			return;
		}
		
		$store = array();
		foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'frontpage_fpages' ), 'IPS\frontpage\Fpages\Fpage' ) as $fpage )
		{
			$perms = $fpage->permissions();
			$store[ $fpage->id ] = array( 'url' => (string) $fpage->url(), 'perm' => $perms['perm_view'] );
		}
		
		\IPS\Data\Store::i()->fpages_fpage_urls = $store;

		\IPS\Member::clearCreateMenu();
	}
	
	/**
	 * Returns (and builds if required) the fpages id => url datastore
	 *
	 * @return array
	 */
	public static function getStore()
	{
		if ( ! isset( \IPS\Data\Store::i()->fpages_fpage_urls ) )
		{
			static::buildFpageUrlStore();
		}
		
		return \IPS\Data\Store::i()->fpages_fpage_urls;
	}

	/**
	 * Returns (and builds if required) the fpages id => url datastore
	 *
	 * @return array
	 * @deprecated
	 */
	public static function getFpageUrlStore()
	{
		return static::getStore();
	}
		
	/**
	 * Set JS/CSS include keys
	 *
	 * @param string|array $value
	 * @return void
	 */
	public function set__js_css_ids( $value )
	{
		$this->_data['js_css_ids'] = ( \is_array( $value ) ? json_encode( $value ) : $value );
	}

	/**
	 * Get JS/CSS include keys
	 *
	 * @return	array|null
	 */
	protected function get__js_css_ids()
	{
		if ( ! \is_array( $this->_data['js_css_ids'] ) )
		{
			$this->_data['js_css_ids'] = json_decode( $this->_data['js_css_ids'], true );
		}

		return ( \is_array( $this->_data['js_css_ids'] ) ) ? $this->_data['js_css_ids'] : array();
	}

	/**
	 * Get JS include keys
	 *
	 * @return	array
	 */
	protected function get_js_includes()
	{
		/* Makes sure js_css_ids is unpacked if required */
		$this->_js_css_ids;

		if ( isset( $this->_data['js_css_ids']['js'] ) )
		{
			return $this->_data['js_css_ids']['js'];
		}

		return array();
	}

	/**
	 * Get CSS include keys
	 *
	 * @return	array
	 */
	protected function get_css_includes()
	{
		/* Makes sure js_css_ids is unpacked if required */
		$this->_js_css_ids;

		if ( isset( $this->_data['js_css_ids']['css'] ) )
		{
			return $this->_data['js_css_ids']['css'];
		}

		return array();
	}

	/**
	 *  Get JS/CSS Objects
	 *
	 * @return array
	 */
	public function getIncludes()
	{
		$return = array( 'css' => NULL, 'js' => NULL );
		
		if ( \IPS\Theme::designersModeEnabled() )
		{
			foreach( array( 'js', 'css' ) as $thing )
			{
				$includeKey = $thing . '_includes';
				if ( \count( $this->$includeKey ) )
				{
					/* Build a file object for each thing */
					foreach( $this->$includeKey as $key )
					{
						try
						{
							$template = \IPS\frontpage\Templates::load( $key );
							
							$return[ $thing ][ $key ] = \IPS\Http\Url::createFromString( \IPS\Http\Url::baseUrl() . "/applications/frontpage/interface/developer/developer.php" )
								->setQueryString( 'file', 'frontpage/' . $template->location . '/' . $template->group . '/' . $template->title );
						}
						catch( \OutOfRangeException $e )
						{
							continue;
						}
					}
				}
			}
			
			return $return;
		}
		
		/* Empty? Lets take a look and see if we need to compile anything */
		if ( empty( $this->_data['js_css_objects'] ) )
		{
			/* Lock it up to prevent a race condition */
			if ( \IPS\Theme::checkLock( "fpage_object_build" . $this->id ) )
			{
				return NULL;
			}

			\IPS\Theme::lock( "fpage_object_build" . $this->id );
			
			if ( \count( $this->js_includes ) )
			{
				/* Build a file object for each JS */
				foreach( $this->js_includes as $key )
				{
					try
					{
						$template = \IPS\frontpage\Templates::load( $key );
						$object   = $template->_file_object;

						$return['js'][ $key ] = $object;
					}
					catch( \OutOfRangeException $e )
					{
						continue;
					}
				}
			}

			if ( \count( $this->css_includes ) )
			{
				/* Build a file object for each JS */
				foreach( $this->css_includes as $key )
				{
					try
					{
						$template = \IPS\frontpage\Templates::load( $key );
						$object   = $template->_file_object;

						$return['css'][ $key ] = $object;
					}
					catch( \Exception $e )
					{
						continue;
					}
				}
			}
			
			if ( ! \IPS\Theme::designersModeEnabled() )
			{
				/* Save this to prevent it looking for includes on every fpage refresh */
				$this->js_css_objects = json_encode( $return );
				$this->save();
			}
			
			\IPS\Theme::unlock( "fpage_object_build" . $this->id );
		}
		else
		{
			$return = json_decode( $this->_data['js_css_objects'], TRUE );
		}

		foreach( $return as $type => $data )
		{
			if ( \is_array( $data ) )
			{
				foreach( $data as $key => $object )
				{
					$return[ $type ][ $key ] = (string) \IPS\File::get( 'frontpage_Fpages', $object )->url;
				}
			}
		}

		return $return;
	}

	/**
	 * Get the content type of this fpage. Calculates based on fpage extension
	 *
	 * @return string
	 */
	public function getContentType()
	{
		$map  = array(
			'js'   => 'text/javascript',
			'css'  => 'text/css',
			'txt'  => 'text/plain',
			'xml'  => 'text/xml',
			'rss'  => 'text/xml',
			'html' => 'text/html',
			'json' => 'application/json'
		);

		$extension = mb_substr( $this->seo_name, ( mb_strrpos( $this->seo_name, '.' ) + 1 ) );

		if ( \in_array( $extension, array_keys( $map ) ) )
		{
			return $map[ $extension ];
		}

		return 'text/html';
	}

	/**
	 * Return the title for the publicly viewable HTML fpage
	 * 
	 * @return string	Title to use between <title> tags
	 */
	public function getHtmlTitle()
	{
		if ( $this->title )
		{
			return $this->title;
		}
		
		if ( $this->_title )
		{
			return $this->_title;
		}
		
		return $this->name;
	}
	
	/**
	 * Return the content for the publicly viewable HTML fpage
	 * 
	 * @return	string	HTML to use on the fpage
	 */
	public function getHtmlContent()
	{
		$functionName = 'content_fpages_' .  $this->id;
		
		if ( \IPS\Theme::i()->designersModeEnabled() and file_exists( \IPS\ROOT_PATH . '/themes/frontpage/fpages/' . $this->full_path ) )
		{
			$contents = @file_get_contents( \IPS\ROOT_PATH . '/themes/frontpage/fpages/' . $this->full_path );
			$contents = preg_replace( '#^<ips:fpages.+?/>(\r\n?|\n)#', '', $contents );
			try
			{
				\IPS\Theme::runProcessFunction( \IPS\Theme::compileTemplate( $contents, $functionName, null, true ), $functionName );
			}
			catch( \Exception $e )
			{
				\IPS\Log::log( $e, 'fpages_error' );
				return '';
			}
		}
		else
		{
			if ( ! isset( \IPS\Data\Store::i()->$functionName ) )
			{ 
				\IPS\Data\Store::i()->$functionName = \IPS\Theme::compileTemplate( $this->content, $functionName, null, true );
			}
			
			\IPS\Theme::runProcessFunction( \IPS\Data\Store::i()->$functionName, $functionName );
		}
		
		$themeFunction = 'IPS\\Theme\\'. $functionName;
		return $themeFunction();
	}

	/**
	 * @brief	Cached widgets
	 */
	protected $cachedWidgets = NULL;

	/**
	 * Return the blocks for this fpage
	 *
	 * @return	array
	 */
	public function getWidgets()
	{
		if( $this->cachedWidgets !== NULL )
		{
			return $this->cachedWidgets;
		}

		$this->cachedWidgets = array();

		$widgets	= array();
		$dbWidgets	= array();
		$areas		= array();
		
		foreach( \IPS\Db::i()->select( '*', 'frontpage_fpage_widget_areas', array( 'area_fpage_id=?', $this->id ) ) as $k => $widgetMain )
		{
			$widgets[ $widgetMain['area_area'] ] = json_decode( $widgetMain['area_widgets'], TRUE );
			$areas[ $widgetMain['area_area'] ]   = $widgetMain;

			/* We need to execute database widgets first as this sets up the Database dispatcher correctly */
			foreach( $widgets[ $widgetMain['area_area'] ] as $widget )
			{
				/* Do not attempt to re-parse database widgets if we already have */
				if ( ! \IPS\frontpage\Databases\Dispatcher::i()->databaseId AND $widget['key'] === 'Database' )
				{
					$orientation = ( ( $widgetMain['area_area'] == 'sidebar' ) ? 'vertical' : ( ( $widgetMain['area_area'] === 'header' OR $widgetMain['area_area'] === 'footer' ) ? 'horizontal' : $widgetMain['area_orientation'] ) );
					$dbWidgets[ $widget['unique'] ] = \IPS\Widget::load( \IPS\Application::load( $widget['app'] ), $widget['key'], $widget['unique'], ( isset( $widget['configuration'] ) ) ? $widget['configuration'] : array(), ( isset( $widget['restrict'] ) ? $widget['restrict'] : null ), $orientation );
					$dbWidgets[ $widget['unique'] ]->render();
				}
			}
		}
		
		if( \count( $widgets ) )
		{
			foreach ( $widgets as $areaKey => $area )
			{
				foreach ( $area as $widget )
				{
					try
					{
						if ( $widget['key'] == 'Database' and array_key_exists( $widget['unique'], $dbWidgets ) )
						{
							$_widget = $dbWidgets[ $widget['unique'] ];
						}
						else
						{
							$orientation = ( ( $areaKey == 'sidebar' ) ? 'vertical' : ( ( $areaKey === 'header' OR $areaKey === 'footer' ) ? 'horizontal' : $areas[ $areaKey ]['area_orientation'] ) );
							
							if ( isset( $widget['app'] ) and $widget['app'] )
							{
								$appOrPlugin = \IPS\Application::load( $widget['app'] );
							}
							else
							{
								$appOrPlugin = \IPS\Plugin::load( $widget['plugin'] );
							}
							
							$_widget = \IPS\Widget::load( $appOrPlugin, $widget['key'], $widget['unique'], ( isset( $widget['configuration'] ) ) ? $widget['configuration'] : array(), ( isset( $widget['restrict'] ) ? $widget['restrict'] : null ), $orientation );
						}
						
						if ( \in_array( $areaKey, array('header', 'footer', 'sidebar' ) ) )
						{
							\IPS\Output::i()->sidebar['widgets'][ $areaKey ][] = $_widget;
						}
							
						$this->cachedWidgets[ $areaKey ][] = $_widget;
					}
					catch ( \Exception $e )
					{
						\IPS\Log::debug( $e, 'fpages_widgets' );
					}
				}
			}
		}

		return $this->cachedWidgets;
	}
	
	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 * @endcode
	 * @param	string	$url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		$buttons = parent::getButtons( $url, $subnode );
		$return  = array();
		
		if ( isset( $buttons['add'] ) )
		{
			unset( $buttons['add'] );
		}
		
		if ( $this->type === 'builder' and isset( $buttons['edit'] ) )
		{
			$return['builder'] = array(
					'icon'	   => 'magic',
					'title'    => 'content_launch_fpage_builder',
					'link'	   => $this->url()->setQueryString( array( '_blockManager' => 1 ) ),
					'target'   => '_blank'
			);
		}
		else
		{
			$return['view'] = array(
					'icon'	   => 'search',
					'title'    => 'content_launch_fpage_view',
					'link'	   => $this->url(),
					'target'   => '_blank'
			);
		}
		
		if ( isset( $buttons['edit'] ) )
		{
			$buttons['edit']['title'] = \IPS\Member::loggedIn()->language()->addToStack('content_edit_fpage');
			$buttons['edit']['data']  = null;
		}
	
		/* Re-arrange */
		if ( isset( $buttons['edit'] ) )
		{
			$return['edit'] = $buttons['edit'];
		}
		
		if ( isset( $buttons['edit_content'] ) )
		{
			$return['edit_content'] = $buttons['edit_content'];
		}
		
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'frontpage', 'fpages', 'fpage_edit' ) )
		{
			$return['default'] = array(
				'icon'	=> $this->default ? 'star' : 'star-o',
				'title'	=> 'content_default_fpage',
				'link'	=> $url->setQueryString( array( 'id' => $this->id, 'subnode' => 1, 'do' => 'setAsDefault' ) )
			);
		}

		$return['view'] = array(
			'icon'	   => 'search',
			'title'    => 'content_launch_fpage',
			'link'	   => $this->url(),
			'target'   => '_blank'
		);
		
		if ( isset( $buttons['permissions'] ) )
		{
			$return['permissions'] = $buttons['permissions'];
		}
		
		if ( isset( $buttons['delete'] ) )
		{
			$return['delete'] = $buttons['delete'];
		}
		
		return $return;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		/* Build form */
		if ( ! $this->id )
		{
			$form->hiddenValues['fpage_type'] = $fpageType = \IPS\Request::i()->fpage_type;
		}
		else
		{
			$fpageType = $this->type;
		}

		/* We shut off the main class and add it per-tab to allow the content field to look good */
		$form->class = '';

		foreach( static::formElements( $this ) as $name => $field )
		{
			if ( $fpageType !== 'html' and \in_array( $name, array( 'fpage_ipb_wrapper', 'fpage_show_sidebar', 'fpage_wrapper_template' ) ) )
			{
				continue;
			}

			if ( $fpageType === 'html' and \in_array( $name, array( 'fpage_template' ) ) )
			{
				continue;
			}

			if ( \is_array( $field ) )
			{
				if ( mb_substr( $name, 0, 4 ) === 'tab_' )
				{
					$form->addTab( ...$field );
				}
				else if ( mb_substr( $name, 0, 4 ) === 'msg_' )
				{
					$form->addMessage( ...$field );
				}
			}
			else
			{
				$form->add( $field );
			}
		}

		if ( ! $this->id )
		{
			$form->addTab( 'content_fpage_form_tab__menu' );
			$toggles    = array( 'menu_manager_access_type', 'menu_parent' );
			$formFields = array();
			
			foreach( \IPS\frontpage\extensions\core\FrontNavigation\Fpages::configuration( array() ) as $field )
			{
				if ( $field->name !== 'menu_content_fpage' )
				{
					$toggles[] = $field->name;
					$formFields[ $field->name ] = $field;
				}
			}
			$form->add( new \IPS\Helpers\Form\YesNo( 'fpage_add_to_menu', FALSE, FALSE, array( 'togglesOn' => $toggles ) ) );
			
			$roots = array();
			foreach ( \IPS\core\FrontNavigation::i()->roots( FALSE ) as $item )
			{
				$roots[ $item->id ] = $item->title();
			}
			$form->add( new \IPS\Helpers\Form\Select( 'menu_parent', '*', NULL, array( 'options' => $roots ), NULL, NULL, NULL, 'menu_parent' ) );

			
			foreach( $formFields as $name => $field )
			{
				$form->add( $field );
			}
			
			$groups = array();
			foreach ( \IPS\Member\Group::groups() as $group )
			{
				$groups[ $group->g_id ] = $group->name;
			}
			$form->add( new \IPS\Helpers\Form\Radio( 'menu_manager_access_type', 0, TRUE, array(
				'options'	=> array( 0 => 'menu_manager_access_type_inherit', 1 => 'menu_manager_access_type_override' ),
				'toggles'	=> array( 1 => array( 'menu_manager_access' ) )
			), NULL, NULL, NULL, 'menu_manager_access_type' ) );
			$form->add( new \IPS\Helpers\Form\Select( 'menu_manager_access', '*', NULL, array( 'multiple' => TRUE, 'options' => $groups, 'unlimited' => '*', 'unlimitedLang' => 'everyone' ), NULL, NULL, NULL, 'menu_manager_access' ) );
		}

		if ( $fpageType === 'builder' )
		{
			if ( $this->id )
			{
				\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->message( \IPS\Member::loggedIn()->language()->addToStack('content_acp_fpage_builder_msg_edit', TRUE, array( 'sprintf' => array( $this->url() ) ) ), 'information', NULL, FALSE );
			}
			else
			{
				\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->message( \IPS\Member::loggedIn()->language()->addToStack('content_acp_fpage_builder_msg_new' ), 'information', NULL, FALSE );
			}
		}

		if( $this->id )
		{
			$form->canSaveAndReload = true;
		}
		
		\IPS\Output::i()->title  = $this->id ? \IPS\Member::loggedIn()->language()->addToStack('content_editing_fpage', NULL, array( 'sprintf' => array( $this->_title ) ) ) : \IPS\Member::loggedIn()->language()->addToStack('content_add_fpage');
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		$isNew = $this->_new;

		if ( ! $this->id )
		{
			$this->type = \IPS\Request::i()->fpage_type;
			$this->save();

			if ( $this->type === 'editor' )
			{
				\IPS\File::claimAttachments( 'fpage-content/fpages-' . $this->id, $this->id );
			}
		}

		if( isset( $values['fpage_name'] ) )
		{
			$_copied	= $values['fpage_name'];
			$values['fpage_seo_name'] = empty( $values['fpage_seo_name'] ) ? ( \is_array( $_copied ) ? array_shift( $_copied ) : $_copied ) : $values['fpage_seo_name'];

			$bits = explode( '.', $values['fpage_seo_name'] );
			foreach( $bits as $i => $v )
			{
				$bits[ $i ] = \IPS\Http\Url\Friendly::seoTitle( $v );
			}

			$values['fpage_seo_name'] = implode( '.', $bits );

			\IPS\Lang::saveCustom( 'frontpage', "frontpage_fpage_" . $this->id, $values['fpage_name'] );
		}
		
		if ( isset( $values['fpage_folder_id'] ) AND ( ! empty( $values['fpage_folder_id'] ) OR $values['fpage_folder_id'] === 0 ) )
		{
			$values['fpage_folder_id'] = ( $values['fpage_folder_id'] === 0 ) ? 0 : $values['fpage_folder_id']->id;
		}

		if ( isset( $values['fpage_includes_js'] ) OR isset( $values['fpage_includes_css'] ) )
		{
			$includes = array();
			if ( isset( $values['fpage_includes_js'] ) )
			{
				$includes['js'] = $values['fpage_includes_js'];
			}

			if ( isset( $values['fpage_includes_css'] ) )
			{
				$includes['css'] = $values['fpage_includes_css'];
			}

			$this->_js_css_ids = $includes;

			/* Trash file objects to be sure */
			$this->js_css_objects = NULL;
			$values['js_css_objects'] = NULL;

			unset( $values['fpage_includes_js'], $values['fpage_includes_css'] );
		}

		try
		{
			$this->processContent( static::PRE_SAVE );
		}
		catch( \LogicException $ex )
		{
			throw new \InvalidArgumentException( \IPS\Member::loggedIn()->language()->addToStack('content_err_fpage_save_exception', FALSE, array( 'sprintf' => \IPS\Member::loggedIn()->language()->addToStack( $ex->getMessage() ) ) ) );
		}

		/* Fpage filename changed? */
		if ( ! $isNew and $values['fpage_seo_name'] !== $this->seo_name )
		{
			$this->storeUrl();
		}

		/* Menu stuffs */
		if ( isset( $values['fpage_add_to_menu'] ) )
		{
			if( $values['fpage_add_to_menu'] )
			{
				$permission = $values['menu_manager_access'] == '*' ? '*' : implode( ',', $values['menu_manager_access'] );
				
				if ( $values['menu_manager_access_type'] === 0 )
				{
					$permission = '';
				}

				$save = array(
					'app'			=> 'frontpage',
					'extension'		=> 'Fpages',
					'config'		=> '',
					'parent'		=> $values['menu_parent'],
					'permissions'   => $permission
				);
				
				try
				{
					$save['position'] = \IPS\Db::i()->select( 'MAX(position)', 'core_menu', array( 'parent=?', \IPS\Request::i()->parent ) )->first() + 1;
				}
				catch ( \UnderflowException $e )
				{
					$save['position'] = 1;
				}
				
				$id = \IPS\Db::i()->insert( 'core_menu', $save );
				
				$values = \IPS\frontpage\extensions\core\FrontNavigation\Fpages::parseConfiguration( $values, $id );
				$config = array( 'menu_content_fpage' => $this->id );
				
				foreach( array( 'menu_title_fpage_type', 'menu_title_fpage' ) as $field )
				{
					if ( isset( $values[ $field ] ) )
					{
						$config[ $field ] = $values[ $field ];
					}
				}
				
				\IPS\Db::i()->update( 'core_menu', array( 'config' => json_encode( $config ) ), array( 'id=?', $id ) );
			}

			unset( $values['fpage_add_to_menu'], $values['menu_title_fpage_type'], $values['menu_title_fpage'], $values['menu_parent'], $values['menu_manager_access'], $values['menu_manager_access_type'] );
		}

		return $values;
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( $values )
	{
		$this->setFullPath( ( $this->folder_id ? \IPS\frontpage\Fpages\Folder::load( $this->folder_id )->path : '' ) );
		$this->save();

		try
		{
			$this->processContent( static::POST_SAVE );
		}
		catch( \LogicException $ex )
		{
			throw new \InvalidArgumentException( \IPS\Member::loggedIn()->language()->addToStack('content_err_fpage_save_exception', FALSE, array( 'sprintf' => \IPS\Member::loggedIn()->language()->addToStack( $ex->getMessage() ) ) ) );
		}
		
		\IPS\Content\Search\Index::i()->index( $this->item() );
	}

	/**
	 * Stores the URL so when its changed, the old can 301 to the new location
	 *
	 * @return void
	 */
	public function storeUrl()
	{
		\IPS\Db::i()->insert( 'frontpage_url_store', array(
			'store_path'       => $this->full_path,
			'store_current_id' => $this->_id,
			'store_type'       => 'fpage'
		) );
	}

	/**
	 * Get the Database ID from the fpage
	 *
	 * @return null|int
	 */
	public function getDatabase()
	{
		try
		{
			return \IPS\frontpage\Databases::load( $this->id, 'database_fpage_id' );
		}
		catch( \OutOfRangeException $e ) { }
	
		return null;
	}

	/**
	 * Get the database ID from the fpage content
	 *
	 * @return  int
	 */
	public function getDatabaseIdFromHtml()
	{
		if ( $this->type !== 'html' )
		{
			throw new \LogicException('frontpage_fpage_not_html');
		}

		preg_match( '#{database="([^"]+?)"#', $this->content, $matches );

		if ( isset( $matches[1] ) )
		{
			if ( \is_numeric( $matches[1] ) )
			{
				return \intval( $matches[1] );
			}
			else
			{
				try
				{
					$database = \IPS\frontpage\Databases::load( $matches[1], 'database_key' );
					return $database->id;
				}
				catch( \OutOfRangeException $ex )
				{
					return NULL;
				}
			}
		}

		return NULL;
	}

	/**
	 * @brief	Cached URL
	 */
	protected $_url	= NULL;

	/**
	 * Get URL
	 * 
	 * @return \IPS\Http\Url object
	 */
	public function url()
	{
		if( $this->_url === NULL )
		{
			if ( ( \IPS\Application::load('frontpage')->default OR \IPS\Settings::i()->frontpage_use_different_gateway ) AND $this->default AND ! $this->folder_id )
			{
				/* Are we using the gateway file? */
				if ( \IPS\Settings::i()->frontpage_use_different_gateway )
				{
					/* Yes, work out the proper URL. */
					$this->_url = \IPS\Http\Url::createFromString( \IPS\Settings::i()->frontpage_root_fpage_url, TRUE );
				}
				else
				{
					/* No - that's easy */
					$this->_url = \IPS\Http\Url::internal( '', 'front' );
				}
			}
			else
			{
				$this->_url = \IPS\Http\Url::internal( 'app=frontpage&module=fpages&controller=fpage&path=' . $this->full_path, 'front', 'content_fpage_path', array( $this->full_path ) );
			}
		}

		return $this->_url;
	}
	
	/**
	 * Process the content to see if there are any tags and so on that need action
	 * 
	 * @param	integer		$flag		Pre or post save flag
	 * @return 	void
	 * @throws	\LogicException
	 */
	public function processContent( $flag )
	{
		if ( $this->type === 'html' )
		{
			$seen = array();
			preg_match_all( '/\{([a-z]+?=([\'"]).+?\\2 ?+)}/', $this->content, $matches, PREG_SET_ORDER );
			
			/* Work out the plugin and the values to pass */
			foreach( $matches as $index => $array )
			{
				preg_match_all( '/(.+?)='.$array[2].'(.+?)'.$array[2].'\s?/', $array[1], $submatches );
				
				$plugin  = array_shift( $submatches[1] );
				$pluginClass = 'IPS\\Output\\Plugin\\' . mb_ucfirst( $plugin );
				
				$value   = array_shift( $submatches[2] );
				$options = array();
				
				foreach ( $submatches[1] as $k => $v )
				{
					$options[ $v ] = $submatches[2][ $k ];
				}

				$seen[ mb_strtolower( $plugin ) ] = array( 'value' => $value, 'options' => $options );

				/* Work out if this plugin belongs to an application, and if so, include it */
				if( !class_exists( $pluginClass ) )
				{
					foreach ( \IPS\Application::applications() as $app )
					{
						if ( file_exists( \IPS\ROOT_PATH . "/applications/{$app->directory}/extensions/core/OutputPlugins/" . mb_ucfirst( $plugin ) . ".php" ) )
						{
							$pluginClass = 'IPS\\' . $app->directory . '\\extensions\\core\\OutputPlugins\\' . mb_ucfirst( $plugin );
						}
					}
				}
				
				$method = ( $flag === static::PRE_SAVE ) ? 'preSaveProcess' : 'postSaveProcess';
				
				if ( method_exists( $pluginClass, $method ) )
				{
					try
					{
						$pluginClass::$method( $value, $options, $this );
					}
					catch( \Exception $ex )
					{
						throw new \LogicException( $ex->getMessage() );
					}
				}
			}

			/* Check to see if we're expecting a database to be here */
			$database = $this->getDatabase();

			if ( $database !== NULL )
			{
				/* We're expecting a database tag, is there one? */
				if ( isset( $seen['database'] ) )
				{
					/* Yep, is it the same ID? */
					if ( $seen['database']['value'] != $database->id AND $seen['database']['value'] != $database->key )
					{
						/* There's been a change.. */
						try
						{
							$this->removeDatabaseMap();
						}
						catch( \LogicException $e ) { }

						$this->mapToDatabase( \intval( $database->id ) );
					}
				}
				else
				{
					/* Nope, not database tag spotted, assume it has been removed */
					try
					{
						$this->removeDatabaseMap();
					}
					catch( \LogicException $e ) { }
				}
			}
		}
	}
	
	/**
	 * Set Theme
	 *
	 * @return	void
	 */
	public function setTheme()
	{
		if ( $this->theme )
		{
			try
			{
				\IPS\Theme::switchTheme( $this->theme );
			}
			catch ( \Exception $e ) { }
		}
	}

	/**
	 * Once widget ordering has ocurred, post process if required
	 *
	 * @return void
	 */
	public function postWidgetOrderSave()
	{
		/* Check for database changes and update mapping if required */
		$databaseUsed = NULL;

		foreach ( \IPS\Db::i()->select( '*', 'frontpage_fpage_widget_areas', array( 'area_fpage_id=?', $this->id ) ) as $item )
		{
			$fpageBlocks   = json_decode( $item['area_widgets'], TRUE );
			$resaveBlock  = NULL;
			foreach( $fpageBlocks as $id => $fpageBlock )
			{
				if( isset( $fpageBlock['app'] ) and $fpageBlock['app'] == 'frontpage' AND $fpageBlock['key'] == 'Database' AND ! empty( $fpageBlock['configuration']['database'] ) )
				{
					if ( $databaseUsed === NULL )
					{
						$databaseUsed = $fpageBlock['configuration']['database'];
					}
					else
					{
						/* Already got a database, so remove this one */
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

		if ( $databaseUsed === NULL and $this->type === 'html' )
		{
			$databaseUsed = $this->getDatabaseIdFromHtml();
		}

		if ( $databaseUsed !== NULL )
		{
			$this->mapToDatabase( \intval( $databaseUsed ) );
		}
		else
		{
			try
			{
				$this->removeDatabaseMap();
			}
			catch( \LogicException $e ) { }
		}
	}

	/**
	 * Map this database to a specific fpage
	 *
	 * @param   int $databaseId Fpage ID
	 * @return  boolean
	 * @throws  \LogicException
	 */
	public function mapToDatabase( $databaseId )
	{
		/* Ensure this fpage has an ID (as in, $fpage->save() has not been called yet on a new fpage) */
		if ( ! $this->id )
		{
			throw new \LogicException('frontpage_err_fpage_id_is_empty');
		}
		try
		{
			/* is this fpage already in use */
			$database = \IPS\frontpage\Databases::load( $this->id, 'database_fpage_id' );

			if ( $database->id == $databaseId )
			{
				/* Nothing to update as this fpage is mapped to this database */
				return TRUE;
			}
			else
			{
				/* We're using another DB on this fpage */
				throw new \LogicException('frontpage_err_db_already_on_fpage' );
			}
		}
		catch( \OutOfRangeException $e )
		{
			/* We didn't load a database based on this fpage, so make sure the database we want isn't being used elsewhere */
			$database = \IPS\frontpage\Databases::load( $databaseId );

			if ( $database->fpage_id > 0 )
			{
				/* We're using another DB on this fpage */
				throw new \LogicException('frontpage_err_db_in_use_other_fpage');
			}
			else
			{
				/* Ok here as this DB is not in use, and this fpage doesn't have a DB in use */
				$database->fpage_id = $this->id;
				$database->save();

				/* Restore content in the search index */
				\IPS\Task::queue( 'core', 'RebuildSearchIndex', array( 'class' => 'IPS\frontpage\Records' . $database->id ) );
				
				/* Restore content in social promote table */
				$class = 'IPS\frontpage\Records' . $database->id;
				\IPS\core\Promote::changeHiddenByClass( new $class, FALSE );
			}

			return TRUE;
		}
	}

	/**
	 * Removes all mapped DBs for this fpage
	 *
	 * @return void
	 */
	public function removeDatabaseMap()
	{
		try
		{
			$database = \IPS\frontpage\Databases::load( $this->id, 'database_fpage_id' );
			$database->fpage_id = 0;
			$database->save();

			/* Remove from search */
			\IPS\Content\Search\Index::i()->removeClassFromSearchIndex( 'IPS\frontpage\Records' . $database->id );
			
			/* Remove content in social promote table */
			$class = 'IPS\frontpage\Records' . $database->id;
			\IPS\core\Promote::changeHiddenByClass( new $class, TRUE );
		}
		catch( \OutOfRangeException $ex )
		{
			/* Fpage was never mapped */
			throw new \LogicException('frontpage_err_db_fpage_never_used');
		}
	}

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		try
		{
			$this->removeDatabaseMap();
		}
		catch( \LogicException $e )
		{

		}
		
		$delete = $this->getMenuItemIds();
		
		if ( \count( $delete ) )
		{
			\IPS\Db::i()->delete( 'core_menu', \IPS\Db::i()->in( 'id', $delete ) );
		}

		/* Remove any widgets for this fpage */
		\IPS\Db::i()->delete( 'frontpage_fpage_widget_areas', array( 'area_fpage_id=?', $this->id ) );
		
		parent::delete();
		
		if ( $this->full_path and \IPS\Theme::designersModeEnabled() )
		{
			static::cleanUpDesignersModeFiles();
		}
		
		\IPS\Content\Search\Index::i()->removeFromSearchIndex( $this->item() );
	}
	
	/**
	 * Returns core_menu ids for all menu items associated with this fpage
	 *
	 * @return array
	 */
	public function getMenuItemIds()
	{
		$items = array();
		foreach( \IPS\Db::i()->select( '*', 'core_menu', array( 'app=? AND extension=?', 'frontpage', 'Fpages' ) ) as $item )
		{
			$json = json_decode( $item['config'], TRUE );
			
			if ( isset( $json['menu_content_fpage'] ) )
			{
				if ( $json['menu_content_fpage'] == $this->id )
				{
					$items[] = $item['id'];
				}
			}
		}
		
		return $items;
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
		static::buildFpageUrlStore();
		
		/* Update perms if we have a child database */
		try
		{
			$database = \IPS\frontpage\Databases::load( $this->id, 'database_fpage_id' );

			foreach( \IPS\Db::i()->select( '*', 'frontpage_database_categories', array( 'category_database_id=?', $database->id ) ) as $cat )
			{
				$class    = '\IPS\frontpage\Categories' . $database->id;
				$category = $class::constructFromData( $cat );

				\IPS\Content\Search\Index::i()->massUpdate( 'IPS\frontpage\Records' . $database->id, $category->_id, NULL, $category->readPermissionMergeWithFpage() );
				\IPS\Content\Search\Index::i()->massUpdate( 'IPS\frontpage\Records\Comment' . $database->id, $category->_id, NULL, $category->readPermissionMergeWithFpage() );
				\IPS\Content\Search\Index::i()->massUpdate( 'IPS\frontpage\Records\Review' . $database->id, $category->_id, NULL, $category->readPermissionMergeWithFpage() );
			}
		}
		catch( \Exception $e ) { }
		
		\IPS\Content\Search\Index::i()->index( $this->item() );
	}

	/**
	 * Save data
	 *
	 * @return void
	 */
	public function save()
	{
		if ( $this->id )
		{
			static::deleteCompiled( $this->id );
		}
		
		parent::save();
		
		static::buildFpageUrlStore();
		
		if ( $this->full_path and \IPS\Theme::designersModeEnabled() )
		{
			static::exportDesignersMode( $this->id );
		}		
	}
		
	/**
	 * Get sortable name
	 *
	 * @return	string
	 */
	public function getSortableName()
	{
		return $this->seo_name;
	}

	/**
	 * Set default
	 *
	 * @return void
	 */
	public function setAsDefault()
	{
		\IPS\Db::i()->update( 'frontpage_fpages', array( 'fpage_default' => 0 ), array( 'fpage_folder_id=?', $this->folder_id ) );
		\IPS\Db::i()->update( 'frontpage_fpages', array( 'fpage_default' => 1 ), array( 'fpage_id=?', $this->id ) );
		
		static::buildFpageUrlStore();
	}
	
	/**
	 * Resets a folder path
	 *
	 * @param	string	$path	Path to reset
	 * @return	void
	 */
	public function setFullPath( $path )
	{
		$this->full_path = trim( $path . '/' . $this->seo_name, '/' );
		$this->save();
	}

	/**
	 * Displays a fpage
	 *
	 * @param	string|NULL	$title			The Fpage title
	 * @param	int|NULL	$httpStatusCode	HTTP Status Code
	 * @param	array|NULL	$httpHeaders	Additional HTTP Headers
	 * @param	string|NULL	$content		Optional content to use. Useful if dynamic replacements need to be made at runtime
	 * @throws \ErrorException
	 * @return  void
	 */
	public function output( $title=NULL, $httpStatusCode=NULL, $httpHeaders=NULL, $content=NULL )
	{
		$includes = $this->getIncludes();

		if ( isset( $includes['js'] ) and \is_array( $includes['js'] ) )
		{
			\IPS\Output::i()->jsFiles  = array_merge( \IPS\Output::i()->jsFiles, array_values( $includes['js'] ) );
		}

		/* Display */
		if ( $this->ipb_wrapper or $this->type === 'builder' )
		{
			$this->setTheme();
			$nav = array();

			\IPS\Output::i()->title  = $this->getHtmlTitle();

			/* This has to be done after setTheme(), otherwise \IPS\Theme::switchTheme() can wipe out CSS includes */
			if ( isset( $includes['css'] ) and \is_array( $includes['css'] ) )
			{
				\IPS\Output::i()->cssFiles  = array_merge( \IPS\Output::i()->cssFiles, array_values( $includes['css'] ) );
			}

			if ( $this->type === 'builder' )
			{
				list( $group, $name, $key ) = explode( '__', $this->template );
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('fpages')->globalWrap( $nav, \IPS\frontpage\Theme::i()->getTemplate($group, 'frontpage', 'fpage')->$name( $this, $this->getWidgets() ), $this );
			}
			else
			{
				/* Populate \IPS\Output::i()->sidebar['widgets'] sidebar/header/footer widgets */
				$this->getWidgets();

				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'fpages', 'frontpage' )->globalWrap( $nav, $content ?: $this->getHtmlContent(), $this );
			}

			/* Set the meta tags, but do not reset them if they are already set - articles can define custom meta tags and this code
				overwrites the ones set by articles if we don't verify they aren't set first */
			if ( $this->meta_keywords AND ( !isset( \IPS\Output::i()->metaTags['keywords'] ) OR !\IPS\Output::i()->metaTags['keywords'] ) )
			{
				\IPS\Output::i()->metaTags['keywords'] = $this->meta_keywords;
			}

			if ( $this->meta_description AND ( !isset( \IPS\Output::i()->metaTags['description'] ) OR !\IPS\Output::i()->metaTags['description'] ) )
			{
				\IPS\Output::i()->metaTags['description'] = $this->meta_description;
			}
			
			/* If this is a default fpage, we may be accessing this from the folder only. The isset() check is to ensure canonical
				tags for more specific things (like databases) are not overridden. */
			if ( !isset( \IPS\Output::i()->linkTags['canonical'] ) )
			{
				\IPS\Output::i()->linkTags['canonical'] = (string) $this->url();
			}

			/* Can only disable sidebar if HTML fpage */
			if ( ! $this->show_sidebar and $this->type === 'html' )
			{
				\IPS\Output::i()->sidebar['enabled'] = false;
			}

			if ( isset( \IPS\Settings::i()->frontpage_error_fpage ) and \IPS\Settings::i()->frontpage_error_fpage and \IPS\Settings::i()->frontpage_error_fpage == $this->id )
			{
				\IPS\Output::i()->sidebar['enabled'] = false;
			}

			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'fpages/fpage.css', 'frontpage', 'front' ) );

			if ( ! ( \IPS\Application::load('frontpage')->default AND ! $this->folder_id AND $this->default ) )
			{
				\IPS\Output::i()->breadcrumb['module'] = array( $this->url(), $this->_title );
			}

			if ( isset( \IPS\Settings::i()->frontpage_error_fpage ) and \IPS\Settings::i()->frontpage_error_fpage and \IPS\Settings::i()->frontpage_error_fpage == $this->id )
			{
				/* Set the title */
				\IPS\Output::i()->title = ( $title ) ? $title : $this->getHtmlTitle();
				\IPS\Output::i()->output = $content ?: $this->getHtmlContent();
				\IPS\Member::loggedIn()->language()->parseOutputForDisplay( \IPS\Output::i()->title );

				/* Send straight to the output engine */
				\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( \IPS\Output::i()->title, \IPS\Output::i()->output, array( 'app' => \IPS\Dispatcher::i()->application->directory, 'module' => \IPS\Dispatcher::i()->module->key, 'controller' => \IPS\Dispatcher::i()->controller ) ), ( $httpStatusCode ? $httpStatusCode : 200 ), 'text/html', ( $httpHeaders ? $httpHeaders : array() ) );
			}
			else
			{
				\IPS\Output::i()->allowDefaultWidgets = FALSE;
				
				/* Let the dispatcher finish off and show fpage */
				return;
			}
		}
		else
		{
			$this->setTheme();
			
			if ( isset( $includes['css'] ) and \is_array( $includes['css'] ) )
			{
				\IPS\Output::i()->cssFiles  = array_merge( \IPS\Output::i()->cssFiles, array_values( $includes['css'] ) );
			}
			
			if ( $this->meta_keywords AND ( !isset( \IPS\Output::i()->metaTags['keywords'] ) OR !\IPS\Output::i()->metaTags['keywords'] ) )
			{
				\IPS\Output::i()->metaTags['keywords'] = $this->meta_keywords;
			}

			if ( $this->meta_description AND ( !isset( \IPS\Output::i()->metaTags['description'] ) OR !\IPS\Output::i()->metaTags['description'] ) )
			{
				\IPS\Output::i()->metaTags['description'] = $this->meta_description;
			}
			
			/* Meta tags */
			\IPS\Output::i()->buildMetaTags();
			
			/* Ensure MFA pop up shows */
			$mfa = \IPS\Dispatcher\Front::i()->checkMfa( TRUE );
			$mfa = $mfa ?: '';
			if ( $this->wrapper_template and $this->wrapper_template !== '_none_' and ! \IPS\Request::i()->isAjax() )
			{
				try
				{
					list( $group, $name, $key ) = explode( '__', $this->wrapper_template );
					$content = $content ?: $this->getHtmlContent();
					$content .= $mfa;
					\IPS\Output::i()->sendOutput( \IPS\frontpage\Theme::i()->getTemplate($group, 'frontpage', 'fpage')->$name( $content, $this->getHtmlTitle() ), 200, $this->getContentType(), \IPS\Output::i()->httpHeaders );
				}
				catch( \OutOfRangeException $e )
				{

				}
			}

			/* Set the title */
			\IPS\Output::i()->title = ( $title ) ? $title : $this->getHtmlTitle();
			\IPS\Member::loggedIn()->language()->parseOutputForDisplay( \IPS\Output::i()->title );

			/* Send straight to the output engine */
			$content = $content ?: $this->getHtmlContent();
			$content .= $mfa;
			\IPS\Output::i()->sendOutput( $content, ( $httpStatusCode ? $httpStatusCode : 200 ), $this->getContentType(), ( $httpHeaders ? $httpHeaders : array() ) );
		}
	}
	
	/**
	 * Write HTML fpages to disk for designer's mode
	 *
	 * @param	NULL|int	$fpageId		Single fpage to export
	 * @return void
	 */
	public static function exportDesignersMode( $fpageId=NULL)
	{
		$where = array( array( 'fpage_type=?', 'html' ) );
		$seen = array();
		
		if ( $fpageId )
		{
			$where[] = array( 'fpage_id=?', $fpageId );
		}
		
		foreach( \IPS\Db::i()->select( '*', 'frontpage_fpages', $where ) as $fpage )
		{
			/* We could use recursive mode but it wouldn't correctly chmod the intermediate dirs */
			$bits = explode( '/', "/themes/frontpage/fpages/" . $fpage['fpage_full_path'] );
			$dir = '';

			$filename = array_pop( $bits );

			foreach( $bits as $part )
			{
				$dir .= $part . '/';

				if ( ! is_dir( \IPS\ROOT_PATH . '/' . trim( $dir, '/' ) ) )
				{
					mkdir( \IPS\ROOT_PATH . '/' . trim( $dir, '/' ), \IPS\IPS_FOLDER_PERMISSION );
					chmod( \IPS\ROOT_PATH . '/' . trim( $dir, '/' ), \IPS\IPS_FOLDER_PERMISSION );
				}
			}
			
			$headers = array();
			
			foreach( array( 'fpage_seo_name', 'fpage_ipb_wrapper', 'fpage_title') as $field )
			{
				$headers[ $field ] = $field . '="' . str_replace( '"', '\\"', $fpage[ $field ] ) . '"';
			}
			
			$header = "<ips:fpages " . implode( " ", $headers ) . " />";
			try
			{
				\file_put_contents( \IPS\ROOT_PATH . '/' . trim( $dir, '/' ) . '/' . $filename, $header . "\n" . $fpage['fpage_content'] );
				@chmod( \IPS\ROOT_PATH . '/' . trim( $dir, '/' ) . '/' . $filename, \IPS\IPS_FILE_PERMISSION );
			}
			catch( \RuntimeException $e ) { }
		}
		
		/* Clear out any older designer mode files */
		if ( ! $fpageId )
		{
			static::cleanUpDesignersModeFiles();
		}
	}
	
	/**
	 * Remove any old designer mode files
	 *
	 * @return void
	 */
	public static function cleanUpDesignersModeFiles()
	{
		$diskPaths = array();
		static::getCurrentDesignersModeFilePaths( $diskPaths, \IPS\ROOT_PATH . "/themes/frontpage/fpages/" );
		
		$databasePaths = array();
		foreach( \IPS\Db::i()->select( '*', 'frontpage_fpages', array( 'fpage_type=?', 'html' ) ) as $fpage )
		{
			$databasePaths[] = $fpage['fpage_full_path'];
		}
		
		if ( \count( $diskPaths ) )
		{
			foreach( $diskPaths as $path )
			{
				if ( ! \in_array( $path, $databasePaths ) )
				{
					@unlink( \IPS\ROOT_PATH . "/themes/frontpage/fpages/" . $path );
				}
			}
		}
	}
	
	/**
	 * Get existing designer's mode fpage paths
	 *
	 * @param	array	$paths		Array of processed fpage paths
	 * @param	string	$path		Path to look into
	 * @return void
	 */
	public static function getCurrentDesignersModeFilePaths( &$paths, $path )
	{
		if ( is_dir( $path ) )
		{
			foreach ( new \DirectoryIterator( $path ) as $dir )
			{
				if ( $dir->isDot() || mb_substr( $dir->getFilename(), 0, 1 ) === '.' )
				{
					continue;
				}

				if ( $dir->isDir() )
				{
					static::getCurrentDesignersModeFilePaths( $paths, $dir->getRealPath() );
				}
				else
				{
					$paths[] = trim( str_replace( \IPS\ROOT_PATH . '/themes/frontpage/fpages/', '', $dir->getRealPath() ), '/' );
				}
			}
		}
	}
	
	/**
	 * Import media from disk for designer's mode
	 *
	 * @return void
	 */
	public static function importDesignersMode()
	{
		$path = \IPS\ROOT_PATH . '/themes/frontpage/fpages';
		$seen = array();

		/* Grab folder data */
		if ( is_dir( $path ) )
		{
			static::importDesignersModeRecurse( $seen, $path );
		}

		\IPS\Db::i()->delete( 'frontpage_fpages', array( 'fpage_type=? and ' . \IPS\Db::i()->in( 'fpage_id', $seen, TRUE ), 'html' ) );
	}

	/**
	 * Import media from disk for designer's mode recursive method
	 *
	 * @param	array	$seen		Array of processed fpage IDs
	 * @param	string	$path		Path to look into
	 * @return void
	 */
	public static function importDesignersModeRecurse( &$seen, $path )
	{
		if ( is_dir( $path ) )
		{
			foreach ( new \DirectoryIterator( $path ) as $dir )
			{
				if ( $dir->isDot() || mb_substr( $dir->getFilename(), 0, 1 ) === '.' )
				{
					continue;
				}

				if ( $dir->isDir() )
				{
					static::importDesignersModeRecurse( $seen, $dir->getRealPath() );
				}
				else
				{
					$folders = \IPS\frontpage\Fpages\Folder::roots();
					$contents = \file_get_contents( $dir->getRealPath() );
					$params = array();
					
					/* Parse the header tag */
					preg_match( '#^<ips:fpages(.+?)/>(\r\n?|\n)#', $contents, $params );
								
					/* Strip it */
					$contents = ( isset($params[0]) ) ? str_replace( $params[0], '', $contents ) : $contents;
					
					/* Get fields */
					preg_match_all( '#([a-z\-_]+?)="(.+?)"#', $params[1], $data, PREG_SET_ORDER );
					
					$fields = array();
					foreach( $data as $id => $matches )
					{
						$fields[ $data[ $id ][1] ] = $data[ $id ][2];
					}
					
					/* Existing fpage, or a new one? */
					$fullPath = trim( str_replace( str_replace( '\\', '/', \IPS\ROOT_PATH ) . '/themes/frontpage/fpages', '', str_replace( '\\', '/', $dir->getRealPath() ) ), '/' );
					$bits = explode( '/', $fullPath );
					$fileName = array_pop( $bits );
					$path = implode( '/', $bits );
					
					try
					{
						$fpage = \IPS\frontpage\Fpages\Fpage::load( $fullPath, 'fpage_full_path' );
						$fpage->content = $contents;
						$fpage->save();
					}
					catch( \OutOfRangeException $e )
					{
						$folderId = 0;
						foreach( $folders as $folder )
						{
							if ( $folder->path == $path )
							{
								$folderId = $folder->id;
							}
						}
						/* Doesn't exist, so this is new */
						$fpage = new \IPS\frontpage\Fpages\Fpage;
						$fpage->content     = $contents;
						$fpage->full_path   = $fullPath;
						$fpage->seo_name    = $fileName;
						$fpage->type        = 'html';
						$fpage->title	   = ( isset( $fields['fpage_title'] ) ) ? $fields['fpage_title'] : $fileName;
						$fpage->ipb_wrapper = ( isset( $fields['fpage_ipb_wrapper'] ) ) ? $fields['fpage_ipb_wrapper'] : 1;
						$fpage->folder_id   = $folderId;
						$fpage->save();
						
						\IPS\Lang::saveCustom( 'frontpage', "frontpage_fpage_" . $fpage->id, $fpage->title );
					}
					
					$seen[] = $fpage->id;
				}
			}
		}
	}
	
	/**
	 * Get item
	 *
	 * @return	\IPS\nexus\Package\Item
	 */
	public function item()
	{
		$data = array();
		foreach ( $this->_data as $k => $v )
		{
			$data[ 'fpage_' . $k ] = $v; 
		}
		
		return \IPS\frontpage\Fpages\FpageItem::constructFromData( $data );
	}
}