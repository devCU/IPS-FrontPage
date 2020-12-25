<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                            https://www.devcu.com/donate
 *
 * @brief		Database Dispatcher
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

namespace IPS\frontpage\Databases;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Database Dispatcher
 */
class _Dispatcher extends \IPS\Dispatcher
{
	/**
	 * @brief	Singleton Instance (So we don't re-use the regular dispatcher)
	 */
	protected static $instance = NULL;
	
	/**
	 * @brief	Controller location
	 */
	public $controllerLocation = 'front';
	
	/**
	 * @brief	Database Id
	 */
	public $databaseId = NULL;
	
	/**
	 * @brief	Category Id
	 */
	public $categoryId = NULL;

	/**
	 * @brief	Record Id
	 */
	public $recordId = NULL;

	/**
	 * @brief	Url
	 */
	public $url = NULL;
	
	/**
	 * @brief	Module
	 */
	public $module = NULL;
	
	/**
	 * @brief	Output to return
	 */
	public $output = "";
	
	/**
	 * Set Database ID
	 *
	 * @param	mixed	$databaseId		Database key or ID
	 * @return	\IPS\Dispatcher
	 */
	public function setDatabase( $databaseId )
	{
		/* Other areas rely on $this->databaseId being numeric */
		if ( !\is_numeric( $databaseId ) )
		{
			$database   = \IPS\frontpage\Databases::load( $databaseId, 'database_key' );
			$databaseId = $database->id;
		}

		$this->databaseId = $databaseId;

		$database   = \IPS\frontpage\Databases::load( $databaseId );
		if ( ! $database->use_categories )
		{
			$this->categoryId = $database->_default_category;
		}

		return $this;
	}
	
	/**
	 * Set Category ID
	 *
	 * @param	mixed	$categoryId		Category ID
	 * @return	\IPS\Dispatcher
	 */
	public function setCategory( $categoryId )
	{
		$this->categoryId = $categoryId;
		return $this;
	}
	
	/**
	 * Init
	 *
	 * @return void
	 */
	public function init()
	{
		if ( ( \IPS\frontpage\Fpages\Fpage::$currentFpage AND ! ( \IPS\Application::load('frontpage')->default AND ! \IPS\frontpage\Fpages\Fpage::$currentFpage->folder_id AND \IPS\frontpage\Fpages\Fpage::$currentFpage->default ) ) )
		{
			\IPS\Output::i()->breadcrumb['module'] = array( \IPS\frontpage\Fpages\Fpage::$currentFpage->url(), \IPS\frontpage\Fpages\Fpage::$currentFpage->_title );
		}
	}

