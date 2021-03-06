<?php
defined('_JEXEC') or die;

// LOAD FILTER
require($APPNAME.'.filter.php');

// LIST

	// pagination var's
	$limitDef = !isset($_SESSION[$APPTAG.'plim']) ? $cfg['pagLimit'] : $_SESSION[$APPTAG.'plim'];
	$_SESSION[$APPTAG.'plim']	= $app->input->post->get('list-lim-'.$APPTAG, $limitDef, 'int');
	$lim	= $app->input->get('limit', ($_SESSION[$APPTAG.'plim'] !== 1 ? $_SESSION[$APPTAG.'plim'] : 10000000), 'int');
	$lim0	= $app->input->get('limitstart', 0, 'int');

	$query = '
		SELECT SQL_CALC_FOUND_ROWS
			'. $db->quoteName('T1.id') .',
			'. $db->quoteName('T1.type') .',
			'. $db->quoteName('T1.proposal') .',
			'. $db->quoteName('T1.name') .',
			'. $db->quoteName('T1.name_company') .',
			'. $db->quoteName('T1.email') .',
			'. $db->quoteName('T1.doc_number') .',
			'. $db->quoteName('T1.description') .',
			'. $db->quoteName('T1.date') .',
			'. $db->quoteName('T1.due_date') .',
			'. $db->quoteName('T1.note') .',
			'. $db->quoteName('T1.state') .',
			'. $db->quoteName('T1.created_date') .',
			'. $db->quoteName('T2.username') .' user
		FROM
			'. $db->quoteName($cfg['mainTable']) .' T1
			JOIN '. $db->quoteName('#__users') .' T2
			ON T2.id = T1.created_by
		WHERE
			'.$where.$orderList;
	;
	try {

		$db->setQuery($query, $lim0, $lim);
		$db->execute();
		$num_rows = $db->getNumRows();
		$res = $db->loadObjectList();

	} catch (RuntimeException $e) {
		 echo $e->getMessage();
		 return;
	}

// VIEW
$html = '
	<form id="form-list-'.$APPTAG.'" method="post">
		<table class="table table-striped table-hover table-condensed">
			<thead>
				<tr>
					<th width="30" class="hidden-print"><input type="checkbox" id="'.$APPTAG.'_checkAll" /></th>
					<th width="50" class="hidden-print">'.$$SETOrder('#', 'T1.id', $APPTAG).'</th>
					<th>'.$$SETOrder(JText::_('FIELD_LABEL_NAME'), 'T1.name', $APPTAG).'</th>
					<th>'.JText::_('FIELD_LABEL_DOC_NUMBER').'</th>
					<th class="text-center">'.$$SETOrder(JText::_('FIELD_LABEL_DUE_DATE'), 'T1.due_date', $APPTAG).'</th>
					<th class="text-center">'.$$SETOrder('Desde', 'T1.date', $APPTAG).'</th>
					<th class="text-center hidden-print" width="60">'.$$SETOrder(JText::_('TEXT_ACTIVE'), 'T1.state', $APPTAG).'</th>
					<th class="text-center hidden-print" width="120">'.JText::_('TEXT_ACTIONS').'</th>
				</tr>
			</thead>
			<tbody>
';

