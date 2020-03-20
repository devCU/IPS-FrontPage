<?php
/**
 * @brief		FrontPage Item Model
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4+
 * @subpackage	FrontPage
 * @version     1.0.0 RC
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     22 MAY 2019
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
 * Package Item Model
 */
class _FpageItem extends \IPS\Content\Item implements \IPS\Content\Searchable
{
	/**
	 * @brief	Application
	 */
	public static $application = 'frontpage';
	
	/**
	 * @brief	Module
	 */
	public static $module = 'fpages';
	
	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'frontpage_fpages';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'fpage_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;
			
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(

	);
	
	/**
	 * @brief	Title
	 */
	public static $title = 'frontpage_fpage';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'files-o';
	
	/**
	 * @brief	Include In Sitemap
	 */
	public static $includeInSitemap = FALSE;
	
	/**
	 * @brief	Can this content be moderated normally from the front-end (will be FALSE for things like Fpages and Commerce Products)
	 */
	public static $canBeModeratedFromFrontend = FALSE;
	
	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array
	 */
	public static function basicDataColumns()
	{
		return array( 'fpage_id', 'fpage_folder_id', 'fpage_full_path', 'fpage_default' );
	}
	
	/**
	 * Get URL from index data
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @return	\IPS\Http\Url
	 */
	public static function urlFromIndexData( $indexData, $itemData )
	{
		if ( ( \IPS\Application::load('frontpage')->default OR \IPS\Settings::i()->frontpage_use_different_gateway ) AND $itemData['fpage_default'] AND !$itemData['fpage_folder_id'] )
		{
			/* Are we using the gateway file? */
			if ( \IPS\Settings::i()->frontpage_use_different_gateway )
			{
				/* Yes, work out the proper URL. */
				return \IPS\Http\Url::createFromString( \IPS\Settings::i()->frontpage_root_fpage_url, TRUE );
			}
			else
			{
				/* No - that's easy */
				return \IPS\Http\Url::internal( '', 'front' );
			}
		}
		else
		{
			return \IPS\Http\Url::internal( 'app=frontpage&module=fpages&controller=fpage&path=' . $itemData['fpage_full_path'], 'front', 'content_fpage_path', array( $itemData['fpage_full_path'] ) );
		}
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
		$indexData['index_title'] = \IPS\Member::loggedIn()->language()->addToStack( 'frontpage_fpage_' . $indexData['index_item_id'] );
		return parent::searchResult( $indexData, $authorData, $itemData, $containerData, $reputationData, $reviewRating, $iPostedIn, $view, $asItem, $canIgnoreComments, $template, $reactions );
	}
		
	/**
	 * Title for search index
	 *
	 * @return	string
	 */
	public function searchIndexTitle()
	{
		$titles = array();
		foreach ( \IPS\Lang::languages() as $lang )
		{
			$titles[] = $lang->get("frontpage_fpage_{$this->id}");
		}
		return implode( ' ', $titles );
	}
	
	/**
	 * Content for search index
	 *
	 * @return	string
	 */
	public function searchIndexContent()
	{
		if ( $this->type == 'builder' )
		{
			$content = array();
			foreach( \IPS\Db::i()->select( '*', 'frontpage_fpage_widget_areas', array( 'area_fpage_id=?', $this->id ) ) as $widgetArea )
			{
				foreach ( json_decode( $widgetArea['area_widgets'], TRUE ) as $widget )
				{
					if ( $widget['app'] == 'frontpage' and $widget['key'] == 'Wysiwyg' )
					{
						$content[] = trim( $widget['configuration']['content'] );
					}
				}
			}
			return implode( ' ', $content );
		}
		else
		{
			return $this->content;
		}
	}
	
	/**
	 * Search Index Permissions
	 *
	 * @return	string	Comma-delimited values or '*'
	 * 	@li			Number indicates a group
	 *	@li			Number prepended by "m" indicates a member
	 *	@li			Number prepended by "s" indicates a social group
	 */
	public function searchIndexPermissions()
	{
		try
		{
			return \IPS\Db::i()->select( 'perm_view', 'core_permission_index', array( "app='frontpage' AND perm_type='fpages' AND perm_type_id=?", $this->id ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			return '';
		}
	}

	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	\IPS\Node\Model|NULL	$container	Container
	 * @return	void
	 */
	public static function incrementPostCount( \IPS\Node\Model $container = NULL )
	{
		return FALSE;
	}
}