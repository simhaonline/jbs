<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$ContactsIDs   =  (array) @$Args['ContactsIDs'];
#-------------------------------------------------------------------------------
$Regulars = Regulars();
#-------------------------------------------------------------------------------
if(SizeOf($ContactsIDs) < 1)
	return new gException('WRONG_SELECT_NO_SELECT','Необходимо выбрать хотя бы один контактный адрес');
#-----------------------------------------------------------------------------
#-----------------------------------------------------------------------------
// ключик для кэша, по IP адресу - сохранённые идентфикаторы и блокировка повторного восстановления
$CacheID1 = Md5($_SERVER['REMOTE_ADDR']);
$CacheID2 = Md5(SPrintF('block%s',$_SERVER['REMOTE_ADDR']));
#-----------------------------------------------------------------------------
$Result = CacheManager::get($CacheID1);
if(!$Result)
	return new gException('CACHE_DATA_MISSING','Произошла ошибка, вероятно, истёк срок действия введённого кода. Вернитесь назад и попробуйте ещё раз');
#-----------------------------------------------------------------------------
#-----------------------------------------------------------------------------
if(CacheManager::get($CacheID2))
	return new gException('RESTORE_RATELIMIT','Пароль нельзя восстанавливать чаще чем раз в 5 минут. Подождите и попробуйте ещё раз');
#-----------------------------------------------------------------------------
#-----------------------------------------------------------------------------
// проверяем, не выбраны ли контакты более чем одного юзера
$Users = Array();
#-----------------------------------------------------------------------------
foreach(Array_Keys($Result) as $Key){
	#-----------------------------------------------------------------------------
	foreach($ContactsIDs as $ContactsID){
		#-----------------------------------------------------------------------------
		if($Key == $ContactsID){
			#-----------------------------------------------------------------------------
			Debug(SPrintF('[comp/www/API/UserPasswordRestore]: Key = %s',$Key));
			#-----------------------------------------------------------------------------
			// сразу достанем идентификатор, чтоб потом не искать снова
			$UserID = $Result[$Key];
			#-----------------------------------------------------------------------------
			if(!In_Array($Result[$Key],$Users))
				$Users[] = $Result[$Key];
			#-----------------------------------------------------------------------------
		}
		#-----------------------------------------------------------------------------
	}
	#-----------------------------------------------------------------------------
}
#-----------------------------------------------------------------------------
Debug(SPrintF('[comp/www/API/UserPasswordRestore]: Users = %s',print_r($Users,true)));
#-----------------------------------------------------------------------------
if(SizeOf($Users) > 1)
	return new gException('WRONG_SELECT_TOO_MANY_SELECTED','Выбраны контакты относящиеся к нескольким пользователям. Необходимо выбрать контакты только одного пользователя');
#-----------------------------------------------------------------------------
#-----------------------------------------------------------------------------
// проверяем присланное, все ли выбранные контакты есть в кэше
foreach($ContactsIDs as $ContactsID)
	if(!In_Array($ContactsID,Array_Keys($Result)))
		return new gException('WRONG_SELECT_NOT_FOUND_USER','Выбран контакт не найденный при поиске');
#-----------------------------------------------------------------------------
#-----------------------------------------------------------------------------
Debug(SPrintF('[comp/www/API/UserPasswordRestore]: Result = %s',print_r($Result,true)));
// идентификатор пользователя у нас есть в массиве что из кэша достали.
$User = DB_Select('Users',Array('ID','IsProtected'),Array('UNIQ','ID'=>$UserID));
#-------------------------------------------------------------------------------
switch(ValueOf($User)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('USER_NOT_FOUND','Пользователь не зарегистрирован в системе');
case 'array':
	#-------------------------------------------------------------------------------
	if($User['IsProtected'])
		return new gException('PASSWORD_RESTORE_DISABLED_FOR_USER','Для данного пользователя запрещена функция восстановления пароля');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Password = Comp_Load('Passwords/Generator');
	if(Is_Error($Password))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$IsUpdated = DB_Update('Users',Array('Watchword'=>Md5($Password)),Array('ID'=>$User['ID']));
	if(Is_Error($IsUpdated))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Tmp = System_Element('tmp');
	if(Is_Error($Tmp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	# часть JBS-757 - грохаем старые сессии юзера
	$Path = SPrintF('%s/sessions',$Tmp);
	#-------------------------------------------------------------------------------
	if(File_Exists($Path)){
		#-------------------------------------------------------------------------------
		$SessionIDsIDs = IO_Scan($Path);
		if(Is_Error($SessionIDsIDs))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		foreach($SessionIDsIDs as $SessionID)
			if(Preg_Match(SPrintF('/^(REMEBMER|SESSION)%s/',MD5($User['ID'])),$SessionID))
				if(!@UnLink(SPrintF('%s/%s',$Path,$SessionID)))
					return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Msg = new Message('UserPasswordRestore',(integer)$User['ID'],Array('Password'=>$Password,'ChargeFree'=>TRUE,'IsImmediately'=>TRUE));
	#-------------------------------------------------------------------------------
	$IsSend = NotificationManager::sendMsg($Msg,Array(),Array('ContactsIDs'=>$ContactsIDs,'IsForceDelivery'=>TRUE));
	#-------------------------------------------------------------------------------
	switch(ValueOf($IsSend)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'true':
		#-------------------------------------------------------------------------------
		//return Array('Status'=>'Ok');
		#-------------------------------------------------------------------------------
		CacheManager::add($CacheID2,TRUE,300);
		#-------------------------------------------------------------------------------
		return new gException('PASSWORD_SENT','Пароль отправлен на выбранные контактные адреса. Обычно, он приходит в течении минуты');
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
