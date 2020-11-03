<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.devcu.com/donate
 *
 * @brief       FrontPage Application Class
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.5x
 * @subpackage	FrontPage
 * @version     1.0.5 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     15 OCT 2020
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

spl_autoload_register( function( $class )
{
	if ( mb_substr( $class, 0, 15 ) === 'IPS\frontpage\Records' and \is_numeric( mb_substr( $class, 15, 1 ) ) )
	{
		$databaseId   = \intval( mb_substr( $class, 15 ) );
		$databases    = \IPS\frontpage\Databases::databases();
		
		if ( ! isset( $databases[ $databaseId ] ) )
		{
			return false;
		}
		
		$titleField   = $databases[ $databaseId ]->field_title;
		$contentField = $databases[ $databaseId ]->field_content;
		$contentType  = $databases[ $databaseId ]->key;
		$titleLang    = 'content_db_lang_su_' . $databaseId;
		$includeInSearch = $databases[ $databaseId ]->search ? "TRUE" : "FALSE";

		$data = <<<EOF
		namespace IPS\frontpage;
		class Records{$databaseId} extends Records
		{
			protected static \$multitons = array();
			protected static \$multitonMap	= array();
			public static \$customDatabaseId = $databaseId;
			public static \$commentClass = 'IPS\frontpage\Records\Comment{$databaseId}';
			public static \$reviewClass = 'IPS\frontpage\Records\Review{$databaseId}';
			public static \$containerNodeClass = 'IPS\frontpage\Categories{$databaseId}';
			public static \$databaseTable = 'frontpage_custom_database_{$databaseId}';
			public static \$title = '{$titleLang}';
			public static \$module = 'records{$databaseId}';
			public static \$includeInSearch = {$includeInSearch};
			public static \$contentType = '{$contentType}';
			public static \$hideLogKey = 'ccs-records{$databaseId}';
			public static \$databaseColumnMap = array(
				'author'				=> 'member_id',
				'container'				=> 'category_id',
				'date'					=> 'record_publish_date',
				'is_future_entry'       => 'record_future_date',
				'future_date'           => 'record_publish_date',
				'title'					=> 'field_{$titleField}',
				'content'				=> 'field_{$contentField}',
				'num_comments'			=> 'record_comments',
				'unapproved_comments'	=> 'record_comments_queued',
				'hidden_comments'		=> 'record_comments_hidden',
				'last_comment'			=> 'record_last_comment',
				'last_comment_by'		=> 'record_last_comment_by',
				'last_comment_name'		=> 'record_last_comment_name',
				'views'					=> 'record_views',
				'approved'				=> 'record_approved',
				'pinned'				=> 'record_pinned',
				'locked'				=> 'record_locked',
				'featured'				=> 'record_featured',
				'rating'				=> 'record_rating',
				'rating_hits'			=> 'rating_hits',
				'rating_average'	    => 'record_rating',
				'rating_total'			=> 'rating_value',
				'num_reviews'	        => 'record_reviews',
				'last_review'	        => 'record_last_review',
				'last_review_by'        => 'record_last_review_by',
				'last_review_name'      => 'record_last_review_name',
				'updated'				=> 'record_last_comment',
				'meta_data'				=> 'record_meta_data'
			);
			public static \$fpagePath;
		}
EOF;
		eval( $data );
	}
	
	if ( mb_substr( $class, 0, 23 ) === 'IPS\frontpage\Records\Comment' and \is_numeric( mb_substr( $class, 23, 1 ) ) )
	{
		$databaseId = \intval( mb_substr( $class, 23 ) );
	
		$data = <<<EOF
		namespace IPS\frontpage\Records;
		class Comment{$databaseId} extends Comment
		{ 
			protected static \$multitons = array();
			protected static \$multitonMap	= array();
			public static \$customDatabaseId = $databaseId;
			public static \$itemClass = 'IPS\frontpage\Records{$databaseId}';
			public static \$title     = 'content_record_comments_title_{$databaseId}';
			public static \$hideLogKey = 'ccs-records{$databaseId}-comments';
		}
EOF;
		eval( $data );
	}

	if ( mb_substr( $class, 0, 22 ) === 'IPS\frontpage\Records\Review' and \is_numeric( mb_substr( $class, 22, 1 ) ) )
	{
		$databaseId = \intval( mb_substr( $class, 22 ) );

		$data = <<<EOF
		namespace IPS\frontpage\Records;
		class Review{$databaseId} extends Review
		{
			protected static \$multitons = array();
			protected static \$multitonMap	= array();
			public static \$customDatabaseId = $databaseId;
			public static \$itemClass = 'IPS\frontpage\Records{$databaseId}';
			public static \$title     = 'content_record_reviews_title_{$databaseId}';
			public static \$hideLogKey = 'ccs-records{$databaseId}-reviews';
		}
EOF;
		eval( $data );
	}
	
	if ( mb_substr( $class, 0, 18 ) === 'IPS\frontpage\Categories' and \is_numeric( mb_substr( $class, 18, 1 ) ) )
	{
		$databaseId = \intval( mb_substr( $class, 18 ) );
	
		$data = <<<EOF
		namespace IPS\frontpage;
		class Categories{$databaseId} extends Categories
		{ 
			protected static \$multitons = array();
			protected static \$multitonMap	= array();
			public static \$customDatabaseId = $databaseId;
			public static \$contentItemClass = 'IPS\frontpage\Records{$databaseId}';
			protected static \$containerIds = NULL;
			public static \$modPerm = 'frontpage{$databaseId}';
			public static \$permType = 'categories_{$databaseId}';
		}
EOF;

		eval( $data );
	}
	
	if ( mb_substr( $class, 0, 32 ) === 'IPS\frontpage\Records\RecordsTopicSync' )
	{
		$databaseId = \intval( mb_substr( $class, 32 ) );
	
		$data = <<<EOF
		namespace IPS\frontpage\Records;
		class RecordsTopicSync{$databaseId} extends \IPS\frontpage\Records{$databaseId}
		{ 
			protected static \$multitons = array();
	 		protected static \$multitonMap	= array();
			public static \$customDatabaseId = $databaseId;
			public static \$databaseTable = 'frontpage_custom_database_{$databaseId}';
			public static \$databaseColumnId = 'record_topicid';
			public static \$commentClass = 'IPS\frontpage\Records\CommentTopicSync{$databaseId}';

			public function useForumComments()
			{
				return false;
			}
		}
EOF;
		eval( $data );
	}

	if ( mb_substr( $class, 0, 32 ) === 'IPS\frontpage\Records\CommentTopicSync' )
	{
		$databaseId = \intval( mb_substr( $class, 32 ) );

		$data = <<<EOF
		namespace IPS\frontpage\Records;
		class CommentTopicSync{$databaseId} extends CommentTopicSync
		{ 
			protected static \$multitons = array();
			protected static \$multitonMap	= array();
			public static \$customDatabaseId = $databaseId;
			public static \$itemClass = 'IPS\frontpage\Records\RecordsTopicSync{$databaseId}';
			public static \$title     = 'content_record_comments_title_{$databaseId}';
		}
EOF;
		eval( $data );
	}
	
	if ( mb_substr( $class, 0, 14 ) === 'IPS\frontpage\Fields' and \is_numeric( mb_substr( $class, 14, 1 ) ) )
	{
		$databaseId = \intval( mb_substr( $class, 14 ) );
		eval( "namespace IPS\\frontpage; class Fields{$databaseId} extends Fields { public static \$customDatabaseId = $databaseId; protected \$caches = array( 'database_reciprocal_links', 'frontpage_fieldids_{$databaseId}' ); }" );
	}

	if ( mb_substr( $class, 0, 47 ) === 'IPS\frontpage\extensions\core\EditorLocations\Records' and \is_numeric( mb_substr( $class, 47, 1 ) ) )
	{
		$databaseId = \intval( mb_substr( $class, 47 ) );
		eval( "namespace IPS\\frontpage\\extensions\\core\\EditorLocations; class Records{$databaseId} extends \\IPS\\frontpage\\extensions\\core\\EditorLocations\\Records { public static \$customDatabaseId = $databaseId; public static \$buttonLocation	= TRUE; }" );
	}
} );

