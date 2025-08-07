<?php
/**
 * Hook into MediaWiki functionality.
 *
 * @file
 */

namespace MediaWiki\Extension\W4G\RatingBar;

class RatingBar
{

	# Initialise magic words
	static function W4GrbMagic ( &$magicWords, $langCode = 'en' )
	{
		# The first array element is whether to be case sensitive, in this case (0) it is not case sensitive, 1 would be sensitive
		# All remaining elements are synonyms for our parser function
		$magicWords['w4grb_rate'] = array( 1, 'w4grb_rate' );
		$magicWords['en'] = [
			'w4grb_rate' => [1, 'w4grb_rate'],
			'w4grb_rawrating' => [1, 'w4grb_rawrating'],
			'w4grb_cat_rating' => [1, 'w4grb_cat_rating'],
		];
		$magicWords['w4grb_rawrating'] = array( 1, 'w4grb_rawrating' );
		$magicWords['w4grb_cat_rating'] = array( 1, 'w4grb_cat_rating' );
		return true; # just needed
	}

	# Setup function
	static function W4GrbSetup ( &$parser )
	{
		$class = RatingBarFun::class;
		# Function hook associating the magic word with its function
		$parser->setFunctionHook( 'w4grb_rate', "W4GrbShowRatingBar" );
		$parser->setFunctionHook( 'w4grb_rawrating', "W4GrbShowRawRating" );
		$parser->setFunctionHook( 'w4grb_cat_rating', "W4GrbShowCatRating" );
		# Tag hook for the toplist
		$parser->setHook( 'w4grb_ratinglist', 'W4GrbShowRatingList' );
		return true;
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
			$dbslave = wfGetDB( DB_REPLICA );
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

			$dbslave = wfGetDB( DB_REPLICA );
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

			$dbslave = wfGetDB( DB_REPLICA );
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

			$dbslave = wfGetDB( DB_REPLICA );

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
			$dbslave = wfGetDB( DB_REPLICA );
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

	function W4GrbShowCatRating ( $parser, $category = '', $votes = '' )
	{
		global $wgDBprefix;
		global $wgW4GRB_Settings;
		$out = '';
		$update = false;
		$insert = false;

		if($category == '') $category = $parser->getTitle()->getBaseText();
		$category = str_replace(' ', '_', $category);
		$escaped_category = addslashes($category);

		$dbmaster = wfGetDB( DB_MASTER );

		$where_filter = array('w4grb_cat_avg.page="'.$escaped_category.'"');
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
			$where_filter = array_merge($where_filter,array('catlink.cl_from=w4grb_avg.pid','catlink.cl_to="'.$escaped_category.'"'));

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

}
