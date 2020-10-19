<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.devcu.com/donate
 *
* @brief		FrontPage Controller
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.5x
 * @subpackage	FrontPage
 * @version     1.0.5 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     19 OCT 2020
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

namespace IPS\frontpage\modules\admin\fpages;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
exit;
}

/**
* Fpage management
*/
class _fpages extends \IPS\Node\Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\frontpage\Fpages\Folder';
	
	/**
	 * Store the database fpage map to prevent many queries
	 */
	protected static $fpageToDatabaseMap = NULL;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'fpage_manage' );
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
	 * Show the fpages tree
	 *
	 * @return	string
	 */
	protected function manage()
	{
		$url = \IPS\Http\Url::internal( "app=frontpage&module=fpages&controller=fpages" );
		static::$fpageToDatabaseMap = iterator_to_array( \IPS\Db::i()->select( 'database_id, database_fpage_id', 'frontpage_databases', array( 'database_fpage_id > 0' ) )->setKeyField('database_fpage_id')->setValueField('database_id') );
		
		/* Display the table */
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('menu__frontpage_fpages_fpages');
		\IPS\Output::i()->output = new \IPS\Helpers\Tree\Tree( $url, 'menu__frontpage_fpages_fpages',
			/* Get Roots */
			function () use ( $url )
			{
				$data = \IPS\frontpage\modules\admin\fpages\fpages::getRowsForTree( 0 );
				$rows = array();

				foreach ( $data as $id => $row )
				{
					$rows[ $id ] = ( $row instanceof \IPS\frontpage\Fpages\Fpage ) ? \IPS\frontpage\modules\admin\fpages\fpages::getFpageRow( $row, $url ) : \IPS\frontpage\modules\admin\fpages\fpages::getFolderRow( $row, $url );
				}

				return $rows;
			},
			/* Get Row */
			function ( $id, $root ) use ( $url )
			{
				if ( $root )
				{
					return \IPS\frontpage\modules\admin\fpages\fpages::getFolderRow( \IPS\frontpage\Fpages\Folder::load( $id ), $url );
				}
				else
				{
					return \IPS\frontpage\modules\admin\fpages\fpages::getFpageRow( \IPS\frontpage\Fpages\Fpage::load( $id ), $url );
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
				$data = \IPS\frontpage\modules\admin\fpages\fpages::getRowsForTree( $id );

				if ( ! isset( \IPS\Request::i()->subnode ) )
				{
					foreach ( $data as $id => $row )
					{
						$rows[ $id ] = ( $row instanceof \IPS\frontpage\Fpages\Fpage ) ? \IPS\frontpage\modules\admin\fpages\fpages::getFpageRow( $row, $url ) : \IPS\frontpage\modules\admin\fpages\fpages::getFolderRow( $row, $url );
					}
				}
				return $rows;
			},
           array( $this, '_getRootButtons' ),
           TRUE,
           FALSE,
           FALSE
		);
		
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'frontpage', 'fpages', 'fpage_add' )  )
		{
			\IPS\Output::i()->sidebar['actions']['add_folder'] = array(
				'primary'	=> true,
				'icon'	=> 'folder-open',
				'title'	=> 'content_add_folder',
				'link'	=> \IPS\Http\Url::internal( 'app=frontpage&module=fpages&controller=fpages&do=form' ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('content_add_folder') )
			);

			\IPS\Output::i()->sidebar['actions']['add_fpage'] = array(
				'primary'	=> true,
				'icon'	=> 'plus-circle',
				'title'	=> 'content_add_fpage',
				'link'	=>  \IPS\Http\Url::internal( 'app=frontpage&module=fpages&controller=fpages&subnode=1&do=add' ),
				'data'  => array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('content_add_fpage') )
			);
		}
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
	 * Fpage content form
	 *
	 * @return void
	 */
	protected function add()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'fpage_add' );

		$form = new \IPS\Helpers\Form( 'form', 'next' );
		$form->hiddenValues['parent'] = ( isset( \IPS\Request::i()->parent ) ) ? \IPS\Request::i()->parent : 0;

		$form->add( new \IPS\Helpers\Form\Radio(
			            'fpage_type',
			            NULL,
			            FALSE,
			            array( 'options'      => array( 'builder' => 'fpage_type_builder', 'html' => 'fpage_type_manual' ),
			                   'descriptions' => array( 'builder' => 'fpage_type_builder_desc', 'html' => 'fpage_type_manual_custom_desc' ) ),
			            NULL,
			            NULL,
			            NULL,
			            'fpage_type'
		            ) );


		if ( $values = $form->values() )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=frontpage&module=fpages&controller=fpages&do=form&subnode=1&fpage_type=' . $values['fpage_type'] . '&parent=' . \IPS\Request::i()->parent ) );
		}

		/* Display */
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'admin' )->block( \IPS\Member::loggedIn()->language()->addToStack('content_add_fpage'), $form, FALSE );
		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('content_add_fpage');
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
			\IPS\Session::i()->csrfCheck();
			\IPS\frontpage\Fpages\Fpage::deleteCompiled( \IPS\Request::i()->id );
		}

		return parent::delete();
	}

	/**
	 * Set as default fpage for this folder
	 *
	 * @return void
	 */
	protected function setAsDefault()
	{
		\IPS\Session::i()->csrfCheck();
		\IPS\frontpage\Fpages\Fpage::load( \IPS\Request::i()->id )->setAsDefault();
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=frontpage&module=fpages&controller=fpages" ), 'saved' );
	}

	/**
	 * Set as default error page
	 *
	 * @return void
	 */
	protected function toggleDefaultError()
	{
		\IPS\Session::i()->csrfCheck();
		\IPS\Settings::i()->changeValues( array( 'frontpage_error_page' => \IPS\Request::i()->id ? \IPS\Request::i()->id : NULL ) );
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=frontpage&module=fpages&controller=fpages" ), 'saved' );
	}

	/**
	 * Tree Search
	 *
	 * @return	void
	 */
	protected function search()
	{
		$rows = array();
		$url  = \IPS\Http\Url::internal( "app=frontpage&module=fpages&controller=fpages" );

		/* Get results */
		$folders = \IPS\frontpage\Fpages\Folder::search( 'folder_name'  , \IPS\Request::i()->input, 'folder_name' );
		$fpages   = \IPS\frontpage\Fpages\Fpage::search( 'fpage_seo_name', \IPS\Request::i()->input, 'fpage_seo_name' );

		$results =  \IPS\frontpage\Fpages\Folder::munge( $folders, $fpages );

		/* Convert to HTML */
		foreach ( $results as $id => $result )
		{
			$rows[ $id ] = ( $result instanceof \IPS\frontpage\Fpages\Fpage ) ? $this->getFpageRow( $result, $url ) : $this->getFolderRow( $result, $url );
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'trees', 'core' )->rows( $rows, '' );
	}

	/**
	 * Return HTML for a fpage row
	 *
	 * @param   array   $fpage	Row data
	 * @param	object	$url	\IPS\Http\Url object
	 * @return	string	HTML
	 */
	public static function getFpageRow( $fpage, $url )
	{
		$badge = NULL;
		
		if ( isset( static::$fpageToDatabaseMap[ $fpage->id ] ) )
		{
			$badge = array( 0 => 'style7', 1 => \IPS\Member::loggedIn()->language()->addToStack( 'fpage_database_display', NULL, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack('content_db_' . static::$fpageToDatabaseMap[ $fpage->id ] ) ) ) ) );
		}
		return \IPS\Theme::i()->getTemplate( 'trees', 'core' )->row( $url, $fpage->id, $fpage->seo_name, false, $fpage->getButtons( \IPS\Http\url::internal('app=frontpage&module=fpages&controller=fpages'), true ), "", 'file-text-o', NULL, FALSE, NULL, NULL, $badge, FALSE, FALSE, FALSE );
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
		return \IPS\Theme::i()->getTemplate( 'trees', 'core' )->row( $url, $folder->id, $folder->name, true, $folder->getButtons( \IPS\Http\url::internal('app=frontpage&module=fpages&controller=fpages') ),  "", 'folder-o', NULL );
	}

	/**
	 * Fetch rows of folders/fpages
	 *
	 * @param	int	$folderId		Parent ID to fetch from
	 */
	public static function getRowsForTree( $folderId=0 )
	{
		try
		{
			if ( $folderId === 0 )
			{
				$folders = \IPS\frontpage\Fpages\Folder::roots();
			}
			else
			{
				$folders = \IPS\frontpage\Fpages\Folder::load( $folderId )->children( NULL, NULL, FALSE );
			}
		}
		catch( \OutOfRangeException $ex )
		{
			$folders = array();
		}

		$fpages   = \IPS\frontpage\Fpages\Fpage::getChildren( $folderId );

		return \IPS\frontpage\Fpages\Folder::munge( $folders, $fpages );
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
		/* If this fpage was the default in a folder, and it was moved to a new folder that already has a default, we need to unset the 
			default fpage flag or there will be two defaults in the destination folder */
		if( $old !== NULL AND $old->folder_id != $new->folder_id AND $old->default )
		{
			/* Is there already a default fpage in the new folder? */
			try
			{
				$existingDefault = \IPS\Db::i()->select( 'fpage_id', 'frontpage_fpages', array( 'fpage_folder_id=? and fpage_default=?', $new->folder_id, 1 ) )->first();

				\IPS\Db::i()->update( 'frontpage_fpages', array( 'fpage_default' => 0 ), array( 'fpage_id=?', $new->id ) );

				\IPS\frontpage\Fpages\Fpage::buildFpageUrlStore();
			}
			catch( \UnderflowException $e )
			{
				/* No default found in destination folder, do nothing */
			}
		}
		
		/* If fpage filename changes or the folder ID changes, we need to clear front navigation cache*/
		if( $old !== NULL AND ( $old->folder_id != $new->folder_id OR $old->seo_name != $new->seo_name ) )
		{
			unset( \IPS\Data\Store::i()->fpages_fpage_urls );
		}

		parent::_afterSave( $old, $new, $lastUsedTab );
	}
}