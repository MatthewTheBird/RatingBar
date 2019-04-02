<?php

/*********************************************************************
**
** This file is part of the W4G Rating Bar extension for MediaWiki
** Copyright (C)2011
**                - David Dernoncourt <www.patheticcockroach.com>
**                - Franck Dernoncourt <www.francky.me>
**
** Home Page : http://www.wiki4games.com/Wiki4Games:W4G Rating Bar
**
** This program is licensed under the Creative Commons
** Attribution-ShareAlike 4.0 license
** <https://creativecommons.org/licenses/by-sa/4.0/>
**
** The attribution part of the license prohibits any unauthorized editing of any line related to
** $wgExtensionCredits['parserhook'][] and $wgExtensionCredits['specialpage'][]
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
**
*********************************************************************/

if (!defined('MEDIAWIKI')) die('This file is part of MediaWiki. It is not a valid entry point.');
 
$dir = dirname(__FILE__) . '/';

/**************
* Options (NB: edit the settings you want to customize in LocalSettings.php)
* $wgW4GRB_Path : path to the extension
* ajax-fresh-data : boolean, whether or not you want to use AJAX to display an uncached rating to visitors. Set to false if you need to restrict database queries (only affects slave databases).
* allow-unoptimized-queries: boolean, whether or not to allow queries which need some extra calculations in MySQL
* anonymous-voting-enabled: boolean, whether or not anonymous (=not logged-in user) voting is enabled
* auto-include: boolean, whether or not to automatically include the rating bar ON EVERY PAGE
* default-items-per-list: default amount of items that will be displayed in a top list (NB: this CAN be higher than max-items-per-list by design)
* fix-spaces: boolean, whether or not you want to enable replacing spaces with underscore in user page name input, so that spaces can be used (otherwise the user must write underscores)
* max-bars-per-page: maximum amount of bars that can be included within one page (this doesn't count the auto-included bar)
* max-items-per-list: maximum amount of items that can be displayed in a top list
* max-lists-per-page: maximum amount of pages that can be displayed in a single page
* multivote-cooldown: time, in seconds, to consider that the same IP = the same person to prevent multivote
* category-cache-time: time, in seconds, before updating the category average
* show-mismatching-bar: boolean, whether or not to display the name of the page being voted on if it's not the same as the page on which the bar is displayed
* show-voter-names: boolean, whether or not it's possible to publicly link user names to their ratings
***************/
$wgW4GRB_Path = '/extensions/RatingBar';
$wgW4GRB_Settings = array (
	'ajax-fresh-data' => true,
	'allow-unoptimized-queries' => true,
	'anonymous-voting-enabled' => false,
	'auto-include' => false,
	'default-items-per-list' => 10,
	'fix-spaces' => true,
	'max-bars-per-page' => 2,
	'max-items-per-list' => 100,
	'max-lists-per-page' => 10,
	'multivote-cooldown' => 3600*24*7,
	'category-cache-time' => 3600*24*7,
	'show-mismatching-bar' => true,
	'show-voter-names' => false );

/**************
* End of options
***************/

$wgExtensionMessagesFiles['w4g_rb'] = $dir . 'w4g_rb.i18n.php';
$wgExtensionAliasesFiles['w4g_rb'] = $dir . 'w4g_rb.alias.php';
$wgAutoloadClasses['W4GRB'] = $dir . 'SpecialW4GRB_body.php';
$wgSpecialPages['W4GRB'] = "W4GRB";
$wgAutoloadClasses['W4GRBPage'] = $dir . 'w4g_rb-page.class.php';

/*********************************************************************
* License restriction: ABSOLUTELY DO NOT EDIT THE FOLLOWING ATTRIBUTION LINES
* (and by "do not edit" we mean "do not edit", not even a single byte)
*********************************************************************/
$wgExtensionCredits['parserhook'][] = array(
	'path' => __FILE__,
	'name' => 'W4G Rating Bar',
	'author' => array(	'[http://www.patheticcockroach.com David Dernoncourt]',
						'[http://www.francky.me Franck Dernoncourt]', 'Reddo'), 
	'url' => 'http://www.wiki4games.com/Wiki4Games:W4G_Rating_Bar',
	'descriptionmsg' => 'w4g_rb-desc-hook',
	'version' => '2.2',
);

$wgHooks['LoadExtensionSchemaUpdates'][] = 'W4G::makeRatingBarDBChanges';
$wgHooks['ParserFirstCallInit'][] = 'W4G::W4GrbSetup'; # Setup function
$wgHooks['LanguageGetMagic'][]    = 'W4G::W4GrbMagic'; # Initialise magic words
$wgHooks['BeforePageDisplay'][] = 'W4G::W4GrbAutoShow'; # Setup function


