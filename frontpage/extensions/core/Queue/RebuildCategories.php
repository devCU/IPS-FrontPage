<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief		Background Task: Rebuild database categories
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

namespace IPS\frontpage\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild database categories
 */
class _RebuildCategories
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public $rebuild	= \IPS\REBUILD_NORMAL;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array
	 */
	public function preQueueData( $data )
	{
		$classname  = $data['class'];
		$databaseId = mb_substr( $classname, 18 );
		
		\IPS\Log::debug( "Getting preQueueData for " . $classname, 'rebuildCategories' );

		try
		{
			$data['count'] = (int) \IPS\Db::i()->select( 'COUNT(*)', 'frontpage_database_categories' )->first();
		}
		catch( \Exception $ex )
		{
			throw new \OutOfRangeException;
		}
		
		if( $data['count'] == 0 )
		{
			return null;
		}
		
		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( $data, $offset )
	{
		$classname  = $data['class'];
		$databaseId = mb_substr( $classname, 18 );
		
		$class  = '\IPS\frontpage\Categories' . $databaseId;
		$parsed	= 0;
		
		foreach ( \IPS\Db::i()->select( '*', 'frontpage_database_categories', array( 'category_database_id=?', $databaseId ), 'category_id asc', array( $offset, $this->rebuild ) ) as $row )
		{
			try
			{
				$cat = $class::constructFromData( $row );
				$cat->setLastComment();
				$cat->setLastReview();
				$cat->save();
				
				$parsed++;
			}
			catch( \Exception $e ){}
		}

		if( $parsed !== $this->rebuild )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		return ( $offset + $this->rebuild );
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( $data, $offset )
	{
		$classname  = $data['class'];
		$databaseId = mb_substr( $classname, 18 );
		
		$title = ( \IPS\Application::appIsEnabled('frontpage') ) ? \IPS\frontpage\Databases::load( $databaseId )->_title : 'Database #' . $databaseId;
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack( 'rebuilding_frontpage_database_categories', FALSE, array( 'sprintf' => array( $title ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}	
}