/**
 * FrontPage Application Class
 */
class _Application extends \IPS\Application
{
	/**
	 * Returns the ACP Menu JSON for this application.
	 *
	 * @return array
	 */
	public function acpMenu()
	{
		$menu = parent::acpMenu();
		
		if ( ! \IPS\Db::i()->checkForTable('frontpage_databases') )
		{
			return $menu;
		}
		
		/* Now add in the databases... */
		foreach( \IPS\frontpage\Databases::acpMenu() as $database )
		{
			$menu[ 'database_' . $database['id'] ][ 'records_' . $database['id'] ] = array(
				'tab' 		  => 'frontpage',
				'module_url'  => 'databases',
				'controller'  => 'records',
				'do' 		  => 'manage&database_id=' . $database['id'],
				'restriction' => 'records_manage',
				'restriction_module' => 'databases',
				'menu_checks' => array( 'do' => 'manage', 'database_id' => $database['id'] ),
				'menu_controller' => 'records_' . $database['id']
			);

			if ( $database['use_categories'] )
			{
				$menu[ 'database_' . $database['id'] ][ 'categories_' . $database['id'] ] = array(
					'tab' 		  => 'frontpage',
					'module_url'  => 'databases',
					'controller'  => 'categories',
					'do' 		  => 'manage&database_id=' . $database['id'],
					'restriction' => 'categories_manage',
					'restriction_module' => 'databases',
					'menu_checks' => array( 'do' => 'manage', 'database_id' => $database['id'] ),
					'menu_controller' => 'categories_' . $database['id']
				);
			}
			
			$menu[ 'database_' . $database['id'] ][ 'fields_' . $database['id'] ] = array(
				'tab' 		  => 'frontpage',
				'module_url'  => 'databases',
				'controller'  => 'fields',
				'do' 		  => 'manage&database_id=' . $database['id'],
				'restriction' => 'frontpage_fields_manage',
				'restriction_module' => 'databases',
				'menu_checks' => array( 'do' => 'manage', 'database_id' => $database['id'] ),
				'menu_controller' => 'fields_' . $database['id']
			);

			\IPS\Member::loggedIn()->language()->words[ 'menu__frontpage_database_' . $database['id'] ]    = $database['title'];
			\IPS\Member::loggedIn()->language()->words[ 'menu__frontpage_database_' . $database['id'] . '_records_' . $database['id'] ]    = $database['record_name'];
			\IPS\Member::loggedIn()->language()->words[ 'menu__frontpage_database_' . $database['id'] . '_categories_' . $database['id'] ] = \IPS\Member::loggedIn()->language()->addToStack('menu__frontpage_categories');
			\IPS\Member::loggedIn()->language()->words[ 'menu__frontpage_database_' . $database['id'] . '_fields_' . $database['id'] ]     = \IPS\Member::loggedIn()->language()->addToStack('menu__frontpage_fields');
		}

		return $menu;
	}