	/**
	 * Run
	 *
	 * @return void
	 */
	public function run()
	{
		/* Coming from a widget? */
		if ( isset( \IPS\Request::i()->fpageID ) and isset( \IPS\Request::i()->blockID ) )
		{
			if ( \IPS\frontpage\Fpages\Fpage::$currentFpage === NULL )
			{
				/* make sure this is a valid widgetized fpage to stop tampering */
				try
				{
					foreach ( \IPS\Db::i()->select( '*', 'frontpage_fpage_widget_areas', array( 'area_fpage_id=?', \IPS\Request::i()->fpageID ) ) as $item )
					{
						foreach( json_decode( $item['area_widgets'], TRUE ) as $block )
						{
							if ( $block['key'] === 'Database' and isset( $block['configuration']['database'] ) and \intval( $block['configuration']['database'] ) === $this->databaseId )
							{
								\IPS\frontpage\Fpages\Fpage::$currentFpage = \IPS\frontpage\Fpages\Fpage::load( \IPS\Request::i()->fpageID );
							}
						}
					}
				}
				catch( \UnderflowException $e ) { }
			}

			/* Try again */
			if ( \IPS\frontpage\Fpages\Fpage::$currentFpage === NULL )
			{
				\IPS\Output::i()->error( 'fpage_doesnt_exist', '2T251/1', 404 );
			}

			/* Unset do query param otherwise it confuses the controller->execute(); */
			\IPS\Request::i()->do = NULL;
		}

		$url = 'app=frontpage&module=fpages&controller=fpage&path=' . \IPS\frontpage\Fpages\Fpage::$currentFpage->full_path;

		try
		{
			$database = \IPS\frontpage\Databases::load( $this->databaseId );
		}
		catch( \OutOfRangeException $ex )
		{
			\IPS\Output::i()->error( 'fpage_doesnt_exist', '2T251/2', 404 );
		}

		$path = trim(  preg_replace( '#' . \IPS\frontpage\Fpages\Fpage::$currentFpage->full_path . '#', '', \IPS\Request::i()->path, 1 ), '/' );

		/* If we visited the default fpage in a folder, the full_path will be like folder/fpage but the request path will just be folder */
		if( \IPS\Request::i()->path . '/' . \IPS\frontpage\Fpages\Fpage::$currentFpage->seo_name == \IPS\frontpage\Fpages\Fpage::$currentFpage->full_path )
		{
			$path = '';
		}
	
		list( $path, $fpageNumber ) = \IPS\frontpage\Fpages\Fpage::getStrippedFpagePath( $path );
		
		$this->databaseId = $database->id;

		if ( ! $database->use_categories )
		{
			$this->categoryId = $database->default_category;
		}

		/* Got a specific category ID? */
		if ( $this->categoryId !== NULL and ! $path and ( ( $database->use_categories and $database->cat_index_type !== 1 ) OR ( ! $database->use_categories and isset( \IPS\Request::i()->do ) ) ) )
		{
			$this->module = 'category';
		}
		else if ( isset( \IPS\Request::i()->c ) AND \is_numeric( \IPS\Request::i()->c ) )
		{
			$this->categoryId = \IPS\Request::i()->c;
			$this->module = 'category';
		}
		else if ( empty( $path ) )
		{
			$this->module = 'index';
		}
		else
		{
			$url .= '/' . $path;
			$recordClass = '\IPS\frontpage\Records' . $database->id;
			$isLegacyCategoryUrl = FALSE;
			
			if ( $database->use_categories )
			{
				$catClass = '\IPS\frontpage\Categories' . $database->id;
				$category = $catClass::loadFromPath( $path, $database->id );
				
				/* /_/ was used in IP.Board 3.x to denote the articles database */
				if ( $category === NULL AND mb_substr( $path, 0, 2 ) === '_/' )
				{
					$category = $catClass::loadFromPath( mb_substr( $path, 2 ), $database->id );
					
					/* We may have a record URL still, so set a flag to handle this later if we never find anything */
					if ( $category !== NULL )
					{
						$isLegacyCategoryUrl = TRUE;
					}
				}

				if ( $category === NULL )
				{
					
					/* Is this a record? */
					$bits = explode( '/', $path );
					$slug = array_pop( $bits );

					try
					{
						$record = $recordClass::loadFromSlug( $slug );
						
						$this->_redirectToCorrectUrl( $record->url() );
					}
					catch ( \OutOfRangeException $ex )
					{
						/* Check slug history */
						try
						{
							$record = $recordClass::loadFromSlugHistory( $slug );

							$this->_redirectToCorrectUrl( $record->url() );
						}
						catch ( \OutOfRangeException $ex )
						{
							\IPS\Output::i()->error( 'fpage_doesnt_exist', '2T251/4', 404 );
						}
					}
				}

				$whatsLeft = preg_replace( '#' . $category->full_path . '#', '', $path, 1 );

				$this->categoryId = $category->id;
			}
			else
			{
				$whatsLeft = $path;
			}

			if ( $whatsLeft )
			{
				/* Find the record */
				try
				{
					$record = $recordClass::loadFromSlug( $whatsLeft, TRUE, $this->categoryId );

					/* Make the Content controller all kinds of happy */
					\IPS\Request::i()->id = $this->recordId = $record->primary_id_field;
				}
				catch( \OutOfRangeException $ex )
				{
					/* Check slug history */
					try
					{
						$record = $recordClass::loadFromSlugHistory( $whatsLeft );

						$this->_redirectToCorrectUrl( $record->url() );
					}
					catch( \OutOfRangeException $ex )
					{
						/* We are absolutely certain this is not a record, but we have found a legacy category - redirect */
						if ( $isLegacyCategoryUrl === TRUE )
						{
							\IPS\Output::i()->redirect( $category->url() );
						}
						
						\IPS\Output::i()->error( 'fpage_doesnt_exist', '2T251/5', 404 );
					}
				}
				
				/* Make sure the URL is correct, for instance, it could have moved categories */
				if ( $database->use_categories AND $this->categoryId != $record->category_id )
				{
					$this->_redirectToCorrectUrl( $record->url() );
				}
				
				$this->module = 'record';
			}
			else
			{
				/* It's a category listing */
				$this->module = 'category';
			}
		}
		
		$this->url = \IPS\Http\Url::internal( $url, 'front', 'content_fpage_path' );
		$className = '\\IPS\\frontpage\\modules\\front\\database\\' . $this->module;

		/* Init class */
		if( !class_exists( $className ) )
		{
			\IPS\Output::i()->error( 'fpage_doesnt_exist', '2T251/6', 404 );
		}
		$controller = new $className;
		if( !( $controller instanceof \IPS\Dispatcher\Controller ) )
		{
			\IPS\Output::i()->error( 'fpage_not_found', '3T251/7', 500, '' );
		}

		\IPS\Dispatcher::i()->dispatcherController	= $controller;
		
		\IPS\Output::i()->defaultSearchOption = array( "frontpage_records{$this->databaseId}", "frontpage_records{$this->databaseId}_pl" );

		/* Add database key to body classes for easier database specific themeing */
		\IPS\Output::i()->bodyClasses[] = 'cFontpageDatabase_' . $database->key;
		
		/* Execute */
		$controller->execute();
		
		return $this->finish();
	}
	
	/**
	 * Redirect to the "correct" URL (for example if the category slug is incorrect)
	 * while retaining any query string parameters in the request URL
	 * For example, an embed to a record might be example.com/records/cat-1/record/?do=embed
	 * If the record is moved so the URL is cat-2, the embed needs to redirect while retaining
	 * the /?do=embed
	 *
	 * @param	\IPS\Http\Url	$correctUrl		The URL for the record
	 * @return	void
	 */
	protected function _redirectToCorrectUrl( $correctUrl )
	{
		$paramsToSet = array();
		foreach ( \IPS\Request::i()->url()->queryString as $k => $v )
		{
			if ( !array_key_exists( $k, $correctUrl->queryString ) and !array_key_exists( $k, $correctUrl->hiddenQueryString ) )
			{
				$paramsToSet[ $k ] = $v;
			}
		}
		if ( \count( $paramsToSet ) )
		{
			$correctUrl = $correctUrl->setQueryString( $paramsToSet );
		}
		
		\IPS\Output::i()->redirect( $correctUrl, NULL, 301 );
	}
	
	/**
	 * Finish
	 *
	 * @return	void
	 */
	public function finish()
	{
		return (string) $this->output ?: \IPS\Output::i()->output;
	}
}