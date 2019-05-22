<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		Background Task: Rebuild database records
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

namespace IPS\frontpage\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild database records
 */
class _RebuildRecords
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
		
		\IPS\Log::debug( "Getting preQueueData for " . $classname, 'rebuildRecords' );

		try
		{
			$data['count'] = (int) \IPS\Db::i()->select( 'COUNT(*)', 'frontpage_custom_database_' . $databaseId )->first();
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

		/* Make sure there's even content to parse */
		if( !class_exists( $classname ) or !isset( $classname::$databaseColumnMap['content'] ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		$fixImage = isset( $data['fixImage'] ) ? (boolean) $data['fixImage'] : TRUE;
		$fixHtml  = isset( $data['fixHtml'] ) ? (boolean) $data['fixHtml'] : FALSE;
		$fixFurls = isset( $data['fixFurls']) ? (boolean) $data['fixFurls'] : FALSE;
		$parsed	= 0;
		$class  = '\IPS\frontpage\Records' . $databaseId;
		
		if ( \IPS\Db::i()->checkForTable( 'frontpage_custom_database_' . $databaseId ) )
		{
			foreach ( \IPS\Db::i()->select( '*', 'frontpage_custom_database_' . $databaseId, NULL, 'primary_id_field asc', array( $offset, $this->rebuild ) ) as $row )
			{
				$record = $class::constructFromData( $row );
				$record->resetLastComment();
				$save = FALSE;
				
				if ( $fixImage )
				{
					if ( $record->record_image and file_exists( \IPS\ROOT_PATH . '/uploads/' . $record->record_image ) )
					{
						try
						{
							$record->record_image = (string) \IPS\File::create( 'frontpage_Records', $record->record_image, file_get_contents( \IPS\ROOT_PATH . '/uploads/' . $record->record_image ) );
						}
						catch ( \Exception $e )
						{
							$record->record_image = NULL;
						}
						$save = TRUE;
					}
				}
				
				if ( ! $record->record_publish_date )
				{
					$record->record_publish_date = $record->record_saved;
					$save = TRUE;
				}
				
				if ( $fixFurls and preg_match( '#%([\d\w]{2})#', $record->record_static_furl ) )
				{
					$record->record_static_furl = urldecode( $record->record_static_furl );
					$save = TRUE;
				}
				
				if ( $save )
				{
					$record->save();
				}
				
				if ( $fixHtml )
				{
					$fields = iterator_to_array( \IPS\Db::i()->select( '*', 'frontpage_database_fields', array( 'field_database_id=? AND field_type=? AND field_html=1', $databaseId, 'Editor' ) ) );
					
					if ( \count( $fields ) )
					{
						foreach( $fields as $field )
						{
							$column = 'field_' . $field['field_id'];
							
							if ( $record->member_id and $record->$column )
							{
								try
								{
									$author = \IPS\Member::load( $record->member_id );
									
									/* In 3.x this would have been shown as HTML */
									if ( $author->group['g_dohtml'] )
									{
										/* This code is copied from IPB3 to ensure it is compatible with data saved */
										$record->$column = str_replace( "&#39;" , "'", $record->$column );
										$record->$column = str_replace( "&#33;" , "!", $record->$column );
										$record->$column = str_replace( "&#036;", "$", $record->$column );
										$record->$column = str_replace( "&#124;", "|", $record->$column );
										$record->$column = str_replace( "&amp;" , "&", $record->$column );
										$record->$column = str_replace( "&gt;"	 , ">", $record->$column );
										$record->$column = str_replace( "&lt;"	 , "<", $record->$column );
										$record->$column = str_replace( "&#60;" , "<", $record->$column );
										$record->$column = str_replace( "&#62;" , ">", $record->$column );
										$record->$column = str_replace( "&quot;", '"', $record->$column );
										$record->$column = str_replace( '&quot;', '"', $record->$column );
										$record->$column = str_replace( '&lt;', '<', $record->$column );
										$record->$column = str_replace( '&gt;', '>', $record->$column );
										
										$record->save();
									}
								}
								catch( \OutOfRangeException $e ) { }
							}		
						}
					}
				}

				$parsed++;
			}
		}
		
		if( $parsed != $this->rebuild )
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
		$databaseId = mb_substr( $classname, 15 );
		
		$title = ( \IPS\Application::appIsEnabled('frontpage') ) ? \IPS\frontpage\Databases::load( $databaseId )->_title : 'Database #' . $databaseId;
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('rebuilding_frontpage_database_records', FALSE, array( 'sprintf' => array( $title ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}	
}