class W4G {
	static function makeRatingBarDBChanges( DatabaseUpdater $updater ) {
		global $wgExtNewTables, $wgExtModifiedFields;
		$updater->addExtensionTable( 'w4grb_votes', __DIR__ . '/create_votes.sql' );
		$updater->addExtensionTable( 'w4grb_avg', __DIR__ . '/create_avg.sql' );
		$updater->addExtensionTable( 'w4grb_cat_avg', __DIR__ . '/create_cat_avg.sql' );
		$updater->modifyExtensionField( 'w4grb_votes', 'uid', __DIR__ . '/annon_voting.sql' );
		return true;
	}
	
	# Setup function
	static function W4GrbSetup ( &$parser )
	{
		# Function hook associating the magic word with its function
		$parser->setFunctionHook( 'w4grb_rate', 'W4GrbShowRatingBar' );
		$parser->setFunctionHook( 'w4grb_rawrating', 'W4GrbShowRawRating' );
		$parser->setFunctionHook( 'w4grb_cat_rating', 'W4GrbShowCatRating' );
		# Tag hook for the toplist
		$parser->setHook( 'w4grb_ratinglist', 'W4GrbShowRatingList' );
		return true;
	}
	# Initialise magic words
	static function W4GrbMagic ( &$magicWords, $langCode = 'en' )
	{
		# The first array element is whether to be case sensitive, in this case (0) it is not case sensitive, 1 would be sensitive
		# All remaining elements are synonyms for our parser function
		$magicWords['w4grb_rate'] = array( 1, 'w4grb_rate' );
		$magicWords['w4grb_rawrating'] = array( 1, 'w4grb_rawrating' );
		$magicWords['w4grb_cat_rating'] = array( 1, 'w4grb_cat_rating' );
		return true; # just needed
	}
	/**
	* To include the rating bar on every page if auto-include is true
	**/
	static function W4GrbAutoShow(&$out, &$sk)
	{
		# $out is of class OutpuPage (includes/OutputPage.php
		global $wgW4GRB_Settings;
		if(!$wgW4GRB_Settings['auto-include']) return true;
		
		global $wgW4GRB_Path;
		global $wgScriptPath;
		# Add JS and CSS
		$out->addHeadItem('w4g_rb.css','<link rel="stylesheet" type="text/css" href="'.$wgScriptPath.$wgW4GRB_Path.'/w4g_rb.css"/>');
		$out->addHeadItem('w4g_rb.js','<script type="text/javascript" src="'.$wgScriptPath.$wgW4GRB_Path.'/w4g_rb.js"></script>');
		
		$page_obj=new W4GRBPage();
		if(!$page_obj->setFullPageName($out->getTitle()))
			return true;
		
		$out->addHTML(W4GrbGetBarBase($page_obj,$wgW4GRB_Settings['max-bars-per-page']+1));
		# global $W4GRB_ratingbar_count; no can access this one for some reason... we'll have to default to max number + 1
		# $out->addHTML('arff'.$W4GRB_ratingbar_count.get_class($out)); # that was for debugging
		return true;
	}
}

// Permissions
$wgGroupPermissions['*']['w4g_rb-canvote'] = false;
$wgGroupPermissions['user']['w4g_rb-canvote'] = true;
$wgGroupPermissions['autoconfirmed']['w4g_rb-canvote'] = true;
$wgGroupPermissions['bot']['w4g_rb-canvote'] = false;
$wgGroupPermissions['sysop']['w4g_rb-canvote'] = true;
$wgGroupPermissions['bureaucrat']['w4g_rb-canvote'] = true;
$wgAvailableRights[] = 'w4g_rb-canvote';


