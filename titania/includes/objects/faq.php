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
if (!defined('IN_TITANIA'))
{
	exit;
}

if (!class_exists('titania_database_object'))
{
	require TITANIA_ROOT . 'includes/core/object_database.' . PHP_EXT;
}

/**
* Class to titania faq.
* @package Titania
*/
class titania_faq extends titania_database_object
{
	/**
	 * SQL Table
	 *
	 * @var string
	 */
	protected $sql_table		= TITANIA_CONTRIB_FAQ_TABLE;

	/**
	 * SQL identifier field
	 *
	 * @var string
	 */
	protected $sql_id_field		= 'faq_id';

	/**
	 * Text parsed for storage
	 *
	 * @var bool
	 */
	private $text_parsed_for_storage = false;

	/*
	 * Contrib type for URLs, etc.
	 *
	 * @var string
	 */
	private $contrib_type		= '';
	
	/*
	 * Contrib data
	 *
	 * @var array
	 */		
	public $contrib_data		= array();

	/**
	 * Constructor class for titania faq
	 *
	 * @param int $faq_id
	 */
	public function __construct($faq_id = false, $contrib_id)
	{
		// Configure object properties
		$this->object_config = array_merge($this->object_config, array(
			'faq_id'		=> array('default' => 0),
			'contrib_id' 		=> array('default' => 0),
			'faq_order_id' 		=> array('default' => 0),
			'faq_subject' 		=> array('default' => '', 'max' => 255),
			'faq_text' 		=> array('default' => ''),
			'faq_text_bitfield'	=> array('default' => '', 'readonly' => true),
			'faq_text_uid'		=> array('default' => '', 'readonly' => true),
			'faq_text_options'	=> array('default' => 7, 'readonly' => true),
			'faq_views'		=> array('default' => 0),
		));

		if ($faq_id !== false)
		{
			$this->faq_id = $faq_id;
		}

		$this->contrib_id = $contrib_id;
		
		// getting contrib data from the contribs table
		$this->get_contrib_data();
		
		// to creating URLs
		$this->contrib_type = $this->contrib_data['contrib_type'];
	}

	/**
	 * Get data about contrib
	 *
	 * @return void
	 */
	public function get_contrib_data()
	{
		$sql = 'SELECT *
			FROM ' . TITANIA_CONTRIBS_TABLE . '
			WHERE contrib_id = ' . $this->contrib_id;
		$result = phpbb::$db->sql_query($sql);
		$this->contrib_data = phpbb::$db->sql_fetchrow($result);
		phpbb::$db->sql_freeresult($result);
		
		if (!$this->contrib_data)
		{
			trigger_error('ERROR_CONTRIB_NOT_FOUND');
		}
	}
	
	/**
	 * Update data or submit new faq
	 *
	 * @return void
	 */
	public function submit()
	{
		// Nobody parsed the text for storage before. Parse text with lowest settings.
		if (!$this->text_parsed_for_storage)
		{
			$this->generate_text_for_storage(true, true, false);
		}

		parent::submit();
	}

	/**
	 * Get faq data from the database
	 *
	 * @return void
	 */
	public function load()
	{
		parent::load();

		$this->text_parsed_for_storage = true;
	}

	/**
	 * Parse text to store in database
	 *
	 * @param bool $allow_bbcode
	 * @param bool $allow_urls
	 * @param bool $allow_smilies
	 *
	 * @return void
	 */
	public function generate_text_for_storage($allow_bbcode, $allow_urls, $allow_smilies)
	{
		$faq_text = $this->faq_text;
		$faq_text_uid = $this->faq_text_uid;
		$faq_text_bitfield = $this->faq_text_bitfield;
		$faq_text_options = $this->faq_text_options;

		generate_text_for_storage($faq_text, $faq_text_uid, $faq_text_bitfield, $faq_text_options, $allow_bbcode, $allow_urls, $allow_smilies);

		$this->faq_text = $faq_text;
		$this->faq_text_uid = $faq_text_uid;
		$this->faq_text_bitfield = $faq_text_bitfield;
		$this->faq_text_options = $faq_text_options;

		$this->text_parsed_for_storage = true;
	}

	/**
	 * Parse text for display
	 *
	 * @return string text content from database for display
	 */
	private function generate_text_for_display()
	{
		return generate_text_for_display($this->faq_text, $this->faq_text_uid, $this->faq_text_bitfield, $this->faq_text_options);
	}

	/**
	 * Parse text for edit
	 *
	 * @return string text content from database for editing
	 */
	private function generate_text_for_edit()
	{
		$return = generate_text_for_edit($this->faq_text, $this->faq_text_uid, $this->faq_text_options);
		$this->faq_text = $return['text'];
	}

	/**
	 * Getter function for faq_text
	 *
	 * @param bool $editable
	 *
	 * @return string generate_text_for edit if editable is true, or display if false
	 */
	public function get_faq_text($editable = false)
	{
		// Text needs to be from database or parsed for database.
		if (!$this->text_parsed_for_storage)
		{
			$this->generate_text_for_storage(true, true, false);
		}

		if ($editable)
		{
			$this->generate_text_for_edit();
		}
		else
		{
			$this->generate_text_for_display();
		}

		return $this->faq_text;
	}