if($num_rows) : // verifica se existe

	// pagination
	$db->setQuery('SELECT FOUND_ROWS();');  //no reloading the query! Just asking for total without limit
	jimport('joomla.html.pagination');
	$found_rows = $db->loadResult();
	$pageNav = new JPagination($found_rows , $lim0, $lim );

	foreach($res as $item) {

		if($cfg['hasUpload']) :
			JLoader::register('uploader', JPATH_BASE.'/templates/base/source/helpers/upload.php');
			$files[$item->id] = uploader::getFiles($cfg['fileTable'], $item->id);
			$listFiles = '';
			for($i = 0; $i < count($files[$item->id]); $i++) {
				if(!empty($files[$item->id][$i]->filename)) :
					$listFiles .= '
						<a href="'.JURI::root(true).'/get-file?fn='.base64_encode($files[$item->id][$i]->filename).'&mt='.base64_encode($files[$item->id][$i]->mimetype).'&tag='.base64_encode($APPNAME).'">
							<span class="base-icon-attach hasTooltip" title="'.$files[$item->id][$i]->filename.'<br />'.((int)($files[$item->id][$i]->filesize / 1024)).'kb"></span>
						</a>
					';
				endif;
			}
		endif;

		if($item->proposal == 1) :
			// conta os meses
			$diff = baseHelper::dateDiff($item->date, 'now');
			$Y = baseHelper::pluralize($diff['y'], '%s '.JText::_('TEXT_YEAR'), '%s '.JText::_('TEXT_YEARS'), ' ');
			$M = baseHelper::pluralize($diff['m'], '%s '.JText::_('TEXT_MONTH'), '%s '.JText::_('TEXT_MONTHS'), ' & ');
			$D = baseHelper::pluralize($diff['d'], '%s '.JText::_('TEXT_DAY'), '%s '.JText::_('TEXT_DAYS'));
			$context = ($diff['M'] >= 2 ? 'danger' : 'warning');
			$tip = strtolower($Y.$M.$D);
			$tip = ($diff['M'] >= 1 ? $tip : JText::_('TEXT_WAITING'));
			$proposal = '<span class="base-icon-clock label label-'.$context.' text-sm cursor-help hasTooltip" title="'.$tip.'"> '.JText::_('TEXT_PROPOSAL').'</span>';
			$invoiceInfo = '<td class="valign-center" colspan="2">'.$proposal.'</td>';
		else :
			$proposal = '';
			$dueDate = $item->due_date < 10 ? '0'.$item->due_date : $item->due_date;
			$invoiceInfo = '
				<td class="valign-center">'.(!empty($item->doc_number) ? $item->doc_number : '-').'</td>
				<td class="text-center valign-center">'.$dueDate.'</td>
			';
		endif;
		if($item->type == 1) :
			$type = '<span class="base-icon-empire cursor-help hasTooltip" title="'.JText::_('TEXT_COMPANY').'"></span> ';
		else :
			$type = '<span class="base-icon-male cursor-help hasTooltip" title="'.JText::_('TEXT_PERSONA').'"></span> ';
		endif;
		$info = '';
		if(!empty($item->name_company) || !empty($item->description)) :
			if(!empty($item->name_company)) $info = '<div class=\'strong\'>'.baseHelper::nameFormat($item->name_company).'</div>';
			if(!empty($item->description)) $info .= '<small>'.$item->description.'</small>';
			if(!empty($item->note)) $info .= '<hr class=\'hr-xs\' /><small class=\'base-icon-info-circled\'> '.$item->note.'</small>';
			$info = '<span class="base-icon-info-circled cursor-help text-info hasPopover" data-placement="top" data-content="'.$info.'"></span >';
		endif;
		$rowState = $item->state == 0 ? 'danger' : ($item->proposal == 1 ? 'warning' : '');
		$html .= '
			<tr id="'.$APPTAG.'-item-'.$item->id.'" class="'.$rowState.'">
				<td class="check-row hidden-print"><input type="checkbox" name="'.$APPTAG.'_ids[]" class="'.$APPTAG.'-chk" value="'.$item->id.'" /></td>
				<td class="hidden-print">'.$item->id.'</td>
				<td>
					'.$type.$item->name.'<br />
					'.$info.'
					<a href="#" class="base-icon-user text-muted hasTooltip" onclick="clientsContacts_listReload(false, false, false, true, \'client_id\', '.$item->id.')" data-toggle="modal" data-target="#modal-list-clientsContacts" title="'.JText::_('MSG_VIEW_CONTACTS').'"></a>
					<a href="#" class="base-icon-location text-muted hasTooltip" onclick="addresses_listReload(false, false, false, false, false, '.$item->id.')" data-toggle="modal" data-target="#modal-list-addresses" title="'.JText::_('MSG_VIEW_ADDRESS').'"></a>
					<a href="#" class="base-icon-phone-squared text-muted hasTooltip" onclick="phones_listReload(false, false, false, false, false, '.$item->id.')" data-toggle="modal" data-target="#modal-list-phones" title="'.JText::_('MSG_VIEW_PHONE').'"></a>
					<a href="#" class="base-icon-chat-empty text-muted hasTooltip" onclick="webSocials_listReload(false, false, false, false, false, '.$item->id.')" data-toggle="modal" data-target="#modal-list-webSocials" title="'.JText::_('MSG_VIEW_SOCIAL').'"></a>
					<a href="#" class="base-icon-globe text-muted hasTooltip" onclick="domains_listReload(false, false, false, true, \'client_id\', '.$item->id.')" data-toggle="modal" data-target="#modal-list-domains" title="'.JText::_('MSG_VIEW_DOMAINS').'"></a>
					<a href="#" class="base-icon-bank text-muted hasTooltip" onclick="banksAccounts_listReload(false, false, false, false, false, '.$item->id.')" data-toggle="modal" data-target="#modal-list-banksAccounts" title="'.JText::_('MSG_VIEW_BANKS_ACCOUNTS').'"></a>
				</td>
				'.$invoiceInfo.'
				<td class="text-center valign-center">'.baseHelper::dateFormat($item->date).'</td>
				<td class="text-center valign-center">
					<a href="#" onclick="'.$APPTAG.'_setState('.$item->id.')" id="'.$APPTAG.'-state-'.$item->id.'">
						<span class="'.($item->state == 1 ? 'base-icon-ok text-success' : 'base-icon-cancel text-danger').' hasTooltip" title="'.JText::_('MSG_ACTIVE_INACTIVE_ITEM').'"></span>
					</a>
				</td>
				<td class="text-center">
					<a href="#" class="btn btn-xs btn-success" onclick="clientsContacts_setParent('.$item->id.')" data-toggle="modal" data-target="#modal-clientsContacts" data-backdrop="static" data-keyboard="false"><span class="base-icon-user-add hasTooltip" title="'.JText::_('MSG_INSERT_CONTACT').'"></span></a>
					<a href="#" class="btn btn-xs btn-success" onclick="addresses_setRelation('.$item->id.')" data-toggle="modal" data-target="#modal-addresses" data-backdrop="static" data-keyboard="false"><span class="base-icon-location hasTooltip" title="'.JText::_('MSG_INSERT_ADDRESS').'"></span></a>
					<a href="#" class="btn btn-xs btn-success" onclick="phones_setRelation('.$item->id.')" data-toggle="modal" data-target="#modal-phones" data-backdrop="static" data-keyboard="false"><span class="base-icon-phone hasTooltip" title="'.JText::_('MSG_INSERT_PHONE').'"></span></a>
					<a href="#" class="btn btn-xs btn-success" onclick="webSocials_setRelation('.$item->id.')" data-toggle="modal" data-target="#modal-webSocials" data-backdrop="static" data-keyboard="false"><span class="base-icon-chat-empty hasTooltip" title="'.JText::_('MSG_INSERT_SOCIAL').'"></span></a>
					<a href="#" class="btn btn-xs btn-success" onclick="domains_setParent('.$item->id.')" data-toggle="modal" data-target="#modal-domains" data-backdrop="static" data-keyboard="false"><span class="base-icon-globe hasTooltip" title="'.JText::_('MSG_INSERT_DOMAIN').'"></span></a>
					<a href="#" class="btn btn-xs btn-success" onclick="banksAccounts_setRelation('.$item->id.')" data-toggle="modal" data-target="#modal-banksAccounts" data-backdrop="static" data-keyboard="false"><span class="base-icon-bank hasTooltip" title="'.JText::_('MSG_INSERT_BANK_ACCOUNT').'"></span></a>
					<a href="#" class="btn btn-xs btn-warning" onclick="'.$APPTAG.'_loadEditFields('.$item->id.', false, false)"><span class="base-icon-pencil hasTooltip" title="'.JText::_('TEXT_EDIT').'"></span></a>
					<a href="#" class="btn btn-xs btn-danger" onclick="'.$APPTAG.'_del('.$item->id.', false)"><span class="base-icon-trash hasTooltip" title="'.JText::_('TEXT_DELETE').'"></span></a>
				</td>
			</tr>
		';
	}

