<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
* @brief		Pages Controller
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4+
 * @subpackage	FrontPage
 * @version     1.0.0
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     02 MAY 2019
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

namespace IPS\frontpage\modules\admin\pages;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
exit;
}

/**
* Page management
*/
class _pages extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\frontpage\Pages\Folder';
	
	/**
	 * Store the database page map to prevent many queries
	 */
	protected static $pageToDatabaseMap = NULL;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'page_manage' );
		parent::execute();
	}

	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons()
	{
		$nodeClass = $this->nodeClass;
		$buttons   = array();

		return $buttons;
	}

	/**
	 * Show the pages tree
	 *
	 * @return	string
	 */
	protected function manage()
	{
		$url = \IPS\Http\Url::internal( "app=frontpage&module=pages&controller=pages" );
		static::$pageToDatabaseMap = iterator_to_array( \IPS\Db::i()->select( 'database_id, database_page_id', 'frontpage_databases', array( 'database_page_id > 0' ) )->setKeyField('database_page_id')->setValueField('database_id') );
		
		/* Display the table */
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('menu__frontpage_pages_pages');
		\IPS\Output::i()->output = new \IPS\Helpers\Tree\Tree( $url, 'menu__frontpage_pages_pages',
			/* Get Roots */
			function () use ( $url )
			{
				$data = \IPS\frontpage\modules\admin\pages\pages::getRowsForTree( 0 );
				$rows = array();

				foreach ( $data as $id => $row )
				{
					$rows[ $id ] = ( $row instanceof \IPS\frontpage\Pages\Page ) ? \IPS\frontpage\modules\admin\pages\pages::getPageRow( $row, $url ) : \IPS\frontpage\modules\admin\pages\pages::getFolderRow( $row, $url );
				}

				return $rows;
			},
			/* Get Row */
			function ( $id, $root ) use ( $url )
			{
				if ( $root )
				{
					return \IPS\frontpage\modules\admin\pages\pages::getFolderRow( \IPS\frontpage\Pages\Folder::load( $id ), $url );
				}
				else
				{
					return \IPS\frontpage\modules\admin\pages\pages::getPageRow( \IPS\frontpage\Pages\Page::load( $id ), $url );
				}
			},
			/* Get Row Parent ID*/
			function ()
			{
				return NULL;
			},
			/* Get Children */
			function ( $id ) use ( $url )
			{
				$rows = array();
				$data = \IPS\frontpage\modules\admin\pages\pages::getRowsForTree( $id );

				if ( ! isset( \IPS\Request::i()->subnode ) )
				{
					foreach ( $data as $id => $row )
					{
						$rows[ $id ] = ( $row instanceof \IPS\frontpage\Pages\Page ) ? \IPS\frontpage\modules\admin\pages\pages::getPageRow( $row, $url ) : \IPS\frontpage\modules\admin\pages\pages::getFolderRow( $row, $url );
					}
				}
				return $rows;
			},
           array( $this, '_getRootButtons' ),
           TRUE,
           FALSE,
           FALSE
		);
		
		/* Add a button for managing DB settings */
		\IPS\Output::i()->sidebar['actions']['pagessettings'] = array(
			'title'		=> 'frontpage_pages_settings',
			'icon'		=> 'wrench',
			'link'		=> \IPS\Http\Url::internal( 'app=frontpage&module=pages&controller=pages&do=settings' ),
			'data'	    => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('frontpage_pages_settings') )
		);

		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'frontpage', 'pages', 'page_add' )  )
		{
			\IPS\Output::i()->sidebar['actions']['add_folder'] = array(
				'primary'	=> true,
				'icon'	=> 'folder-open',
				'title'	=> 'content_add_folder',
				'link'	=> \IPS\Http\Url::internal( 'app=frontpage&module=pages&controller=pages&do=form' ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('content_add_folder') )
			);

			\IPS\Output::i()->sidebar['actions']['add_page'] = array(
				'primary'	=> true,
				'icon'	=> 'plus-circle',
				'title'	=> 'content_add_page',
				'link'	=>  \IPS\Http\Url::internal( 'app=frontpage&module=pages&controller=pages&subnode=1&do=add' ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('content_add_page') )
			);
		}
	}
	
	/**
	 * Page settings form
	 *
	 * @return void
	 */
	protected function settings()
	{
		$url 	  = parse_url( \IPS\Settings::i()->base_url );
		$disabled = FALSE;
		$options  = array();
		$url['path'] = preg_replace( '#^/?(.+?)?/?$#', '\1', $url['path'] );
		
		$disabled = ( \IPS\Settings::i()->frontpage_use_different_gateway or $url['path'] ) ? FALSE : TRUE;
		$dirs     = explode( '/', $url['path'] );
		
		if ( \count( $dirs ) )
		{
			array_pop( $dirs );
			$base = $url['scheme'] . '://' . $url['host'];
			if ( isset( $url['port'] ) )
			{
				$base .= ':' .$url['port'];
			}

			$base .= '/';
			$options[ $base ] = $base;
			foreach( $dirs as $dir )
			{
				$base .= $dir . '/'; 
				$options[ $base ] = $base;
			}
		}
		
		if ( $disabled )
		{
			\IPS\Member::loggedIn()->language()->words['frontpage_use_different_gateway_warning'] = \IPS\Member::loggedIn()->language()->addToStack('frontpage_pages_different_gateway_impossible');
		}
		
		if ( \IPS\Settings::i()->htaccess_mod_rewrite )
		{
			\IPS\Member::loggedIn()->language()->words['frontpage_root_page_url_desc'] = \IPS\Member::loggedIn()->language()->addToStack('frontpage_root_page_url_rewrite_desc');
		}
		
		$form = new \IPS\Helpers\Form( 'form', 'save' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'frontpage_use_different_gateway', \IPS\Settings::i()->frontpage_use_different_gateway, FALSE, array( 'togglesOn' => array( 'frontpage_root_page_url' ), 'disabled' => $disabled ) ) );
		$form->add( new \IPS\Helpers\Form\Select( 'frontpage_root_page_url', \IPS\Settings::i()->frontpage_root_page_url, FALSE, array( 'options' => $options ), function( $val )
		{
			if ( $val and \IPS\Request::i()->frontpage_use_different_gateway )
			{
				if ( mb_substr( $val, -1 ) !== '/' )
				{
					$val .= '/';
				}
				
				$page = \IPS\frontpage\Pages\Page::getDefaultPage();
				
				$response = \IPS\Http\Url::external( ( \IPS\Settings::i()->htaccess_mod_rewrite ? $val . $page->full_path : $val . 'index.php?/' . $page->full_path ) )->request( NULL, NULL, FALSE )->get();
				
				if ( $response->httpResponseCode != 200 and $response->httpResponseCode != 303 and ( \IPS\Settings::i()->site_online OR $response->httpResponseCode != 503 ) )
				{
					if ( \IPS\Settings::i()->htaccess_mod_rewrite )
					{
						throw new \LogicException( 'pages_different_gateway_load_error_rewrite' );
					}
					else
					{
						throw new \LogicException( 'pages_different_gateway_load_error' );
					}
				}
			}
		}, NULL, NULL, 'frontpage_root_page_url' ) );

		$form->add( new \IPS\Helpers\Form\Node( 'frontpage_error_page', \IPS\Settings::i()->frontpage_error_page ? \IPS\Settings::i()->frontpage_error_page : 0, FALSE,array(
			'class'           => '\IPS\frontpage\Pages\Page',
			'zeroVal'         => 'frontpage_error_page_none',
			'subnodes'		  => true,
			'permissionCheck' => function( $node )
			{
				return $node->type == 'html';
			}
		) ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
			\IPS\Member::clearCreateMenu();
									
			/* Clear Sidebar Caches */
			\IPS\Widget::deleteCaches();
			
			/* Possible gateway choice changed and thusly menu and page_urls will change */
			if ( isset( \IPS\Data\Store::i()->pages_page_urls ) )
			{
				unset( \IPS\Data\Store::i()->pages_page_urls  );
			}
			
			if ( isset( \IPS\Data\Store::i()->frontNavigation ) )
			{
				unset( \IPS\Data\Store::i()->frontNavigation  );
			}
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=frontpage&module=pages&controller=pages" ), 'saved' );
		}
	
		/* Display */
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( \IPS\Member::loggedIn()->language()->addToStack('frontpage_pages_settings'), $form, FALSE );
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('frontpage_pages_settings');
	}
	
	/**
	 * Download .htaccess file
	 *
	 * @return	void
	 */
	protected function htaccess()
	{
		$dir = str_replace( \IPS\CP_DIRECTORY . '/index.php', '', $_SERVER['PHP_SELF'] );
		$dirs = explode( '/', trim( $dir, '/' ) );
		
		if ( \count( $dirs ) )
		{
			array_pop( $dirs );
			$dir = implode( '/', $dirs );
			
			if ( ! $dir )
			{
				$dir = '/';
			}
		}
		
		$path = $dir . 'index.php';
		
		if( \strpos( $dir, ' ' ) !== FALSE )
		{
			$dir = '"' . $dir . '"';
			$path = '"' . $path . '"';
		}


		$htaccess = <<<FILE
<IfModule mod_rewrite.c>
Options -MultiViews
RewriteEngine On
RewriteBase {$dir}
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule \\.(js|css|jpeg|jpg|gif|png|ico)(\\?|$) - [L,NC,R=404]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . {$path} [L]
</IfModule>
FILE;

		\IPS\Output::i()->sendOutput( $htaccess, 200, 'application/x-htaccess', array( 'Content-Disposition' => 'attachment; filename=.htaccess' ) );
	}

	/**
	 * Page content form
	 *
	 * @return void
	 */
	protected function add()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'page_add' );

		$form = new \IPS\Helpers\Form( 'form', 'next' );
		$form->hiddenValues['parent'] = ( isset( \IPS\Request::i()->parent ) ) ? \IPS\Request::i()->parent : 0;

		$form->add( new \IPS\Helpers\Form\Radio(
			            'page_type',
			            NULL,
			            FALSE,
			            array( 'options'      => array( 'builder' => 'page_type_builder', 'html' => 'page_type_manual' ),
			                   'descriptions' => array( 'builder' => 'page_type_builder_desc', 'html' => 'page_type_manual_custom_desc' ) ),
			            NULL,
			            NULL,
			            NULL,
			            'page_type'
		            ) );


		if ( $values = $form->values() )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=frontpage&module=pages&controller=pages&do=form&subnode=1&page_type=' . $values['page_type'] . '&parent=' . \IPS\Request::i()->parent ) );
		}

		/* Display */
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( \IPS\Member::loggedIn()->language()->addToStack('content_add_page'), $form, FALSE );
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('content_add_page');
	}

	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete()
	{
		if ( isset( \IPS\Request::i()->id ) )
		{
			\IPS\frontpage\Pages\Page::deleteCompiled( \IPS\Request::i()->id );
		}

		return parent::delete();
	}

	/**
	 * Set as default page for this folder
	 *
	 * @return void
	 */
	protected function setAsDefault()
	{
		\IPS\frontpage\Pages\Page::load( \IPS\Request::i()->id )->setAsDefault();
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=frontpage&module=pages&controller=pages" ), 'saved' );
	}

	/**
	 * Tree Search
	 *
	 * @return	void
	 */
	protected function search()
	{
		$rows = array();
		$url  = \IPS\Http\Url::internal( "app=frontpage&module=pages&controller=pages" );

		/* Get results */
		$folders = \IPS\frontpage\Pages\Folder::search( 'folder_name'  , \IPS\Request::i()->input, 'folder_name' );
		$pages   = \IPS\frontpage\Pages\Page::search( 'page_seo_name', \IPS\Request::i()->input, 'page_seo_name' );

		$results =  \IPS\frontpage\Pages\Folder::munge( $folders, $pages );

		/* Convert to HTML */
		foreach ( $results as $id => $result )
		{
			$rows[ $id ] = ( $result instanceof \IPS\frontpage\Pages\Page ) ? $this->getPageRow( $result, $url ) : $this->getFolderRow( $result, $url );
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'trees', 'core' )->rows( $rows, '' );
	}

	/**
	 * Return HTML for a page row
	 *
	 * @param   array   $page	Row data
	 * @param	object	$url	\IPS\Http\Url object
	 * @return	string	HTML
	 */
	public static function getPageRow( $page, $url )
	{
		$badge = NULL;
		
		if ( isset( static::$pageToDatabaseMap[ $page->id ] ) )
		{
			$badge = array( 0 => 'style7', 1 => \IPS\Member::loggedIn()->language()->addToStack( 'page_database_display', NULL, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack('content_db_' . static::$pageToDatabaseMap[ $page->id ] ) ) ) ) );
		}
		return \IPS\Theme::i()->getTemplate( 'trees', 'core' )->row( $url, $page->id, $page->seo_name, false, $page->getButtons( \IPS\Http\url::internal('app=frontpage&module=pages&controller=pages'), true ), "", 'file-text-o', NULL, FALSE, NULL, NULL, $badge, FALSE, FALSE, FALSE );
	}

	/**
	 * Return HTML for a folder row
	 *
	 * @param   array   $folder	Row data
	 * @param	object	$url	\IPS\Http\Url object
	 * @return	string	HTML
	 */
	public static function getFolderRow( $folder, $url )
	{
		return \IPS\Theme::i()->getTemplate( 'trees', 'core' )->row( $url, $folder->id, $folder->name, true, $folder->getButtons( \IPS\Http\url::internal('app=frontpage&module=pages&controller=pages') ),  "", 'folder-o', NULL );
	}

	/**
	 * Fetch rows of folders/pages
	 *
	 * @param	int	$folderId		Parent ID to fetch from
	 */
	public static function getRowsForTree( $folderId=0 )
	{
		try
		{
			if ( $folderId === 0 )
			{
				$folders = \IPS\frontpage\Pages\Folder::roots();
			}
			else
			{
				$folders = \IPS\frontpage\Pages\Folder::load( $folderId )->children( NULL, NULL, FALSE );
			}
		}
		catch( \OutOfRangeException $ex )
		{
			$folders = array();
		}

		$pages   = \IPS\frontpage\Pages\Page::getChildren( $folderId );

		return \IPS\frontpage\Pages\Folder::munge( $folders, $pages );
	}

	/**
	 * Redirect after save
	 *
	 * @param	\IPS\Node\Model	$old			A clone of the node as it was before or NULL if this is a creation
	 * @param	\IPS\Node\Model	$new			The node now
	 * @param	string			$lastUsedTab	The tab last used in the form
	 * @return	void
	 */
	protected function _afterSave( \IPS\Node\Model $old = NULL, \IPS\Node\Model $new, $lastUsedTab = FALSE )
	{
		/* If this page was the default in a folder, and it was moved to a new folder that already has a default, we need to unset the 
			default page flag or there will be two defaults in the destination folder */
		if( $old !== NULL AND $old->folder_id != $new->folder_id AND $old->default )
		{
			/* Is there already a default page in the new folder? */
			try
			{
				$existingDefault = \IPS\Db::i()->select( 'page_id', 'frontpage_pages', array( 'page_folder_id=? and page_default=?', $new->folder_id, 1 ) )->first();

				\IPS\Db::i()->update( 'frontpage_pages', array( 'page_default' => 0 ), array( 'page_id=?', $new->id ) );

				\IPS\frontpage\Pages\Page::buildPageUrlStore();
			}
			catch( \UnderflowException $e )
			{
				/* No default found in destination folder, do nothing */
			}
		}
		
		/* If page filename changes or the folder ID changes, we need to clear front navigation cache*/
		if( $old !== NULL AND ( $old->folder_id != $new->folder_id OR $old->seo_name != $new->seo_name ) )
		{
			unset( \IPS\Data\Store::i()->pages_page_urls );
		}

		parent::_afterSave( $old, $new, $lastUsedTab );
	}
}