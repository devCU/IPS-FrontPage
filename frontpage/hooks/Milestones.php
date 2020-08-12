//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class frontpage_hook_Milestones extends _HOOK_CLASS_
{


	/**
	 * Get HTML using the template (language strings not parsed)
	 *
	 * @return	string
	 */
	public function output()
	{
		return parent::output();
	}

}
