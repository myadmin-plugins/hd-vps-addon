<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_hd define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Hd Licensing VPS Addon',
	'description' => 'Allows selling of Hd Server and VPS License Types.  More info at https://www.netenberg.com/hd.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a hd license. Allow 10 minutes for activation.',
	'module' => 'vps',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-hd-vps-addon',
	'repo' => 'https://github.com/detain/myadmin-hd-vps-addon',
	'version' => '1.0.0',
	'type' => 'addon',
	'hooks' => [
		'vps.load_addons' => ['Detain\MyAdminVpsHd\Plugin', 'Load'],
		/* 'function.requirements' => ['Detain\MyAdminVpsHd\Plugin', 'Requirements'],
		'licenses.settings' => ['Detain\MyAdminVpsHd\Plugin', 'Settings'],
		'licenses.activate' => ['Detain\MyAdminVpsHd\Plugin', 'Activate'],
		'licenses.change_ip' => ['Detain\MyAdminVpsHd\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminVpsHd\Plugin', 'Menu'] */
	],
];
