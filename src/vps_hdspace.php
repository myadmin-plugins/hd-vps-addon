<?php
/**
 * VPS Functionality
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2018
 * @package MyAdmin
 * @category VPS
 */

/**
 * vps_hdspace()
 *
 * @return bool|void
 * @throws \Exception
 * @throws \SmartyException
 */
function vps_hdspace()
{
	$module = 'vps';
	$settings = get_module_settings($module);
	page_title('Purchase Additional VPS HD Space');
	$db = get_module_db($module);
	$db2 = clone $db;
	$settings = get_module_settings($module);
	$id = (int)$GLOBALS['tf']->variables->request['id'];
	$serviceInfo = get_service($id, $module);
	if ($serviceInfo === false) {
		dialog('Invalid VPS', 'Invalid VPS ID Passed');
		return false;
	}
	$GLOBALS['tf']->accounts->set_db_module('vps');
	$extra = parse_vps_extra($serviceInfo['vps_extra']);
	$table = new TFTable;
	if (mb_strpos($_SERVER['PHP_SELF'], 'iframe.php') !== false) {
		$table->set_post_location('iframe.php');
	}
	$table->add_hidden('id', $serviceInfo[$settings['PREFIX'].'_id']);
	$table->set_title('Additional VPS Drive Space');
	$table->add_field('VPS ID', 'r');
	$table->add_field($serviceInfo['vps_id'], 'r');
	$table->add_row();
	$table->add_field('Hostname', 'r');
	$table->add_field($serviceInfo['vps_hostname'], 'r');
	$table->add_row();
	if (isset($extra['diskused'])) {
		$table->add_field('Current HD Used', 'r');
		$table->add_field(round($extra['diskused'] / 1000000).' GB', 'r');
		$table->add_row();
		$table->add_field('Current HD Total', 'r');
		$table->add_field(round($extra['diskmax'] / 1000000).' GB', 'r');
		$table->add_row();
	}
	$frequency = $serviceInfo[$settings['PREFIX'].'_frequency'];
	$gbcost = round(get_reseller_price($module, 'hd', VPS_HD_COST), 2);
	$cost = $gbcost;
	$size = 0;
	$repeat_invoice = new \MyAdmin\Orm\Repeat_Invoice($db);
	$repeat_invoices = $repeat_invoice->find([['description','like','Additional % GB Space for VPS '.$serviceInfo['vps_id']]]);
	if (count($repeat_invoices) > 0) {
		$repeat_invoice->load_real($repeat_invoices[0]);
		$rinv = $repeat_invoice->get_raw_row();
		if (preg_match('/Additional (.*) GB Space/', $repeat_invoice->get_description(), $matches)) {
			$size = $matches[1];
		} else {
			add_output('Unable to get current addon disk usage.. please contact support@interserver.net about this');
			return false;
		}
		$table->add_field('Additional Space Ordered', 'r');
		$table->add_field($size.' GB', 'r');
		$table->add_row();
	}
	$cursize = $size;
	if (isset($GLOBALS['tf']->variables->request['size'])) {
		$size = (int)$GLOBALS['tf']->variables->request['size'];
	}
	$cost = $size * $cost;
	$service_invoice = new \MyAdmin\Orm\Repeat_Invoice($db);
	$service_invoice = $service_invoice->load_real($serviceInfo[$settings['PREFIX'].'_invoice']);
	if ($service_invoice->loaded === true) {
		$service_date = $db->fromTimestamp($service_invoice->get_date());
		$new_date = date('Y-m').'-'.date('d', $service_date).' 01:01:01';
		$curday = date('d');
		$oday = date('d', $service_date);
		if ($curday >= $oday) {
			$diffday = $curday - $oday;
		} else {
			$diffday = $oday - $curday;
		}
		$daysinmonth = date('t');
		$daycost = $cost / $daysinmonth;
		$diffcost = number_format(($daysinmonth - $diffday) * $daycost, 2);
	} else {
		$new_date = date('Y-m-d 01:01:01');
		$diffcost = $cost;
	}
	if ($frequency > 1) {
		$diffcost = round($diffcost + ($cost * ($frequency - 1)), 2);
	}
	$cost = round($cost * $frequency, 2);

	if (!isset($GLOBALS['tf']->variables->request['confirm']) || $GLOBALS['tf']->variables->request['confirm'] != 'yes') {
		$table->csrf('additional_hd');
		$GLOBALS['tf']->add_html_head_js_string('
	jQuery(function() {
		jQuery( "#hdslider" ).slider({
			range: "min",
			value:'.$size.',
			min: 0,
			max: 100,
			step: 10,
			slide: function( event, ui ) {
				jQuery( "#hdamount" ).val( "$" + (ui.value * '.$gbcost.') );
				jQuery( "#size" ).val( ui.value );
			}
		});
		jQuery( "#hdamount" ).val( "$" + ('.$gbcost.' * jQuery( "#hdslider" ).slider( "value" ) ) );
		jQuery( "#size" ).val( jQuery( "#hdslider" ).slider( "value" ) );
	});
');
		$table->add_field('Cost Per Month', 'r');
		$table->add_field('<input type=text id="hdamount" readonly=true style="border: none; text-align: right;" size=5>', 'r');
		$table->add_row();
		$table->add_field('Additional GB', 'r');
		$table->add_field('<input type=text id="size" name="size" style="text-align: right;" size=5>', 'r');
		$table->add_row();
		$table->set_colspan(2);
		$table->add_field('How Much Additional Space? (1-100 GB)');
		$table->add_row();
		$table->set_colspan(2);
		$table->add_field('<div id="hdslider"></div>');
		$table->add_row();
		$table->set_colspan(2);
		$table->add_field('Up to 100GB Additional HD Space can be purchased for $1.00 per 10 GB per Month');
		$table->add_row();
		$table->add_field('Confirm Purchase', 'r');
		$table->add_field('<select name=confirm><option value=no>No</option><option value=yes>Yes</option></select>', 'r');
		$table->add_row();
		$table->set_colspan(2);
		$table->add_field($table->make_submit('Continue'));
		$table->add_row();
		add_output($table->get_table());
	} elseif (verify_csrf('additional_hd')) {
		if ($size >= 1 && $size <= 100) {
			myadmin_log('vps', 'info', 'Update Additional Drive Space Function Called', __LINE__, __FILE__);
			myadmin_log('vps', 'info', '  VPS ID: '.$serviceInfo[$settings['PREFIX'].'_id'], __LINE__, __FILE__);
			myadmin_log('vps', 'info', '  VPS Cust ID: '.$serviceInfo[$settings['PREFIX'].'_custid'], __LINE__, __FILE__);
			myadmin_log('vps', 'info', '  VPS Hostname: '.$serviceInfo[$settings['PREFIX'].'_hostname'], __LINE__, __FILE__);
			myadmin_log('vps', 'info', "	New Size: {$size}", __LINE__, __FILE__);
			myadmin_log('vps', 'info', "	Previous Update Size: {$cursize}", __LINE__, __FILE__);
			myadmin_log('vps', 'info', "	New Cost: {$cost}", __LINE__, __FILE__);
			myadmin_log('vps', 'info', "	Diff Cost: {$diffcost}", __LINE__, __FILE__);
			if ($size != $cursize) {
				$description = 'Additional '.$size.' GB Space for '.$settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_id'];
				// check if they previously purchased additional drive space
				if ($cursize > 0) {
					$rid = $repeat_invoice->get_id();
					myadmin_log('vps', 'info', "	Updated Repeat Invoice $rid", __LINE__, __FILE__);
					$repeat_invoice->setDescription($description)
						->setCost($cost)
						->save();
					$invoice = new \MyAdmin\Orm\Invoice($db);
					$invoices = $invoice->find([['type','=','1'],['extra','=',$rid],['date','>=',mysql_date_sub(mysql_now(), 'INTERVAL 1 MONTH')]]);
					foreach ($invoices as $invoice_id) {
						$invoice->load_real($invoice_id);
						if ($invoice->get_paid() == 1) {
							$diffcost = bcsub($diffcost, $invoice->get_amount(), 2);
							myadmin_log('vps', 'info', '	Crediting '.$invoice->get_amount().' Due To Paid Invoice '.$invoice->get_id(), __LINE__, __FILE__);
						} else {
							sql_delete_by_id('invoices', $db->Record['invoices_id'], $serviceInfo[$settings['PREFIX'].'_custid'], $module);
							myadmin_log('vps', 'info', '	Deleting Unpaid Invoice '.$db->Record['invoices_id'], __LINE__, __FILE__);
						}
					}
				} else {
					$repeat_invoice = new \MyAdmin\Orm\Repeat_Invoice($db);
					$repeat_invoice->setId(null)
						->setDescription($description)
						->setType(1)
						->setCost($cost)
						->setCustid($serviceInfo[$settings['PREFIX'].'_custid'])
						->setFrequency($frequency)
						->setDate($new_date)
						->setModule($module)
						->setService($id)
						->save();
					$rid = $repeat_invoice->get_id();
					myadmin_log('vps', 'info', "	Created new \\MyAdmin\\Orm\\Invoice {$rid}", __LINE__, __FILE__);
				}
				if ($diffcost > 0) {
					$invoice = $repeat_invoice->invoice($new_date, (float)$diffcost, false);
					$iid = $invoice->get_id();
					myadmin_log('vps', 'info', "	Created Invoice {$iid} For {$diffcost}", __LINE__, __FILE__);
					add_output('Invoice Created, Please Pay This To Activate Extra Space<br>');
				} else {
					myadmin_log('vps', 'info', '	Queued Drive Update', __LINE__, __FILE__);
					//got here if the space shrank
					add_output('Repeat Invoice Updated, Server Size Update Queued');
					$GLOBALS['tf']->history->add($module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'update_hdsize', '', $serviceInfo[$settings['PREFIX'].'_custid']);
				}
				if (mb_strpos($_SERVER['PHP_SELF'], 'iframe.php') === false) {
					function_requirements('view_vps');
					view_vps($serviceInfo[$settings['PREFIX'].'_id']);
				}
			} else {
				add_output('No Change Made, Size The Same');
			}
		} else {
			add_output('Invalid Size Specified');
		}
	}
}
