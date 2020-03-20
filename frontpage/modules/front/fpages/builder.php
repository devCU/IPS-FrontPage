<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.patreon.com/devcu
 *
 * @brief		FrontPage Builder Controller
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4+
 * @subpackage	FrontPage
 * @version     1.0.0
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     02 MAY 2019
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
namespace IPS\frontpage\modules\front\fpages;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * content
 */
class _builder extends \IPS\core\modules\front\system\widgets
{
	/**
	 * Preview a block (from the ACP or elsewhere dynamically)
	 *
	 * @return html
	 */
	public function previewBlock()
	{
		$output = "";
		
		if ( isset( \IPS\Request::i()->block_plugin ) )
		{
			$block  = new \IPS\frontpage\Blocks\Block;
			$block->type       = "plugin";
			$block->plugin     = \IPS\Request::i()->block_plugin;
			$block->plugin_app = ( isset( \IPS\Request::i()->block_plugin_app ) ) ? \IPS\Request::i()->block_plugin_app : \IPS\Request::i()->block_app;
			$block->plugin_plugin = \IPS\Request::i()->block_plugin_plugin;
			$block->key		   = md5( mt_rand() );
			
			$params		= array();
			$block->content = NULL;

			if ( isset( \IPS\Request::i()->_sending ) )
			{
				foreach( explode( ",", \IPS\Request::i()->_sending ) as $field )
				{
					/* Multi-Selects will pass their parameters through as an array, so we need to make sure we check those properly to include all options. */
					if ( mb_strstr( $field, '[]' ) !== FALSE )
					{
						$field		= str_replace( '[]', '', $field );
						$isArray	= TRUE;
					}
					else
					{
						$isArray	= FALSE;
					}
					
					if ( $field and isset( \IPS\Request::i()->$field ) )
					{
						if ( $field == 'block_content' )
						{
							$block->content = \IPS\Request::i()->$field;
							
							if ( isset( \IPS\Request::i()->template_params ) )
							{
								$block->template_params = \IPS\Request::i()->template_params;
							}
							continue;
						}

						if( mb_strpos( $field, "widget_feed_container_" ) !== FALSE )
						{
							/* On means that the all checkbox is ticked */
							$params[ 'widget_feed_container'] = \IPS\Request::i()->$field == 'on' ? 0 : \IPS\Request::i()->$field;
							continue;
						}

						/* We need to handle tags special */
						if( $field == 'widget_feed_tags' )
						{
							$params[ $field ] = explode( ',', \IPS\Request::i()->$field );
							continue;
						}
						
						/* Is it an array? */
						if ( $isArray )
						{
							foreach( \IPS\Request::i()->$field AS $multi )
							{
								$params[ $field ][] = $multi;
							}
							continue;
						}

						$params[ $field ] = \IPS\Request::i()->$field;
					}
				}
			}
			
			$block->plugin_config = json_encode( $params );
			
			/* Template stuffs */
			if ( \IPS\Request::i()->block_template_use_how == 'copy' )
			{
				$block->widget()->template( array( $block, 'getTemplate' ) );
			}

			$output = $block->widget()->render();
		}
							
		\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $output ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
	}
	
	/**
	 * Get Output For Adding A New Block
	 *
	 * @return	void
	 */
	protected function getBlock()
	{		
		$key = $block = explode( "_", \IPS\Request::i()->blockID );
		
		if ( isset( \IPS\Request::i()->contentID ) )
		{
			try
			{
				foreach ( \IPS\Db::i()->select( '*', 'frontpage_content_widget_areas', array( 'area_content_id=?', \IPS\Request::i()->contentID ) ) as $item )
				{
					$blocks = json_decode( $item['area_widgets'], TRUE );
					
					foreach( $blocks as $block )
					{
						if( $block['key'] == $key[2] AND $block['unique'] == $key[3] )
						{ 
							if ( isset( $block['app'] ) and $block['app'] == $key[1] )
							{
								$widget = \IPS\Widget::load( \IPS\Application::load( $block['app'] ), $block['key'], $block['unique'], $block['configuration'], null, \IPS\Request::i()->orientation );
							}
							elseif ( isset( $block['plugin'] ) and $block['plugin'] == $key[1] )
							{
								$widget = \IPS\Widget::load( \IPS\Plugin::load( $block['plugin'] ), $block['key'], $block['unique'], $block['configuration'], null, \IPS\Request::i()->orientation );
							}
						}
					}
				}
			}
			catch ( \UnderflowException $e ) { }

			/* Make sure the current content is set so the widgets have database/content scope */
			\IPS\frontpage\Fpages\Fpage::$currentContent = \IPS\frontpage\Fpages\Fpage::load( \IPS\Request::i()->contentID );

			/* Have we got a database for this content? */
			$database = \IPS\frontpage\Fpages\Fpage::$currentContent->getDatabase();

			if ( $database )
			{
				\IPS\frontpage\Databases\Dispatcher::i()->setDatabase( $database->id );
			}
		}
		
		if ( !isset( $widget ) )
		{
			try
			{
				$widget = \IPS\Widget::load( \IPS\Application::load( $key[1] ), $key[2], $key[3], array(), null, \IPS\Request::i()->orientation );

			}
			catch ( \OutOfRangeException $e )
			{
				$widget = \IPS\Widget::load( \IPS\Plugin::load( $key[1] ), $key[2], $key[3], array(), null, \IPS\Request::i()->orientation );
			}
		}

		$output = (string) $widget;

		\IPS\Output::i()->output = ( $output ) ? $output :  \IPS\Theme::i()->getTemplate( 'widgets', 'core', 'front' )->blankWidget( $widget );
	}

