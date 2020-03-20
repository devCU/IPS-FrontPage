<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		Customization AJAX actions
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

namespace IPS\frontpage\modules\admin\templates;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Members AJAX actions
 */
class _ajax extends \IPS\Dispatcher\Controller
{
	/**
	 * Return a CSS or HTML menu
	 *
	 * @return	void
	 */
	public function loadMenu()
	{
		$request   = array(
			't_location'  => ( isset( \IPS\Request::i()->t_location ) ) ? \IPS\Request::i()->t_location : null,
			't_group'     => ( isset( \IPS\Request::i()->t_group ) ) ? \IPS\Request::i()->t_group : null,
			't_key' 	  => ( isset( \IPS\Request::i()->t_key ) ) ? \IPS\Request::i()->t_key : null,
			't_type'      => ( isset( \IPS\Request::i()->t_type ) ) ? \IPS\Request::i()->t_type : 'templates',
		);

		switch( $request['t_type'] )
		{
			default:
			case 'template':
				$flag = \IPS\frontpage\Templates::RETURN_ONLY_TEMPLATE;
				break;
			case 'js':
				$flag = \IPS\frontpage\Templates::RETURN_ONLY_JS;
				break;
			case 'css':
				$flag = \IPS\frontpage\Templates::RETURN_ONLY_CSS;
				break;
		}

		$templates = \IPS\frontpage\Templates::buildTree( \IPS\frontpage\Templates::getTemplates( $flag + \IPS\frontpage\Templates::RETURN_DATABASE_ONLY ) );

		$current = new \IPS\frontpage\Templates;
		
		if ( ! empty( $request['t_key'] ) )
		{
			try
			{
				$current = \IPS\frontpage\Templates::load( $request['t_key'] );
			}
			catch( \OutOfRangeException $ex )
			{
				
			}
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'templates' )->menu( $templates, $current, $request );
	}

	/**
	 * Return HTML template as JSON
	 *
	 * @return	void
	 */
	public function loadTemplate()
	{
		$t_location  = \IPS\Request::i()->t_location;
		$t_key       = \IPS\Request::i()->t_key;
		
		if ( $t_location === 'block' and $t_key === '_default_' and isset( \IPS\Request::i()->block_key ) )
		{
			/* Find it from the normal template system */
			if ( isset( \IPS\Request::i()->block_app ) )
			{
				$plugin = \IPS\Widget::load( \IPS\Application::load( \IPS\Request::i()->block_app ), \IPS\Request::i()->block_key, mt_rand() );
			}
			else
			{
				$plugin = \IPS\Widget::load( \IPS\Plugin::load( \IPS\Request::i()->block_plugin ), \IPS\Request::i()->block_key, mt_rand() );
			}
			
			$location = $plugin->getTemplateLocation();
			
			$templateBits  = \IPS\Theme::master()->getRawTemplates( $location['app'], $location['location'], $location['group'], \IPS\Theme::RETURN_ALL );
			$templateBit   = $templateBits[ $location['app'] ][ $location['location'] ][ $location['group'] ][ $location['name'] ];
			
			if ( ! isset( \IPS\Request::i()->noencode ) OR ! \IPS\Request::i()->noencode )
			{
				$templateBit['template_content'] = htmlentities( $templateBit['template_content'], ENT_DISALLOWED, 'UTF-8', TRUE );
			}
			
			$templateArray = array(
				'template_id' 			=> $templateBit['template_id'],
				'template_key' 			=> 'template_' . $templateBit['template_name'] . '.' . $templateBit['template_id'],
				'template_title'		=> $templateBit['template_name'],
				'template_desc' 		=> null,
				'template_content' 		=> $templateBit['template_content'],
				'template_location' 	=> null,
				'template_group' 		=> null,
				'template_container' 	=> null,
				'template_rel_id' 		=> null,
				'template_user_created' => null,
				'template_user_edited'  => null,
				'template_params'  	    => $templateBit['template_data']
			);
		}
		else
		{
			try
			{
				if ( \is_numeric( $t_key ) )
				{
					$template = \IPS\frontpage\Templates::load( $t_key, 'template_id' );
				}
				else
				{
					$template = \IPS\frontpage\Templates::load( $t_key );
				}
			}
			catch( \OutOfRangeException $ex )
			{
				\IPS\Output::i()->json( array( 'error' => true ) );
			}

			if ( $template !== null )
			{
				$templateArray = array(
	                'template_id' 			=> $template->id,
	                'template_key' 			=> $template->key,
	                'template_title'		=> $template->title,
	                'template_desc' 		=> $template->desc,
	                'template_content' 		=> ( isset( \IPS\Request::i()->noencode ) AND \IPS\Request::i()->noencode ) ? $template->content : htmlentities( $template->content, ENT_DISALLOWED, 'UTF-8', TRUE ),
	                'template_location' 	=> $template->location,
	                'template_group' 		=> $template->group,
	                'template_container' 	=> $template->container,
	                'template_rel_id' 		=> $template->rel_id,
	                'template_user_created' => $template->user_created,
	                'template_user_edited'  => $template->user_edited,
	                'template_params'  	    => $template->params
	            );
			}
		}

		if ( \IPS\Request::i()->show == 'json' )
		{
			\IPS\Output::i()->json( $templateArray );
		}
		else
		{
			\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( \IPS\Theme::i()->getTemplate( 'templates', 'frontpage', 'admin' )->viewTemplate( $templateArray ) ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
		}
	}
}