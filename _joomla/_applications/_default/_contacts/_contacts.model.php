<?php
// BLOCK DIRECT ACCESS
if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) AND strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") :

	header( 'Cache-Control: no-cache' );
	header( 'content-type: application/json; charset=utf-8' );

	function is_valid_callback($subject) {
		$identifier_syntax = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';

		$reserved_words = array(
			'break', 'do', 'instanceof', 'typeof', 'case',
			'else', 'new', 'var', 'catch', 'finally', 'return', 'void', 'continue',
			'for', 'switch', 'while', 'debugger', 'function', 'this', 'with',
			'default', 'if', 'throw', 'delete', 'in', 'try', 'class', 'enum',
			'extends', 'super', 'const', 'export', 'import', 'implements', 'let',
			'private', 'public', 'yield', 'interface', 'package', 'protected',
			'static', 'null', 'true', 'false'
		);

		return preg_match($identifier_syntax, $subject) && ! in_array(mb_strtolower($subject, 'UTF-8'), $reserved_words);
	}

	// load Joomla's framework
	// _DIR_ => apps/THIS_APP
	require(__DIR__.'/../../libraries/envolute/_init.joomla.php');
	defined('_JEXEC') or die;
	$app = JFactory::getApplication('site');

	$ajaxRequest = true;
	require('config.php');

	// IMPORTANTE: Carrega o arquivo 'helper' do template
	JLoader::register('baseHelper', JPATH_CORE.DS.'helpers/base.php');
    // classes customizadas para usuários Joomla
    JLoader::register('baseUserHelper',  JPATH_CORE.DS.'helpers/user.php');
	$lang->load('lib_joomla', JPATH_ADMINISTRATOR, $_SESSION[$APPTAG.'langDef'], true);

	// get current user's data
	$user		= JFactory::getUser();
	$groups		= $user->groups;

	//joomla get request data
	$input      = $app->input;

	// Default Params
	$APPTAG		= $input->get('aTag', $APPTAG, 'str');
	$RTAG		= $input->get('rTag', $APPTAG, 'str');
	$task       = $input->get('task', null, 'str');
	$data       = array();

	if($task != null) :

		// database connect
		$db = JFactory::getDbo();

		// Carrega o arquivo de tradução
		// OBS: para arquivos externos com o carregamento do framework '_init.joomla.php' (geralmente em 'ajax')
		// a language 'default' não é reconhecida. Sendo assim, carrega apenas 'en-GB'
		// Para possibilitar o carregamento da language 'default' de forma dinâmica,
		// é necessário passar na sessão ($_SESSION[$APPTAG.'langDef'])
		if(isset($_SESSION[$APPTAG.'langDef'])) :
			$lang->load('base_apps', JPATH_BASE, $_SESSION[$APPTAG.'langDef'], true);
			$lang->load('base_'.$APPNAME, JPATH_BASE, $_SESSION[$APPTAG.'langDef'], true);
		endif;

		// params requests
		$id         = $input->get('id', 0, 'int');

		// fields 'List' requests
		$listIds    = $input->get($APPTAG.'_ids', array(), 'array');
		$ids        = (count($listIds) > 0) ? implode($listIds, ',') : $id;
		$state      = $input->get('st', 2, 'int');

		// upload actions
		$fileMsg 	= '';
		if($cfg['hasUpload']) :
			$fname		= $input->get('fname', '', 'string');
			$fileId		= $input->get('fileId', 0, 'int');
			// image groups
			$fileGrp	= isset($_POST[$cfg['fileField'].'Group']) ? $_POST[$cfg['fileField'].'Group'] : '';
			$fileGtp	= isset($_POST[$cfg['fileField'].'Gtype']) ? $_POST[$cfg['fileField'].'Gtype'] : '';
			$fileCls	= isset($_POST[$cfg['fileField'].'Class']) ? $_POST[$cfg['fileField'].'Class'] : '';
			// image description
			$fileLbl	= isset($_POST[$cfg['fileField'].'Label']) ? $_POST[$cfg['fileField'].'Label'] : '';
			// load 'uploader' class
			JLoader::register('uploader', JPATH_CORE.DS.'helpers/files/upload.php');
		endif;

		// fields 'Form' requests
		$request						= array();
		// default
		$request['relationId']			= $input->get('relationId', 0, 'int');
		$request['state']				= $input->get('state', 1, 'int');
		// app
		$request['newUser']				= $input->get('newUser', 0, 'int');
		$request['user_id']				= $input->get('user_id', 0, 'int');
			// Define o usuário
			$userID						= $request['newUser'] ? $request['newUser'] : $request['user_id'];
		$request['group_id']			= $input->get('group_id', 0, 'int');
		$request['name']				= $input->get('name', '', 'string');
		$request['nickname']			= $input->get('nickname', '', 'string');
		$request['email']				= $input->get('email', '', 'string');
		$request['cpf']					= $input->get('cpf', '', 'string');
		$request['rg']					= $input->get('rg', '', 'string');
		$request['rg_orgao']			= $input->get('rg_orgao', '', 'string');
		$request['gender']				= $input->get('gender', 1, 'int');
		$request['birthday']			= $input->get('birthday', '', 'string');
		$request['occupation']			= $input->get('occupation', '', 'string');
		$request['marital_status']		= $input->get('marital_status', 0, 'int');
		$request['partner']				= $input->get('partner', '', 'string');
	  	$request['children']			= $input->get('children', 0, 'int');
		$request['zip_code']			= $input->get('zip_code', '', 'string');
		$request['address']				= $input->get('address', '', 'string');
		$request['address_number']		= $input->get('address_number', '', 'string');
		$request['address_info']		= $input->get('address_info', '', 'string');
		$request['address_district']	= $input->get('address_district', '', 'string');
		$request['address_city']		= $input->get('address_city', '', 'string');
		$request['address_state']		= $input->get('address_state', '', 'string');
		$request['address_country']		= $input->get('address_country', '', 'string');
		$request['onlyBR']				= $input->get('onlyBR', 0, 'int');
		$phone							= $input->get('phone', '', 'array');
		$phone							= str_replace(';', '.', $phone); // formata
		$request['phone']				= implode(';', $phone);
		$whatsapp						= $input->get('whatsapp', '', 'array');
		$whatsapp						= str_replace(';', '.', $whatsapp); // formata
		$request['whatsapp']			= implode(';', $whatsapp);
		$phone_desc						= $input->get('phone_desc', '', 'array');
		$phone_desc						= str_replace(';', '.', $phone_desc); // formata
		$request['phone_desc']			= implode(';', $phone_desc);
		$chat_name						= $input->get('chat_name', '', 'array');
		$chat_name						= str_replace(';', '.', $chat_name); // formata
		$request['chat_name']			= implode(';', $chat_name);
		$chat_user						= $input->get('chat_user', '', 'array');
		$chat_user						= str_replace(';', '.', $chat_user); // formata
		$request['chat_user']			= implode(';', $chat_user);
		$weblink_text					= $input->get('weblink_text', '', 'array');
		$weblink_text					= str_replace(';', '.', $weblink_text); // formata
		$request['weblink_text']		= implode(';', $weblink_text);
		$weblink_url					= $input->get('weblink_url', '', 'array');
		$weblink_url					= str_replace(';', '.', $weblink_url); // formata
		$request['weblink_url']			= implode(';', $weblink_url);
		$request['extra_info']			= $input->get('extra_info', '', 'raw');
		$request['note']				= $input->get('note', '', 'string');
	    // user registration action
	  	$request['access']				= $input->get('access', 0, 'int');
	    $request['username']			= $input->get('username', '', 'string');
		$request['usergroups']			= isset($_POST['usergroups']) ? $_POST['usergroups'] : $_SESSION[$APPTAG.'newUsertype'];
		$request['password']			= $input->get('password', '', 'string');
	  	$request['repassword']			= $input->get('repassword', '', 'string');
	  	$request['emailConfirm']		= $input->get('emailConfirm', 0, 'int');
	  	$request['emailInfo']			= $input->get('emailInfo', '', 'string');
	  	$request['reasonStatus']		= $input->get('reasonStatus', '', 'string');
		// Se o acesso for liberado, limpa o valor do campo 'motivo'
		$reason = $request['access'] == 1 ? '' : $request['reasonStatus'];

	    // CUSTOM -> default vars for registration e-mail
	    $config			= JFactory::getConfig();
	    $sitename		= $config->get('sitename');
	    $domain			= baseHelper::getDomain();
	    $subject		= JText::sprintf('MSG_ACTIVATION_EMAIL_SUBJECT', $sitename);
	    $mailFrom		= $config->get('mailfrom');

		// SAVE CONDITION
		// Condição para inserção e atualização dos registros
		$save_condition = (!empty($request['group_id']) && !empty($request['name']));

		if($id || (!empty($ids) && $ids != 0)) :  //UPDATE OR DELETE

			$exist = 0;
			if($id) :
				// GET FORM DATA
				$query	= 'SELECT * FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '. $db->quoteName('id') .' = '. $id;
				$db->setQuery($query);
				$item	= $db->loadObject();
	    		$exist	= (isset($item->id) && !empty($item->id) && $item->id > 0);
				// CUSTOM -> VERIFY IF IS REGISTERED USER
				$isUser = $userInfoId = $userInfoBlock = $userGroups = 0;
				$userInfoName = $userInfoEmail = '';
				if($item->user_id != 0) :
					$usr			= baseUserHelper::getUserData($item->user_id);
					$isUser         = $usr['exist'];
					$userInfo       = $usr['obj'];
					// $usr['id']      =
					$userInfoId     = isset($userInfo[0]['id']) ? $userInfo[0]['id'] : 0;
					$userInfoName   = isset($userInfo[0]['name']) ? $userInfo[0]['name'] : '';
					$userInfoEmail  = isset($userInfo[0]['email']) ? $userInfo[0]['email'] : '';
			        $userInfoBlock  = isset($userInfo[0]['block']) ? $userInfo[0]['block'] : 0;
				endif;
				// get previous ID
				$query = 'SELECT MAX(id) FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '. $db->quoteName('id') .' < '. $id;
				$db->setQuery($query);
				$prev = $db->loadResult();
				$prev = !empty($prev) ? $prev : 0;
				// get next ID
				$query = 'SELECT MIN(id) FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '. $db->quoteName('id') .' > '. $id;
				$db->setQuery($query);
				$next = $db->loadResult();
				$next = !empty($next) ? $next : 0;
				if($cfg['hasUpload']) :
					// get files
					$query = 'SELECT *, TO_BASE64('. $db->quoteName('filename') .') fn, TO_BASE64('. $db->quoteName('mimetype') .') mt FROM '. $db->quoteName($cfg['fileTable']) .' WHERE '. $db->quoteName('id_parent') .' = '. $id . ' ORDER BY '. $db->quoteName('index');
					$db->setQuery($query);
					$listFiles = $db->loadAssocList();
				endif;
			else :
				// COUNT LIST IDS
				$query = 'SELECT COUNT(*) FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '. $db->quoteName('id') .' IN ('.$ids.')';
				$db->setQuery($query);
				$exist = $db->loadResult();
			endif;

			if($exist) : // verifica se existe

				// GET FORM DATA
				if($task == 'get') :

					$itemUID    = ($isUser) ? $userInfoId : 0;
					$itemName   = ($isUser) ? baseHelper::nameFormat($userInfoName) : '';
					$itemEmail  = ($isUser) ? $userInfoEmail : $item->email;
					$itemBlock  = ($isUser) ? $userInfoBlock : 1; // inverso do 'access'
					// Obs: Se 'block' = 1 / 'access' = 0;
					// O padrão do 'Acesso' na edição é 'Não => 0' ('block = 1'),
					// pois caso não exista um usuário associado ao cliente
					// o campo de acesso aparece como falso 'Não'

					$data[] = array(
						// Default Fields
						'id'				=> $item->id,
						'state'				=> $item->state,
						'prev'				=> $prev,
						'next'				=> $next,
						// App Fields
						'group_id'			=> $item->group_id,
						'user_id'			=> $itemUID,
						'user'				=> $itemName,
						'name'				=> $item->name,
						'nickname'			=> $item->nickname,
						'email'				=> $itemEmail,
						'cpf'				=> $item->cpf,
						'rg'				=> $item->rg,
						'rg_orgao'			=> $item->rg_orgao,
						'gender'			=> $item->gender,
						'birthday'			=> $item->birthday,
						'occupation'		=> $item->occupation,
						'marital_status'	=> $item->marital_status,
						'partner'			=> $item->partner,
						'children'			=> $item->children,
						'zip_code'			=> $item->zip_code,
						'address'			=> $item->address,
						'address_number'	=> $item->address_number,
						'address_info'		=> $item->address_info,
						'address_district'	=> $item->address_district,
						'address_city'		=> $item->address_city,
						'address_state'		=> $item->address_state,
						'address_country'	=> $item->address_country,
						'onlyBR'			=> $item->onlyBR,
						'phone'				=> $item->phone,
						'whatsapp'			=> $item->whatsapp,
						'phone_desc'		=> $item->phone_desc,
						'chat_name'			=> $item->chat_name,
						'chat_user'			=> $item->chat_user,
						'weblink_text'		=> $item->weblink_text,
						'weblink_url'		=> $item->weblink_url,
						'extra_info'		=> $item->extra_info,
						'note'				=> $item->note,
						'access'			=> ($itemBlock ? 0 : 1),
						'reasonStatus'		=> $item->reasonStatus,
						'files'				=> $listFiles
					);

				// UPDATE
				elseif($task == 'save' && $save_condition && $id) :

					$query  = 'UPDATE '.$db->quoteName($cfg['mainTable']).' SET ';
					$query .=
						$db->quoteName('group_id')			.'='. $request['group_id'] .','.
						$db->quoteName('user_id')			.'='. $userID .','.
						$db->quoteName('name')				.'='. $db->quote($request['name']) .','.
						$db->quoteName('nickname')			.'='. $db->quote($request['nickname']) .','.
						$db->quoteName('email')				.'='. $db->quote($request['email']) .','.
						$db->quoteName('cpf')				.'='. $db->quote($request['cpf']) .','.
						$db->quoteName('rg')				.'='. $db->quote($request['rg']) .','.
						$db->quoteName('rg_orgao')			.'='. $db->quote($request['rg_orgao']) .','.
						$db->quoteName('gender')			.'='. $request['gender'] .','.
						$db->quoteName('birthday')			.'='. $db->quote($request['birthday']) .','.
						$db->quoteName('occupation')		.'='. $db->quote($request['occupation']) .','.
						$db->quoteName('marital_status') 	.'='. $request['marital_status'] .','.
						$db->quoteName('partner')			.'='. $db->quote($request['partner']) .','.
						$db->quoteName('children')			.'='. $request['children'] .','.
						$db->quoteName('zip_code')			.'='. $db->quote($request['zip_code']) .','.
						$db->quoteName('address')			.'='. $db->quote($request['address']) .','.
						$db->quoteName('address_number')	.'='. $db->quote($request['address_number']) .','.
						$db->quoteName('address_info')		.'='. $db->quote($request['address_info']) .','.
						$db->quoteName('address_district')	.'='. $db->quote($request['address_district']) .','.
						$db->quoteName('address_city')		.'='. $db->quote($request['address_city']) .','.
						$db->quoteName('address_state')		.'='. $db->quote($request['address_state']) .','.
						$db->quoteName('address_country')	.'='. $db->quote($request['address_country']) .','.
						$db->quoteName('onlyBR')			.'='. $request['onlyBR'] .','.
						$db->quoteName('phone')				.'='. $db->quote($request['phone']) .','.
						$db->quoteName('whatsapp')			.'='. $db->quote($request['whatsapp']) .','.
						$db->quoteName('phone_desc')		.'='. $db->quote($request['phone_desc']) .','.
						$db->quoteName('chat_name')			.'='. $db->quote($request['chat_name']) .','.
						$db->quoteName('chat_user')			.'='. $db->quote($request['chat_user']) .','.
						$db->quoteName('weblink_text')		.'='. $db->quote($request['weblink_text']) .','.
						$db->quoteName('weblink_url')		.'='. $db->quote($request['weblink_url']) .','.
						$db->quoteName('extra_info')		.'='. $db->quote($request['extra_info']) .','.
						$db->quoteName('note')				.'='. $db->quote($request['note']) .','.
						$db->quoteName('access')			.'='. $request['access'] .','.
						$db->quoteName('reasonStatus')		.'='. $db->quote($reason) .','.
						$db->quoteName('state')				.'='. $request['state'] .','.
						$db->quoteName('alter_date')		.'= NOW(),'.
						$db->quoteName('alter_by')			.'='. $user->id
					;
					$query .= ' WHERE '. $db->quoteName('id') .'='. $id;

					try {

						$db->setQuery($query);
						$db->execute();

						// Upload
						if($cfg['hasUpload'])
						$fileMsg = uploader::uploadFile($id, $cfg['fileTable'], $_FILES[$cfg['fileField']], $fileGrp, $fileGtp, $fileCls, $fileLbl, $cfg);

						// UPDATE FIELD
						$element = $elemVal = $elemLabel = '';
						if(!empty($_SESSION[$RTAG.'FieldUpdated']) && !empty($_SESSION[$RTAG.'TableField'])) :
							$element = $_SESSION[$RTAG.'FieldUpdated'];
							$elemVal = $id;
							$query = 'SELECT '. $db->quoteName($_SESSION[$RTAG.'TableField']) .' FROM '. $db->quoteName($cfg['mainTable']).' WHERE '. $db->quoteName('id') .' = '.$id.' AND state = 1';
							$db->setQuery($query);
							$elemLabel = $db->loadResult();
						endif;

						// CUSTOM -> user registration
						// cria o usuário se não existir
						$userMsg = '';
						if($request['access'] == 1 && !$isUser) :
							// email de confirmação
							$mailHtml = '';
							if($request['emailConfirm'] == 1) :
								// se a senha for gerada pelo sistema, envia a senha. Senão, não envia...
								$bodyData = empty($request['password']) ? JText::sprintf('MSG_ACTIVATION_EMAIL_PWD', $pwd) : JText::_('MSG_ACTIVATION_EMAIL_NOPWD');
								$emailInfo = !empty($request['emailInfo']) ? '<p>'.$request['emailInfo'].'</p>' : '';
								$eBody = JText::sprintf('MSG_ACTIVATION_EMAIL_BODY', baseHelper::nameFormat($request['name']), $domain, $request['email'], $bodyData, $emailInfo);
								// Email Template
								$boxStyle	= array('bg' => '#eee', 'color' => '#555', 'border' => '3px solid #303b4d');
								$headStyle	= array('bg' => '#303b4d', 'color' => '#fff', 'border' => 'none');
								$bodyStyle	= array('bg' => '#fff');
								$mailLogo	= 'logo-news.png';
								$mailHtml	= baseHelper::mailTemplateDefault($eBody, JText::_('MSG_ACTIVATION_EMAIL_TITLE'), '', $mailLogo, $boxStyle, $headStyle, $bodyStyle);
							endif;
							if($userID == 0) :
								// define a senha
								$pwd = ($request['password'] && !empty($request['password'])) ? $request['password'] : baseHelper::randomPassword();
								// prepara os dados
								$isBlock = ($request['state'] == 1) ? 0 : 1;
								// cria o usuário
								$newUserId = baseUserHelper::createJoomlaUser($request['name'], $request['username'], $request['email'], $pwd, $request['usergroups'], $isBlock, $request['emailConfirm'], $mailFrom, $subject, $mailHtml);
								// atribui o usuário ao cliente
								if(is_int($newUserId) && $newUserId > 0) :
									$query = 'UPDATE '. $db->quoteName($cfg['mainTable']) .' SET user_id = '. $newUserId .' WHERE id = '.$id;
									$db->setQuery($query);
									$db->execute();
									$userMsg = JText::_('MSG_USER_CREATED');
								else :
									$userMsg = !is_int($newUserId) ? JText::_($newUserId) : $userMsg = JText::_('MSG_USER_NOT_CREATED');
								endif;
							// se for selecionado um usuário já existente, atribui o 'user_id'
							else :
								$query = 'UPDATE '. $db->quoteName($cfg['mainTable']) .' SET user_id = '. $userID .' WHERE id = '.$id;
								$db->setQuery($query);
								$db->execute();
								if($request['emailConfirm']) baseHelper::sendMail($mailFrom, $request['email'], $subject, $mailHtml);
								$userMsg = JText::_('MSG_USER_ATTRIBUTED');
							endif;
							$userMsg = '<br />'.$userMsg;
						// permite o bloqueio do acesso
						elseif($isUser && $userInfoId) :
							baseUserHelper::stateToJoomlaUser($userInfoId, $request['access']);
						endif;

						$data[] = array(
							'status'			=> 2,
							'msg'				=> JText::_('MSG_SAVED').$userMsg,
							'uploadError'		=> $fileMsg,
							'parentField'		=> $element,
							'parentFieldVal'	=> $elemVal,
							'parentFieldLabel'	=> baseHelper::nameFormat($elemLabel)
						);

					} catch (RuntimeException $e) {

						// Error treatment
						switch($e->getCode()) {
							case '1062':
							$sqlErr = JText::_('MSG_SQL_DUPLICATE_KEY');
							break;
							default:
							$sqlErr = 'Erro: '.$e->getCode().'. '.$e->getMessage();
						}

						$data[] = array(
							'status'			=> 0,
							'msg'				=> $sqlErr,
							'uploadError'		=> $fileMsg
						);

					}

				// DELETE
				elseif($task == 'del') :

					$query = 'DELETE FROM '. $db->quoteName($cfg['mainTable']) .' WHERE '. $db->quoteName('id') .' IN ('.$ids.')';

					try {

						$db->setQuery($query);
						$db->execute();

						// FILE: remove o(s) arquivo(s)
						if($cfg['hasUpload'] && !empty($ids) && $ids != 0)
						$fileMsg = uploader::deleteFiles($ids, $cfg['fileTable'], $cfg['uploadDir'], JText::_('MSG_FILEERRODEL'));

						// DELETE RELATIONSHIP
						if(!empty($_SESSION[$RTAG.'RelTable']) && !empty($_SESSION[$RTAG.'AppNameId'])) :
							$query = 'DELETE FROM '. $db->quoteName($_SESSION[$RTAG.'RelTable']) .' WHERE '. $db->quoteName($_SESSION[$RTAG.'AppNameId']) .' IN ('.$ids.')';
							$db->setQuery($query);
							$db->execute();
						endif;
						// FORCE DELETE RELATIONSHIPS
						// força a exclusão do(s) relacionamento(s) caso os parâmetros não sejam setados
						// isso é RECOMENDÁVEL sempre que houver um ou mais relacionamentos
						// PROVIDERS -> remove os registros relacionados aos conveniados
		                $query = 'DELETE FROM '. $db->quoteName('#__'.$cfg['project'].'_rel_providers_contacts') .' WHERE '. $db->quoteName('contact_id') .' IN ('.$ids.')';
		                $db->setQuery($query);
						$db->execute();

						$rIDs = explode(',', $ids);
						foreach ($rIDs as $rID) {

							// ACCOUNT BANKS -> remove os registros relacionados às contas bancárias
							// $query = '
							// 	SELECT T2.id
							// 	FROM '. $db->quoteName('#__base_rel_contacts_banksAccounts') .' T1
							// 		JOIN '. $db->quoteName('#__base_banks_accounts') .' T2
							// 		ON  '. $db->quoteName('T2.id') .' = '. $db->quoteName('T1.bankAccount_id') .'
							// 	WHERE '. $db->quoteName('T1.contact_id') .' = '.$rID
							// ;
							// $db->setQuery($query);
							// $relId = $db->loadColumn();
							// $dIDs = implode(',', $relId);
							// if(!empty($dIDs)) :
							// 	// exclui as contas
							// 	$query = 'DELETE FROM '. $db->quoteName('#__base_banks_accounts') .' WHERE '. $db->quoteName('id') .' IN ('.$dIDs.')';
							// 	$db->setQuery($query);
							// 	$db->execute();
							// endif;
							// // exclui o relacionamento
							// $query = 'DELETE FROM '. $db->quoteName('#__base_rel_contacts_banksAccounts') .' WHERE '. $db->quoteName('contact_id') .' = '.$rID;
							// $db->setQuery($query);
							// $db->execute();

						}

						// UPDATE FIELD
						// executa apenas com valores individuais
						$element = $elemVal = $elemLabel = '';
						if(!empty($_SESSION[$RTAG.'FieldUpdated']) && !empty($_SESSION[$RTAG.'TableField'])) :
							$element = $_SESSION[$RTAG.'FieldUpdated'];
							$elemVal = $ids;
						endif;

			            // DELETE USER REGISTERED
			            $userMsg = '';
						foreach ($uList as $usr) {
							if($usr->user_id != 0) {
								if(baseUserHelper::deleteJoomlaUser($usr->user_id)) $userMsg = JText::_('MSG_USER_DELETED');
							}
						}

						$data[] = array(
							'status'			=> 3,
							'ids'				=> explode(',', $ids),
							'msg'				=> JText::_('MSG_DELETED').$userMsg,
							'uploadError'		=> $fileMsg,
							'parentField'		=> $element,
							'parentFieldVal'	=> $elemVal
						);

					} catch (RuntimeException $e) {

						$data[] = array(
							'status'			=> 0,
							'msg'				=> $e->getMessage(),
							'uploadError'		=> $fileMsg
						);

					}

				// STATE
				elseif($task == 'state') :

					$stateVal = ($state == 2 ? 'IF(state = 1, 0, 1)' : $state);
					$query = 'UPDATE '. $db->quoteName($cfg['mainTable']) .' SET '. $db->quoteName('state') .' = '.$stateVal.' WHERE '. $db->quoteName('id') .' IN ('.$ids.')';

					try {

						$db->setQuery($query);
						$db->execute();

						// UPDATE FIELD
						// executa apenas com valores individuais
						$element = $elemVal = $elemLabel = '';
						if(!empty($_SESSION[$RTAG.'FieldUpdated']) && !empty($_SESSION[$RTAG.'TableField'])) :
							$element = $_SESSION[$RTAG.'FieldUpdated'];
							$elemVal = $ids;
							$query = 'SELECT '. $db->quoteName($_SESSION[$RTAG.'TableField']) .' FROM '. $db->quoteName($cfg['mainTable']).' WHERE '. $db->quoteName('id') .' = '.$ids;
							$db->setQuery($query);
							$elemLabel = $db->loadResult();
						endif;

						$data[] = array(
							'status'			=> 4,
							'state'				=> $state,
							'ids'				=> explode(',', $ids),
							'msg'				=> '',
							'parentField'		=> $element,
							'parentFieldVal'	=> $elemVal,
							'parentFieldLabel'	=> baseHelper::nameFormat($elemLabel)
						);

					} catch (RuntimeException $e) {

						$data[] = array(
							'status'			=> 0,
							'msg'				=> $e->getMessage()
						);

					}

				// DELETE FILE
				elseif($cfg['hasUpload'] && $task == 'delFile' && $fname) :

					// FILE: remove o arquivo
					$fileMsg = uploader::deleteFile($fname, $cfg['fileTable'], $cfg['uploadDir'], JText::_('MSG_FILEERRODEL'));

					if(empty($fileMsg)) {
						// IMPORTANTE: Reorganiza a ordem
						// remove os saltos entre os "index", pois não deve haver!!
						$sIndex = $cfg['indexFileInit'] - 1;
						uploader::rebuildIndexFiles($cfg['fileTable'], $id, $sIndex);
					}

					$data[] = array(
						'status'				=> 5,
						'msg'					=> JText::_('MSG_FILE_DELETED'),
						'uploadError'			=> $fileMsg
					);

				// DELETE FILES
				elseif($cfg['hasUpload'] && $task == 'delFiles' && $fileId) :

					// FILE: remove o arquivo
					$fileMsg = uploader::deleteFiles($fileId, $cfg['fileTable'], $cfg['uploadDir'], JText::_('MSG_FILEERRODEL'));

					$data[] = array(
						'status'				=> 6,
						'msg'					=> JText::_('MSG_FILES_DELETED'),
						'uploadError'			=> $fileMsg
					);

				// UNLINK USER AND CONTACT
				elseif($task == 'unlink') :

					$query = 'UPDATE '. $db->quoteName($cfg['mainTable']) .' SET '. $db->quoteName('user_id') .' = 0, '. $db->quoteName('access') .' = 0 WHERE '. $db->quoteName('id') .' = '.$id;

					try {

						$db->setQuery($query);
						$db->execute();

						$data[] = array(
							'status'			=> 1,
							'msg'				=> JText::_('MSG_USER_UNLINKED')
						);

					} catch (RuntimeException $e) {

						$data[] = array(
							'status'			=> 0,
							'msg'				=> $e->getMessage()
						);

					}

				endif; // end task

			endif; // num rows

		else :

			// INSERT
			if($task == 'save') :

				// validation
				if($save_condition) :

					// Prepare the insert query
					$query  = '
						INSERT INTO '. $db->quoteName($cfg['mainTable']) .'('.
							$db->quoteName('group_id') .','.
							$db->quoteName('user_id') .','.
							$db->quoteName('name') .','.
							$db->quoteName('nickname') .','.
							$db->quoteName('email') .','.
							$db->quoteName('cpf') .','.
							$db->quoteName('rg') .','.
							$db->quoteName('rg_orgao') .','.
							$db->quoteName('gender') .','.
							$db->quoteName('birthday') .','.
							$db->quoteName('occupation') .','.
							$db->quoteName('marital_status') .','.
							$db->quoteName('partner') .','.
							$db->quoteName('children') .','.
							$db->quoteName('zip_code') .','.
							$db->quoteName('address') .','.
							$db->quoteName('address_number') .','.
							$db->quoteName('address_info') .','.
							$db->quoteName('address_district') .','.
							$db->quoteName('address_city') .','.
							$db->quoteName('address_state') .','.
							$db->quoteName('address_country') .','.
							$db->quoteName('onlyBR') .','.
							$db->quoteName('phone') .','.
							$db->quoteName('whatsapp') .','.
							$db->quoteName('phone_desc') .','.
							$db->quoteName('chat_name') .','.
							$db->quoteName('chat_user') .','.
							$db->quoteName('weblink_text') .','.
							$db->quoteName('weblink_url') .','.
							$db->quoteName('extra_info') .','.
							$db->quoteName('note') .','.
							$db->quoteName('access') .','.
							$db->quoteName('reasonStatus') .','.
							$db->quoteName('state') .','.
							$db->quoteName('created_by')
						.') VALUES ('.
							$request['group_id'] .','.
							$userID .','.
							$db->quote($request['name']) .','.
							$db->quote($request['nickname']) .','.
							$db->quote($request['email']) .','.
							$db->quote($request['cpf']) .','.
							$db->quote($request['rg']) .','.
							$db->quote($request['rg_orgao']) .','.
							$request['gender'] .','.
							$db->quote($request['birthday']) .','.
							$db->quote($request['occupation']) .','.
							$request['marital_status'] .','.
							$db->quote($request['partner']) .','.
							$request['children'] .','.
							$db->quote($request['zip_code']) .','.
							$db->quote($request['address']) .','.
							$db->quote($request['address_number']) .','.
							$db->quote($request['address_info']) .','.
							$db->quote($request['address_district']) .','.
							$db->quote($request['address_city']) .','.
							$db->quote($request['address_state']) .','.
							$db->quote($request['address_country']) .','.
							$request['onlyBR'] .','.
							$db->quote($request['phone']) .','.
							$db->quote($request['whatsapp']) .','.
							$db->quote($request['phone_desc']) .','.
							$db->quote($request['chat_name']) .','.
							$db->quote($request['chat_user']) .','.
							$db->quote($request['weblink_text']) .','.
							$db->quote($request['weblink_url']) .','.
							$db->quote($request['extra_info']) .','.
							$db->quote($request['note']) .','.
							$request['access'] .','.
							$db->quote($reason) .','.
							$request['state'] .','.
							$user->id
						.')
					';

					try {

						$db->setQuery($query);
						$db->execute();
						$id = $db->insertid();
						// Upload
						if($cfg['hasUpload'] && $id)
						$fileMsg = uploader::uploadFile($id, $cfg['fileTable'], $_FILES[$cfg['fileField']], $fileGrp, $fileGtp, $fileCls, $fileLbl, $cfg);

						// CREATE RELATIONSHIP
						if(!empty($_SESSION[$RTAG.'RelTable']) && !empty($_SESSION[$RTAG.'RelNameId']) && !empty($_SESSION[$RTAG.'AppNameId']) && !empty($request['relationId'])) :
							$query  = '
								INSERT INTO '. $db->quoteName($_SESSION[$RTAG.'RelTable']) .'('.
									$db->quoteName($_SESSION[$RTAG.'AppNameId']) .','.
									$db->quoteName($_SESSION[$RTAG.'RelNameId'])
								.') VALUES ('.
									$id .','.
									$request['relationId']
								.')
							';
							$db->setQuery($query);
							$db->execute();
						endif;

						// UPDATE FIELD
						$element = $elemVal = $elemLabel = '';
						if(!empty($_SESSION[$RTAG.'FieldUpdated']) && !empty($_SESSION[$RTAG.'TableField'])) :
							$element = $_SESSION[$RTAG.'FieldUpdated'];
							$elemVal = $id;
							$query = 'SELECT '. $db->quoteName($_SESSION[$RTAG.'TableField']) .' FROM '. $db->quoteName($cfg['mainTable']).' WHERE '. $db->quoteName('id') .' = '.$id.' AND state = 1';
							$db->setQuery($query);
							$elemLabel = $db->loadResult();
						endif;

						// CUSTOM -> user registration
						// cria o usuário se não existir
						$userMsg = '';
						if($request['access'] == 1) :
							// email de confirmação
							$mailHtml = '';
							if($request['emailConfirm'] == 1) :
								// se a senha for gerada pelo sistema, envia a senha. Senão, não envia...
								$bodyData = empty($request['password']) ? JText::sprintf('MSG_ACTIVATION_EMAIL_PWD', $pwd) : JText::_('MSG_ACTIVATION_EMAIL_NOPWD');
								$emailInfo = !empty($request['emailInfo']) ? '<p>'.$request['emailInfo'].'</p>' : '';
								$eBody = JText::sprintf('MSG_ACTIVATION_EMAIL_BODY', baseHelper::nameFormat($request['name']), $domain, $request['email'], $bodyData, $emailInfo);
								// Email Template
								$boxStyle	= array('bg' => '#eee', 'color' => '#555', 'border' => '3px solid #303b4d');
								$headStyle	= array('bg' => '#303b4d', 'color' => '#fff', 'border' => 'none');
								$bodyStyle	= array('bg' => '#fff');
								$mailLogo	= 'logo-news.png';
								$mailHtml	= baseHelper::mailTemplateDefault($eBody, JText::_('MSG_ACTIVATION_EMAIL_TITLE'), '', $mailLogo, $boxStyle, $headStyle, $bodyStyle);
							endif;
							if($userID == 0) :
								// define a senha
								$pwd = ($request['password'] && !empty($request['password'])) ? $request['password'] : baseHelper::randomPassword();
								// prepara os dados
								$isBlock = ($request['state'] == 1) ? 0 : 1;
								// cria o usuário
								$newUserId = baseUserHelper::createJoomlaUser($request['name'], $request['username'], $request['email'], $pwd, $request['usergroups'], $isBlock, $request['emailConfirm'], $mailFrom, $subject, $mailHtml);
								// atribui o usuário ao cliente
								if(is_int($newUserId) && $newUserId > 0) :
									$query = 'UPDATE '. $db->quoteName($cfg['mainTable']) .' SET user_id = '. $newUserId .' WHERE id = '.$id;
									$db->setQuery($query);
									$db->execute();
									$userMsg = JText::_('MSG_USER_CREATED');
								else :
									$userMsg = !is_int($newUserId) ? JText::_($newUserId) : $userMsg = JText::_('MSG_USER_NOT_CREATED');
								endif;
							// se for selecionado um usuário já existente, atribui o 'user_id'
							else :
								$query = 'UPDATE '. $db->quoteName($cfg['mainTable']) .' SET user_id = '. $userID .' WHERE id = '.$id;
								$db->setQuery($query);
								$db->execute();
								if($request['emailConfirm']) baseHelper::sendMail($mailFrom, $request['email'], $subject, $mailHtml);
								$userMsg = JText::_('MSG_USER_ATTRIBUTED');
							endif;
							$userMsg = '<br />'.$userMsg;
						endif;

						$data[] = array(
							'status'			=> 1,
							'msg'				=> JText::_('MSG_SAVED').$userMsg,
							'regID'				=> $id,
							'uploadError'		=> $fileMsg,
							'parentField'		=> $element,
							'parentFieldVal'	=> $elemVal,
							'parentFieldLabel'	=> baseHelper::nameFormat($elemLabel)
						);

					} catch (RuntimeException $e) {

						// Error treatment
						switch($e->getCode()) {
							case '1062':
							$sqlErr = JText::_('MSG_SQL_DUPLICATE_KEY');
							break;
							default:
							$sqlErr = 'Erro: '.$e->getCode().'. '.$e->getMessage();
						}

						$data[] = array(
							'status'			=> 0,
							'msg'				=> $sqlErr,
							'uploadError'		=> $fileMsg
						);

					}

				else :

					$data[] = array(
						'status'				=> 0,
						'msg'					=> JText::_('MSG_ERROR'),
						'uploadError'			=> $fileMsg
					);

				endif; // end validation

			// USERS SYNCRONIZE
			elseif($task == 'userSync') :

				// seleciona os contatos com relação
				$query = '
					SELECT
						'. $db->quoteName('T1.id') .',
						'. $db->quoteName('T2.name') .' uName
					FROM
						'. $db->quoteName($cfg['mainTable']).' T1
						LEFT JOIN '. $db->quoteName('#__users').' T2
						ON T2.id = T1.user_id
					WHERE T1.user_id != 0
				';
				$db->setQuery($query);
				$cUsers = $db->loadObjectList();
				foreach ($cUsers as $usr) {
					// se o nome do usuário vier vazio significa que o usuário não existe
					if(empty($usr->uName)) :
						// reseta o user_id
						$query = 'UPDATE '. $db->quoteName($cfg['mainTable']) .' SET user_id = 0 WHERE id = '.$usr->id;
						$db->setQuery($query);
						$db->execute();
					endif;
				}

				$data[] = array(
					'status'	=> 1,
					'msg'		=> JText::_('MSG_SYNCHRONIZED')
				);

			endif; // end 'task'

		endif; // end 'id'

		$json = json_encode($data);

		# JSON if no callback
		if(!isset($_GET['callback'])) exit($json);

		# JSONP if valid callback
		if(is_valid_callback($_GET['callback'])) exit("{$_GET['callback']}($json)");

		# Otherwise, bad request
		header('status: 400 Bad Request', true, 400);

	endif;

else :

	# Otherwise, bad request
	header('status: 400 Bad Request', true, 400);

endif;

?>
