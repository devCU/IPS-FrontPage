<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief		FrontPage Download Handler for custom record upload fields
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

\define('REPORT_EXCEPTIONS', TRUE);
require_once str_replace( 'applications/frontpage/interface/file/file.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
\IPS\Dispatcher\External::i();

try
{
	/* Load member */
	$member = \IPS\Member::loggedIn();
	
	/* Set up autoloader for FrontPage */

	/* Init */
	$databaseId  = \intval( \IPS\Request::i()->database );
	$database    = \IPS\frontpage\Databases::load( $databaseId );
	$recordId    = \intval( \IPS\Request::i()->record );
	$fileName    = urldecode( \IPS\Request::i()->file );
	$recordClass = '\IPS\frontpage\Records' . $databaseId;
	$realFileName = NULL;

	try
	{
		$record = $recordClass::load( $recordId );
	}
	catch( \OutOfRangeException $ex )
	{
		\IPS\Output::i()->error( 'no_module_permission', '2T279/1', 403, '' );
	}
	
	if ( ! $record->canView() )
	{
		\IPS\Output::i()->error( 'no_module_permission', '2T279/2', 403, '' );
	}

	$realFileName = \IPS\Text\Encrypt::fromCipher( base64_decode( \IPS\Request::i()->fileKey ) )->decrypt();

	if ( ! $realFileName )
	{
		\IPS\Output::i()->error( 'no_module_permission', '2T279/4', 403, '' );
	}

	/* Get file and data */
	try
	{
		$file = \IPS\File::get( 'frontpage_Records', $realFileName );
	}
	catch( \Exception $ex )
	{
		\IPS\Output::i()->error( 'no_module_permission', '2T279/3', 404, '' ); 
	}
		
	$headers = array_merge( \IPS\Output::getCacheHeaders( time(), 360 ), array( "Content-Disposition" => \IPS\Output::getContentDisposition( 'attachment', \IPS\Request::i()->file ), "X-Content-Type-Options" => "nosniff" ) );
	
	/* Send headers and print file */
	\IPS\Output::i()->sendStatusCodeHeader( 200 );
	\IPS\Output::i()->sendHeader( "Content-type: " . \IPS\File::getMimeType( \IPS\Request::i()->file ) . ";charset=UTF-8" );

	foreach( $headers as $key => $header )
	{
		\IPS\Output::i()->sendHeader( $key . ': ' . $header );
	}
	\IPS\Output::i()->sendHeader( "Content-Length: " . $file->filesize() );
	\IPS\Output::i()->sendHeader( "Content-Security-Policy: default-src 'none'; sandbox" );
	\IPS\Output::i()->sendHeader( "X-Content-Security-Policy:  default-src 'none'; sandbox" );

	$file->printFile();
	exit;
}
catch ( \UnderflowException $e )
{
	\IPS\Dispatcher\Front::i();
	\IPS\Output::i()->sendOutput( '', 404 );
}