	/**
	 * Setter function for faq_text
	 *
	 * @param string $text
	 * @param string $uid
	 * @param string $bitfield
	 * @param int $flags
	 *
	 * @return void
	 */
	public function set_faq_text($text, $uid = false, $bitfield = false, $flags = false)
	{
		$this->faq_text = $text;
		$this->text_parsed_for_storage = false;

		if ($uid !== false)
		{
			$this->faq_text_uid = $uid;
		}

		if ($bitfield !== false)
		{
			$this->faq_text_bitfield = $bitfield;
		}

		if ($flags !== false)
		{
			$this->faq_text_options = $flags;
		}
	}

	/*
	 * Submit FAQ
	 */
	public function submit_faq($action)
	{	
		if (!phpbb::$auth->acl_get('titania_faq_mod') && !phpbb::$auth->acl_get('titania_faq_' . $action) && phpbb::$user->data['user_id'] != $this->contrib_data['contrib_user_id'])
		{
			return;
		}
		
		$submit = (isset($_POST['submit'])) ? true : false;

		$errors = array();
		
		if ($submit)
		{
			$this->faq_subject 	= utf8_normalize_nfc(request_var('subject', '', true));
			$text 			= utf8_normalize_nfc(request_var('text', '', true));
			
			if (empty($this->faq_subject))
			{
				$errors[] = phpbb::$user->lang['SUBJECT_EMPTY'];
			}

			if (empty($text))
			{
				$errors[] = phpbb::$user->lang['TEXT_EMPTY'];
			}

			if (!sizeof($errors))
			{
				// obtain the last order id
				$sql = 'SELECT MAX(faq_order_id) as max_order_id
					FROM ' . TITANIA_CONTRIB_FAQ_TABLE;
				$result = phpbb::$db->sql_query_limit($sql, 1);
				$max_order_id = phpbb::$db->sql_fetchfield('max_order_id');
				phpbb::$db->sql_freeresult($result);

				// set order id on the last one
				$this->faq_order_id = $max_order_id + 1;
				
				// prepare a text to storage
				$this->set_faq_text($text);

				$this->submit();

				$message = ($action == 'edit') ? phpbb::$user->lang['FAQ_EDITED'] : phpbb::$user->lang['FAQ_CREATED'];
				$message .= '<br /><br />' . sprintf(phpbb::$user->lang['RETURN_FAQ'], '<a href="' . titania_sid('contributions/index', "mode=faq&amp;action=details&amp;c={$this->contrib_id}&amp;f={$this->faq_id}") . '">', '</a>');
				$message .= '<br /><br />' . sprintf(phpbb::$user->lang['RETURN_FAQ_LIST'], '<a href="' . titania_sid('contributions/index', "mode=faq&amp;c={$this->contrib_id}") . '">', '</a>');

				trigger_error($message);
			}
		}

		if ($action == 'edit')
		{
			$this->load();
		}
		
		phpbb::$template->assign_vars(array(
			'U_ACTION'		=> titania_sid('contributions/index', "mode=faq&amp;action=$action&amp;c={$this->contrib_id}&amp;f={$this->faq_id}"),

			'S_EDIT_FAQ'		=> true,

			'L_EDIT_FAQ'		=> ($action == 'edit') ? phpbb::$user->lang['EDIT_FAQ'] : phpbb::$user->lang['CREATE_FAQ'],

			'ERROR_MSG'		=> (sizeof($errors)) ? implode('<br />', $errors) : false,
			
			'FAQ_SUBJECT'		=> $this->faq_subject,
			'FAQ_TEXT'		=> $this->get_faq_text(true),
		));
	}

	/*
	 * Delete FAQ
	 */
	public function delete_faq()
	{
		if (!phpbb::$auth->acl_get('titania_faq_mod') && !phpbb::$auth->acl_get('titania_faq_delete') && phpbb::$user->data['user_id'] != $this->contrib_data['contrib_user_id'])
		{
			return;
		}
		
		$submit = (isset($_POST['submit'])) ? true : false;

		if ($submit)
		{
			if (confirm_box(true))
			{
				$this->delete($this->faq_id);

				return true;
			}
			return false;
		}
		else
		{
			confirm_box(false, 'DELETE_FAQ', build_hidden_fields(array(
				'submit'	=> true,
				'faq'		=> $faq_id
			)));
		}
	}

	/*
	 * FAQ Management List
	 */
	public function management_list()
	{
		if (!phpbb::$auth->acl_get('titania_faq_mod') && phpbb::$user->data['user_id'] != $this->contrib_data['contrib_user_id'])
		{
			return;
		}	
		
	}