else : // num_rows = 0

	$html .= '
		<tr>
			<td colspan="8">
				<div class="alert alert-warning alert-icon no-margin">'.JText::_('MSG_LISTNOREG').'</div>
			</td>
		</tr>
	';

endif;

$html .= '
			</tbody>
		</table>
	</form>
';

if($num_rows) :

	// PAGINAÇÃO
		// stats
		$listStart	= $lim0 + 1;
		$listEnd		= $lim0 + $num_rows;
	if($found_rows != $num_rows) :
		$html .= '
			<div class="base-app-pagination pull-left">
				'.$pageNav->getListFooter().'
				<div class="list-stats small text-muted">
					'.JText::sprintf('LIST_STATS', $listStart, $listEnd, $found_rows).'
				</div>
			</div>
		';
	endif;

	$html .= '
		<form id="form-order-'.$APPTAG.'" action="'.$_SERVER['REQUEST_URI'].'" class="pull-right form-inline" method="post">
			<input type="hidden" name="'.$APPTAG.'oF" id="'.$APPTAG.'oF" value="'.$_SESSION[$APPTAG.'oF'].'" />
			<input type="hidden" name="'.$APPTAG.'oT" id="'.$APPTAG.'oT" value="'.$_SESSION[$APPTAG.'oT'].'" />
		</form>
	';

	// ITENS POR PÁGINA
	// seta o parametro 'start = 0' na URL sempre que o limit for refeito
	// isso evita erro quando estiver navegando em páginas subjacentes
	$a = preg_replace("#\?start=.*#", '', $_SERVER['REQUEST_URI']);
	$a = preg_replace("#&start=.*#", '', $a);

	$html .= '
		<form id="form-limit-'.$APPTAG.'" action="'.$a.'" class="pull-right form-inline hidden-print" method="post">
			<label>'.JText::_('LIST_PAGINATION_LIMIT').'</label>
			<select name="list-lim-'.$APPTAG.'" onchange="'.$APPTAG.'_setListLimit()">
				<option value="5" '.($_SESSION[$APPTAG.'plim'] === 5 ? 'selected' : '').'>5</option>
				<option value="20" '.($_SESSION[$APPTAG.'plim'] === 20 ? 'selected' : '').'>20</option>
				<option value="50" '.($_SESSION[$APPTAG.'plim'] === 50 ? 'selected' : '').'>50</option>
				<option value="100" '.($_SESSION[$APPTAG.'plim'] === 100 ? 'selected' : '').'>100</option>
				<option value="1" '.($_SESSION[$APPTAG.'plim'] === 1 ? 'selected' : '').'>Todos</option>
			</select>
		</form>
	';

endif;

return $htmlFilter.$html;

?>