/**
* To include the rating bar when called by {{#w4grb_rate:[full page name]}}
**/
function W4GrbShowRatingBar ( $parser, $fullpagename = '' )
{
	global $W4GRB_ratingbar_count, $wgW4GRB_Settings;
	if(is_int($W4GRB_ratingbar_count))
		{
		if ($W4GRB_ratingbar_count>=$wgW4GRB_Settings['max-bars-per-page'])
			return array('<span class="w4g_rb-error">'.wfMessage('w4g_rb-error_exceeded_max_bars',$wgW4GRB_Settings['max-bars-per-page']).'.</br></span>', 'noparse' => true, 'isHTML' => true);
		else $W4GRB_ratingbar_count++;
		}
	else if($wgW4GRB_Settings['max-bars-per-page']>0) $W4GRB_ratingbar_count=1;
	else return array('<span class="w4g_rb-error">'.wfMessage('w4g_rb-error_no_bar_allowed').'.</br></span>', 'noparse' => true, 'isHTML' => true);
	
	# Get neeeded globals
	global $wgScriptPath;
	global $wgW4GRB_Path;
	
	# Initialize needed variables
	$output = '';
	
	# Add JS and CSS if not added by W4GrbAutoShow
	if(!$wgW4GRB_Settings['auto-include'] && $W4GRB_ratingbar_count<=1)
	{
	$parser->mOutput->addHeadItem('<link rel="stylesheet" type="text/css" href="'.$wgScriptPath.$wgW4GRB_Path.'/w4g_rb.css"/>');
	$parser->mOutput->addHeadItem('<script type="text/javascript" src="'.$wgScriptPath.$wgW4GRB_Path.'/w4g_rb.js"></script>');
	}
	
	$showTitle=true;
	# Get textual page id
	if($fullpagename == '') {
		$fullpagename = $parser->getTitle();
		$showTitle = false; # no need to show title, we're sure the page where the bar is is the same as the page we're rating
	}
	
	$page_obj=new W4GRBPage();
	if(!$page_obj->setFullPageName($fullpagename))
		{
		$parser->disableCache();
		return array('<span class="w4g_rb-error">'.wfMessage('w4g_rb-no_page_with_this_name',$fullpagename).'</br></span>', 'noparse' => true, 'isHTML' => true);
		}
	
	if($showTitle) {
		$page_obj2=new W4GRBPage();
		$page_obj2->setFullPageName($parser->getTitle());
		if($page_obj->getFullPageName()==$page_obj2->getFullPageName()) $showTitle=false;
	}
	$output .= W4GrbGetBarBase($page_obj,$W4GRB_ratingbar_count,$showTitle);
	
	# With this the stuff won't get parsed (otherwise it's treated as wikitext)
	return array($output, 'noparse' => true, 'isHTML' => true);
}

