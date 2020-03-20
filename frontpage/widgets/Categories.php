<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief       FrontPage Categories Widget
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4.x
 * @subpackage	FrontPage
 * @version     1.0.4 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     20 MAR 2020
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

namespace IPS\frontpage\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Categories Widget
 */
class _Categories extends \IPS\Widget\PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'Categories';
	
	/**
	 * @brief	App
	 */
	public $app = 'frontpage';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		/* If we're not on a Fpages fpage, return nothing */
		if( !\IPS\frontpage\Fpages\Fpage::$currentFpage )
		{
			return '';
		}

		/* Scope makes it possible for this block to fire before the main block which sets up the dispatcher */
		$db = NULL;
		if ( ! \IPS\frontpage\Databases\Dispatcher::i()->databaseId )
		{
			try
			{
				$db = \IPS\frontpage\Fpages\Fpage::$currentFpage->getDatabase()->id;
			}
			catch( \Exception $ex )
			{

			}
		}
		else
		{
			$db = \IPS\frontpage\Databases\Dispatcher::i()->databaseId;
		}

		if ( ! \IPS\frontpage\Fpages\Fpage::$currentFpage->full_path or ! $db )
		{
			return '';
		}

		$url = \IPS\Http\Url::internal( "app=frontpage&module=fpages&controller=fpage&path=" . \IPS\frontpage\Fpages\Fpage::$currentFpage->full_path, 'front', 'content_fpage_path', \IPS\frontpage\Fpages\Fpage::$currentFpage->full_path );

		return $this->output($url);
	}
}