	/**
	 * Get Configuration
	 *
	 * @return	void
	 */
	protected function getConfiguration()
	{
		/* Standard widget area, allow the core stuff to handle this */
		if( \in_array( \IPS\Request::i()->area, array( 'sidebar', 'header', 'footer' ) ) )
		{
			return parent::getConfiguration();
		}
		
		$key	= explode( "_", \IPS\Request::i()->block );
		$blocks	= array( 'area_widgets' => NULL );
		
		/* Frontpage only stuff */
		try
		{
			$blocks       = \IPS\Db::i()->select( '*', 'frontpage_content_widget_areas', array( 'area_content_id=? AND area_area=?', \IPS\Request::i()->contentID, \IPS\Request::i()->contentArea ) )->first();

			$where = ( $key[0] ) == 'app' ? '`key`=? AND `app`=?' : '`key`=? AND `plugin`=?';
			$widgetMaster = \IPS\Db::i()->select( '*', 'core_widgets', array( $where, $key[2], $key[1] ) )->first();
		}
		catch ( \UnderflowException $e )
		{
		}
		
		$blocks	= json_decode( $blocks['area_widgets'], TRUE );
		$widget	= NULL;

		if( !empty( $blocks ) )
		{
			foreach ( $blocks as $k => $block )
			{
				if ( $block['key'] == $key[2] AND $block['unique'] == $key[3] )
				{
					if ( isset( $block['app'] ) and $block['app'] == $key[1] )
					{
						$widget = \IPS\Widget::load( \IPS\Application::load( $block['app'] ), $block['key'], $block['unique'], $block['configuration'] );
						$widget->menuStyle = $widgetMaster['menu_style'];
					}
					elseif ( isset( $block['plugin'] ) and $block['plugin'] == $key[1] )
					{
						$widget = \IPS\Widget::load( \IPS\Plugin::load( $block['plugin'] ), $block['key'], $block['unique'], $block['configuration'] );
						$widget->menuStyle = $widgetMaster['menu_style'];
					}
				}

				if( $widget !== NULL AND method_exists( $widget, 'configuration' ) )
				{
					$form = new \IPS\Helpers\Form( 'form', 'saveSettings' );
					if ( $widget->configuration( $form ) !== NULL )
					{
						if ( $values = $form->values() )
						{
							if ( method_exists( $widget, 'preConfig' ) )
							{
								$values = $widget->preConfig( $values );
							}
							
							$blocks[ $k ]['configuration'] = $values;
							\IPS\Db::i()->insert( 'frontpage_content_widget_areas', array( 'area_content_id' => \IPS\Request::i()->contentID, 'area_area' => \IPS\Request::i()->contentArea, 'area_widgets' => json_encode( $blocks ) ), TRUE );
							\IPS\Output::i()->json( 'OK' );
						}
						\IPS\Output::i()->output = $widget->configuration()->customTemplate( array( \IPS\Theme::i()->getTemplate( 'widgets', 'core' ), 'formTemplate' ), $widget );
					}
				}
			}
		}
	}
	
	/**
	 * Reorder Blocks
	 *
	 * @return	void
	 */
	protected function saveOrder()
	{
		$newOrder = array();
		$seen     = array();

		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$currentConfig = \IPS\Db::i()->select( '*', 'frontpage_content_widget_areas', array( 'area_content_id=? AND area_area=?', \IPS\Request::i()->contentID, \IPS\Request::i()->area ) )->first();
			$widgets = json_decode( $currentConfig['area_widgets'], TRUE );
		}
		catch ( \UnderflowException $e )
		{
			$widgets = array();
		}

