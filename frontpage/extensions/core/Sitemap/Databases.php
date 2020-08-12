<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief		Support Fpages Databases in sitemaps
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
 * Support Fpages Databases in sitemaps
 */
class _Databases
{
	/**
	 * @brief	Recommended Settings
	 */
	public $recommendedSettings = array(
		'sitemap_databases_include'		=> true,
		'sitemap_databases_count'		=> -1,
		'sitemap_databases_priority'	=> 1
	);
	
	/**
	 * Settings for ACP configuration to the form
	 *
	 * @return	array
	 */
	public function settings()
	{
		return array(
			'sitemap_databases_include'	=> new \IPS\Helpers\Form\YesNo( "sitemap_databases_include", \IPS\Settings::i()->sitemap_databases_count != 0, FALSE, array( 'togglesOn' => array( "sitemap_databases_count", "sitemap_databases_priority" ) ), NULL, NULL, NULL, "sitemap_databases_include" ),
			'sitemap_databases_count'	 => new \IPS\Helpers\Form\Number( 'sitemap_databases_count', \IPS\Settings::i()->sitemap_databases_count, FALSE, array( 'min' => '-1', 'unlimited' => '-1' ), NULL, NULL, NULL, 'sitemap_databases_count' ),
			'sitemap_databases_priority' => new \IPS\Helpers\Form\Select( 'sitemap_databases_priority', \IPS\Settings::i()->sitemap_databases_priority, FALSE, array( 'options' => \IPS\Sitemap::$priorities, 'unlimited' => '-1', 'unlimitedLang' => 'sitemap_dont_include' ), NULL, NULL, NULL, 'sitemap_databases_priority' )
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
			\IPS\Settings::i()->changeValues( array( 'sitemap_databases_count' => $this->recommendedSettings['sitemap_databases_count'], 'sitemap_databases_priority' => $this->recommendedSettings['sitemap_databases_priority'] ) );
		}
		else
		{
			\IPS\Settings::i()->changeValues( array( 'sitemap_databases_count' => $values['sitemap_databases_include'] ? $values['sitemap_databases_count'] : 0, 'sitemap_databases_priority' => $values['sitemap_databases_priority'] ) );
		}
	}
	
	/**
	 * Get the sitemap filename(s)
	 *
	 * @return	array
	 */
	public function getFilenames()
	{
		/* Are we including? */
		if ( ! \IPS\Settings::i()->sitemap_databases_count )
		{
			return array();
		}

		$files = array();
		
		/* Check that guests can access the content at all */
		foreach( \IPS\frontpage\Databases::databases() as $database )
		{
			if ( $database->fpage_id > 0 )
			{
				try
				{
					if ( !$database->can( 'view', new \IPS\Member ) )
					{
						throw new \OutOfRangeException;
					}
				}
				catch ( \OutOfRangeException $e )
				{
					continue;
				}

				try
				{
					$fpage = \IPS\frontpage\Fpages\Fpage::load( $database->fpage_id );

					if( !$fpage->can( 'view', new \IPS\Member ) )
					{
						throw new \OutOfRangeException;
					}
				}
				catch ( \OutOfRangeException $e )
				{
					continue;
				}
				
				$class = '\IPS\frontpage\Records' . $database->id;
				
				if ( isset( $class::$containerNodeClass ) )
				{
					$nodeClass = $class::$containerNodeClass;
					
					/* We need one file for the nodes */
					$files[] = $database->id . '_sitemap_database_categories';
				}
				
				/* And however many for the content items */
				$count = ceil( max( (int) $class::getItemsWithPermission( $class::sitemapWhere(), NULL, 10, 'read', \IPS\Content\Hideable::FILTER_PUBLIC_ONLY, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS, new \IPS\Member )->count( TRUE ), \IPS\Settings::i()->sitemap_databases_count ) / \IPS\Sitemap::MAX_PER_FILE );
				for( $i=1; $i <= $count; $i++ )
				{
					$files[] = $database->id . '_sitemap_database_records_' . $i;
				}
			}
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
		if ( ! \IPS\Settings::i()->sitemap_databases_count )
		{
			return NULL;
		}
		
		$tmp = explode( '_', $filename );
		$databaseId = \intval( array_shift( $tmp ) );
		$database   = \IPS\frontpage\Databases::load( $databaseId );
		
		$class = '\IPS\frontpage\Records' . $databaseId;
		if ( isset( $class::$containerNodeClass ) )
		{
			$nodeClass = $class::$containerNodeClass;
		}
		$entries = array();
		
		if ( isset( $nodeClass ) and $filename == $databaseId . '_sitemap_database_categories' )
		{
			$select = array();
			if ( \in_array( 'IPS\Content\Permissions', class_implements( $nodeClass ) ) or \in_array( 'IPS\Node\Permissions', class_implements( $nodeClass ) ) )
			{
				$select = new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', $nodeClass::$databaseTable, array( 'category_database_id=? AND (' . \IPS\Db::i()->findInSet( 'perm_view', array( \IPS\Settings::i()->guest_group ) ) . ' OR ' . 'perm_view=? )', $databaseId, '*' ) )->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id={$nodeClass::$databaseTable}.{$nodeClass::$databasePrefix}{$nodeClass::$databaseColumnId}", $nodeClass::$permApp, $nodeClass::$permType ) ), $nodeClass );
			}
			else if ( $nodeClass::$ownerTypes !== NULL and is_subclass_of( $nodeClass, 'IPS\Node\Model' ) )
			{ 
				$select = $nodeClass::loadByOwner( new \IPS\Member );
			}

			foreach ( $select as $node )
			{
				/* We only want nodes we can see, and that have actual content inside */
				if( $node->url() !== NULL and $node->can( 'view', new \IPS\Member ) and ( $node->hasChildren() OR ( $node->show_records and $node->getContentItemCount() ) ) )
				{
					$data = array( 'url' => $node->url(), 'lastmod' => $node->getLastCommentTime() );
					
					$priority = \intval( \IPS\Settings::i()->sitemap_databases_priority );
					if ( $priority !== -1 )
					{
						$data['priority'] = $priority;
					}

					$entries[] = $data;
				}
			}
		}
		else
		{
			$exploded = explode( '_', $filename );
			$block = (int) array_pop( $exploded );
			
			$offset = ( $block - 1 ) * \IPS\Sitemap::MAX_PER_FILE;
			$limit = \IPS\Sitemap::MAX_PER_FILE;
			
			$totalLimit = \IPS\Settings::i()->sitemap_databases_count;
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
			
			foreach ( $class::getItemsWithPermission( $class::sitemapWhere(), NULL, array( $offset, $limit ), 'read', \IPS\Content\Hideable::FILTER_PUBLIC_ONLY, 0, new \IPS\Member, TRUE ) as $item )
			{
				$data = array( 'url' => $item->url() );

				$lastMod = $item->lastModificationDate();

				if ( $lastMod )
				{
					$data['lastmod'] = $lastMod;
				}

				$priority = ( $item->sitemapPriority() ?: ( \intval( \IPS\Settings::i()->sitemap_databases_priority ) ) );
				if ( $priority !== -1 )
				{
					$data['priority'] = $priority;
				}

				$entries[] = $data;
			}
		}

		$sitemap->buildSitemapFile( $filename, $entries );
	}
}