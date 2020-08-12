<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief		Support Fpages in sitemaps
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

namespace IPS\frontpage\extensions\core\Sitemap;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Support Fpages in sitemaps
 */
class _Fpages
{
	/**
	 * @brief	Recommended Settings
	 */
	public $recommendedSettings = array(
		'sitemap_pages_include'		=> true,
		'sitemap_pages_count'		=> -1,
		'sitemap_pages_priority'	=> 1
	);
	
	/**
	 * Settings for ACP configuration to the form
	 *
	 * @return	array
	 */
	public function settings()
	{
		return array(
			'sitemap_fpages_include'	=> new \IPS\Helpers\Form\YesNo( "sitemap_fpages_include", \IPS\Settings::i()->sitemap_fpages_count != 0, FALSE, array( 'togglesOn' => array( "sitemap_fpages_count", "sitemap_fpages_priority" ) ), NULL, NULL, NULL, "sitemap_fpages_include" ),
			'sitemap_fpages_count'	 => new \IPS\Helpers\Form\Number( 'sitemap_fpages_count', \IPS\Settings::i()->sitemap_fpages_count, FALSE, array( 'min' => '-1', 'unlimited' => '-1' ), NULL, NULL, NULL, 'sitemap_fpages_count' ),
			'sitemap_fpages_priority' => new \IPS\Helpers\Form\Select( 'sitemap_fpages_priority', \IPS\Settings::i()->sitemap_fpages_priority, FALSE, array( 'options' => \IPS\Sitemap::$priorities, 'unlimited' => '-1', 'unlimitedLang' => 'sitemap_dont_include' ), NULL, NULL, NULL, 'sitemap_fpages_priority' )
		);
	}

	/**
	 * Save settings for ACP configuration
	 *
	 * @param	array	$values	Values
	 * @return	void
	 */
	public function saveSettings( $values )
	{
		if ( $values['sitemap_configuration_info'] )
		{
			\IPS\Settings::i()->changeValues( array( 'sitemap_fpages_count' => $this->recommendedSettings['sitemap_fpages_count'], 'sitemap_fpages_priority' => $this->recommendedSettings['sitemap_fpages_priority'] ) );
		}
		else
		{
				\IPS\Settings::i()->changeValues( array( 'sitemap_fpages_count' => $values['sitemap_fpages_include'] ? $values['sitemap_fpages_count'] : 0, 'sitemap_fpages_priority' => $values['sitemap_fpages_priority'] ) );
		}
	}
	
	/**
	 * Get the sitemap filename(s)
	 *
	 * @return	array
	 */
	public function getFilenames()
	{
		/* Are we even including? */
		if( \IPS\Settings::i()->sitemap_fpages_count == 0 )
		{
			return array();
		}

		$files  = array();
		$class  = '\IPS\frontpage\Fpages\Fpage';
		$count  = 0;
		$member = new \IPS\Member;
		$permissionCheck = 'view';
		
		$where = array( array( '(' . \IPS\Db::i()->findInSet( 'perm_' . $class::$permissionMap[ $permissionCheck ], $member->groups ) . ' OR ' . 'perm_' . $class::$permissionMap[ $permissionCheck ] . '=? )', '*' ) );
			
		$count = \IPS\Db::i()->select( '*', $class::$databaseTable )
				->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . $class::$databaseTable . "." . $class::$databasePrefix . $class::$databaseColumnId, $class::$permApp, $class::$permType ) )
				->count();
				
		$count = ceil( max( $count, \IPS\Settings::i()->sitemap_fpages_count ) / \IPS\Sitemap::MAX_PER_FILE );
		
		for( $i=1; $i <= $count; $i++ )
		{
			$files[] = 'sitemap_fpages_' . $i;
		}

		return $files;
	}

	/**
	 * Generate the sitemap
	 *
	 * @param	string			$filename	The sitemap file to build (should be one returned from getFilenames())
	 * @param	\IPS\Sitemap	$sitemap	Sitemap object reference
	 * @return	void
	 */
	public function generateSitemap( $filename, $sitemap )
	{
		/* We have elected to not add databases to the sitemap */
		if ( ! \IPS\Settings::i()->sitemap_fpages_count )
		{
			return NULL;
		}
		
		$class  = '\IPS\frontpage\Fpages\Fpage';
		$count  = 0;
		$member = new \IPS\Member;
		$permissionCheck = 'view';
		$entries = array();
		
		$exploded = explode( '_', $filename );
		$block = (int) array_pop( $exploded );
			
		$offset = ( $block - 1 ) * \IPS\Sitemap::MAX_PER_FILE;
		$limit = \IPS\Sitemap::MAX_PER_FILE;
		
		$totalLimit = \IPS\Settings::i()->sitemap_fpages_count;
		if ( $totalLimit > -1 and ( $offset + $limit ) > $totalLimit )
		{
			if ( $totalLimit < $limit )
			{
				$limit = $totalLimit;
			}
			else
			{
				$limit = $totalLimit - $offset;
			}
		}
			
		$where = array( array( '(' . \IPS\Db::i()->findInSet( 'perm_' . $class::$permissionMap[ $permissionCheck ], $member->groups ) . ' OR ' . 'perm_' . $class::$permissionMap[ $permissionCheck ] . '=? )', '*' ) );
			
		$select = \IPS\Db::i()->select( '*', $class::$databaseTable, $where, 'fpage_id ASC', array( $offset, $limit ) )
				->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . $class::$databaseTable . "." . $class::$databasePrefix . $class::$databaseColumnId, $class::$permApp, $class::$permType ) );

		foreach( $select as $row )
		{
			$item = $class::constructFromData( $row );
			
			$data = array( 'url' => $item->url() );				
			$priority = \intval( \IPS\Settings::i()->sitemap_fpages_priority );
			if ( $priority !== -1 )
			{
				$data['priority'] = $priority;
			}

			$entries[] = $data;
		}

		$sitemap->buildSitemapFile( $filename, $entries );
	}

}