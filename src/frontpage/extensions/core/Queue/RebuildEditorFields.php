<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		Background Task: Rebuild database editor fields
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

namespace IPS\cms\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild database editor fields
 */
class _RebuildEditorFields
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public $rebuild	= \IPS\REBUILD_SLOW;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array
	 */
	public function preQueueData( $data )
	{
		$classname  = $data['class'];
		$databaseId = mb_substr( $classname, 15 );
		$fieldId    = $data['fieldId'];

		try
		{
			$data['count'] = (int) \IPS\Db::i()->select( 'MAX(primary_id_field)', 'cms_custom_database_' . $databaseId )->first();
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
		$databaseId = mb_substr( $classname, 15 );
		$fieldId    = $data['fieldId'];

		$parsed	= 0;
		$class  = '\IPS\cms\Records' . $databaseId;
		$last   = NULL;
		
		if ( \IPS\Db::i()->checkForTable( 'cms_custom_database_' . $databaseId ) AND \IPS\Db::i()->checkForColumn( 'cms_custom_database_' . $databaseId, 'field_' . $fieldId ) )
		{
			foreach ( \IPS\Db::i()->select( '*', 'cms_custom_database_' . $databaseId, array( 'primary_id_field > ?', $offset ), 'primary_id_field asc', array( 0, $this->rebuild ) ) as $row )
			{
				$item = $class::constructFromData( $row );
				$contentColumn = 'field_' . $fieldId;
				
				$member     = \IPS\Member::load( $item->mapped('author') );
				$extensions = \IPS\Application::load( $classname::$application )->extensions( 'core', 'EditorLocations' );
				$idColumn   = $classname::$databaseColumnId;
				
				if( isset( $classname::$itemClass ) )
				{
					$itemClass	= $classname::$itemClass;
					$module		= mb_ucfirst( $itemClass::$module );
				}
				else
				{
					$module     = mb_ucfirst( $classname::$module );
				}
				
				$extension  = NULL;
				
				if ( isset( $extensions[ $module ] ) )
				{
					$extension = $extensions[ $module ];
				}
				
				$canUseHtml = (bool) $member->group['g_dohtml'];
				
				if ( $extension )
				{
					$extensionCanUseHtml = $extension->canUseHtml( $member );
					if ( $extensionCanUseHtml !== NULL )
					{
						$canUseHtml = $extensionCanUseHtml;
					}
				}
			
				try
				{
					$item->$contentColumn	= \IPS\Text\LegacyParser::parseStatic( $item->$contentColumn, $member, $canUseHtml, 'cms_Records', $item->$idColumn, $data['fieldId'], $databaseId, isset( $classname::$itemClass ) ? $classname::$itemClass : \get_class( $item ) );
				}
				catch( \InvalidArgumentException $e )
				{
					if( $e->getcode() == 103014 )
					{
						$item->$contentColumn	= preg_replace( "#\[/?([^\]]+?)\]#", '', $item->$contentColumn );
					}
					else
					{
						throw $e;
					}
				}
				
				$item->save();
			
				$last = $item->$idColumn;
			}
		}

		if( $last === NULL )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		return $last;
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
		$databaseId = mb_substr( $classname, 15 );
				
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('rebuilding_cms_database_records', FALSE, array( 'sprintf' => array( \IPS\cms\Databases::load( $databaseId )->_title ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}	
}