	/*
	 * FAQ Details Page
	 */
	public function faq_details()
	{
		// increase a FAQ views counter
		$this->increase_views_counter();
		
		$sql_ary = array(
			'SELECT'	=> 'f.*, c.contrib_user_id, c.contrib_name',
			'FROM'		=> array(
				TITANIA_CONTRIB_FAQ_TABLE => 'f',
				TITANIA_CONTRIBS_TABLE 	=> 'c'
			),
			'WHERE'		=> 'f.faq_id = ' . $this->faq_id . '
						AND c.contrib_id = f.contrib_id'
		);
		$sql = phpbb::$db->sql_build_query('SELECT', $sql_ary);
		$result = phpbb::$db->sql_query($sql);
		$row = phpbb::$db->sql_fetchrow($result);
		phpbb::$db->sql_freeresult($result);
		
		if (!$row)
		{
			return false;
		}

		phpbb::$template->assign_vars(array(
			'FAQ_SUBJECT'		=> $row['faq_subject'],
			'FAQ_TEXT'		=> generate_text_for_display($row['faq_text'], $row['faq_text_uid'], $row['faq_text_bitfield'], $row['faq_text_options']),
			'FAQ_VIEWS'		=> $row['faq_views'],

			'S_FAQ_DETAILS'		=> true,
			
			'U_EDIT_FAQ'		=> (phpbb::$user->data['user_id'] == $row['contrib_user_id'] || phpbb::$auth->acl_get('titania_faq_edit')) ? titania_sid('contributions/index', 'mode=faq&amp;action=edit&amp;c=' . $row['contrib_id'] . '&amp;f=' . $row['faq_id']) : false,
		));

		return true;
	}

	/**
	 * FAQ List
	 *
	 * @param int $contrib_id
	 */
	public function faq_list()
	{
		if (!class_exists('sort'))
		{
			include(TITANIA_ROOT . 'includes/tools/sort.' . PHP_EXT);
		}

		if (!class_exists('pagination'))
		{
			include(TITANIA_ROOT . 'includes/tools/pagination.' . PHP_EXT);
		}

		$sort = new sort();

		$sort->set_sort_keys(array(
			'a' => array('SORT_SUBJECT',	'f.faq_subject', 'default' => true),
			'b' => array('SORT_VIEWS',	'f.faq_views'),
		));

		$sort->sort_request(false);

		/*$pagination = new pagination();
		$start = $pagination->set_start();
		$limit = $pagination->set_limit();*/

		$sql_ary = array(
			'SELECT'	=> 'f.*',
			'FROM'		=> array(
					TITANIA_CONTRIB_FAQ_TABLE	=> 'f'
			),
			'WHERE'		=> 'f.contrib_id = ' . $this->contrib_id,
			'ORDER_BY'	=> $sort->get_order_by()
		);

		$sql = phpbb::$db->sql_build_query('SELECT', $sql_ary);
		$result = phpbb::$db->sql_query_limit($sql, 15, $start);

		$results = 0;

		while ($row = phpbb::$db->sql_fetchrow($result))
		{
			$results++;

			strip_bbcode($row['faq_text'], $row['faq_text_uid']);

			phpbb::$template->assign_block_vars('faqlist', array(
				'U_FAQ'			=> titania_sid('contributions/index', "mode=faq&amp;action=details&amp;c={$row['contrib_id']}&amp;f={$row['faq_id']}"),

				'FAQ_SUBJECT'		=> $row['faq_subject'],
				'TEXT'			=> (utf8_strlen($row['faq_text']) > 250) ? utf8_substr($row['faq_text'], 0, 250) . '...' : $row['faq_text'],
				'VIEWS'			=> $row['faq_views'],
			));
		}
		phpbb::$db->sql_freeresult($result);

		if (!$results)
		{
			return false;
		}
/*
		$pagination->sql_total_count($sql_ary, 'f.faq_id', $results);

		$pagination->set_params(array(
			'sk'	=> $sort->get_sort_key(false),
			'sd'	=> $sort->get_sort_dir(false),
		));

		// Build a pagination
		$pagination->build_pagination(titania_sid('contributions/index', "mode=faq&amp;c={$this->contrib_id}"));
*/
		phpbb::$template->assign_vars(array(
			'S_MODE_SELECT'		=> $sort->get_sort_key_list(),
			'S_ORDER_SELECT'	=> $sort->get_sort_dir_list(),
			
			'U_CREATE_FAQ'		=> (phpbb::$auth->acl_get('titania_faq_create') || phpbb::$user->data['user_id'] == $this->contrib_data['contrib_user_id']) ? titania_sid('contributions/index', "mode=faq&amp;c={$this->contrib_id}&amp;action=create") : false,
		));

		return true;
	}
	
	/*
	 * Increase a FAQ views counter
	 */		
	public function increase_views_counter()
	{
		if (phpbb::$user->data['is_bot'])
		{
			return;
		}
		
		$sql = 'UPDATE ' . TITANIA_CONTRIB_FAQ_TABLE . '
			SET faq_views = faq_views + 1
			WHERE faq_id = ' . $this->faq_id;
		phpbb::$db->sql_query($sql);
	}
}