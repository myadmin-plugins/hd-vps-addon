<?php

namespace Detain\MyAdminVpsHd;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
	}

	public static function Load(GenericEvent $event) {
		$service = $event->getSubject();
		function_requirements('class.Addon');
		$addon = new \Addon();
		$addon->set_module('vps')->set_text('Additional GB')->set_text_match('Additional (.*) GB')
			->set_cost(VPS_HD_COST)->set_require_ip(false)->set_enable(function() {
				$service_info = $service_order->get_service_info();
				$settings = get_module_settings($service_order->get_module());
				require_once 'include/licenses/license.functions.inc.php';
				myadmin_log($service_order->get_module(), 'info', "Activating $space GB additional HD space for {$settings['TBLNAME']} {$service_info[$settings['PREFIX'].'_id']}", __LINE__, __FILE__);
				$GLOBALS['tf']->history->add($service_order->get_module() . 'queue', $service_info[$settings['PREFIX'] . '_id'], 'update_hdsize', $space, $service_info[$settings['PREFIX'] . '_custid']);
			})->set_disable(function() {
			})->register();
		$service->add_addon($addon);
	}

	public static function Settings(GenericEvent $event) {
		$module = 'vps';
		$settings = $event->getSubject();
		$settings->add_text_setting($module, 'Addon Costs', 'vps_hd_cost', 'VPS Additional HD Space Cost:', 'This is the cost for purchasing additional HD space for a VPS.', $settings->get_setting('VPS_HD_COST'));
	}
}
