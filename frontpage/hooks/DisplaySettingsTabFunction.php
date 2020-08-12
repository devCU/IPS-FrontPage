//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class frontpage_hook_DisplaySettingsTabFunction extends _HOOK_CLASS_
{
	/**
	 * Build and return the settings form: Display
	 *
	 * @note	Abstracted to allow third party devs to extend easier
	 * @return	\IPS\Helpers\Form
	 */
	protected function _display()
	{
		if ( \IPS\Settings::i()->frontpage_display_settings_enabled)
		{
		  $form = new \IPS\Helpers\Form;
		  $form->class = 'ipsForm_collapseTablet';

		  $form->add( new \IPS\Helpers\Form\YesNo( 'frontpage_display_fixed', \IPS\Member::loggedIn()->frontpage_display_fixed, FALSE, array(), NULL, NULL, NULL, 'frontpage_display_fixed') );
		  $form->add( new \IPS\Helpers\Form\YesNo( 'frontpage_display_inverse', \IPS\Member::loggedIn()->frontpage_display_inverse, FALSE, array(), NULL, NULL, NULL, 'frontpage_display_inverse') );
			$form->add( new \IPS\Helpers\Form\Custom( 'frontpage_display_highlight', \IPS\Member::loggedIn()->frontpage_display_highlight, FALSE, array( 'getHtml' => function( $element )
			{
				return \IPS\Theme::i()->getTemplate( 'forms', 'frontpage', 'front' )->highlightsSelection( $element->name, $element->value );
			} ), NULL, NULL, NULL, 'frontpage_display_highlight' ) );
		}
		else
		{
		return \IPS\Member::loggedIn()->language()->addToStack( 'system_frontpage_display_disabled_admin' );
		}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			
			\IPS\Member::loggedIn()->frontpage_display_fixed = $values['frontpage_display_fixed'];
			\IPS\Member::loggedIn()->frontpage_display_inverse = $values['frontpage_display_inverse'];
			\IPS\Member::loggedIn()->frontpage_display_highlight = $values['frontpage_display_highlight'];
			
			\IPS\Member::loggedIn()->save();
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=settings&area=display', 'front', 'settingsDisplay ' ), 'frontpage_display_updated' );

	   }

		return \IPS\Theme::i()->getTemplate( 'system', 'frontpage', 'front' )->settingsDisplay( $form, 'tab' );
   }

	protected function display()
	{
		if ( \IPS\Settings::i()->frontpage_display_settings_enabled)
		{
		  $form = new \IPS\Helpers\Form;
		  $form->class = 'ipsForm_collapseTablet';

		  $form->add( new \IPS\Helpers\Form\YesNo( 'frontpage_display_fixed', \IPS\Settings::i()->frontpage_display_fixed, FALSE, array(), NULL, NULL, NULL, 'frontpage_display_fixed') );
		  $form->add( new \IPS\Helpers\Form\YesNo( 'frontpage_display_inverse', \IPS\Settings::i()->frontpage_display_inverse, FALSE, array(), NULL, NULL, NULL, 'frontpage_display_inverse') );
		  $form->add( new \IPS\Helpers\Form\Radio( 'frontpage_display_highlight', \IPS\Member::loggedIn()->frontpage_display_highlight, FALSE, array( 'options' => array(
			'red'	=> 'frontpage_display_red',
			'pink'	=> 'frontpage_display_pink',
			'orange'	=> 'frontpage_display_orange',
			'yellow'	=> 'frontpage_display_yellow',
			'lime'	=> 'frontpage_display_lime',
			'green'	=> 'frontpage_display_green',
			'teal'	=> 'frontpage_display_teal',
			'default'	=> 'frontpage_display_default',
			'blue'	=> 'frontpage_display_blue',
			'purple'	=> 'frontpage_display_purple',
			'indigo'	=> 'frontpage_display_indigo',
			'black'	=> 'frontpage_display_black'
		  ), NULL, NULL, NULL, 'frontpage_display_highlight' ) ) );
		}
		else
		{
		return \IPS\Member::loggedIn()->language()->addToStack( 'system_frontpage_display_disabled_admin' );
		}

				\IPS\Output::i()->cssFiles	= array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/settings.css' ) );
				
				if ( \IPS\Theme::i()->settings['responsive'] )
				{
					\IPS\Output::i()->cssFiles	= array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/settings_responsive.css' ) );
				}
			
				\IPS\Output::i()->breadcrumb[] 	= array( \IPS\Http\Url::internal( 'app=core&module=system&controller=settings', 'front', 'settings' ), \IPS\Member::loggedIn()->language()->addToStack('settings') );
				\IPS\Output::i()->breadcrumb[] 	= array( NULL, \IPS\Member::loggedIn()->language()->addToStack('frontpage_display_settings_title') );
				\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('frontpage_display_settings_title');


		/* Handle submissions */
		if ( $values = $form->values() )
		{
			
			\IPS\Member::loggedIn()->frontpage_display_fixed = $values['frontpage_display_fixed'];
			\IPS\Member::loggedIn()->frontpage_display_inverse = $values['frontpage_display_inverse'];
			
			\IPS\Member::loggedIn()->save();
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=settings&area=display', 'front', 'settingsDisplay ' ), 'frontpage_display_updated' );

	   }

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system', 'frontpage', 'front' )->settingsDisplay( $form, 'overview' );
   }
}