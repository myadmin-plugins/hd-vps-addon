<?php

namespace Detain\MyAdminVpsHd;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
	}

	public static function Load(GenericEvent $event) {
		$service = $event->getSubject();
		$addon = new Addon();
		$addon->set_module('vps')->set_text('Additional GB')->set_text_match('Additional (.*) GB')
			->set_cost(VPS_HD_COST)->set_require_ip(false)->set_enable(function() {
			})->set_disable(function() {
			})->register();
	}

}
