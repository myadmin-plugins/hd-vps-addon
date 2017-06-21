<?php

namespace Detain\MyAdminVpsHd;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Additional HD Space VPS Addon';
	public static $description = 'Allows selling of Hd Server and VPS License Types.  More info at https://www.netenberg.com/hd.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a hd license. Allow 10 minutes for activation.';
	public static $module = 'vps';
	public static $type = 'addon';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			'vps.load_addons' => [__CLASS__, 'Load'],
			'vps.settings' => [__CLASS__, 'getSettings'],
		];
	}

	public static function Load(GenericEvent $event) {
		$service = $event->getSubject();
		function_requirements('class.Addon');
		$addon = new \Addon();
		$addon->set_module('vps')
			->set_text('Additional GB')
			->set_text_match('Additional (.*) GB')
			->set_cost(VPS_HD_COST)
			->set_require_ip(FALSE)
			->set_enable([__CLASS__, 'doEnable'])
			->set_disable([__CLASS__, 'doDisable'])
			->register();
		$service->add_addon($addon);
	}

	public static function doEnable(\Service_Order $serviceOrder) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings($serviceOrder->get_module());
		require_once 'include/licenses/license.functions.inc.php';
		myadmin_log($serviceOrder->get_module(), 'info', "activating $space GB additional HD space for {$settings['TBLNAME']} {$serviceInfo[$settings['PREFIX'].'_id']}", __LINE__, __FILE__);
		$GLOBALS['tf']->history->add($serviceOrder->get_module().'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'update_hdsize', $space, $serviceInfo[$settings['PREFIX'].'_custid']);
	}

	public static function doDisable(\Service_Order $serviceOrder) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings($serviceOrder->get_module());
		require_once 'include/licenses/license.functions.inc.php';
		myadmin_log($serviceOrder->get_module(), 'info', "activating $space GB additional HD space for {$settings['TBLNAME']} {$serviceInfo[$settings['PREFIX'].'_id']}", __LINE__, __FILE__);
		$GLOBALS['tf']->history->add($serviceOrder->get_module().'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'update_hdsize', $space, $serviceInfo[$settings['PREFIX'].'_custid']);
	}

	public static function getSettings(GenericEvent $event) {
		$module = 'vps';
		$settings = $event->getSubject();
		$settings->add_text_setting($module, 'Addon Costs', 'vps_hd_cost', 'VPS Additional HD Space Cost:', 'This is the cost for purchasing additional HD space for a VPS.', $settings->get_setting('VPS_HD_COST'));
	}
}
