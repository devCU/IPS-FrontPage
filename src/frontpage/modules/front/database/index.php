<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		FrontPage [Database] Category List Controller
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

namespace IPS\frontpage\modules\front\database;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * List
 */
class _index extends \IPS\frontpage\Databases\Controller
{

	/**
	 * Determine which method to load
	 *
	 * @return void
	 */
	public function manage()
	{
		/* If the Databases module is set as default we end up here, but not routed through the database dispatcher which means the
			database ID isn't set. In that case, just re-route back through the pages controller which handles everything. */
		if( \IPS\frontpage\Databases\Dispatcher::i()->databaseId === NULL )
		{
			$pages = new \IPS\frontpage\modules\front\pages\page;
			return $pages->manage();
		}

		$database = \IPS\frontpage\Databases::load( \IPS\frontpage\Databases\Dispatcher::i()->databaseId );

		/* Not using categories? */
		if ( ! $database->use_categories AND $database->cat_index_type === 0 )
		{
			$controller = new \IPS\frontpage\modules\front\database\category( $this->url );
			return $controller->view();
		}
		
		$this->view();
	}

	/**
	 * Display database category list.
	 *
	 * @return	void
	 */
	protected function view()
	{
		$database    = \IPS\frontpage\Databases::load( \IPS\frontpage\Databases\Dispatcher::i()->databaseId );
		$recordClass = 'IPS\frontpage\Records' . \IPS\frontpage\Databases\Dispatcher::i()->databaseId;
		$url         = \IPS\Http\Url::internal( "app=frontpage&module=pages&controller=page&path=" . \IPS\frontpage\Pages\Page::$currentPage->full_path, 'front', 'content_page_path', \IPS\frontpage\Pages\Page::$currentPage->full_path );

		/* RSS */
		if ( $database->rss )
		{
			/* Show the link */
			\IPS\Output::i()->rssFeeds[ $database->_title ] = $url->setQueryString( 'rss', 1 );

			/* Or actually show RSS feed */
			if ( isset( \IPS\Request::i()->rss ) )
			{
				$document     = \IPS\Xml\Rss::newDocument( $url, \IPS\Member::loggedIn()->language()->get('content_db_' . $database->id ), \IPS\Member::loggedIn()->language()->get('content_db_' . $database->id . '_desc' ) );
				$contentField = 'field_' . $database->field_content;
				
				foreach ( $recordClass::getItemsWithPermission( array(), $database->field_sort . ' ' . $database->field_direction, $database->rss, 'read' ) as $record )
				{
					$content = $record->$contentField;
						
					if ( $record->record_image )
					{
						$content = \IPS\frontpage\Theme::i()->getTemplate( 'listing', 'frontpage', 'database' )->rssItemWithImage( $content, $record->record_image );
					}

					$document->addItem( $record->_title, $record->url(), $content, \IPS\DateTime::ts( $record->_publishDate ), $record->_id );
				}
		
				/* @note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
				\IPS\Output::i()->sendOutput( $document->asXML(), 200, 'text/xml', array(), TRUE );
			}
		}

		$page = isset( \IPS\Request::i()->page ) ? \intval( \IPS\Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		if ( $database->cat_index_type === 1 and ! isset( \IPS\Request::i()->show ) )
		{
			/* Featured */
			$limit = 0;
			$count = 0;

			if ( isset( \IPS\Request::i()->page ) )
			{
				$limit = $database->featured_settings['perpage'] * ( $page - 1 );
			}

			$where = ( $database->featured_settings['featured'] ) ? array( array( 'record_featured=?', 1 ) ) : NULL;
			
			if ( isset( $database->featured_settings['categories'] ) and \is_array( $database->featured_settings['categories'] ) and \count( $database->featured_settings['categories'] ) )
			{
				$categoryField = "`frontpage_custom_database_{$database->_id}`.`category_id`";
				$where[] = array( \IPS\Db::i()->in( $categoryField, array_values( $database->featured_settings['categories'] ) ) );
			}
			
			$articles = $recordClass::getItemsWithPermission( $where, 'record_pinned DESC, ' . $database->featured_settings['sort'] . ' ' . $database->featured_settings['direction'], array( $limit, $database->featured_settings['perpage'] ), 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, NULL, TRUE, FALSE, FALSE, FALSE );

			if ( $database->featured_settings['pagination'] )
			{
				$count = $recordClass::getItemsWithPermission( $where, 'record_pinned DESC, ' . $database->featured_settings['sort'] . ' ' . $database->featured_settings['direction'], $database->featured_settings['perpage'], 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, NULL, FALSE, FALSE, FALSE, TRUE );
			}

			/* Pagination */
			$pagination = array(
				'page'  => $page,
				'pages' => ( $count > 0 ) ? ceil( $count / $database->featured_settings['perpage'] ) : 1
			);
			
			/* Make sure we are viewing a real page */
			if ( $page > $pagination['pages'] )
			{
				\IPS\Output::i()->redirect( \IPS\Request::i()->url()->setPage( 'page', 1 ), NULL, 303 );
			}
			
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'database_index/featured.css', 'frontpage', 'front' ) );
			\IPS\Output::i()->title = ( $page > 1 ) ? \IPS\Member::loggedIn()->language()->addToStack( 'title_with_page_number', FALSE, array( 'sprintf' => array( $database->pageTitle(), $page ) ) ) : $database->pageTitle();

			\IPS\frontpage\Databases\Dispatcher::i()->output .= \IPS\Output::i()->output = \IPS\frontpage\Theme::i()->getTemplate( $database->template_featured, 'frontpage', 'database' )->index( $database, $articles, $url, $pagination );
		}
		else
		{
			/* Category view */
			$class = '\IPS\frontpage\Categories' . $database->id;
			
			/* Load into memory */
			$class::loadIntoMemory();
			$categories = $class::roots();

			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'records/index.css', 'frontpage', 'front' ) );
			\IPS\Output::i()->title = $database->pageTitle();
			\IPS\frontpage\Databases\Dispatcher::i()->output .= \IPS\Output::i()->output = \IPS\frontpage\Theme::i()->getTemplate( $database->template_categories, 'frontpage', 'database' )->index( $database, $categories, $url );
		}
	}

	/**
	 * Show the pre add record form. This is used when no category is set.
	 *
	 * @return	void
	 */
	protected function form()
	{
		/* If the page is the default page and Pages is the default app, the node selector cannot find the page as it bypasses the Database dispatcher */
		if ( \IPS\frontpage\Pages\Page::$currentPage === NULL and \IPS\frontpage\Databases\Dispatcher::i()->databaseId === NULL and isset( \IPS\Request::i()->page_id ) )
		{
			try
			{
				\IPS\frontpage\Pages\Page::$currentPage = \IPS\frontpage\Pages\Page::load( \IPS\Request::i()->page_id );
				$database = \IPS\frontpage\Pages\Page::$currentPage->getDatabase();
				
			}
			catch( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'content_err_page_404', '2T389/1', 404, '' );
			}
		}
		else if ( \IPS\frontpage\Pages\Page::$currentPage === NULL and \IPS\frontpage\Databases\Dispatcher::i()->databaseId === NULL and isset( \IPS\Request::i()->d ) )
		{
			\IPS\frontpage\Pages\Page::$currentPage = \IPS\frontpage\Pages\Page::loadByDatabaseId( \IPS\Request::i()->d );
		}
		
		$form = new \IPS\Helpers\Form( 'select_category', 'continue' );
		$form->class = 'ipsForm_vertical ipsForm_noLabels';
		$form->add( new \IPS\Helpers\Form\Node( 'category', NULL, TRUE, array(
			'url'					=> \IPS\frontpage\Pages\Page::$currentPage->url()->setQueryString( array( 'do' => 'form', 'page_id' => \IPS\frontpage\Pages\Page::$currentPage->id ) ),
			'class'					=> 'IPS\frontpage\Categories' . \IPS\frontpage\Databases\Dispatcher::i()->databaseId,
			'permissionCheck'		=> function( $node )
			{
				if ( $node->can( 'view' ) )
				{
					if ( $node->can( 'add' ) )
					{
						return TRUE;
					}

					return FALSE;
				}

				return NULL;
			},
		) ) );

		if ( $values = $form->values() )
		{
			\IPS\Output::i()->redirect( $values['category']->url()->setQueryString( 'do', 'form' ) );
		}

		\IPS\Output::i()->title						= \IPS\Member::loggedIn()->language()->addToStack( 'frontpage_select_category' );
		\IPS\Output::i()->breadcrumb[]				= array( NULL, \IPS\Member::loggedIn()->language()->addToStack( 'frontpage_select_category' ) );
		\IPS\frontpage\Databases\Dispatcher::i()->output	= \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'records' )->categorySelector( $form );
	}
}