	/**
	 * Get Extensions
	 *
	 * @param	\IPS\Application|string		$app		    The app key of the application which owns the extension
	 * @param	string						$extension	    Extension Type
	 * @param	bool						$construct	    Should an object be returned? (If false, just the classname will be returned)
	 * @param	\IPS\Member|bool			$checkAccess	Check access permission for extension against supplied member (or logged in member, if TRUE)
	 * @return	array
	 */
	public function extensions( $app, $extension, $construct=TRUE, $checkAccess=FALSE )
	{		
		$classes = parent::extensions( $app, $extension, $construct, $checkAccess );

		if ( $extension === 'EditorLocations' )
		{
			foreach( \IPS\frontpage\Databases::databases() as $obj )
			{
				$classname = '\\IPS\\frontpage\\extensions\\core\\EditorLocations\\Records' . $obj->_id;

				if ( method_exists( $classname, 'generate' ) )
				{
					$classes = array_merge( $classes, $classname::generate() );
				}
				elseif ( !$construct )
				{
					$classes[ 'Records' . $obj->_id ] = $classname;
				}
				else
				{
					try
					{
						$classes[ 'Records' . $obj->_id ] = new $classname( $checkAccess === TRUE ? \IPS\Member::loggedIn() : ( $checkAccess === FALSE ? NULL : $checkAccess ) );
					}
					catch( \RuntimeException $e ){}
				}
			}
		}

		return $classes;
	}