function W4GrbShowRatingList ( $input, $argv, $parser, $frame )
{
	global $W4GRB_ratinglist_count, $wgW4GRB_Settings;
	$hidevotecount = false;
	if(is_int($W4GRB_ratinglist_count))
		{
		if($W4GRB_ratinglist_count>=$wgW4GRB_Settings['max-lists-per-page'])
			return '<span class="w4g_ratinglist-error">'.wfMessage('w4g_rb-error_exceeded_max_lists',$wgW4GRB_Settings['max-lists-per-page']).'.</span><br/>';
		else $W4GRB_ratinglist_count++;
		}
	else if($wgW4GRB_Settings['max-lists-per-page']>0) $W4GRB_ratinglist_count=1;
	else return '<span class="w4g_ratinglist-error">'.wfMessage('w4g_rb-error_no_list_allowed').'.</span><br/>';
	
	# Get neeeded globals
	global $wgScriptPath, $wgDBprefix;
	global $wgW4GRB_Path;
	
	# Possible types: toppages, topvoters, uservotes, pagevotes, latestvotes
	
	# If notitle is set the user doesn't want to display titles
	$displaytitle = !isset($argv['notitle']);
	
	# If nosort is set the user doesn't want a sortable table 
	$sortable = isset($argv['nosort']) ? '' : 'sortable';
	
	# Get max number of items
	if(isset($argv['items']))
		{
		$max_items=intval($argv['items']);
		if($max_items>$wgW4GRB_Settings['max-items-per-list']) $max_items=$wgW4GRB_Settings['max-items-per-list'];
		if($max_items<=1) $max_items=$wgW4GRB_Settings['default-items-per-list'];
		}
	else $max_items=$wgW4GRB_Settings['default-items-per-list'];
	
	# Get offset
	if(isset($argv['offset']) && $argv['offset']>0)
		$skippy = intval($argv['offset']);
	else $skippy = 0;
	
	# Get orderby - possible values: rating
	if(isset($argv['orderby']) && in_array($argv['orderby'],array('rating')))
		$orderby = $argv['orderby'];
	else $orderby='';
	
	# Get order - possible values: asc, desc
	if(isset($argv['order']) && in_array($argv['order'],array('asc','desc')))
		$order = $argv['order'];
	else $order='';
	
	# Get category
	if(isset($argv['category']))
		$category = $wgW4GRB_Settings['fix-spaces'] ? str_replace(" ","_",$argv['category']) : $argv['category'];
	else $category='';
	
	$days = '';
	
	# Get period (in days) and convert it into the timestamp of the beginning of that period
	if(isset($argv['days']) && $argv['days']>0)
		{
		$days=intval($argv['days']);
		$starttime = time() - ($days * 24 * 3600 );
		}
	else $starttime = 0;
	

	/* To display latest votes.
	**/
	if(isset($argv['latestvotes']))
	{
		$dbslave = wfGetDB( DB_SLAVE );
		$where_filter = array('w4grb_votes.uid=user.user_id','w4grb_votes.pid=page.page_id','w4grb_votes.time>'.$starttime);
		$database_filter = $wgDBprefix.'w4grb_votes AS w4grb_votes, '.$wgDBprefix.'user AS user, '.$wgDBprefix.'page AS page';
		if($category!='')
			{
			$where_filter = array_merge($where_filter,array('catlink.cl_from=w4grb_votes.pid','catlink.cl_to="'.$category.'"'));
			$database_filter .= ', '.$wgDBprefix.'categorylinks AS catlink';
			}
		$result=$dbslave->select(
				$database_filter,
				'w4grb_votes.vote AS vote, w4grb_votes.uid AS uid, w4grb_votes.time AS time, user.user_name AS uname, page.page_namespace AS ns, page.page_title AS title',
				$where_filter,
				__METHOD__,
				array('ORDER BY' => 'w4grb_votes.time DESC', 'LIMIT' => $max_items, 'OFFSET' => $skippy)
				);
		$out = '<table class="w4g_rb-ratinglist-table '.$sortable.'" >'
			. ($displaytitle? '<caption>'
						.wfMessage('w4g_rb-latest-votes',
							(($category!='') ? wfMessage('w4g_rb-votes-in-cat',htmlspecialchars($category)) : ''),
							$max_items,
							(is_int($days)? wfMessage('w4g_rb-votes-in-days',$days) : ''))
						.'</caption>' : '')
			.'<tr>'
			.'<th>'.wfMessage('w4g_rb-time').'</th>'
			.'<th>'.wfMessage('w4g_rb-page').'</th>'
			.'<th>'.wfMessage('w4g_rb-rating').'</th>'
			.'<th>'.wfMessage('w4g_rb-user').'</th>'
			.'</tr>';
			while($row = $dbslave->fetchObject($result))
			{
			$out .= '<tr>'
				.'<td>'.date("F j, Y, g:i a",($row->time)).'</td>'
				.'<td>'.W4GrbMakeLinkPage($row->ns, $row->title).'</td>'
				.'<td>'.$row->vote.'%</td>'
				. ($wgW4GRB_Settings['show-voter-names']? '<td>'.W4GrbMakeLinkUser($row->uid, $row->uname).'</td>' : '<td>'.wfMessage('w4g_rb-hidden_name').'</td>')
				.'</tr>';
			}
		$out .= "</table>";
		$dbslave->freeResult($result);
		unset($dbslave);
		return $out;
	}
	
	/* To display the votes for one page.
	**/
	if(isset($argv['pagevotes']))
	{
		if(isset($argv['idpage'])) $fullpagename = $argv['idpage'];
			else $fullpagename = $parser->getTitle();
		$page_obj=new W4GRBPage();
		if(!$page_obj->setFullPageName($fullpagename))
			return '<span class="w4g_rb-error">'.wfMessage('w4g_rb-no_page_with_this_name',htmlspecialchars($argv['idpage'])).'</br></span>';
		
		$dbslave = wfGetDB( DB_SLAVE );
		$result=$dbslave->select(
				$wgDBprefix.'w4grb_votes AS w4grb_votes, '.$wgDBprefix.'user AS user',
				'w4grb_votes.vote AS vote, w4grb_votes.uid AS uid, w4grb_votes.time AS time, user.user_name AS uname',
				array('w4grb_votes.uid=user.user_id','w4grb_votes.pid='.$page_obj->getPID(),'w4grb_votes.time>'.$starttime),
				__METHOD__,
				array('ORDER BY' => 'w4grb_votes.time DESC', 'LIMIT' => $max_items, 'OFFSET' => $skippy)
				);
		$out .= '<table class="w4g_rb-ratinglist-table '.$sortable.'" >'
			. ($displaytitle? '<caption>'
						.wfMessage('w4g_rb-caption-pagevotes',
							W4GrbMakeLinkPage($page_obj->getNsID(), $page_obj->getFullPageName()),
							$max_items,
							(is_int($days)? wfMessage('w4g_rb-votes-in-days',$days) : ''))
						.'</caption>' : '')
			.'<tr>'
			.'<th>'.wfMessage('w4g_rb-time').'</th>'
			.'<th>'.wfMessage('w4g_rb-rating').'</th>'
			.'<th>'.wfMessage('w4g_rb-user').'</th>'
			.'</tr>';
		while($row = $dbslave->fetchObject($result))
			{
			$out .= '<tr>'
				.'<td>'.date("F j, Y, g:i a",($row->time)).'</td>'
				.'<td>'.$row->vote.'%</td>'
				. ($wgW4GRB_Settings['show-voter-names']? '<td>'.W4GrbMakeLinkUser($row->uid, $row->uname).'</td>' : '<td>'.wfMessage('w4g_rb-hidden_name').'</td>')
				.'</tr>';
			}
		$out .= "</table>";
		$dbslave->freeResult($result);
		unset($dbslave);
		return $out;
	}
	
	/* To display votes by a user
	**/
	if(isset($argv['uservotes']))
	{
		if(!$wgW4GRB_Settings['show-voter-names']) return '<span class="w4g_rb-error">'.wfMessage('w4g_rb-error_function_disabled','w4g_ratinglist->uservotes').'<br/></span>';
		if(!isset($argv['user']) || $argv['user']=='') return '<span class="w4g_rb-error">'.wfMessage('w4g_rb-error_missing_param','<i>user</i>').'<br/></span>';
		$user = $wgW4GRB_Settings['fix-spaces'] ? str_replace("_"," ",$argv['user']) : $argv['user'];
		if(is_null(User::idFromName($user))) return '<span class="w4g_rb-error">'.wfMessage('w4g_rb-no_user_with_this_name',htmlspecialchars($user)).'</br></span>';
		
		$dbslave = wfGetDB( DB_SLAVE );
		$where_filter = array('w4grb_votes.uid=user.user_id','w4grb_votes.pid=page.page_id','w4grb_votes.time>'.$starttime,'user.user_name="'.$user.'"');
		$database_filter = $wgDBprefix.'w4grb_votes AS w4grb_votes, '.$wgDBprefix.'user AS user, '.$wgDBprefix.'page AS page';
		if($category!='')
			{
			$where_filter = array_merge($where_filter,array('catlink.cl_from=w4grb_votes.pid','catlink.cl_to="'.$category.'"') );
			$database_filter .= ', '.$wgDBprefix.'categorylinks AS catlink';
			}
		$orderby_field = 'w4grb_votes.time';
		if($orderby=='rating') $orderby_field = 'w4grb_votes.vote';
			
		$result=$dbslave->select(
				$database_filter,
				'w4grb_votes.pid, w4grb_votes.vote AS vote, w4grb_votes.uid AS uid, w4grb_votes.time AS time, user.user_name AS uname, page.page_namespace AS ns, page.page_title AS title',
				$where_filter,
				__METHOD__,
				array('ORDER BY' => $orderby_field.' '. (($order!='')?$order:'DESC'), 'LIMIT' => $max_items, 'OFFSET' => $skippy)
				);
		
		$out = '<table class="w4g_rb-ratinglist-table '.$sortable.'" >'
			. ($displaytitle? '<caption>'
						.wfMessage('w4g_rb-caption-user-votes',
							W4GrbMakeLinkUser(User::idFromName($user), $user),
							(($category!='') ? wfMessage('w4g_rb-votes-in-cat',htmlspecialchars($category)) : ''),
							$max_items,
							(is_int($days)? wfMessage('w4g_rb-votes-in-days',$days) : ''))->plain()
						.'</caption>' : '')
			.'<tr>'
			.'<th>'.wfMessage('w4g_rb-page').'</th>'
			.'<th>'.wfMessage('w4g_rb-rating').'</th>'
			.'<th>'.wfMessage('w4g_rb-time').'</th>'
			.'</tr>';
		while($row = $dbslave->fetchObject($result))
			{
			$out .= '<tr>'
				.'<td>'.W4GrbMakeLinkPage($row->ns, $row->title).'</td>'
				.'<td>'.$row->vote.'%</td>'
				.'<td>'.date("F j, Y, g:i a",($row->time)).'</td>'
				.'</tr>';
			}
		$out .= "</table>";
		$dbslave->freeResult($result);
		unset($dbslave);
		return $out;
	}
	
	/* To display top rated pages
	**/
	if(isset($argv['toppages']))
	{
		$out = "";
		# Minimum number of votes to include the page in the toplist
		if(isset($argv['minvotecount']) && $argv['minvotecount']>1)
			$minvotecount = intval($argv['minvotecount']);
		else $minvotecount = 1;
		
		# If hidevotecount is set the user doesn't want to display the number of votes
		$hidevotecount = isset($argv['hidevotecount']);
		
		$sortOrder = (isset($argv['order']) && $argv['order'] == 'ASC') ? 'ASC'  : 'DESC';
		
		# If hideavgrating is set the user doesn't want to display the average rating
		$hideavgrating = isset($argv['hideavgrating']);
		
		# If topvotecount is set we want to sort by vote count instead of rating
		$topvotecount = isset($argv['topvotecount']);
		
		$dbslave = wfGetDB( DB_SLAVE );
		
		# Choose what kind of query to do: simple or with more calculations
		if(!$wgW4GRB_Settings['allow-unoptimized-queries']
			|| $starttime==0)
			{
			if($topvotecount) $top_filter = 'w4grb_avg.n '.$sortOrder;
			else $top_filter = 'w4grb_avg.avg '.$sortOrder;
			
			$where_filter = array('w4grb_avg.pid=page.page_id','w4grb_avg.n>='.$minvotecount);
			$database_filter = $wgDBprefix.'w4grb_avg AS w4grb_avg, '.$wgDBprefix.'page AS page';
			if($category!='')
				{
				$where_filter = array_merge($where_filter,array('catlink.cl_from=w4grb_avg.pid','catlink.cl_to="'.$category.'"'));
				$database_filter .= ', '.$wgDBprefix.'categorylinks AS catlink';
				}
			$result=$dbslave->select(
					$database_filter,
					'w4grb_avg.avg AS avg, w4grb_avg.n AS n, page.page_namespace AS ns, page.page_title AS title',
					$where_filter,
					__METHOD__,
					array('ORDER BY' => $top_filter, 'LIMIT' => $max_items, 'OFFSET' => $skippy)
					);
			}
		else
			{
			if($topvotecount) $top_filter = 'COUNT(*) '.$sortOrder;
			else $top_filter = 'AVG(w4grb_votes.vote) '.$sortOrder;
			
			$where_filter = array('w4grb_votes.pid=page.page_id','w4grb_votes.time>'.$starttime);
			$database_filter = $wgDBprefix.'w4grb_votes AS w4grb_votes, '.$wgDBprefix.'page AS page';
			if($category!='')
				{
				$where_filter = array_merge($where_filter,array('catlink.cl_from=w4grb_avg.pid','catlink.cl_to="'.$category.'"'));
				$database_filter .= ', '.$wgDBprefix.'categorylinks AS catlink';
				}
			$result=$dbslave->select(
					$database_filter,
					'AVG(w4grb_votes.vote) AS avg, COUNT(*) AS n, page.page_namespace AS ns, page.page_title AS title',
					$where_filter,
					__METHOD__,
					array('GROUP BY' => 'page.page_id', 'HAVING' => 'COUNT(*)>='.$minvotecount, 'ORDER BY' => $top_filter, 'LIMIT' => $max_items, 'OFFSET' => $skippy)
					);
			}
		if ($sortOrder == 'ASC'){
		$out .= '<table class="w4g_rb-ratinglist-table '.$sortable.'" >'. ($displaytitle? '<caption>'
			.wfMessage('w4g_rb-caption-bottom-pages',
				($topvotecount ? wfMessage('w4g_rb-amount-of-votes') : wfMessage('w4g_rb-average-rating')),
				(($category!='') ? wfMessage('w4g_rb-votes-in-cat',str_replace('_', ' ', htmlspecialchars($category))) : ''),
				$max_items,
				(($minvotecount>1) ? wfMessage('w4g_rb-with-at-least-x-votes',$minvotecount) : ''),
				(is_int($days)? wfMessage('w4g_rb-votes-in-days',$days) : ''))	
				.'</caption>' : '');
		} else {
		$out .= '<table class="w4g_rb-ratinglist-table '.$sortable.'" >'. ($displaytitle? '<caption>'
			.wfMessage('w4g_rb-caption-toppages',
				($topvotecount ? wfMessage('w4g_rb-amount-of-votes') : wfMessage('w4g_rb-average-rating')),
				(($category!='') ? wfMessage('w4g_rb-votes-in-cat',str_replace('_', ' ', htmlspecialchars($category))) : ''),
				$max_items,
				(($minvotecount>1) ? wfMessage('w4g_rb-with-at-least-x-votes',$minvotecount) : ''),
				(is_int($days)? wfMessage('w4g_rb-votes-in-days',$days) : ''))			
				.'</caption>' : '');
		}
			$out .= '<tr>'
			.'<th>'.wfMessage('w4g_rb-page').'</th>'
			.($hideavgrating? '' : '<th>'.wfMessage('w4g_rb-rating').'</th>')
			.($hidevotecount? '' : '<th>'.wfMessage('w4g_rb-vote-count').'</th>')
			.'</tr>';
		while($row = $dbslave->fetchObject($result))
			{
			$out .= '<tr>'
				.'<td>'.W4GrbMakeLinkPage($row->ns, $row->title).'</td>'
				.($hideavgrating? '' : '<td>'.round($row->avg,2).'%</td>')
				.($hidevotecount? '' : '<td>'.$row->n.'</td>')
				.'</tr>';
			}
		$out .= "</table>";
		$dbslave->freeResult($result);
		unset($dbslave);
		return $out;
	}
	
	/* To display top voters
	**/
	if(isset($argv['topvoters']) && $wgW4GRB_Settings['allow-unoptimized-queries'])
	{
		$hidevotecount = false;
		$dbslave = wfGetDB( DB_SLAVE );
		$where_filter = array('w4grb_votes.uid=user.user_id','w4grb_votes.time>'.$starttime);
		$database_filter = $wgDBprefix.'w4grb_votes AS w4grb_votes, '.$wgDBprefix.'user AS user';
		if($category!='')
			{
			$where_filter = array_merge($where_filter,array('catlink.cl_from=w4grb_votes.pid','catlink.cl_to="'.$category.'"'));
			$database_filter .= ', '.$wgDBprefix.'categorylinks AS catlink';
			}
		$result=$dbslave->select(
				$database_filter,
				'AVG(w4grb_votes.vote) AS avg, COUNT(*) AS n, user.user_name AS uname, user.user_id AS uid',
				$where_filter,
				__METHOD__,
				array('GROUP BY' => 'user.user_id', 'ORDER BY' => 'COUNT(*) DESC', 'LIMIT' => $max_items, 'OFFSET' => $skippy)
				);
		
		$out = '<table class="w4g_rb-ratinglist-table '.$sortable.'" >'
			. ($displaytitle? '<caption>'
						.wfMessage('w4g_rb-caption-topvoters',
							(is_int($days)? wfMessage('w4g_rb-votes-in-days',$days) : ''),
							(($category!='') ? wfMessage('w4g_rb-votes-in-cat',htmlspecialchars($category)) : ''),
							$max_items)
						.'</caption>' : '')
			.'<tr>'
			.'<th>'.wfMessage('w4g_rb-user').'</th>'
			.($wgW4GRB_Settings['show-voter-names']? '<th>'.wfMessage('w4g_rb-average').'</th>' : '')
			.($hidevotecount? '' : '<th>'.wfMessage('w4g_rb-vote-count').'</th>')
			.'</tr>';
		while($row = $dbslave->fetchObject($result))
			{
			$out .= '<tr>'
				.'<td>'.W4GrbMakeLinkUser($row->uid, $row->uname).'</td>'
				.($wgW4GRB_Settings['show-voter-names']? '<td>'.round($row->avg).'%</td>' : '')
				.($hidevotecount? '' : '<td>'.$row->n.'</td>')
				.'</tr>';
			}
		$out .= "</table>";
		$dbslave->freeResult($result);
		unset($dbslave);
		return $out;
	}

	return wfMessage('w4g_rb-error_syntax_check_doc','<a href="http://www.wiki4games.com/Wiki4Games:W4G Rating Bar">','</a>');
}

