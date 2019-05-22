<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		FrontPage External Block Gateway
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4+
 * @subpackage	FrontPage
 * @version     1.0.0 RC
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     21 MAY 2019
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

\define('REPORT_EXCEPTIONS', TRUE);
require_once str_replace( 'applications/frontpage/interface/developer/developer.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
\IPS\Dispatcher\External::i();

if ( \IPS\IN_DEV !== true AND ! \IPS\Theme::designersModeEnabled() )
{
	exit();
}

/* The CSS is parsed by the theme engine, and the theme engine has plugins, and those plugins need to now which theme ID we're using */
if ( \IPS\Theme::designersModeEnabled() )
{
	\IPS\Session\Front::i();
}

if ( isset( \IPS\Request::i()->file ) )
{
	$realPath = realpath( \IPS\ROOT_PATH . '/themes/' . \IPS\Request::i()->file );
	$pathContainer = realpath(\IPS\ROOT_PATH . '/themes/' );

	if( $realPath === FALSE OR mb_substr( $realPath, 0, mb_strlen( $pathContainer ) ) !== $pathContainer )
	{
		\IPS\Output::i()->error( 'node_error', '3C171/8', 403, '' );
		exit;
	}

	$file = file_get_contents( \IPS\ROOT_PATH . '/themes/' . \IPS\Request::i()->file );
		
	\IPS\Output::i()->sendOutput( preg_replace( '#<ips:template.+?\n#', '', $file ), 200, ( mb_substr( \IPS\Request::i()->file, -4 ) === '.css' ) ? 'text/css' : 'text/javascript' );
}