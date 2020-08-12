//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class frontpage_hook_RedirectUrl extends _HOOK_CLASS_
{

	/**
	 * Process the login - set the session data, and send required cookies
	 *
	 * @return	void
	 */
	public function process()
	{
		try
		{
			parent::process();
	        if ( \IPS\Settings::i()->frontpage_redirect_url != '' && \IPS\Settings::i()->frontpage_redirect_enable )
	      {
	        \IPS\Output::i()->redirect( \IPS\Settings::i()->frontpage_redirect_url );
	      }
	      
	      
		}
		catch ( \RuntimeException $e )
		{
			if ( method_exists( get_parent_class(), __FUNCTION__ ) )
			{
				return call_user_func_array( 'parent::' . __FUNCTION__, func_get_args() );
			}
			else
			{
				throw $e;
			}
		}
	}

}