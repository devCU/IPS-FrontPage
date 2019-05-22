<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		Designers Mode Extension
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

namespace IPS\frontpage\extensions\core\DesignersMode;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Designers Mode Extension
 */
class _Frontpage
{
	/**
	 * Anything need building?
	 *
	 * @return bool
	 */
	public function toBuild()
	{
		/* Yeah.. not gonna even bother trying to match up timestamps and such like and so on etc and etcetera is that spelled right? */
		return TRUE;
	}
	
	/**
	 * Designer's mode on
	 *
	 * @param	mixed	$data	Data
	 * @return bool
	 */
	public function on( $data=NULL )
	{
		\IPS\frontpage\Theme\Advanced\Theme::export();
		\IPS\frontpage\Media::exportDesignersModeMedia();
		\IPS\frontpage\Fpages\Fpage::exportDesignersMode();
		
		return TRUE;
	}
	
	/**
	 * Designer's mode off
	 *
	 * @param	mixed	$data	Data
	 * @return bool
	 */
	public function off( $data=NULL )
	{
		\IPS\frontpage\Theme\Advanced\Theme::import();
		\IPS\frontpage\Media::importDesignersModeMedia();
		\IPS\frontpage\Fpages\Fpage::importDesignersMode();
		
		return TRUE;
	}
}