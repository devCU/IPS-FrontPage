<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief		Editor Extension: Record Form
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4.10 FINAL
 * @subpackage	FrontPage
 * @version     1.0.5 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     12 AUG 2020
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

namespace IPS\frontpage\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Extension: Record Content
 */
class _Widgets
{
	/**
	 * Can we use HTML in this editor?
	 *
	 * @param	\IPS\Member	$member	The member
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	public function canUseHtml( $member )
	{
		return NULL;
	}
	
	/**
	 * Can we use attachments in this editor?
	 *
	 * @param	\IPS\Member	$member	The member
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	public function canAttach( $member )
	{
		return TRUE;
	}

	/**
	 * Permission check for attachments
	 *
	 * @param	\IPS\Member	$member		The member
	 * @param	int|null	$id1		Primary ID
	 * @param	int|null	$id2		Secondary ID
	 * @param	string|null	$id3		Arbitrary data
	 * @param	array		$attachment	The attachment data
	 * @param	bool		$viewOnly	If true, just check if the user can see the attachment rather than download it
	 * @return	bool
	 */
	public function attachmentPermissionCheck( $member, $id1, $id2, $id3, $attachment, $viewOnly=FALSE )
	{
		if ( ! $id3 )
		{
			throw new \OutOfRangeException;
		}
		
		/* See if it's on a fpage in Fpages */
		$fpageId = $this->getFpageIdFromWidgetUniqueId( $id3 );
		
		if ( $fpageId !== NULL )
		{
			return \IPS\frontpage\Fpages\Fpage::load( $fpageId )->can( 'view', $member );
		}
		
		/* Still here? Look elsewhere */
		$area = $this->getAreaFromWidgetUniqueId( $id3 );
		
		if ( $area !== NULL )
		{
			return \IPS\Application\Module::get( $area[0], $area[1], 'front' )->can( 'view', $member );
		}
		
		/* Still here? */
		throw new \OutOfRangeException;
	}
	
	/**
	 * Attachment lookup
	 *
	 * @param	int|null	$id1	Primary ID
	 * @param	int|null	$id2	Secondary ID
	 * @param	string|null	$id3	Arbitrary data
	 * @return	\IPS\Http\Url|\IPS\Content|\IPS\Node\Model
	 * @throws	\LogicException
	 */
	public function attachmentLookup( $id1, $id2, $id3 )
	{
		$fpageId = $this->getFpageIdFromWidgetUniqueId( $id3 );
		
		if ( $fpageId !== NULL )
		{
			return \IPS\frontpage\Fpages\Fpage::load( $fpageId );
		}
		
		$area = $this->getAreaFromWidgetUniqueId( $id3 );
		
		if ( $area !== NULL )
		{
			return \IPS\Application\Module::get( $area[0], $area[1] );
		}
		
		return FALSE;
	}
	
	/**
	 * Returns the fpage ID based on the widget's unique ID
	 *
	 * @param	string	$uniqueId	The widget's unique ID
	 * @return	null|int
	 */
	protected function getFpageIdFromWidgetUniqueId( $uniqueId )
	{
		$fpageId = NULL;
		foreach( \IPS\Db::i()->select( '*', 'frontpage_fpage_widget_areas' ) as $item )
		{
			$widgets = json_decode( $item['area_widgets'], TRUE );

			foreach( $widgets as $widget )
			{
				if ( $widget['unique'] == $uniqueId )
				{
					$fpageId = $item['area_fpage_id'];
				}
			}
		}
		
		return $fpageId;
	}
	
	/**
	 * Returns area information if the widget is not on a frontpage fpage
	 *
	 * @param	string	$uniqueId	The widget's unique ID
	 * @return	array|null			Index 0 = Application, Index 1 = Module, Index 2 = Controller
	 */
	protected function getAreaFromWidgetUniqueId( $uniqueId )
	{
		$return = NULL;
		foreach( \IPS\Db::i()->select( '*', 'core_widget_areas' ) AS $row )
		{
			$widgets = json_decode( $row['widgets'], TRUE );
			
			foreach( $widgets AS $widget )
			{
				if ( $widget['unique'] == $uniqueId )
				{
					$return = array( $row['app'], $row['module'], $row['controller'] );
				}
			}
		}
		
		return $return;
	}
}