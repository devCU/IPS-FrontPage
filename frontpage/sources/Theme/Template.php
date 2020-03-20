<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		Magic Template Class for IN_DEV mode
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

namespace IPS\frontpage\Theme;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Magic Template Class for IN_DEV mode
 */
class _Template extends \IPS\Theme\Template
{
	/**
	 * @brief	Source Folder
	 */
	public $sourceFolder = NULL;
	
	/**
	 * Extract the data object from the param tag
	 * @note We always assume the tag is in the format of <ips:template parameters="" data="" />
	 *
	 * @param	string	$tag	<ips:template> tag
	 * @param	string	$key	Data key to fetch
	 * @return  null|array
	 */
	public static function extractDataFromTag( $tag, $key="data" )
	{
		if ( ! preg_match( '/^<ips:template parameters="(.+?)?"([^>]+?)>(\r\n?|\n)/', $tag, $matches ) )
		{
			return NULL;
		}
		
		if ( preg_match( '#' . $key . '="([^"]+?)"#', $matches[2], $submatches ) )
		{
			return $submatches[1];
		}
		
		return NULL;
	}
	
	/**
	 * Extract the data object from the param tag
	 * @note We always assume the tag is in the format of <ips:template parameters="" data="" />
	 *
	 * @param	string	$tag	<ips:template> tag
	 * @return  null|array
	 */
	public static function extractParamsFromTag( $tag )
	{
		if ( ! preg_match( '/^<ips:template parameters="(.+?)?"([^>]+?)>(\r\n?|\n)/', $tag, $matches ) )
		{
			return $matches[1];
		}
		
		return NULL;
	}
	
	/**
	 * Contructor
	 *
	 * @param	string	$app				Application Key
	 * @param	string	$templateLocation	Template location (admin/public/etc.)
	 * @param	string	$templateName		Template Name
	 * @return	void
	 */
	public function __construct( $app, $templateLocation, $templateName )
	{
		parent::__construct( $app, $templateLocation, $templateName );
		$this->app = $app;
		$this->templateLocation = $templateLocation;
		$this->templateName = $templateName;
		
		if ( \IPS\Theme::designersModeEnabled() )
		{
			$this->sourceFolder = \IPS\ROOT_PATH . "/themes/frontpage/{$templateLocation}/{$templateName}/";
		}
		else if ( \IPS\IN_DEV )
		{
			$this->sourceFolder = \IPS\ROOT_PATH . "/applications/{$app}/dev/html/{$templateLocation}/{$templateName}/";
		}
	}
	
	/**
	 * Magic Method: Call Template Bit
	 *
	 * @param	string	$bit	Template Bit Name
	 * @param	array	$params	Parameters
	 * @return	string
	 */
	public function __call( $bit, $params )
	{
		if ( \IPS\IN_DEV or \IPS\Theme::designersModeEnabled() )
		{
			/* What are we calling this? */
			$functionName = "theme_{$this->app}_{$this->templateLocation}_{$this->templateName}_{$bit}";
	
			/* If it doesn't exist, build it */
			if( !\function_exists( 'IPS\\Theme\\'.$functionName ) )
			{
				/* Find the file */
				$file = $this->sourceFolder . $bit . '.phtml';
				
				/* Get the content */
				if ( $file === NULL or !file_exists( $file ) )
				{
					/* Try the database */
					try
					{
						$template = \IPS\Db::i()->select( '*', 'frontpage_templates', array( 'template_location=? and LOWER(template_group)=? and template_title=?', $this->templateLocation, $this->templateName, $bit ) )->first();
						
						\IPS\frontpage\Theme::makeProcessFunction( $template['template_content'], $functionName, $template['template_params'], TRUE );
					}
					catch ( \UnderflowException $e )
					{
						throw new \BadMethodCallException( 'NO_TEMPLATE_FILE - ' . $file );
					}
				}
				else
				{
					
					$output = file_get_contents( $file );
					
					/* Parse the header tag */
					if ( !preg_match( '/^<ips:template parameters="(.+?)?"([^>]+?)>(\r\n?|\n)/', $output, $matches ) )
					{
						throw new \BadMethodCallException( 'NO_HEADER - ' . $file );
					}
					
					/* Strip it */
					$output = preg_replace( '/^<ips:template parameters="(.+?)?"([^>]+?)>(\r\n?|\n)/', '', $output );
					
					/* Make it into a lovely function */
					\IPS\frontpage\Theme::makeProcessFunction( $output, $functionName, ( isset( $matches[1] ) ? $matches[1] : '' ), TRUE );
				}
			}
			
			/* Run it */
			ob_start();
			$template = 'IPS\\Theme\\'.$functionName;
			$return = $template( ...$params );
			if( $error = ob_get_clean() )
			{
				echo "<strong>{$functionName}</strong><br>{$error}<br><br><pre>{$output}";
				exit;
			}
			
			/* Return */
			return $return;
		}
	}
	
}