function W4GrbShowCatRating ( $parser, $category = '', $votes = '' ){
	global $wgDBprefix;
	global $wgW4GRB_Settings;
	$out;
	$update = false;
	$insert = false;

	if($category == '') $category = $parser->getTitle()->getBaseText();
	$category = addslashes(str_replace(' ', '_', $category));

	$dbmaster = wfGetDB( DB_MASTER );
	
	$where_filter = array('w4grb_cat_avg.page="'.$category.'"');
	$database_filter = $wgDBprefix.'w4grb_cat_avg AS w4grb_cat_avg';
	
	$result=$dbmaster->selectRow(
		$database_filter,
		'avg, n, time',
		$where_filter,
		__METHOD__,
		array()
	);

	//data found in db
	if($result){
		//check if the current data is old
		$time =  $result->time;
		$diff = time() - $time;
		
		if ($wgW4GRB_Settings['category-cache-time'] < $diff){
			$update = true;
		}
		//if it's not old let's store it and move on
		else {
			$avg = round($result->avg,1);
			$count = $result->n;
		}
	}
	//data not found in db
	else {
		$insert = true;
	}
	//get new data
	if ($update || $insert){
		$where_filter = array('w4grb_avg.pid=page.page_id','w4grb_avg.n>0');
		$where_filter = array_merge($where_filter,array('catlink.cl_from=w4grb_avg.pid','catlink.cl_to="'.$category.'"'));

		$database_filter = $wgDBprefix.'w4grb_avg AS w4grb_avg, '.$wgDBprefix.'page AS page, '.$wgDBprefix.'categorylinks AS catlink';

		$row=$dbmaster->selectRow(
			$database_filter,
			'AVG(w4grb_avg.avg) AS avg, SUM(w4grb_avg.n) AS n',
			$where_filter,
			__METHOD__,
			array()
		);
		
		$avg = round($row->avg,1);
		$count = $row->n;
		if ($count == null) $count = 0;
		
		//insert the new data into the table
		if ($insert){
				$dbmaster->insert('w4grb_cat_avg',
				array(	'page' => $category,
						'avg' => $avg,
						'n' => $count,
						'time' => time()),
				__METHOD__ ) or die('Category average instert failed!');
		}
		//update an existing row
		if ($update){
			if (!$dbmaster->update('w4grb_cat_avg',
				array(	'avg' => $avg,
						'n' => $count,
						'time' => time()),
				array(	'page' => $category),
				__METHOD__ ))
			echo 'Category average update failed!';
		}
	}

	//set out to the data requested
	$out = $avg;
	if ($votes == 'votes'){
		$out = $count;
	}
	unset($dbmaster);
	return $out;
}

