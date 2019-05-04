<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		FrontPage Page Controller
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

namespace IPS\frontpage\modules\front\pages;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * page
 */
class _page extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		parent::execute();
	}

	/**
	 * Determine which method to load
	 *
	 * @return void
	 */
	public function manage()
	{
		$this->view();
	}
	
	/**
	 * Display a page. Sounds simple doesn't it? Well it's not.
	 *
	 * @return	void
	 */
	protected function view()
	{
		$page = $this->getPage();
		
		/* Database specific checks */
		if ( isset( \IPS\Request::i()->advancedSearchForm ) AND isset( \IPS\Request::i()->d ) )
		{
			/* showTableSearchForm just triggers __call which returns the database dispatcher HTML as we
			 * do not want the page content around the actual database */
			\IPS\Output::i()->output = $this->showTableSearchForm();
			return;
		}

		if ( \IPS\Request::i()->path == $page->full_path )
		{
			/* Did we have a trailing slash? */
			if ( \IPS\Settings::i()->htaccess_mod_rewrite and mb_substr( \IPS\Request::i()->url()->data[ \IPS\Http\Url::COMPONENT_PATH ], -1 ) != '/' )
			{
				$url = $page->url();
				
				foreach( \IPS\Request::i()->url()->queryString as $k => $v )
				{
					$url = $url->setQueryString( $k, $v );
				}
				
				if ( ! empty( \IPS\Request::i()->url()->fragment ) )
				{
					$url = $url->setFragment( \IPS\Request::i()->url()->fragment );
				}

				\IPS\Output::i()->redirect( $url, NULL, 301 );
			}
			else if ( ! \IPS\Settings::i()->htaccess_mod_rewrite and ! mb_strstr( \IPS\Request::i()->url()->data[ \IPS\Http\Url::COMPONENT_QUERY ], $page->url()->data[ \IPS\Http\Url::COMPONENT_QUERY ] ) )
			{
				$url = $page->url();
				
				foreach( \IPS\Request::i()->url()->queryString as $k => $v )
				{
					if ( mb_substr( $k, 0, 1 ) == '/' and mb_substr( $k, -1 ) != '/' )
					{
						$k .= '/';
					}
				}
				
				$url = $url->setQueryString( $k, $v );
				
				if ( ! empty( \IPS\Request::i()->url()->fragment ) )
				{
					$url = $url->setFragment( \IPS\Request::i()->url()->fragment );
				}

				\IPS\Output::i()->redirect( $url, NULL, 301 );
			}

			/* Just viewing this page, no database categories or records */
			$permissions = $page->permissions();
			\IPS\Session::i()->setLocation( $page->url(), explode( ",", $permissions['perm_view'] ), 'loc_frontpage_viewing_page', array( 'frontpage_page_' . $page->_id => TRUE ) );
		}
		
		try
		{
			$page->output();
		}
		catch ( \ParseError $e )
		{
			\IPS\Log::log( $e, 'page_error' );
			\IPS\Output::i()->error( 'content_err_page_500', '2T187/4', 500, 'content_err_page_500_admin', array(), $e );
		}
	}
	
	/**
	 * Get the current page
	 * 
	 * @return \IPS\frontpage\Pages\Page
	 */
	public function getPage()
	{
		$page = null;
		if ( isset( \IPS\Request::i()->page_id ) )
		{
			try
			{
				$page = \IPS\frontpage\Pages\Page::load( \IPS\Request::i()->page_id );
			}
			catch ( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'content_err_page_404', '2T187/1', 404, '' );
			}
		}
		else if ( isset( \IPS\Request::i()->path ) AND  \IPS\Request::i()->path != '/' )
		{
			try
			{
				$page = \IPS\frontpage\Pages\Page::loadFromPath( \IPS\Request::i()->path );
			}
			catch ( \OutOfRangeException $e )
			{
				try
				{
					$page = \IPS\frontpage\Pages\Page::getUrlFromHistory( \IPS\Request::i()->path, ( isset( \IPS\Request::i()->url()->data['query'] ) ? \IPS\Request::i()->url()->data['query'] : NULL ) );

					if( (string) $page == (string) \IPS\Request::i()->url() )
					{
						\IPS\Output::i()->error( 'content_err_page_404', '2T187/3', 404, '' );
					}

					\IPS\Output::i()->redirect( $page, NULL, 301 );
				}
				catch( \OutOfRangeException $e )
				{
					\IPS\Output::i()->error( 'content_err_page_404', '2T187/2', 404, '' );
				}
			}
		}
		else
		{
            try
            {
                $page = \IPS\frontpage\Pages\Page::getDefaultPage();
            }
            catch ( \OutOfRangeException $e )
            {
                \IPS\Output::i()->error( 'content_err_page_404', '2T257/1', 404, '' );
            }
		}
		
		if ( $page === NULL )
		{
            \IPS\Output::i()->error( 'content_err_page_404', '2T257/2', 404, '' );
		}

		if ( ! $page->can('view') )
		{
			\IPS\Output::i()->error( 'content_err_page_403', '2T187/3', 403, '' );
		}
		
		/* Set the current page, so other blocks, DBs, etc don't have to figure out where they are */
		\IPS\frontpage\Pages\Page::$currentPage = $page;
		
		return $page;
	}
	
	/**
	 * Capture database specific things
	 *
	 * @param	string	$method	Desired method
	 * @param	array	$args	Arguments
	 * @return	void
	 */
	public function __call( $method, $args )
	{
		$page = $this->getPage();
		$page->setTheme();
		$databaseId = ( isset( \IPS\Request::i()->d ) ) ? \IPS\Request::i()->d : $page->getDatabase()->_id;

		if ( $databaseId !== NULL )
		{
			try
			{
				if ( \IPS\Request::i()->isAjax() )
				{
					return \IPS\frontpage\Databases\Dispatcher::i()->setDatabase( $databaseId )->run();
				}
				else
				{
					$page->output();
				}
			}
			catch( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'content_err_page_404', '2T257/3', 404, '' );
			}
		}
	}

	/**
	 * Embed
	 *
	 * @return	void
	 */
	protected function embed()
	{
		return $this->__call( 'embed', \func_get_args() );
	}
}