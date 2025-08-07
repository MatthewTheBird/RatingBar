<?php
/**
 * Hook into MediaWiki functionality.
 *
 * @file
 */

namespace MediaWiki\Extension\W4G\RatingBar;

class Migrate
{
	static function makeRatingBarDBChanges( DatabaseUpdater $updater )
	{
		global $wgExtNewTables, $wgExtModifiedFields;
		$updater->addExtensionTable( 'w4grb_votes', __DIR__ . '/create_votes.sql' );
		$updater->addExtensionTable( 'w4grb_avg', __DIR__ . '/create_avg.sql' );
		$updater->addExtensionTable( 'w4grb_cat_avg', __DIR__ . '/create_cat_avg.sql' );
		$updater->modifyExtensionField( 'w4grb_votes', 'uid', __DIR__ . '/annon_voting.sql' );
		return true;
	}
}
