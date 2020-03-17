<?php
/**
 *
 *  Joonte Billing System
 *
 *  Copyright © 2012 Joonte Software
 * 
 *  rewritten by Alex Keda, for www.host-food.ru, 2019-09-10 in 13:00 MSK
 *
 */

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
class SendMessage implements Dispatcher{
	#-------------------------------------------------------------------------------
	/** Instance of email dispatcher. */
	private static $instance;
	#-------------------------------------------------------------------------------
	/** Private. This dispatcher have only one instance. */
	private function __construct(){}
	#-------------------------------------------------------------------------------
	public static function get(){
		#-------------------------------------------------------------------------------
		if(!isset(self::$instance)){
			#-------------------------------------------------------------------------------
			self::$instance = new SendMessage();
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		return self::$instance;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	public function send(Msg $msg){
		#-------------------------------------------------------------------------------
		$smarty = JSmarty::get();
		#-------------------------------------------------------------------------------
		$smarty->clearAllAssign();
		#-------------------------------------------------------------------------------
		$smarty->assign('Config',Config());
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		// Get template file path.
		$templatePath = SPrintF('Notifies/%s/%s.tpl',$msg->getParam('MethodID'),$msg->getTemplate());
		#-------------------------------------------------------------------------------
		if(!$smarty->templateExists($templatePath)){
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[system/classes/SendMessage]: шаблон по типу сообщения не найден (%s)',$templatePath));
			#-------------------------------------------------------------------------------
			// пробуем новый шаблон
			$MethodSettings = $msg->getParam('MethodSettings');
			#-------------------------------------------------------------------------------
			$templatePath = SPrintF('Notifies/%s/%s.tpl',$MethodSettings['MessageTemplate'],$msg->getTemplate());
			#-------------------------------------------------------------------------------
			if(!$smarty->templateExists($templatePath)){
				#-------------------------------------------------------------------------------
				throw new jException(SPrintF('Template file not found: %s',$templatePath));
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[system/classes/SendMessage]: используемый шаблон сообщения: %s',$templatePath));
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		foreach(Array_Keys($msg->getParams()) as $paramName)
			$smarty->assign($paramName, $msg->getParam($paramName));
		#-------------------------------------------------------------------------------
		$message = $smarty->fetch($templatePath);
		#-------------------------------------------------------------------------------
		try{
			#-------------------------------------------------------------------------------
			if($msg->getParam('Theme')){
				#-------------------------------------------------------------------------------
				$theme = $msg->getParam('Theme');
				#-------------------------------------------------------------------------------
			}else{
				#-------------------------------------------------------------------------------
				$theme = $smarty->getTemplateVars('Theme');
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			// костыль для JBS-1380
			if($theme){
				#-------------------------------------------------------------------------------
				$GLOBALS['JBS-1380-Theme'] = $theme;
				#-------------------------------------------------------------------------------
			}else{
				#-------------------------------------------------------------------------------
				$theme = IsSet($GLOBALS['JBS-1380-Theme'])?$GLOBALS['JBS-1380-Theme']:'$Theme';
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		catch (Exception $e){
			#-------------------------------------------------------------------------------
			throw new jException(SPrintF("Can't fetch template: %s", $templatePath), $e->getCode(), $e);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$recipient = $msg->getParam('User');
		#-------------------------------------------------------------------------------
		if(!$recipient['Email'])
			throw new jException(SPrintF('E-mail address not found for user: %s',$recipient['ID']));
		#-------------------------------------------------------------------------------
		$sender = $msg->getParam('From');
		#-------------------------------------------------------------------------------
		#Debug(SPrintF('[system/classes/SendMessage] sender = %s',print_r($sender,true)));
		#-------------------------------------------------------------------------------
		$emailHeads = Array(
					SPrintF('From: %s', $sender['Email']),
					'MIME-Version: 1.0',
					'Content-Transfer-Encoding: 8bit',
					SPrintF('Content-Type: multipart/related; boundary="----==--%s"',HOST_ID)
					);
		#-------------------------------------------------------------------------------
		// added by lissyara 2013-02-13 in 15:45 MSK, for JBS-609
		if($msg->getParam('MessageID'))
			$emailHeads[] = SPrintF('Message-ID: <%s@%s>',$msg->getParam('MessageID'),HOST_ID);
		#-------------------------------------------------------------------------------
		// JBS-1315, возможны дополнительные заголовки
		if($msg->getParam('Headers')){
			#-------------------------------------------------------------------------------
			$Lines = Explode("\n", Trim($msg->getParam('Headers')));
			#-------------------------------------------------------------------------------
			foreach($Lines as $Line)
				$emailHeads[] = Trim($Line);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Params = Array();
		#-------------------------------------------------------------------------------
		$Params[] = $msg->getParam('ToRecipient');
		$Params[] = $message;
		$Params[] = Array(
					'Theme'		=> $theme,					// тема сообщения
					'Heads'		=> Implode("\r\n", $emailHeads),		// почтовые заголовки
					'Attachments'	=> $msg->getParam('Attachments'),		// массив с вложениями, TODO разобраться а чё иногда вдруг не массив?
					'UserID'	=> $recipient['ID'],				// идентфикатор пользователя
					'TimeBegin'	=> $msg->getParam('TimeBegin'),			// время начала рассылки
					'TimeEnd'	=> $msg->getParam('TimeEnd'),			// время окончания рассылки
					'ChargeFree'	=> ($msg->getParam('ChargeFree'))?TRUE:FALSE,	// платно или бесплатно отправлять
					'ExternalID'	=> $msg->getParam('ExternalID'),		// внешний идентфикатор, для телеги
					'ContactID'	=> $msg->getParam('ContactID'),			// идентфикатор контакта
					'MessageID'	=> $msg->getParam('MessageID'),			// идентфикатор сообщения, из тикетниы
					'TicketID'	=> $msg->getParam('TicketID'),			// номер тикета
					'HTML'		=> $msg->getParam('HTML')			// текст сообщения в HTML (используется в рассылках, только для Email)
				);
		#-------------------------------------------------------------------------------
		$taskParams = Array(
					'UserID'	=> $recipient['ID'],				// идентифкатор юзера-получателя
					'TypeID'	=> $msg->getParam('MethodID'),			// метод оповещения
					'Params'	=> $Params					// массив параметров
					);
		#-------------------------------------------------------------------------------
		#Debug(SPrintF('[system/classes/SendMessage] taskParams = %s',print_r($taskParams,true)));
		#Debug(SPrintF('[system/classes/SendMessage] msg = %s',print_r($msg,true)));
		#-------------------------------------------------------------------------------
		$result = Comp_Load('www/Administrator/API/TaskEdit',$taskParams);
		switch(ValueOf($result)) {
		case 'error':
			throw new jException("Couldn't add task to queue: ".$result);
		case 'exception':
			throw new jException("Couldn't add task to queue: ".$result->String);
		case 'array':
			return TRUE;
		default:
			throw new jException("Unexpected error.");
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
