<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief		Template Plugin - Content: Database
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

namespace IPS\frontpage\extensions\core\OutputPlugins;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin - Content: Database
 */
class _Database
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static $canBeUsedInCss = FALSE;
	
	/**
	 * @brief	Record how many database tags there are per page
	 */
	public static $count = 0;
	
	/**
	 * Run the plug-in
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options    Array of options
	 * @return	string		Code to eval
	 */
	public static function runPlugin( $data, $options )
	{
		if ( isset( $options['category'] ) )
		{
			return '\IPS\frontpage\Databases\Dispatcher::i()->setDatabase( "' . $data . '" )->setCategory( "' . $options['category'] . '" )->run()';
		}
		
		return '\IPS\frontpage\Databases\Dispatcher::i()->setDatabase( "' . $data . '" )->run()';
	}
	
	/**
	 * Do any processing before a fpage is added/saved
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options  Array of options
	 * @param	object		$fpage	  Fpage being edited/saved
	 * @return	void
	 */
	public static function preSaveProcess( $data, $options, $fpage )
	{
		/* Keep a count of databases used so far */
		static::$count++;
		
		if ( static::$count > 1 )
		{
			throw new \LogicException( \IPS\Member::loggedIn()->language()->addToStack('frontpage_err_db_already_on_fpage') );
		}
	}
	
	/**
	 * Do any processing after a fpage is added/saved
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options  Array of options
	 * @param	object		$fpage	  Fpage being edited/saved
	 * @return	void
	 */
	public static function postSaveProcess( $data, $options, $fpage )
	{
		$database = NULL;
		
		try
		{
			if ( \is_numeric( $data ) )
			{
				$database = \IPS\frontpage\Databases::load( $data );
			}
			else
			{
				$database = \IPS\frontpage\Databases::load( $data, 'database_key' );
			}
			
			if ( $database->id AND $fpage->id )
			{
				try
				{
					$fpage->mapToDatabase( $database->id );
				}
				catch( \LogicException $ex )
				{
					throw new \LogicException( $ex->getMessage() );
				}
			}
		}
		catch( \OutofRangeException $ex ) { }
	}

}