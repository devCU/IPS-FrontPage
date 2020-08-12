<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       FrontPage Custom block Widget
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

namespace IPS\frontpage\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Custom block Widget
 */
class _Blocks extends \IPS\Widget\PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'Blocks';
	
	/**
	 * @brief	App
	 */
	public $app = 'frontpage';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';
	
	/**
	 * Constructor
	 *
	 * @param	string				$uniqueKey				Unique key for this specific instance
	 * @param	array				$configuration			Widget custom configuration
	 * @param	null|string|array	$access					Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param	null|string			$orientation			Orientation (top, bottom, right, left)
	 * @return	void
	 */
	public function __construct( $uniqueKey, array $configuration, $access=null, $orientation=null )
	{
		try
		{
			if (  isset( $configuration['frontpage_widget_custom_block'] ) )
			{
				$block = \IPS\frontpage\Blocks\Block::load( $configuration['frontpage_widget_custom_block'], 'block_key' );
				if ( $block->type === 'custom' AND ! $block->cache )
				{
					$this->neverCache = TRUE;
				}
				else if ( $block->type === 'plugin' )
				{
					try
					{
						/* loads and JS and CSS needed */
						$block->orientation = $orientation;
						$block->widget()->init();
					}
					catch( \Exception $e ) { }
				}
			}
		}
		catch( \Exception $e ) { }
		
		parent::__construct( $uniqueKey, $configuration, $access, $orientation );
	}
	
	/**
	 * Specify widget configuration
	 *
	 * @param   \IPS\Helpers\Form   $form       Form Object
	 * @return	null|\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
 	{
		$form = parent::configuration( $form );
		
		/* A block may be deleted on the back end */
		$block = NULL;
		try
		{
			if ( isset( $this->configuration['frontpage_widget_custom_block'] ) )
			{
				$block = \IPS\frontpage\Blocks\Block::load( $this->configuration['frontpage_widget_custom_block'], 'block_key' );
			}
		}
		catch( \OutOfRangeException $e ) { }
		
	    $form->add( new \IPS\Helpers\Form\Node( 'frontpage_widget_custom_block', $block, FALSE, array(
            'class' => '\IPS\frontpage\Blocks\Container',
            'permissionCheck' => function( $node )
                {
	                if ( $node instanceof \IPS\frontpage\Blocks\Container )
	                {
		                return FALSE;
	                }

	                return TRUE;
                }
        ) ) );

	    return $form;
 	}

	/**
	 * Pre config
	 *
	 * @param   array   $values     Form values
	 * @return  array
	 */
	public function preConfig( $values )
	{
		$newValues = $values;

		if ( isset( $values['frontpage_widget_custom_block'] ) )
		{
			$newValues['frontpage_widget_custom_block'] = $values['frontpage_widget_custom_block']->key;
		}

		return $newValues;
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		if ( isset( $this->configuration['frontpage_widget_custom_block'] ) )
		{
			return (string) \IPS\frontpage\Blocks\Block::display( $this->configuration['frontpage_widget_custom_block'], $this->orientation );
		}

		return '';
	}
}