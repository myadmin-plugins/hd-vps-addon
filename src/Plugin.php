<?php

namespace Detain\MyAdminVpsHd;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminVpsHd
 */
class Plugin {

	public static $name = 'HD Space VPS Addon';
	public static $description = 'Allows selling of HD Space Upgrades to a VPS.';
	public static $help = '';
	public static $module = 'vps';
	public static $type = 'addon';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			'function.requirements' => [__CLASS__, 'getRequirements'],
			self::$module.'.load_addons' => [__CLASS__, 'getAddon'],
			self::$module.'.settings' => [__CLASS__, 'getSettings']
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_page_requirement('vps_hdspace', '/../vendor/detain/myadmin-hd-vps-addon/src/vps_hdspace.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getAddon(GenericEvent $event) {
		$service = $event->getSubject();
		function_requirements('class.AddonHandler');
		$addon = new \AddonHandler();
		$addon->setModule(self::$module)
			->set_text('Additional GB')
			->set_text_match('Additional (.*) GB')
			->set_cost(VPS_HD_COST)
			->set_require_ip(FALSE)
			->setEnable([__CLASS__, 'doEnable'])
			->setDisable([__CLASS__, 'doDisable'])
			->register();
		$service->addAddon($addon);
	}

	/**
	 * @param \ServiceHandler $serviceOrder
	 * @param                $repeatInvoiceId
	 * @param bool           $regexMatch
	 */
	public static function doEnable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = FALSE) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		$space = $regexMatch;
		myadmin_log(self::$module, 'info', self::$name." Activating {$space} GB additional HD space for {$settings['TBLNAME']} {$serviceInfo[$settings['PREFIX'].'_id']}", __LINE__, __FILE__);
		$GLOBALS['tf']->history->add(self::$module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'update_hdsize', $space, $serviceInfo[$settings['PREFIX'].'_custid']);
	}

	/**
	 * @param \ServiceHandler $serviceOrder
	 * @param                $repeatInvoiceId
	 * @param bool           $regexMatch
	 */
	public static function doDisable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = FALSE) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
		$space = $regexMatch;
		myadmin_log(self::$module, 'info', self::$name." Deactivating $space GB additional HD space for {$settings['TBLNAME']} {$serviceInfo[$settings['PREFIX'].'_id']}", __LINE__, __FILE__);
		$GLOBALS['tf']->history->add(self::$module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'update_hdsize', $space, $serviceInfo[$settings['PREFIX'].'_custid']);
		add_output('Additional '.$space.' GB HD Space Removed And Canceled');
		$email = $settings['TBLNAME'].' ID: '.$serviceInfo[$settings['PREFIX'].'_id'].'<br>'.$settings['TBLNAME'].' Hostname: '.$serviceInfo[$settings['PREFIX'].'_hostname'].'<br>Repeat Invoice: '.$repeatInvoiceId.'<br>Additional Space: '.$space.' GB<br>Description: '.self::$name.'<br>';
		$subject = $settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_id'].' Canceled Additional '.$space.' GB HD Space';
		$headers = '';
		$headers .= 'MIME-Version: 1.0'.EMAIL_NEWLINE;
		$headers .= 'Content-type: text/html; charset=UTF-8'.EMAIL_NEWLINE;
		$headers .= 'From: '.$settings['TITLE'].' <'.$settings['EMAIL_FROM'].'>'.EMAIL_NEWLINE;
		admin_mail($subject, $email, $headers, FALSE, 'admin/vps_hdspace_canceled.tpl');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'Addon Costs', 'vps_hd_cost', 'VPS Additional HD Space Cost:', 'This is the cost for purchasing additional HD space for a VPS.', $settings->get_setting('VPS_HD_COST'));
	}
}
