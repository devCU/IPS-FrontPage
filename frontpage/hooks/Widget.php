//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

abstract class frontpage_hook_Widget extends _HOOK_CLASS_
{
	/**
	 * Delete caches
	 *
	 * @param	String	$key				Widget key
	 * @param	String	$app				Parent application
	 * @param	String	$plugin				Parent plugin
	 * @return	void
	 */
	static public function deleteCaches( $key=NULL, $app=NULL, $plugin=NULL )
	{
		\IPS\frontpage\Widget::deleteCachesForBlocks( $key, $app, $plugin );
      
		/* Hand over to normal method */
		parent::deleteCaches( $key, $app, $plugin );
	}
}