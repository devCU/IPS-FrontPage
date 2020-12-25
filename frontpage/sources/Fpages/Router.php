<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                            https://www.devcu.com/donate
 *
 * @brief		FrontPage Router Model
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
class _Router extends \IPS\Patterns\ActiveRecord
{
	/**
	 * Load Fpages Thing based on a URL.
	 * The URL is sometimes complex to figure out, so this will help
	 *
	 * @param	\IPS\Http\Url	$url	URL to load from
	 * @return	\IPS\frontpage\Fpages\Fpage
	 * @throws	\InvalidArgumentException
	 * @throws	\OutOfRangeException
	 */
	public static function loadFromUrl( \IPS\Http\Url $url )
	{
		if ( ! isset( $url->queryString['path'] ) )
		{
			throw new \OutOfRangeException();
		}
		
		$path = $url->queryString['path'];
		
		/* First, we need a fpage */
		$fpage = \IPS\frontpage\Fpages\Fpage::loadFromPath( $path );
		
		/* What do we have left? */
		$whatsLeft = trim( preg_replace( '#' . $fpage->full_path . '#', '', $path, 1 ), '/' );
		
		if ( $whatsLeft )
		{
			/* Check databases */
			$databases = iterator_to_array( \IPS\Db::i()->select( '*', 'frontpage_databases', array( 'database_fpage_id > 0' ) ) );
			foreach( $databases as $db )
			{
				$classToTry = 'IPS\frontpage\Records' . $db['database_id'];
				try
				{
					$record = $classToTry::loadFromSlug( $whatsLeft, FALSE, FALSE );
					
					return $record;
				}
				catch( \Exception $ex ) { }
			}
			
			/* Check categories */
			foreach( $databases as $db )
			{
				$classToTry = 'IPS\frontpage\Categories' . $db['database_id'];
				try
				{
					$category = $classToTry::loadFromPath( $whatsLeft );
					
					if ( $category !== NULL )
					{
						return $category;
					}
				}
				catch( \Exception $ex ) { }
			}
		}
		else
		{
			/* It's a fpage */
			return $fpage;
		}
		
		/* No idea, sorry */
		throw new \InvalidArgumentException;
	}
}