function W4GrbShowRawRating ( $parser, $fullpagename = '', $type = '' )
{
	$output = '';
	if(!in_array($type, array('avg','n'))) $type = 'avg';
	
	# Get textual page id
	if($fullpagename == '')
		$fullpagename = $parser->getTitle();
		
	$page_obj=new W4GRBPage();
	if(!$page_obj->setFullPageName($fullpagename))
		return array('<span class="w4g_rb-error">'.wfMessage('w4g_rb-no_page_with_this_name',$fullpagename).'</br></span>', 'noparse' => true, 'isHTML' => true);
	
	if($type=='avg') $output = $page_obj->getAVG();
	else if ($type=='n') $output = $page_obj->getNVotes();
	return $output;
}

/**
* Will return all the HTML needed to display a rating bar (not the headers, though)
* @ $page_obj: the page, as a W4GRBPage object
* @ $bid: the numerical ID of the bar (for multiple bars on a same page)
* @ $showTitle: boolean, whether or not to display the name of the page being voted on (this will
*	be used only when the page where the bar is displayed isn't the same as the page being rated)
**/
function W4GrbGetBarBase ( W4GRBPage $page_obj, $bid, $showTitle=false )
{
	global $wgScriptPath;
	global $wgW4GRB_Settings;
	$output = '';
	
	# If we need to show the page title (and if settings agree)
	if($showTitle && $wgW4GRB_Settings['show-mismatching-bar'])
		$output .= '<span class="w4g_rb-rating-page-named">'
		.wfMessage('w4g_rb-rating_page_named',W4GrbMakeLinkPage($page_obj->getNsID(), $page_obj->getFullPageName()))
		.'</span><br/>';

	# Start AJAX area
	$output .= '<span id="w4g_rb_area-'.$bid.'">';
	
	# Get current rating
	if($page_obj->getNVotes()>0)
		{
		$average_rating = $page_obj->getAVG();
		$num_votes = $page_obj->getNVotes();
		$output .= wfMessage('w4g_rb-current_user_rating','<b>'.$average_rating.'/100</b>',$num_votes);
		}
	else
		{
		$average_rating = 0;
		$num_votes = 0;
		$output .= wfMessage('w4g_rb-nobody_voted').'<br/>';
		}
	
	# Close AJAX area
	$output .= '</span>';
	
	# The bar in JavaScript - variable preparation
	$output .= '
<script type="text/javascript">
//<![CDATA[';
	# Our namespace, for all JS globals - only defined on first bar - +JS fix to avoid double declaration in case of auto-include combined with an inline bar
	if($bid==1 || ($wgW4GRB_Settings['auto-include'] && $bid==$wgW4GRB_Settings['max-bars-per-page']+1)) $output .='
if(typeof(W4GRB)=="undefined")
{
window.W4GRB = new Object();
W4GRB.average_rating=new Array();
W4GRB.user_rating=new Array();
W4GRB.pid=new Array();
W4GRB.query_url=new Array();
}';
	$output .='
W4GRB.average_rating['.$bid.']='.$average_rating.';
W4GRB.user_rating['.$bid.']=0;
W4GRB.pid['.$bid.']='.$page_obj->getPID().';
W4GRB.query_url['.$bid.']="'.$wgScriptPath.'/index.php?title='.SpecialPage::getTitleFor('W4GRB').'\x26bid='.$bid.'\x26pid="+W4GRB.pid['.$bid.'];';
	# Only if we want the AJAX refresh
	if($wgW4GRB_Settings['ajax-fresh-data']) $output .='
query2page(W4GRB.query_url['.$bid.'],"w4g_rb_area-'.$bid.'",2,'.$bid.');';
	$output .='
//]]>
</script>';

	# Now the bar itself - leave the first and last line breaks or the bloody parser will cause trouble
	$output .='
<div class="rating_box" id="rating_box-'.$bid.'">
<div class="rating_target" id="rating_target-'.$bid.'" onmouseout="updatebox(\''.$bid.'\',W4GRB.user_rating['.$bid.'])">
<div class="w4g_rb_nojs">&nbsp;'.wfMessage('w4g_rb-error_need_js').'</div>
</div>
<div class="rating_text"><div class="rating_text_text" id="rating_text-'.$bid.'"></div></div>
</div>
<script type="text/javascript">
//<![CDATA[
loadbox('.$bid.');
updatebox('.$bid.',W4GRB.average_rating['.$bid.']);
//]]>
</script>
';

	return $output;
}

// Make a link to another page of the wiki using MediaWiki's API
function W4GrbMakeLinkPage( $page_namespace, $page_title, $safe=false )
{	
	if(!$safe)
		$link = Linker::link(Title::makeTitle($page_namespace, $page_title));
	else
		{
		$title = Title::makeTitleSafe($page_namespace, $page_title);
		if(!is_null($title))
			$link = Linker::link($title);
		else
			$link = "There is no <i>" . htmlspecialchars( $page_title ) . "</i> in namespace <i>" . htmlspecialchars( $page_namespace )."</i>";
		}
	return $link;
}

// Make a link to a user page of the wiki using MediaWiki's API
function W4GrbMakeLinkUser( $user_id, $user_name)
{
	if($user_id==0) return wfMessage('w4g_anonymous'); // deals with user ID 0 (=Anonymous)
	$link = Linker::link(Title::makeTitle(NS_USER, $user_name), $user_name);
	return $link; 
}