<?php
#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','Address','Message','Attribs');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
// возможно, параметры не заданы/требуется немедленная отправка - время не опредлеяем
if(!IsSet($Attribs['IsImmediately']) || !$Attribs['IsImmediately']){
	#-------------------------------------------------------------------------------
	// проверяем, можно ли отправлять в заданное время
	$TransferTime = Comp_Load('Formats/Task/TransferTime',$Attribs['UserID'],$Address,'Telegram',$Attribs['TimeBegin'],$Attribs['TimeEnd']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($TransferTime)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'integer':
		return $TransferTime;
	case 'false':
		break;
	default:
		return ERROR | @Trigger_Error(100);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/Telegram]: отправка Telegram сообщения для (%s)', $Address));
Debug(SPrintF('[comp/Tasks/Telegram]: Attribs = %s',print_r($Attribs,true)));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/HTTP.php','libs/Telegram.php','libs/Server.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Settings = SelectServerSettingsByTemplate('Telegram');
#-------------------------------------------------------------------------------
switch(ValueOf($Settings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = 'server with template: Telegram, params: IsActive, IsDefault not found';
	#-------------------------------------------------------------------------------
	if(IsSet($GLOBALS['IsCron']))
		return 3600;
	#-------------------------------------------------------------------------------
	return $Settings;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Telegram = new Telegram($Settings['Params']['Token'],$Settings['Params']['Secret']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// вырезаем некоторые теги, очень уж мешаются при просмотре сообщений
$Message = Preg_Replace('/\[size=([0-9]+)\](.+)\[\/size\]/sU','\\2',$Message);
$Message = Preg_Replace('/\[color=([a-z]+)\](.+)\[\/color\]/sU','<i>\\2</i>',$Message);
$Message = Preg_Replace('/\[quote\](.+)\[\/quote\]/sU',"<i>\\1</i>\n",$Message);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// параметры, нужны для базы отправленных сообщений
$Attribs['MessageID']	= IsSet($Attribs['MessageID'])?$Attribs['MessageID']:0;
$Attribs['TicketID']	= IsSet($Attribs['TicketID'])?$Attribs['TicketID']:0;
#-------------------------------------------------------------------------------
if($TgMessageIDs = $Telegram->MessageSend($Attribs['ExternalID'],$Message,($Attribs['MessageID'])?TRUE:FALSE)){
	#-------------------------------------------------------------------------------
	// сохраняем сооветствие отправленного сообщения и кому оно ушло
	if(Is_Array($TgMessageIDs))
		foreach($TgMessageIDs as $TgMessageID)
			if(!$Telegram->SaveThreadID($Attribs['UserID'],$Attribs['TicketID'],$Attribs['MessageID'],$TgMessageID))
				return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	// если не отправилось, ждём час и пробуем снова
	return 3600;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# достаём данные юзера которому идёт письмо
$User = DB_Select('Users',Array('ID','Params','Email'),Array('UNIQ','ID'=>$Attribs['UserID']));
if(!Is_Array($User))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
// шлём файл, если он есть
$Attribs['Attachments'] = Is_Array($Attribs['Attachments'])?$Attribs['Attachments']:Array();
#-------------------------------------------------------------------------------
if(SizeOf($Attribs['Attachments']) > 0){
	#-------------------------------------------------------------------------------
	// шлём файл, если он есть
	if($User['Params']['Settings']['SendEdeskFilesToTelegram'] == "Yes"){
		#-------------------------------------------------------------------------------
		if($TgMessageIDs = $Telegram->FileSend($Attribs['ExternalID'],$Attribs['Attachments'],(IsSet($Attribs['MessageID'])?TRUE:FALSE))){
			#-------------------------------------------------------------------------------
			// сохраняем сооветствие отправленнго файла и кому он ушёл
			foreach($TgMessageIDs as $TgMessageID)
				if(!$Telegram->SaveThreadID($Attribs['UserID'],$Attribs['TicketID'],$Attribs['MessageID'],$TgMessageID))
					return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Tasks/Telegram]: не удалось отправить файл в Telegram'));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/Telegram]: отсутствуют файлы приложенные к сообщению'));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods']['Telegram']['IsEvent'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Comp_Load('Events/EventInsert', Array('UserID'=>$Attribs['UserID'],'Text'=>SPrintF('Сообщение для (%s) через службу Telegram отправлено', $Address)));
if(!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'][$User['Email']]	= Array($Address,$Attribs['ExternalID']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
