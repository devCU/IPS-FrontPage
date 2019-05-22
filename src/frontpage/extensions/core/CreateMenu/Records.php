<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		Create Menu Extension : Records
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

namespace IPS\frontpage\extensions\core\CreateMenu;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Create Menu Extension: Records
 */
class _Records
{
	/**
	 * Get Items
	 *
	 * @return	array
	 */
	public function getItems()
	{
		$items = array();
		
		foreach( \IPS\frontpage\Databases::databases() as $database )
		{
			$theOnlyCategory = NULL;
			if ( $database->fpage_id > 0 and $database->can('view') and $database->can('add') )
			{
				$catClass = '\IPS\frontpage\Categories' . $database->id;
				if ( $catClass::canOnAny('add') )
				{
					try
					{
						$fpage = \IPS\frontpage\Fpages\Fpage::load( $database->fpage_id );

						if( $database->use_categories AND $theOnlyCategory = $catClass::theOnlyNode() )
						{
							$items[ 'frontpage_create_menu_records_' . $database->id ] = array(
								'link' 		=> $theOnlyCategory->url()->setQueryString( array( 'do' => 'form', 'd' => $database->id ) )
							);
							continue;
						}

						$items[ 'frontpage_create_menu_records_' . $database->id ] = array(
							'link' 			=> $fpage->url()->setQueryString( array( 'do' => 'form', 'd' => $database->id ) ),
							'extraData'		=> ( $database->use_categories ) ? array( 'data-ipsDialog' => true, 'data-ipsDialog-size' => "narrow" ) : array(),
							'title' 		=> 'frontpage_select_category'
						);
						
					}
					catch( \OutOfRangeException $ex ) { }
				}
			}
		}
		
		ksort( $items );
		
		return $items;
	}
}