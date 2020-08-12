<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief		CMS Widgets
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

namespace IPS\frontpage;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * CMS Widgets
 */
class _Widget extends \IPS\Widget
{
	/**
	 * Fetch the configuration for this unqiue ID. Looks in active tables and trash. When a widget is moved, saveOrder is called twice,
	 * once to remove the widget from column A and again to add it to column B. We store the widget removed from column A into the trash
	 * table.
	 *
	 * @param   string  $uniqueId   Widget's unique ID
	 * @return  array
	 */
	public static function getConfiguration( $uniqueId )
	{
		foreach( \IPS\Db::i()->select( '*', 'frontpage_fpage_widget_areas' ) as $item )
		{
			$widgets = json_decode( $item['area_widgets'], TRUE );

			if( \is_array( $widgets ) )
			{
				foreach( $widgets as $widget )
				{
					if ( $widget['unique'] == $uniqueId )
					{
						if ( isset( $widget['configuration'] ) )
						{
							return $widget['configuration'];
						}
					}
				}
			}
		}

		/* Still here? rummage in the trash */
		return parent::getConfiguration( $uniqueId );
	}

	/**
	 * Delete caches. We need a different name from the parent class otherwise the Fpages app hook will get stuck in infinite recursion
	 *
	 * @param	String	$key				Widget key
	 * @param	String	$app				Parent application
	 * @param	String	$plugin				Parent plugin
	 * @return	void
	 */
	static public function deleteCachesForBlocks( $key=NULL, $app=NULL, $plugin=NULL )
	{
		/* Delete any custom block caches relevant to this plug in */
		if ( $key OR $app )
		{
			$where = array( array( 'block_type=?', 'plugin' ) );

			if( $key )
			{
				$where[] = array( 'block_key=?', (string) $key );
			}

			if( $app )
			{
				$where[] = array( 'block_plugin_app=?', (string) $app );
			}

			$blocks = array();
			foreach( \IPS\Db::i()->select( '*', 'frontpage_blocks', $where ) as $row )
			{
				$blocks[ $row['block_key'] ] = $row;
			}

			if ( \count( $blocks ) )
			{
				$uniqueIds = array();
				foreach( \IPS\Db::i()->select( '*', 'frontpage_fpage_widget_areas' ) as $item )
				{
					$widgets = json_decode( $item['area_widgets'], TRUE );

					foreach( $widgets as $widget )
					{
						if ( $widget['app'] === 'frontpage' and $widget['key'] === 'Blocks' and isset( $widget['unique'] ) and isset( $widget['configuration'] ) and isset( $widget['configuration']['frontpage_widget_custom_block'] ) )
						{
							if ( \in_array( $widget['configuration']['frontpage_widget_custom_block'], array_keys( $blocks ) ) )
							{
								$uniqueIds[] = $widget['unique'];
							}
						}
					}
				}

				foreach( \IPS\Db::i()->select( '*', 'core_widget_areas' ) as $item )
				{
					$widgets = json_decode( $item['widgets'], TRUE );

					foreach( $widgets as $widget )
					{
						if ( $widget['app'] === 'frontpage' and $widget['key'] === 'Blocks' and isset( $widget['unique'] ) and isset( $widget['configuration'] ) and isset( $widget['configuration']['frontpage_widget_custom_block'] ) )
						{
							if ( \in_array( $widget['configuration']['frontpage_widget_custom_block'], array_keys( $blocks ) ) )
							{
								$uniqueIds[] = $widget['unique'];
							}
						}
					}
				}

				if ( \count( $uniqueIds ) )
				{
					$widgetRow = \IPS\Db::i()->select( '*', 'core_widgets', array( '`key`=? and app=?', 'Blocks', 'frontpage' ) )->first();

					if ( ! empty( $widgetRow['caches'] ) )
					{
						$caches = json_decode( $widgetRow['caches'], TRUE );

						if ( \is_array( $caches ) )
						{
							$save  = $caches;
							foreach( $caches as $key => $time )
							{
								foreach( $uniqueIds as $id )
								{
									if ( mb_stristr( $key, 'widget_Blocks_' . $id ) )
									{
										if ( isset( \IPS\Data\Store::i()->$key ) )
										{
											unset( \IPS\Data\Store::i()->$key );
										}

										unset( $save[ $key ] );
									}
								}
							}

							if ( \count( $save ) !== \count( $caches ) )
							{
								\IPS\Db::i()->update( 'core_widgets', array( 'caches' => ( \count( $save ) ? json_encode( $save ) : NULL ) ), array( 'id=?', $widgetRow['id'] ) );
								unset( \IPS\Data\Store::i()->widgets );
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * Return unique IDs in use
	 *
	 * @return array
	 */
	public static function getUniqueIds()
	{
		$uniqueIds = parent::getUniqueIds();
		foreach ( \IPS\Db::i()->select( '*', 'frontpage_fpage_widget_areas' ) as $row )
		{
			$data = json_decode( $row['area_widgets'], TRUE );
			
			if ( \count( $data ) )
			{
				foreach( $data as $widget )
				{
					if ( isset( $widget['unique'] ) )
					{ 
						$uniqueIds[] = $widget['unique'];
					}
				}
			}
		}
		
		return $uniqueIds;
	}
}
