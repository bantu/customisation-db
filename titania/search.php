<?php
/**
 *
 * @package Titania
 * @version $Id$
 * @copyright (c) 2008 phpBB Customisation Database Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
* @ignore
*/
define('IN_TITANIA', true);
if (!defined('TITANIA_ROOT')) define('TITANIA_ROOT', './');
if (!defined('PHP_EXT')) define('PHP_EXT', substr(strrchr(__FILE__, '.'), 1));
require TITANIA_ROOT . 'common.' . PHP_EXT;

phpbb::$user->add_lang('search');

//$search_fields =
$keywords = utf8_normalize_nfc(request_var('keywords', '', true));
$author = utf8_normalize_nfc(request_var('author', '', true));
$search_fields = request_var('sf', '');
//$display = request_var('display', '');

// Display the advanced search page
if (!$keywords && !$author)
{
	titania::page_header('SEARCH');
	titania::page_footer(true, 'common/search_body.html');
}

// Add some POST stuff to the url
if (isset($_POST['submit']))
{
	if ($keywords)
	{
		titania_url::$params['keywords'] = $keywords;
	}
	if ($author)
	{
		titania_url::$params['author'] = $author;
	}
	if ($search_fields)
	{
		titania_url::$params['sf'] = $search_fields;
	}
	/*if ($display)
	{
		titania_url::$params['display'] = $display;
	}*/
}

// Setup the sort tool
$sort = new titania_sort();
$sort->default_limit = phpbb::$config['posts_per_page'];
$sort->request();

// Setup the search tool and make sure it is working
titania_search::initialize();
if (titania_search::$do_not_index)
{
	// Solr service is down
	trigger_error('SEARCH_UNAVAILABLE');
}

// Initialize the query
$query = titania_search::create_find_query();

// Query fields
$query_fields = array();
switch ($search_fields)
{
	case 'titleonly' :
		$query_fields[] = 'title';
	break;

	case 'msgonly' :
		$query_fields[] = 'text';
	break;

	default:
		$query_fields[] = 'title';
		$query_fields[] = 'text';
	break;
}

// Keywords specified?
if ($keywords)
{
	titania_search::clean_keywords($keywords);

	$qb = new ezcSearchQueryBuilder();
	$qb->parseSearchQuery($query, $keywords, $query_fields);
	unset($qb);
}

// Author specified?
if ($author)
{
	$sql = 'SELECT user_id FROM ' . USERS_TABLE . '
		WHERE username_clean = \'' . phpbb::$db->sql_escape(utf8_clean_string($author)) . '\'';
	phpbb::$db->sql_query($sql);
	$user_id = phpbb::$db->sql_fetchfield('user_id');
	phpbb::$db->sql_freeresult();

	if (!$user_id)
	{
		trigger_error('NO_USER');
	}

	$query->where($query->eq('author', $user_id));
}

// Do the search
$results = titania_search::custom_search($query, $sort);

// Grab the users
users_overlord::load_users($results['user_ids']);

/*switch ($display)
{
	case 'topics' :
		foreach ($results['documents'] as $document)
		{
			$url_base = $document->url;
			$url_params = '';
			if (substr($url_base, -1) != '/')
			{
				$url_params = substr($url_base, (strrpos($url_base, '/') + 1));
				$url_base = substr($url_base, 0, (strrpos($url_base, '/') + 1));
			}

			phpbb::$template->assign_block_vars('searchresults', array(
				'TOPIC_TITLE'		=> censor_text($document->title),

				'TOPIC_AUTHOR_FULL'	=> users_overlord::get_user($document->author, '_full'),
				'FIRST_POST_TIME'	=> phpbb::$user->format_date($document->date),

				'U_VIEW_TOPIC'		=> titania_url::build_url($url_base, $url_params),

				'S_TOPIC_REPORTED'		=> ($document->reported) ? true : false,
				//'S_TOPIC_UNAPPROVED'	=> (!$document->approved) ? true : false,
			));
		}
	break;

	default : */
		foreach ($results['documents'] as $document)
		{
			$url_base = $url_params = '';
			titania_url::split_base_params($url_base, $url_params, $document->url);

			phpbb::$template->assign_block_vars('searchresults', array(
				'POST_SUBJECT'		=> censor_text($document->title),
				'MESSAGE'			=> titania_generate_text_for_display($document->text, $document->text_uid, $document->text_bitfield, $document->text_options),

				'POST_AUTHOR_FULL'	=> ($document->author) ? users_overlord::get_user($document->author, '_full') : false,
				'POST_DATE'			=> ($document->date) ? phpbb::$user->format_date($document->date) : false,

				'U_VIEW_POST'		=> titania_url::build_url($url_base, $url_params),

				'S_POST_REPORTED'	=> ($document->reported) ? true : false,
			));
		}
/*	break;
}*/

$sort->build_pagination(titania_url::$current_page, titania_url::$params);

phpbb::$template->assign_vars(array(
	'S_IN_SEARCH'		=> true,
//	'S_SHOW_TOPICS'		=> ($display == 'topics') ? true : false,

	'S_SEARCH_ACTION'	=> titania_url::$current_page_url,
));

titania::page_header('SEARCH');
titania::page_footer(true, 'common/search_results.html');