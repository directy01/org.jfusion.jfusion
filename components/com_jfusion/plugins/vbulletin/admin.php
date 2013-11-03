<?php

/**
 * file containing administrator function for the jfusion plugin
 *
 * PHP version 5
 *
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage vBulletin
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * JFusion Admin Class for vBulletin
 * For detailed descriptions on these functions please check the model.abstractadmin.php
 *
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage vBulletin
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

class JFusionAdmin_vbulletin extends JFusionAdmin
{
	static private $mods = array('jfvbtask' => 'JFusion API Plugin - REQUIRED',
		'redirect' => 'JFusion Redirect Plugin',
		'frameless' => 'JFusion Frameless Integration Plugin',
		'duallogin' => 'JFusion Dual Login Plugin',
		'globalfix' => 'JFusion Global Fix Plugin');

	/**
	 * @var $helper JFusionHelper_vbulletin
	 */
	var $helper;

	/**
	 * returns the name of this JFusion plugin
	 * @return string name of current JFusion plugin
	 */
	function getJname()
	{
		return 'vbulletin';
	}

	/**
	 * @return string
	 */
	function getTablename()
	{
		return 'user';
	}

	/**
	 * @param string $softwarePath
	 *
	 * @return array
	 */
	function setupFromPath($softwarePath)
	{
		$myfile = $softwarePath . 'includes' . DIRECTORY_SEPARATOR . 'config.php';
		$funcfile = $softwarePath . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';

		//try to open the file
		$params = array();
		$lines = $this->readFile($myfile);
		if ($lines === false) {
			JFusionFunction::raiseWarning(JText::_('WIZARD_FAILURE') . ': '.$myfile. ' ' . JText::_('WIZARD_MANUAL'), $this->getJname());
			return false;
		} else {
			//parse the file line by line to get only the config variables
			$config = array();

			foreach ($lines as $line) {
				if (strpos($line, '$config') === 0) {
					$vars = explode("'", $line);
					if (isset($vars[5])) {
						$name1 = trim($vars[1], ' $=');
						$name2 = trim($vars[3], ' $=');
						$value = trim($vars[5], ' $=');
						$config[$name1][$name2] = $value;
					}
				}
			}

			//save the parameters into the standard JFusion params format
			$params = array();
			$params['database_host'] = $config['MasterServer']['servername'];
			$params['database_type'] = $config['Database']['dbtype'];
			$params['database_name'] = $config['Database']['dbname'];
			$params['database_user'] = $config['MasterServer']['username'];
			$params['database_password'] = $config['MasterServer']['password'];
			$params['database_prefix'] = $config['Database']['tableprefix'];
			$params['cookie_prefix'] = $config['Misc']['cookieprefix'];
			$params['source_path'] = $softwarePath;
			//find the path to vbulletin, for this we need a database connection
			$host = $config['MasterServer']['servername'];
			$user = $config['MasterServer']['username'];
			$password = $config['MasterServer']['password'];
			$database = $config['Database']['dbname'];
			$prefix = $config['Database']['tableprefix'];
			$driver = 'mysql';
			$options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix);
			$vdb = JDatabaseDriver::getInstance($options);
			if (method_exists($vdb, 'setQuery')) {
				//Find the path to vbulletin
				$query = $vdb->getQuery(true)
					->select('value, varname')
					->from('#__setting')
					->where('varname IN (\'bburl\',\'cookietimeout\',\'cookiepath\',\'cookiedomain\')');

				$vdb->setQuery($query);
				$settings = $vdb->loadObjectList('varname');
				$params['source_url'] = $settings['bburl']->value;
				$params['cookie_expires'] = $settings['cookietimeout']->value;
				$params['cookie_path'] = $settings['cookiepath']->value;
				$params['cookie_domain'] = $settings['cookiedomain']->value;
			}

			$lines = $this->readFile($funcfile);
			if ($lines !== false) {
				$cookie_salt = '';
				foreach ($lines as $line) {
					if (strpos($line, 'COOKIE_SALT') !== false) {
						$vars = explode("'", $line);
						if (isset($vars[3])) {
							$cookie_salt = $vars[3];
						}
						break;
					}
				}

				$params['cookie_salt'] = $cookie_salt;
			}
		}
		return $params;
	}

	/**
	 * @return string
	 */
	function getRegistrationURL()
	{
		return 'register.php';
	}

	/**
	 * @return string
	 */
	function getLostPasswordURL()
	{
		return 'login.php?do=lostpw';
	}

	/**
	 * @return string
	 */
	function getLostUsernameURL()
	{
		return 'login.php?do=lostpw';
	}

	/**
	 * Returns the a list of users of the integrated software
	 *
	 * @param int $limitstart start at
	 * @param int $limit number of results
	 *
	 * @return array
	 */
	function getUserList($limitstart = 0, $limit = 0)
	{
		try {
			// initialise some objects
			$db = JFusionFactory::getDatabase($this->getJname());

			$query = $db->getQuery(true)
				->select('username, email')
				->from('#__user');

			$db->setQuery($query, $limitstart, $limit);
			//getting the results
			$userlist = $db->loadObjectList();
		} catch (Exception $e) {
			JFusionFunction::raiseError($e, $this->getJname());
			$userlist = array();
		}
		return $userlist;
	}

	/**
	 * @return int
	 */
	function getUserCount()
	{
		try {
			//getting the connection to the db
			$db = JFusionFactory::getDatabase($this->getJname());

			$query = $db->getQuery(true)
				->select('count(*)')
				->from('#__user');

			$db->setQuery($query);
			//getting the results
			$no_users = $db->loadResult();
		} catch (Exception $e) {
			JFusionFunction::raiseError($e, $this->getJname());
			$no_users = 0;
		}
		return $no_users;
	}

	/**
	 * @return array
	 */
	function getUsergroupList()
	{
		try {
			//get the connection to the db
			$db = JFusionFactory::getDatabase($this->getJname());

			$query = $db->getQuery(true)
				->select('usergroupid as id, title as name')
				->from('#__usergroup');

			$db->setQuery($query);
			//getting the results
			return $db->loadObjectList();
		} catch (Exception $e) {
			JFusionFunction::raiseError($e, $this->getJname());
			return array();
		}
	}

	/**
	 * @return string|array
	 */
	function getDefaultUsergroup()
	{
		try {
			$usergroup = JFusionFunction::getUserGroups($this->getJname(), true);

			if ($usergroup !== null) {
				//we want to output the usergroup name
				$db = JFusionFactory::getDatabase($this->getJname());

				if (!isset($usergroup->membergroups)) {
					$usergroup->membergroups = array($usergroup->defaultgroup);
				} else if (!in_array($usergroup->defaultgroup, $usergroup->membergroups)) {
					$usergroup->membergroups[] = $usergroup->defaultgroup;
				}

				$group = array();
				foreach ($usergroup->membergroups as $g) {
					$query = $db->getQuery(true)
						->select('title')
						->from('#__usergroup')
						->where('usergroupid = ' . $db->quote($g));

					$db->setQuery($query);
					$group[] = $db->loadResult();
				}
			} else {
				$group = '';
			}
		} catch (Exception $e) {
			JFusionFunction::raiseError($e, $this->getJname());
			$group = '';
		}
		return $group;
	}

	/**
	 * @return bool
	 */
	function allowRegistration()
	{
		$result = false;
		try {
			$db = JFusionFactory::getDatabase($this->getJname());

			$query = $db->getQuery(true)
				->select('value')
				->from('#__setting')
				->where('varname = ' . $db->quote('allowregistration'));

			$db->setQuery($query);
			//getting the results
			$new_registration = $db->loadResult();
			if ($new_registration == 1) {
				$result = true;
			}
		} catch (Exception $e) {
			JFusionFunction::raiseError($e, $this->getJname());
		}
		return $result;
	}

	/**
	 * @param string $name         name of element
	 * @param string $value        value of element
	 * @param string $node         node
	 * @param string $control_name name of controller
	 *
	 * @return string html
	 */
	function jfvbtask($name, $value, $node, $control_name)
	{
		return $this->renderHook($name);
	}

	/**
	 * @param string $name         name of element
	 * @param string $value        value of element
	 * @param string $node         node
	 * @param string $control_name name of controller
	 *
	 * @return string html
	 */
	function redirect($name, $value, $node, $control_name)
	{
		return $this->renderHook($name);
	}

	/**
	 * @param string $name         name of element
	 * @param string $value        value of element
	 * @param string $node         node
	 * @param string $control_name name of controller
	 *
	 * @return string html
	 */
	function frameless($name, $value, $node, $control_name)
	{
		return $this->renderHook($name);
	}

	/**
	 * @param string $name         name of element
	 * @param string $value        value of element
	 * @param string $node         node
	 * @param string $control_name name of controller
	 *
	 * @return string html
	 */
	function duallogin($name, $value, $node, $control_name)
	{
		return $this->renderHook($name);
	}

	/**
	 * @param string $name         name of element
	 * @param string $value        value of element
	 * @param string $node         node
	 * @param string $control_name name of controller
	 *
	 * @return string html
	 */
	function globalfix($name, $value, $node, $control_name)
	{
		return $this->renderHook($name);
	}

	/**
	 * @param string $name         name of element
	 *
	 * @return string html
	 */
	function renderHook($name)
	{
		try {
			try {
				$db = JFusionFactory::getDatabase($this->getJname());
			} catch (Exception $e) {
				throw new RuntimeException(JText::_('VB_CONFIG_FIRST'));
			}
			$secret = $this->params->get('vb_secret', null);
			if (empty($secret)) {
				throw new RuntimeException(JText::_('VB_SECRET_EMPTY'));
			}

			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from('#__plugin')
				->where('hookname = ' . $db->quote('init_startup'))
				->where('title = ' . $db->quote(static::$mods[$name]))
				->where('active = 1');

			$db->setQuery($query);
			$check = ($db->loadResult() > 0) ? true : false;

			if ($check) {
				//return success
				$enabled = JText::_('ENABLED');
				$disable = JText::_('DISABLE_THIS_PLUGIN');
				$reenable = JText::_('REENABLE_THIS_PLUGIN');
				$output = <<<HTML
                    <img style="float: left;" src="components/com_jfusion/images/check_good_small.png">
                    <span style="float: left; margin-left: 5px;">{$enabled}</span>
                    <a style="margin-left:5px; float: left;" href="javascript:void(0);" onclick="return JFusion.Plugin.module('toggleHook', '{$name}', 'disable');">{$disable}</a>
                    <a style="margin-left:5px; float: left;" href="javascript:void(0);" onclick="return JFusion.Plugin.module('toggleHook', '{$name}', 'reenable');">{$reenable}</a>
HTML;
			} else {
				$disabled = JText::_('DISABLED');
				$enable = JText::_('ENABLE_THIS_PLUGIN');
				$output = <<<HTML
                    <img style="float: left;" src="components/com_jfusion/images/check_bad_small.png">
                    <span style="float: left; margin-left: 5px;">{$disabled}</span>
                    <a style="margin-left:5px; float: left;" href="javascript:void(0);" onclick="return JFusion.Plugin.module('toggleHook', '{$name}', 'enable');">{$enable}</a>
HTML;
			}
		} catch (Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}

	/**
	 * @param string $name         name of element
	 * @param string $value        value of element
	 * @param string $node         node
	 * @param string $control_name name of controller
	 *
	 * @return string html
	 */
	function framelessoptimization($name, $value, $node, $control_name)
	{
		try {
			try {
				$db = JFusionFactory::getDatabase($this->getJname());
			} catch (Exception $e) {
				throw new RuntimeException(JText::_('VB_CONFIG_FIRST'));
			}

			//let's first check the default icon
			$query = $db->getQuery(true)
				->select('value')
				->from('#__setting')
				->where('varname = ' . $db->quote('showdeficon'));

			$db->setQuery($query);
			$deficon = $db->loadResult();
			$check = (!empty($deficon) && strpos($deficon, 'http') === false) ? false : true;
			if ($check) {
				//this will perform functions like rewriting image paths to include the full URL to images to save processing time
				$tables = array('smilie' => 'smiliepath', 'avatar' => 'avatarpath', 'icon' => 'iconpath');
				foreach ($tables as $tbl => $col) {
					$query = $db->getQuery(true)
						->select($col)
						->from('#__'.$tbl);

					$db->setQuery($query);
					$images = $db->loadRowList();
					if ($images) {
						foreach ($images as $image) {
							$check = (strpos($image[0], 'http') !== false) ? true : false;
							if (!$check) break;
						}
					}
					if (!$check) break;
				}
			}
			if ($check) {
				//return success
				$complete = JText::_('COMPLETE');
				$undo = JText::_('VB_UNDO_OPTIMIZATION');
				$output = <<<HTML
		                    <img style="float: left;" src="components/com_jfusion/images/check_good_small.png">
		                    <span style="float: left; margin-left: 5px;">{$complete}</span>
		                    <a style="margin-left:5px; float: left;" href="javascript:void(0);" onclick="return JFusion.Plugin.module('toggleHook', 'framelessoptimization', 'disable');">{$undo}</a>
HTML;
				return $output;
			} else {
				$incomplete = JText::_('INCOMPLETE');
				$do = JText::_('VB_DO_OPTIMIZATION');
				$output = <<<HTML
		                    <img style="float: left;" src="components/com_jfusion/images/check_bad_small.png">
		                    <span style="float: left; margin-left: 5px;">{$incomplete}</span>
		                    <a style="margin-left:5px; float: left;" href="javascript:void(0);" onclick="return JFusion.Plugin.module('toggleHook', 'framelessoptimization', 'enable');">{$do}</a>
HTML;
				return $output;
			}


		} catch (Exception $e) {
			$output = $e->getMessage();
		}
		return $output;
	}

	/**
	 * @param string $hook
	 * @param string $action
	 *
	 * @return void
	 */
	function toggleHook($hook, $action)
	{
		try {
			$params = JFactory::getApplication()->input->get('params', array(), 'array');
			$itemid = $params['plugin_itemid'];
			$db = JFusionFactory::getDatabase($this->getJname());
			if ($hook != 'framelessoptimization') {
				$hookName = static::$mods[$hook];

				if ($hookName) {
					//all three cases, we want to remove the old hook
					$query = $db->getQuery(true)
						->delete('#__plugin')
						->where('hookname = ' . $db->quote('init_startup'))
						->where('title = ' . $db->quote($hookName));

					$db->setQuery($query);
					$db->execute();

					//enable or re-enable the plugin
					if ($action != 'disable') {
						$secret = $this->params->get('vb_secret', null);
						if (empty($secret)) {
							JFusionFunction::raiseWarning(JText::_('VB_SECRET_EMPTY'));
						} else if (($hook == 'redirect' || $hook == 'frameless') && !$this->isValidItemID($itemid)) {
							JFusionFunction::raiseWarning(JText::_('VB_REDIRECT_HOOK_ITEMID_EMPTY'));
						} else {
							//install the hook
							$php = $this->getHookPHP($hook, $itemid);

							//add the post to the approval queue
							$plugin = new stdClass;
							$plugin->title = $hookName;
							$plugin->hookname = 'init_startup';
							$plugin->phpcode = $php;
							$plugin->product = 'vbulletin';
							$plugin->active = 1;
							$plugin->executionorder = 1;

							$db->insertObject('#__plugin', $plugin);
						}
					}
				}
			} else {
				//this will perform functions like rewriting image paths to include the full URL to images to save processing time
				$source_url = $this->params->get('source_url');
				if (substr($source_url, -1) != '/') {
					$source_url.= '/';
				}
				//let's first update all the image paths for database stored images
				$tables = array('smilie' => 'smiliepath', 'avatar' => 'avatarpath', 'icon' => 'iconpath');
				foreach ($tables as $tbl => $col) {
					$criteria = ($action == 'enable') ? 'NOT LIKE \'http%\'' : 'LIKE \'%http%\'';

					$query = $db->getQuery(true)
						->select($tbl.'id, '.$col)
						->from('#__'.$tbl)
						->where($col.' '.$criteria);

					$db->setQuery($query);
					$images = $db->loadRowList();
					foreach ($images as $i) {
						$q = $db->getQuery(true)
							->update('#__'.$tbl);

						if ($action == 'enable') {
							$q->set($col.' = '.$q->quote($source_url.$i[1]));
						} else {
							$i[1] = str_replace($source_url, '', $i[1]);
							$q->set($col.' = '.$q->quote($i[1]));
						}

						$q->where($tbl.'id = '.$i[0]);

						$db->setQuery($q);
						$db->execute();
					}
				}
				//let's update the default icon
				$query = $db->getQuery(true)
					->select('value')
					->from('#__setting')
					->where('varname = ' . $db->quote('showdeficon'));

				$db->setQuery($query);
				$deficon = $db->loadResult();
				if (!empty($deficon)) {
					$query = $db->getQuery(true)
						->update('#__setting');

					if ($action == 'enable' && strpos($deficon, 'http') === false) {
						$query->set('value = ' . $db->quote($source_url.$deficon));
					} elseif ($action == 'disable') {
						$deficon = str_replace($source_url, '', $deficon);
						$query->set('value = ' . $db->quote($deficon));
					}
					$query->where('varname = '.$db->quote('showdeficon'));

					$db->setQuery($query);
					$db->execute();
				}
			}
		} catch (Exception $e) {
			JFusionFunction::raiseError($e, $this->getJname());
		}
	}

	/**
	 * @param $plugin
	 * @param $itemid
	 * @return string
	 */
	function getHookPHP($plugin, $itemid)
	{
		$hookFile = JFUSION_PLUGIN_PATH . DIRECTORY_SEPARATOR . $this->getJname() . DIRECTORY_SEPARATOR . 'hooks.php';
		$php = "defined('_VBJNAME') or define('_VBJNAME', '{$this->getJname()}');\n";
		$php.= "defined('JPATH_PATH') or define('JPATH_BASE', '" . (str_replace(DIRECTORY_SEPARATOR.'administrator', '', JPATH_BASE)) . "');\n";
		$php.= "defined('JFUSION_VB_HOOK_FILE') or define('JFUSION_VB_HOOK_FILE', '$hookFile');\n";
		if ($plugin == 'globalfix') {
			$php.= "if (defined('_JEXEC') && empty(\$GLOBALS['vbulletin']) && !empty(\$vbulletin)) {\n";
			$php.= "\$GLOBALS['vbulletin'] = \$vbulletin;\n";
			$php.= "\$GLOBALS['db'] = \$vbulletin->db;\n";
			$php.= '}';
			return $php;
		} elseif ($plugin == 'frameless') {
			//we only want to initiate the frameless if we are inside Joomla or using AJAX
			$php.= "if (defined('_JEXEC') || isset(\$_GET['jfusion'])){\n";
		} elseif ($plugin == 'redirect') {
			$php.= "if (!defined('_JEXEC')){\n";
			$sefmode = $this->params->get('sefmode', 0);
			$config = JFactory::getConfig();
			$sef = $config->get('sef');
			//get the baseUR
			$app = JApplication::getInstance('site');
			$router = $app->getRouter();
			/**
			 * @ignore
			 * @var $uri JUri
			 */
			$uri = $router->build('index.php?option=com_jfusion&Itemid=' . $itemid);
			$baseURL = $uri->toString();
			$joomla_url = JFusionFunction::getJoomlaURL();
			if (!strpos($baseURL, '?')) {
				$baseURL.= '/';
			}
			$juri = new JURI($joomla_url);
			$path = $juri->getPath();
			if ($path != '/') {
				$baseURL = str_replace($path, '', $baseURL);
			}
			if (substr($joomla_url, -1) == '/') {
				if ($baseURL[0] == '/') {
					$baseURL = substr($joomla_url, 0, -1) . $baseURL;
				} else {
					$baseURL = $joomla_url . $baseURL;
				}
			} else {
				if ($baseURL[0] == '/') {
					$baseURL = $joomla_url . $baseURL;
				} else {
					$baseURL = $joomla_url . '/' . $baseURL;
				}
			}
			//let's clean up the URL here before passing it
			$baseURL = str_replace('&amp;', '&', $baseURL);
			//remove /administrator from path
			$baseURL = str_replace('/administrator', '', $baseURL);
			//set some constants needed to recreate the Joomla URL
			$php.= "define('SEFENABLED','$sef');\n";
			$php.= "define('SEFMODE','$sefmode');\n";
			$php.= "define('JOOMLABASEURL','$baseURL');\n";
			$php.= "define('REDIRECT_IGNORE','" . $this->params->get('redirect_ignore') . "');\n";
		} elseif ($plugin == 'duallogin') {
			//only login if not logging into the frontend of the forum and if $JFusionActivePlugin is not active for this plugin
			$php.= "global \$JFusionActivePlugin,\$JFusionLoginCheckActive;\n";
			$php.= "if (empty(\$_POST['logintype']) && \$JFusionActivePlugin != '{$this->getJname() }' && empty(\$JFusionLoginCheckActive)) {\n";
			$php.= "\$JFusionActivePlugin = '{$this->getJname() }';\n";
			//set the JPATH_BASE needed to initiate Joomla if no already inside Joomla
			$php.= "defined('JPATH_BASE') or define('JPATH_BASE','" . JPATH_ROOT . "');\n";
		}

		$php.= "if (file_exists(JFUSION_VB_HOOK_FILE)) {\n";
		$php.= "include_once(JFUSION_VB_HOOK_FILE);\n";
		$php.= "\$val = '$plugin';\n";
		$secret = $this->params->get('vb_secret', JFactory::getConfig()->get('secret'));
		$php.= "\$JFusionHook = new executeJFusionHook('init_startup', \$val, '$secret');\n";

		$version = $this->helper->getVersion();
		if (substr($version, 0, 1) > 3) {
			$php.= "vBulletinHook::set_pluginlist(\$vbulletin->pluginlist);\n";
		}
		$php.= "}\n";
		if ($plugin != 'jfvbtask') {
			$php.= "}\n";
		}
		return $php;
	}

	function debugConfigExtra()
	{
		try {
			$db = JFusionFactory::getDatabase($this->getJname());

			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from('#__plugin')
				->where('hookname = ' . $db->quote('init_startup'))
				->where('title = ' . $db->quote(static::$mods['jfvbtask']))
				->where('active = 1');

			$db->setQuery($query);
			if ($db->loadResult() == 0) {
				JFusionFunction::raiseWarning(JText::_('VB_API_HOOK_NOT_INSTALLED'), $this->getJname());
			}
		} catch (Exception $e) {
			JFusionFunction::raiseError($e, $this->getJname());
		}
	}

	/**
	 * @param $name
	 * @param $value
	 * @param $node
	 * @param $control_name
	 * @return mixed|string
	 */
	function name_field($name, $value, $node, $control_name)
	{
		try {
			if ($this->isConfigured()) {
				try {
					$db = JFusionFactory::getDatabase($this->getJname());
				} catch (Exception $e) {
					throw new RuntimeException(JText::_('SAVE_CONFIG_FIRST'));
				}

				//get a list of field names for custom profile fields
				$custom_fields = $db->getTableColumns('#__userfield');

				$vb_options = array(JHTML::_('select.option', '', '', 'id', 'name'));
				if ($custom_fields) {
					unset($custom_fields['userid']);
					unset($custom_fields['temp']);

					foreach($custom_fields as $field  => $type) {
						$query = $db->getQuery(true)
							->select('text')
							->from('#__phrase')
							->where('varname = ' . $db->quote($field.'_title'))
							->where('fieldname = ' . $db->quote('cprofilefield'));

						$db->setQuery($query, 0, 1);
						$title = $db->loadResult();
						$vb_options[] = JHTML::_('select.option', $field, $title, 'id', 'name');
					}
				}

				$value = (empty($value)) ? '' : $value;

				return JHTML::_('select.genericlist', $vb_options, $control_name . '[' . $name . ']', 'class="inputbox"', 'id', 'name', $value);
			} else {
				throw new RuntimeException(JText::_('SAVE_CONFIG_FIRST'));
			}
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * @return array
	 */
	function uninstall()
	{
		$return = false;
		$reasons = array();
		try {
			$db = JFusionFactory::getDatabase($this->getJname());
			$hookNames = array();

			foreach (static::$mods as $mod) {
				$hookNames[] = $db->quote($mod);
			}

			$query = $db->getQuery(true)
				->delete('#__plugin')
				->where('hookname = ' . $db->quote('init_startup'))
				->where('title IN (' . implode(', ', $hookNames) . ')');

			$db->setQuery($query);
			$db->execute();

			$return = true;
		} catch (Exception $e) {
			$reasons[] = $e->getMessage();
		}

		return array($return, $reasons);
	}

	/*
	 * do plugin support multi usergroups
	 * return UNKNOWN for unknown
	 * return JNO for NO
	 * return JYES for YES
	 * return ... ??
	 */
	/**
	 * @return string
	 */
	function requireFileAccess()
	{
		return 'JYES';
	}

	/**
	 * create the render group function
	 *
	 * @return string
	 */
	function getRenderGroup()
	{
		$jname = $this->getJname();

		JFusionFunction::loadJavascriptLanguage(array('MAIN_USERGROUP', 'DISPLAYGROUP', 'DEFAULT', 'MEMBERGROUPS'));
		$js = <<<JS
		JFusion.renderPlugin['{$jname}'] = function(index, plugin, pair) {
			var usergroups = JFusion.usergroups[plugin.name];

			var div = new Element('div');

			// render default group
			div.appendChild(new Element('div', {'html': Joomla.JText._('MAIN_USERGROUP')}));

		    var defaultselect = new Element('select', {
		    	'name': 'usergroups['+plugin.name+']['+index+'][defaultgroup]',
		    	'id': 'usergroups_'+plugin.name+index+'defaultgroup'
		    });

			jQuery(document).on('change', '#usergroups_'+plugin.name+index+'defaultgroup', function() {
                var value = this.get('value');

				jQuery('#'+'usergroups_'+plugin.name+index+'membergroups'+' option').each(function() {
					if (jQuery(this).attr('value') == value) {
						jQuery(this).prop('selected', false);
						jQuery(this).prop('disabled', true);

						jQuery(this).trigger('chosen:updated').trigger('liszt:updated');
	                } else if (jQuery(this).prop('disabled') === true) {
						jQuery(this).prop('disabled', false);
						jQuery(this).trigger('chosen:updated').trigger('liszt:updated');
					}
				});
			});

		    Array.each(usergroups, function (group) {
			    var options = {'value': group.id,
					            'html': group.name};

		        if (pair && pair.defaultgroup && pair.defaultgroup == group.id) {
					options.selected = 'selected';
		        }

				defaultselect.appendChild(new Element('option', options));
		    });
		    div.appendChild(defaultselect);

			// render display group
			div.appendChild(new Element('div', {'html': Joomla.JText._('DISPLAYGROUP')}));

		    var displayselect = new Element('select', {
			    'name': 'usergroups['+plugin.name+']['+index+'][displaygroup]',
			    'id': 'usergroups_'+plugin.name+index+'displaygroup'});

			displayselect.appendChild(new Element('option', {'value': 0, 'html': Joomla.JText._('DEFAULT')}));
		    Array.each(usergroups, function (group) {
			    if (group.id != 1 && group.id != 3 && group.id != 4) {
				    var options = {'value': group.id,
				            'html': group.name};
				    if (pair && pair['displaygroup'] !== null && pair['displaygroup'] == group.id) {
				    	options.selected = 'selected';
				    }

			    	displayselect.appendChild(new Element('option', options));
			    }
		    });
			div.appendChild(displayselect);

			// render default member groups
			div.appendChild(new Element('div', {'html': Joomla.JText._('MEMBERGROUPS')}));

		    var membergroupsselect = new Element('select', {
		    	'name': 'usergroups['+plugin.name+']['+index+'][membergroups][]',
		    	'multiple': 'multiple',
		    	'id': 'usergroups_'+plugin.name+index+'membergroups'
		    });

		    Array.each(usergroups, function (group, i) {
			    var options = {'id': 'usergroups_'+plugin.name+index+'membergroups'+group.id,
			    				'value': group.id,
					            'html': group.name};

		        if (pair && pair.defaultgroup == group.id) {
					options.disabled = 'disabled';
		        } else if (!pair && i === 0) {
		        	options.disabled = 'disabled';
		        } else {
		            if (pair && pair.membergroups && pair.membergroups.contains(group.id)) {
		            	options.selected = 'selected';
			        }
		        }

				membergroupsselect.appendChild(new Element('option', options));
		    });
			div.appendChild(membergroupsselect);
		    return div;
		};
JS;
		return $js;
	}

	/**
	 * do plugin support multi usergroups
	 *
	 * @return bool
	 */
	function isMultiGroup()
	{
		return false;
	}
}