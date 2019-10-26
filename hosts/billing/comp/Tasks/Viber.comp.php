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
	$TransferTime = Comp_Load('Formats/Task/TransferTime',$Attribs['UserID'],$Address,'Viber',$Attribs['TimeBegin'],$Attribs['TimeEnd']);
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
Debug(SPrintF('[comp/Tasks/Viber]: отправка Viber сообщения для (%s)', $Address));
Debug(SPrintF('[comp/Tasks/Viber]: Attribs = %s',print_r($Attribs,true)));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/HTTP.php','libs/Viber.php','libs/Server.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Settings = SelectServerSettingsByTemplate('Viber');
#-------------------------------------------------------------------------------
switch(ValueOf($Settings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = 'server with template: Viber, params: IsActive, IsDefault not found';
	#-------------------------------------------------------------------------------
	if(IsSet($GLOBALS['IsCron']))
		return 3600;
	#-------------------------------------------------------------------------------
	return $Settings;
	#-------------------------------------------------------------------------------
case 'array':
	#-------------------------------------------------------------------------------
	$Viber = new Viber($Settings['Params']['Token']);
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// вырезаем некоторые теги, очень уж мешаются при просмотре сообщений
$Message = Preg_Replace('/\[size=([0-9]+)\](.+)\[\/size\]/sU','\\2',$Message);
$Message = Preg_Replace('/\[color=([a-z]+)\](.+)\[\/color\]/sU','\\2',$Message);
$Message = Preg_Replace('/\[quote\](.+)\[\/quote\]/sU',"\n--\\1--\n",$Message);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# достаём данные юзера которому идёт письмо
$User = DB_Select('Users',Array('ID','Params','Email'),Array('UNIQ','ID'=>$Attribs['UserID']));
if(!Is_Array($User))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($Viber->MessageSend($Attribs['ExternalID'],$Message)){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/Viber]: сообщение для %s отправлено',$User['Email']));
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	// если не отправилось, ждём час и пробуем снова
	return 3600;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// шлём файл, если он есть
$Attribs['Attachments'] = Is_Array($Attribs['Attachments'])?$Attribs['Attachments']:Array();
#-------------------------------------------------------------------------------
if(SizeOf($Attribs['Attachments']) > 0){
	#-------------------------------------------------------------------------------
	// шлём файл, если он есть
	if($User['Params']['Settings']['SendEdeskFilesToViber'] == "Yes"){
		if($Viber->FileSend($Attribs['ExternalID'],$Attribs['Attachments']))
			Debug(SPrintF('[comp/Tasks/Viber]: отправлен файл в Viber'));
/*
		#-------------------------------------------------------------------------------
		if($TgMessageIDs = TgSendFile($Settings,$Attribs['ExternalID'],$Attribs['Attachments'],(IsSet($Attribs['MessageID'])?TRUE:FALSE))){
			#-------------------------------------------------------------------------------
			// сохраняем сооветствие отправленнго файла и кому он ушёл
			foreach($TgMessageIDs as $TgMessageID)
				if(!TgSaveThreadID($Attribs['UserID'],$Attribs['TicketID'],$Attribs['MessageID'],$TgMessageID))
					return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Tasks/Viber]: не удалось отправить файл в Viber'));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
*/
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/Viber]: отсутствуют файлы приложенные к сообщению'));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods']['Viber']['IsEvent'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Comp_Load('Events/EventInsert', Array('UserID'=>$Attribs['UserID'],'Text'=>SPrintF('Сообщение для (%s) через службу Viber отправлено', $Address)));
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