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

if (!class_exists('titania_type_base'))
{
	include(TITANIA_ROOT . 'includes/types/base.' . PHP_EXT);
}


class titania_type_style extends titania_type_base
{
	/**
	 * The type id
	 *
	 * @var int type id (for custom types not specified in titania to start, please start with 10 in case we add any extra later)
	 */
	public $id = 2;

	/**
	 * For the url slug
	 *
	 * @var string portion to be used in the URL slug
	 */
	public $url = 'style';

	/**
	 * The name of the field used to hold the number of this item in the authors table
	 *
	 * @var string author count
	 */
	public $author_count = 'author_styles';

	public function __construct()
	{
		$this->lang = phpbb::$user->lang['STYLE'];
	}

	/**
	* Check auth level
	*
	* @param string $auth ('view', 'test', 'validate')
	* @return bool
	*/
	public function acl_get($auth)
	{
		switch ($auth)
		{
			// Can view the style queue
			case 'view' :
				return phpbb::$auth->acl_get('titania_style_queue');
			break;

			// Can validate styles in the queue
			case 'validate' :
				return phpbb::$auth->acl_get('titania_style_validate');
			break;

			// Can moderate styles
			case 'moderate' :
				return phpbb::$auth->acl_get('titania_style_moderate');
			break;
		}

		return false;
	}

	/**
	* Automatically install the type if required
	*
	* For adding type specific permissions, etc.
	*/
	public function auto_install()
	{
		if (!isset(phpbb::$config['titania_num_styles']))
		{
			if (!class_exists('umil'))
			{
				include(PHPBB_ROOT_PATH . 'umil/umil.' . PHP_EXT);
			}

			$umil = new umil(true, phpbb::$db);

			// Permissions
			$umil->permission_add(array(
				'titania_style_queue',
				'titania_style_validate',
				'titania_style_moderate',
			));

			// Style count holder
			$umil->config_add('titania_num_styles', 0, true);
		}
	}

	public function increment_count()
	{
		set_config('titania_num_styles', ++phpbb::$config['titania_num_styles'], true);
	}

	public function decrement_count()
	{
		set_config('titania_num_styles', --phpbb::$config['titania_num_styles'], true);
	}

	public function get_count()
	{
		return phpbb::$config['titania_num_styles'];
	}
}