		/* Loop over the new order and merge in current blocks so we don't lose config */
		if ( isset ( \IPS\Request::i()->order ) )
		{
			foreach ( \IPS\Request::i()->order as $block )
			{
				$block = explode( "_", $block );
				
				$added = FALSE;
				foreach( $widgets as $widget )
				{
					if ( $widget['key'] == $block[2] and $widget['unique'] == $block[3] )
					{
						$seen[]     = $widget['unique'];
						$newOrder[] = $widget;
						$added = TRUE;
						break;
					}
				}
				if( !$added )
				{
					$newBlock = array();
					
					if ( $block[0] == 'app' )
					{
						$newBlock['app'] = $block[1];
					}
					else
					{
						$newBlock['plugin'] = $block[1];
					}
					
					$newBlock['key'] 		  = $block[2];
					$newBlock['unique']		  = $block[3];
					$newBlock['configuration']	= array();

					/* Make sure this widget doesn't have configuration in another area */
					$newBlock['configuration'] = \IPS\frontpage\Widget::getConfiguration( $newBlock['unique'] );

					$seen[]     = $block[3];
					$newOrder[] = $newBlock;
				}
			}
		}

		/* Anything to update? */
		if ( \count( $widgets ) > \count( $newOrder ) )
		{
			/* No items left in area, or one has been removed */
			foreach( $widgets as $widget )
			{
				/* If we haven't seen this widget, it's been removed, so add to trash */
				if ( ! \in_array( $widget['unique'], $seen ) )
				{
					\IPS\Widget::trash( $widget['unique'], $widget );
				}
			}
		}
		
		/* Check core_widget_areas to ensure that the block wasn't added there */
		if ( isset( \IPS\Request::i()->exclude ) and ! empty( \IPS\Request::i()->exclude ) )
		{
			$bits = explode( "_", \IPS\Request::i()->exclude );
			$this->_checkAndDeleteFromCoreWidgets( $bits[3], $seen );
		}
		
		/* Expire Caches so up to date information displays */
		\IPS\Widget::deleteCaches();

		/* Save to database */
		$orientation = ( isset( \IPS\Request::i()->orientation ) and \IPS\Request::i()->orientation === 'vertical' ) ? 'vertical' : 'horizontal';
		\IPS\Db::i()->replace( 'frontpage_content_widget_areas', array( 'area_orientation' => $orientation, 'area_content_id' => \IPS\Request::i()->contentID, 'area_widgets' => json_encode( $newOrder ), 'area_area' => \IPS\Request::i()->area ) );
		
		\IPS\frontpage\Fpages\Fpage::load( \IPS\Request::i()->contentID )->postWidgetOrderSave();
	}
	
	/**
	 * Sometimes the widgets end up in the core table. We haven't really found out why this happens. It happens very rarely.
	 * It may be that the Frontpage JS mixin doesn't load so the core ajax URLs are used (system/widgets.php) and not the frontpage widget (fpages/builder.php).
	 * This method ensures that any widgets in the core table are removed
	 *
	 * @param	string	$uniqueId	The unique key of the widget (eg: wzsj1233)
	 * @param	array	$widgets	Current widgets (eg from core_widget_areas.widgets (json decoded))
	 * @return	bool				True if something removed, false if not
	 */
	protected function _checkAndDeleteFromCoreWidgets( $uniqueId, $widgets )
	{
		if ( ! \in_array( $uniqueId, $widgets ) )
		{
			/* This widget hasn't been seen, so it isn't in the frontpage table */
			try
			{
				$frontpageWidget = \IPS\Db::i()->select( '*', 'core_widget_areas', array( 'app=? and module=? and controller=? and area=?', 'frontpage', 'contents', 'content', \IPS\Request::i()->area ) )->first();
				$frontpageWidgets = json_decode( $frontpageWidget['widgets'], TRUE );
				$newWidgets = array();
				
				foreach( $frontpageWidgets as $item )
				{
					if ( $item['unique'] !== $uniqueId )
					{
						$newWidgets[] = $item;
					}
				}
				
				/* Anything to save? */
				if ( \count( $newWidgets ) )
				{
					\IPS\Db::i()->replace( 'core_widget_areas', array( 'app' => 'frontpage', 'module' => 'contents', 'controller' => 'content', 'widgets' => json_encode( $newWidgets ), 'area' => \IPS\Request::i()->area ) );
				}
				else
				{
					/* Just remove the entire row */
					\IPS\Db::i()->delete( 'core_widget_areas', array( 'app=? and module=? and controller=? and area=?', 'frontpage', 'contents', 'content', \IPS\Request::i()->area ) );
				}
				
				return TRUE;
			}
			catch( \UnderFlowException $ex )
			{
				/* Well, it isn't there either... */
				return FALSE;
			}
		}
	}
}