	/**
	 * Developer sync items
	 *
	 * @param   int     $lastSync       Last time syncd
	 * @return  boolean                 Updated (true), nothing updated(false)
	 */
	public function developerSync( $lastSync )
	{
		$updated = false;

		if ( $lastSync < filemtime( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/databaseschema.json" ) )
		{
			foreach( \IPS\frontpage\Databases::databases() as $key => $db )
			{
				\IPS\frontpage\Databases::checkandFixDatabaseSchema( $db->_id );

				$updated = TRUE;
			}
		}

		return $updated;
	}

	/**
	 * Install 'other' items.
	 *
	 * @return void
	 */
	public function installOther()
	{
		/* Install default database and page */
		$database = new \IPS\frontpage\Databases;
		$database->key = 'articles';
		$database->save();

		/* Add in permissions */
		$groups = array();
		foreach( \IPS\Db::i()->select( 'row_id', 'core_admin_permission_rows', array( 'row_id_type=?', 'group' ) ) as $row )
		{
			$groups[] = $row;
		}

		$default = implode( ',', $groups );

		\IPS\Db::i()->insert( 'core_permission_index', array(
             'app'			=> 'frontpage',
             'perm_type'	=> 'databases',
             'perm_type_id'	=> $database->id,
             'perm_view'	=> '*', # view
             'perm_2'		=> '*', # read
             'perm_3'		=> $default, # add
             'perm_4'		=> $default, # edit
             'perm_5'		=> $default, # reply
             'perm_6'		=> $default  # rate
        ) );

		/* Needs to be added before createDatabase is called */
		\IPS\Lang::saveCustom( 'frontpage', "content_db_" . $database->id, "Articles" );
		\IPS\Lang::saveCustom( 'frontpage', "module__frontpage_records" . $database->id, "Articles" );
		\IPS\Lang::saveCustom( 'frontpage', "content_db_" . $database->id . '_desc', "Our website articles" );
		\IPS\Lang::saveCustom( 'frontpage', "content_db_lang_sl_" . $database->id, 'article' );
		\IPS\Lang::saveCustom( 'frontpage', "content_db_lang_pl_" . $database->id, 'articles' );
		\IPS\Lang::saveCustom( 'frontpage', "content_db_lang_su_" . $database->id, 'Article' );
		\IPS\Lang::saveCustom( 'frontpage', "content_db_lang_pu_" . $database->id, 'Articles' );
		\IPS\Lang::saveCustom( 'frontpage', "content_db_lang_ia_" . $database->id, 'an article' );
		\IPS\Lang::saveCustom( 'frontpage', "content_db_lang_sl_" . $database->id . '_pl', 'Articles' );
		\IPS\Lang::saveCustom( 'frontpage', "frontpage_create_menu_records_" . $database->id, 'Article in Articles' );
		\IPS\Lang::saveCustom( 'frontpage', "digest_area_frontpage_records" . $database->id, "Articles" );
		\IPS\Lang::saveCustom( 'frontpage', "frontpage_records" . $database->id . '_pl', 'Article' );

		try
		{
			\IPS\frontpage\Databases::createDatabase( $database );
		}
		catch ( \Exception $ex )
		{
			$database->delete();

			\IPS\Log::log( $ex, 'fpages_create_db_error' );

			throw new \LogicException( $ex->getMessage() );
		}

		$database->all_editable = 0;
		$database->revisions    = 1;
		$database->search       = 1;
		$database->comment_bump = 1; # Just new comments bump record
		$database->rss	        = 10;
		$database->record_count = 1;
		$database->fixed_field_perms = array( 'record_image' => array( 'visible' => true, 'perm_view' => '*', 'perm_2' => '*', 'perm_3' => '*' ) );
		$database->options['comments'] = 1;
		$database->field_sort      = 'primary_id_field';
		$database->field_direction = 'asc';
		$database->field_perpage   = 25;
		$database->save();
		
		/* Create default record */
		$item    = 'IPS\frontpage\Records' . $database->id;
		$comment = 'IPS\frontpage\Records\Comment' . $database->id;
		$container = 'IPS\frontpage\Categories' . $database->id;
		
		$link = (string) \IPS\Http\Url::dcu('docs/frontpage_docs');

		$content = <<<EOF
<p>Welcome to FrontPage</p>
<p>FrontPage extends your site with custom content management designed especially for communities.
Create brand new sections of your community using features like blocks, databases and articles,
pulling in data from other areas of your community.</p>
<p>Create custom contents in your community using our drag'n'drop, WYSIWYG editor.
Build blocks that pull in all kinds of data from throughout your community to create dynamic contents,
or use one of the ready-made widgets we include with the Invision Community.</p>
<p><br></p>
<p><a href="{$link}">View our FrontPage documentation</a></p>
EOF;
		
		$titleField = 'field_' . $database->field_title;
		$contentField = 'field_' . $database->field_content;
		$category = $container::load( $database->_default_category );
		
		$member = \IPS\Member::loggedIn()->member_id ? \IPS\Member::loggedIn() : \IPS\Member::load(1);
		
		$record = $item::createItem( $member, \IPS\Request::i()->ipAddress(), \IPS\DateTime::ts( time() ), $category, FALSE );
		$record->$titleField = "Welcome to FrontPage";
		$record->$contentField = $content;
		$record->save();

		\IPS\Content\Search\Index::i()->index( $record );
		
		$category->last_record_date = time();
		$category->save();
		
		/* Create the page */
		$fpageValues = array(
			'fpage_name'         => "Articles",
			'fpage_title'        => "Articles",
			'fpage_seo_name'     => "index.html",
			'fpage_folder_id'    => 0,
			'fpage_ipb_wrapper'  => 1,
			'fpage_show_sidebar' => 1,
			'fpage_type'         => 'builder',
			'fpage_template'     => 'fpage_builder__single_column__fpage_fpage_builder_single_column'
		);
		
		try
		{
			$fpage = \IPS\frontpage\Fpages\Fpage::createFromForm( $fpageValues, 'html' );
		}
		catch( \Exception $ex )
		{
			\IPS\Log::log( $ex, 'fpages_create_fpage_error' );
		}
		
		$fpage->setAsDefault();
		
		\IPS\Db::i()->replace( 'core_permission_index', array(
             'app'			=> 'frontpage',
             'perm_type'	=> 'fpages',
             'perm_type_id'	=> $fpage->id,
             'perm_view'	=> '*'
        ) );
        
		$database->fpage_id = $fpage->id;
		$database->save();
		
		unset( \IPS\Data\Store::i()->fpages_fpage_urls );
		
		$defaultWidgets = array();
		$buttonUrl      = (string) \IPS\Http\Url::internal( 'applications/frontpage/interface/default/block_arrow.png', 'none' );
		$defaultContent = <<<EOF
		<p>
			<strong>Welcome to FrontPage</strong>
		</p>
		<p>
			To get started, make sure you are logged in and click the arrow on the left hand side <img src="{$buttonUrl}" alt="Block Manager"> to expand the block manager.
			<br>
			You can move, add and edit blocks without the need for complex coding!
		</p>
EOF;

		/* Default WYSIWYG widget */
		$defaultWidgets[] = array( 
			'app'           => 'frontpage',
			'key'           => 'Wysiwyg',
			'unique'        => mt_rand(),
			'configuration' => array( 'content' => $defaultContent )
		);
		
		/* Default database widget */
		$defaultWidgets[] = array( 
			'app'           => 'frontpage',
			'key'           => 'Database',
			'unique'        => mt_rand(),
			'configuration' => array( 'database' => $database->id )
		);
		
		\IPS\Db::i()->insert( 'frontpage_fpage_widget_areas', array(
			'area_fpage_id'     => $fpage->id,
			'area_widgets'     => json_encode( $defaultWidgets ),
			'area_area'        => 'col1',
			'area_orientation' => 'horizontal'
		) );
						
		/* Add block container (custom)*/
		$container = new \IPS\frontpage\Blocks\Container;
		$container->parent_id = 0;
		$container->name      = "Custom";
		$container->type      = 'block';
		$container->key       = 'block_custom';
		$container->save();

		/* Add block container (plugins) */
		$container = new \IPS\frontpage\Blocks\Container;
		$container->parent_id = 0;
		$container->name      = "Plugins";
		$container->type      = 'block';
		$container->key       = 'block_plugins';
		$container->save();
		
		\IPS\frontpage\Templates::importXml( \IPS\ROOT_PATH . "/applications/frontpage/data/frontpage_theme.xml", NULL, NULL, FALSE );
	}

	/**
	 * Install the application's templates
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @param	bool		$update	If set to true, do not overwrite current theme setting values
	 * @param	int|null	$offset Offset to begin import from
	 * @param	int|null	$limit	Number of rows to import	
	 * @return	int			Rows inserted
	 */
	public function installTemplates( $update=FALSE, $offset=null, $limit=null )
	{
		$inserted = parent::installTemplates( $update, $offset, $limit );
		
		if ( ( ! $inserted or ( $inserted < $limit ) ) AND $update )
		{
			\IPS\frontpage\Templates::importXml( \IPS\ROOT_PATH . "/applications/frontpage/data/frontpage_theme.xml", NULL, NULL, $update );
		}
	}

	/**
	 * Build skin templates for an app
	 *
	 * @return	void
	 * @throws	\RuntimeException
	 */
	public function buildThemeTemplates()
	{
		$return = parent::buildThemeTemplates();

		foreach( array( 'database', 'block', 'fpage' ) as $location )
		{
			\IPS\frontpage\Theme::importInDev( $location );
		}

		/* Build XML and write to app directory */
		$xml = new \XMLWriter;
		$xml->openMemory();
		$xml->setIndent( TRUE );
		$xml->startDocument( '1.0', 'UTF-8' );

		/* Root tag */
		$xml->startElement('theme');
		$xml->startAttribute('name');
		$xml->text( "Default" );
		$xml->endAttribute();
		$xml->startAttribute('author_name');
		$xml->text( "devCU Software" );
		$xml->endAttribute();
		$xml->startAttribute('author_url');
		$xml->text( "https://devcu.com" );
		$xml->endAttribute();

		/* Templates */
		foreach ( \IPS\Db::i()->select( '*', 'frontpage_templates', array( 'template_master=1 and template_user_created=0 and template_user_edited=0' ), 'template_group, template_title' ) as $template )
		{
			/* Initiate the <template> tag */
			$xml->startElement('template');

			foreach( $template as $k => $v )
			{
				if ( \in_array( \substr( $k, 9 ), array('key', 'title', 'desc', 'location', 'group', 'params', 'app', 'type' ) ) )
				{
					$xml->startAttribute( $k );
					$xml->text( $v );
					$xml->endAttribute();
				}
			}

			/* Write value */
			if ( preg_match( '/<|>|&/', $template['template_content'] ) )
			{
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $template['template_content'] ) );
			}
			else
			{
				$xml->text( $template['template_content'] );
			}

			/* Close the <template> tag */
			$xml->endElement();
		}

		/* Finish */
		$xml->endDocument();

		/* Write it */
		if ( is_writable( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data' ) )
		{
			\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data/frontpage_theme.xml', $xml->outputMemory() );
		}
		else
		{
			throw new \RuntimeException( \IPS\Member::loggedIn()->language()->addToStack('dev_could_not_write_data') );
		}

		return $return;
	}

	/**
	 * Can view page even when user is a guest when guests cannot access the site
	 *
	 * @param	\IPS\Application\Module	$module			The module
	 * @param	string					$controller		The controller
	 * @param	string|NULL				$do				To "do" parameter
	 * @return	bool
	 */
	public function allowGuestAccess( \IPS\Application\Module $module, $controller, $do )
	{
		return (
			$module->key == 'fpages' and $controller == 'fpage'
		);
	}


	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return	string|null
	 */
	protected function get__icon()
	{
		return 'paper-plane';
	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation()
	{
		$browseTabs = array();
		
		try
		{
			$defaultFpage = \IPS\frontpage\Fpages\Fpage::getDefaultFpage();
			$browseTabs[] = array( 'key' => 'Fpages', 'config' => array( 'menu_content_fpage' => $defaultFpage->id, 'menu_title_fpage_type' => 0 ) );
		}
		catch( \OutOfRangeException $ex ) { }
		
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> $browseTabs,
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}
}