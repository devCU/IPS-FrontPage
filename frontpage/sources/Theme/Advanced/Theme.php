<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.devcu.com/donate
 *
 * @brief		Designer's Mode Theme
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.5x
 * @subpackage	FrontPage
 * @version     1.0.5 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     19 OCT 2020
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

namespace IPS\frontpage\Theme\Advanced;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Designer's Mode Theme
 */
class _Theme extends \IPS\frontpage\Theme
{
	/**
	 * Get currently logged in member's theme
	 *
	 * @return	static
	 */
	public static function i()
	{
		return new self;
	}
	
	/**
	 * Write files to disk
	 *
	 * @return void
	 */
	public static function export()
	{
		foreach( \IPS\Db::i()->select(
			"*, MD5( CONCAT(template_location, ',', template_group, ',', template_title) ) as bit_key",
			'frontpage_templates',
			NULL,
			'template_user_edited ASC'  /* Ensure we get edited versions, not the master version if one exists */
		)->setKeyField('bit_key') as $template )
		{
			static::writeTemplate( $template, TRUE );
		}
	}
	
	/**
	 * Imports templates from the designer's mode directories.
	 *
	 * @return	void
	 */
	public static function import()
	{
		$seen = array();
		
		foreach( array( 'block', 'fpage', 'database', 'js', 'css' ) as $location )
		{
			$templates = iterator_to_array( \IPS\Db::i()->select(
					"*, MD5( CONCAT(template_location, ',', template_group, ',', template_title) ) as bit_key",
					'frontpage_templates',
					array( 'template_location=?', $location ),
					'template_user_edited ASC'  /* Ensure we get edited versions, not the master version if one exists */
				)->setKeyField('bit_key') );
		
			$master = iterator_to_array( \IPS\Db::i()->select(
					"*, MD5( CONCAT(template_location, ',', template_group, ',', template_title) ) as bit_key",
					'frontpage_templates',
					array( 'template_master=1 and template_location=?', $location )
				)->setKeyField('bit_key') );
				
			$path = static::_getHtmlPath( 'frontpage', $location );

			if ( is_dir( $path ) )
			{
				foreach( new \DirectoryIterator( $path ) as $group )
				{
					if ( $group->isDot() || mb_substr( $group->getFilename(), 0, 1 ) === '.' )
					{
						continue;
					}

					if ( $group->isDir() )
					{
						$seen = array();

						foreach( new \DirectoryIterator( $path . '/' . $group->getFilename() ) as $file )
						{
							if ( $file->isDot() || ( mb_substr( $file->getFilename(), -6 ) !== '.phtml' and mb_substr( $file->getFilename(), -3 ) !== '.js' and mb_substr( $file->getFilename(), -4 ) !== '.css' ) )
							{
								continue;
							}

							/* Get the content */
							$html = file_get_contents( $path . '/' . $group->getFilename() . '/' . $file->getFilename() );
	
							/* Parse the header tag */
							preg_match( '/^<ips:template parameters="([^"]+?)?"([^>]+?)>(\r\n?|\n)/', $html, $params );
	
							/* Strip it */
							$html = ( isset($params[0]) ) ? str_replace( $params[0], '', $html ) : $html;
							$title = str_replace( '.phtml', '', $file->getFilename() );
							$originalGroup = $group->getFilename();

							if ( isset( $params[2] ) and mb_stristr( $params[2], 'original_group' ) )
							{
								preg_match( '#original_group="(.+?)?"#', $params[2], $submatches );

								if ( isset( $submatches[1] ) )
								{
									$originalGroup = $submatches[1];
								}
							}

							/* If we're syncing designer mode, check for actual changes */
							$key = md5( $location . ',' . $group->getFilename() . ',' . $title );

							$seen[] = $title;
							$added  = FALSE;
							
							if ( isset( $templates[ $key ] ) )
							{
								if( md5( trim( $html ) ) == md5( trim( $templates[ $key ]['template_content'] ) ) )
								{
									/* No change  */
									continue;
								}
								
								/* Update */
								if ( ! $templates[ $key ]['template_master'] )
								{
									$added = TRUE;
									\IPS\Db::i()->update( 'frontpage_templates', array(
										'template_content'     => $html,
										'template_params'      => ( isset($params[1]) ) ? $params[1] : '',
										'template_file_object' => NULL
									), array( 'template_id=?', $templates[ $key ]['template_id'] ) );
								}
							}
							
							if ( ! $added )
							{
								$templateType = 'template';

								if ( $location === 'css' )
								{
									$templateType = 'css';
								}
								else if ( $location === 'js' )
								{
									$templateType = 'js';
								}
									
								/* New template */
								\IPS\Db::i()->insert( 'frontpage_templates', array(
									'template_key'            => $location . '_' . $group->getFilename() . '_' . $title,
									'template_title'	      => $title,
									'template_desc'		      => '',
									'template_content'        => $html,
									'template_location'       => $location,
									'template_group'          => $group->getFilename(),
									'template_original_group' => $originalGroup,
									'template_container'      => 0,
									'template_params'	      => ( isset($params[1]) ) ? $params[1] : '',
									'template_master'         => 0,
									'template_user_edited'    => ( isset( $master[ $key ] ) ) ? 1 : 0,
								    'template_user_created'   => ( isset( $master[ $key ] ) ) ? 0 : 1,
								    'template_type'           => $templateType
								) );
							}

							/* remove compiled version */
							$key = \strtolower( 'template_frontpage_' .static::makeBuiltTemplateLookupHash( 'frontpage', $location, $group->getFilename() ) . '_' . static::cleanGroupName( $group->getFilename() ) );

							if ( isset( \IPS\Data\Store::i()->$key ) )
							{
								unset(\IPS\Data\Store::i()->$key);
							}
						}
					}
					
					/* Remove any templates we've not imported for this location/group */
					if ( \count( $seen ) )
					{
						\IPS\Db::i()->delete( 'frontpage_templates', array('template_master=0 and template_location=? and template_group=? and ' . \IPS\Db::i()->in( 'template_title', $seen, TRUE ), $location, $group->getFilename() ) );
					}
				}
			}
		}
	}
	
	/**
	 * Returns the path for the IN_DEV .phtml files
	 * @param string 	 	  $app			Application Key
	 * @param string|null	  $location		Location
	 * @param string|null 	  $path			Path or Filename
	 * @return string
	 */
	protected static function _getHtmlPath( $app, $location=null, $path=null )
	{
		return rtrim( \IPS\ROOT_PATH . "/themes/frontpage/{$location}/{$path}", '/' ) . '/';
	}
}