<?php

/**
 * 
 * PHP version 5
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage JoomlaExt 
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * load the common Joomla JFusion plugin functions
 */
require_once JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_jfusion' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'model.jplugin.php';
//require the standard joomla user functions
jimport('joomla.user.helper');
/**
 * JFusion User Class for an external Joomla database
 * For detailed descriptions on these functions please check the model.abstractadmin.php
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage JoomlaExt 
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class JFusionUser_joomla_ext extends JFusionUser {
    /**
     * returns the name of this JFusion plugin
     * @return string name of current JFusion plugin
     */    
    function getJname() {
        return 'joomla_ext';
    }

    /**
     * @param object $userinfo
     * @param int $overwrite
     * @return array
     */
    function updateUser($userinfo, $overwrite = 0) {
        $status = JFusionJplugin::updateUser($userinfo, $overwrite, $this->getJname());
        return $status;
    }

    /**
     * @param object $userinfo
     * @return array
     */
    function deleteUser($userinfo) {
        //get the database ready
        $db = JFusionFactory::getDatabase($this->getJname());
        //setup status array to hold debug info and errors
        $status = array('error' => array(),'debug' => array());
        $username = $userinfo->username;
        $userid = $userinfo->userid;
        $query = 'DELETE FROM #__users WHERE id = ' . (int)$userid;
        $db->setQuery($query);
	    try {
		    $db->execute();

		    $query = 'DELETE FROM #__user_profiles WHERE user_id = ' . (int)$userid;
		    $db->setQuery($query);
		    $db->execute();

		    $query = 'DELETE FROM #__user_usergroup_map WHERE user_id = ' . (int)$userid;
		    $db->setQuery($query);
		    $db->execute();

		    $status['debug'][] = JText::_('USER_DELETION') . ' ' . $username;
	    } catch (Exception $e) {
		    $status['error'][] = JText::_('ERROR_DELETE') . ' ' . $username . ' ' . $e->getMessage();
	    }
        return $status;
    }

    /**
     * @param object $userinfo
     * @return null|object
     */
    function getUser($userinfo) {
        $userinfo = JFusionJplugin::getUser($userinfo, $this->getJname());
        return $userinfo;
    }

    /**
     * @param string $username
     * @return string
     */
    function filterUsername($username) {
        $username = JFusionJplugin::filterUsername($username, $this->getJname());
        return $username;
    }

    /**
     * @param object $userinfo
     * @param array $options
     *
     * @return array
     */
    function destroySession($userinfo, $options) {
    	$params = JFusionFactory::getParams($this->getJname());
        $status = JFusionJplugin::destroySession($userinfo, $options, $this->getJname(),$params->get('logout_type'));
        return $status;
    }

    /**
     * @param object $userinfo
     * @param array $options
     * 
     * @return array
     */
    function createSession($userinfo, $options) {
        $status = array('error' => array(),'debug' => array());
        if (!empty($userinfo->block) || !empty($userinfo->activation)) {
            $status['error'][] = JText::_('FUSION_BLOCKED_USER');
        } else {
            $params = JFusionFactory::getParams($this->getJname());
            $status = JFusionJplugin::createSession($userinfo, $options, $this->getJname(),$params->get('brute_force'));
        }
        return $status;
    }

    /**
     * @param object $userinfo
     * @param object &$existinguser
     * @param array &$status
     *
     * @return void
     */
    function updateUsergroup($userinfo, $existinguser, &$status) {
        JFusionJplugin::updateUsergroup($userinfo, $existinguser, $status, $this->getJname());
    }

    /**
     * @param object $userinfo
     * @param object &$existinguser
     * @param array &$status
     *
     * @return void
     */
    function updatePassword($userinfo, &$existinguser, &$status) {
        JFusionJplugin::updatePassword($userinfo, $existinguser, $status, $this->getJname());
    }

    /**
     * @param object $userinfo
     * @param object &$existinguser
     * @param array &$status
     *
     * @return void
     */
    function updateUsername($userinfo, &$existinguser, &$status) {
        JFusionJplugin::updateUsername($userinfo, $existinguser, $status, $this->getJname());
    }

    /**
     * @TODO - To implement after the RC 1.1.2
     *
     * @param object $userinfo
     * @param object &$existinguser
     * @param array &$status
     *
     * @return void
     */
    function updateUserLanguage($userinfo, &$existinguser, &$status) {
        JFusionJplugin::updateUserLanguage($userinfo, $existinguser, $status, $this->getJname());
    }

    /**
     * @param object $userinfo
     * @param object $existinguser
     * @param array &$status
     *
     * @return void
     */
    function updateEmail($userinfo, &$existinguser, &$status) {
        JFusionJplugin::updateEmail($userinfo, $existinguser, $status, $this->getJname());
    }

    /**
     * @param object $userinfo
     * @param object &$existinguser
     * @param array &$status
     *
     * @return void
     */
    function blockUser($userinfo, &$existinguser, &$status) {
        JFusionJplugin::blockUser($userinfo, $existinguser, $status, $this->getJname());
    }

    /**
     * @param object $userinfo
     * @param object &$existinguser
     * @param array &$status
     *
     * @return void
     */
    function unblockUser($userinfo, &$existinguser, &$status) {
        JFusionJplugin::unblockUser($userinfo, $existinguser, $status, $this->getJname());
    }

    /**
     * @param object $userinfo
     * @param object &$existinguser
     * @param array &$status
     *
     * @return void
     */
    function activateUser($userinfo, &$existinguser, &$status) {
        JFusionJplugin::activateUser($userinfo, $existinguser, $status, $this->getJname());
    }

    /**
     * @param object $userinfo
     * @param object &$existinguser
     * @param array &$status
     *
     * @return void
     */
    function inactivateUser($userinfo, &$existinguser, &$status) {
        JFusionJplugin::inactivateUser($userinfo, $existinguser, $status, $this->getJname());
    }

    /**
     * @param object $userinfo
     * @param array &$status
     *
     * @return void
     */
    function createUser($userinfo, &$status) {
        JFusionJplugin::createUser($userinfo, $status, $this->getJname());
    }
}
