<?php

#-------------------------------------------------------------------------------
/** @author Rootden, for Lowhosting.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('libs/Server.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ServerSettings = SelectServerSettingsByTemplate('SMS');
#-------------------------------------------------------------------------------
switch(ValueOf($ServerSettings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return $ServerSettings;
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!IsSet($Config['Interface']['Notes']['User']['MobileConfirmation']['ConfirmInterval']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Mobile = (string) @$Args['Mobile'];
$Confirm = (string) @$Args['Confirm'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$CacheID = Md5($__FILE__.$GLOBALS['__USER']['ID']);
$CacheID2 = Md5('mobileconfirmlimit'.$GLOBALS['__USER']['ID']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($Mobile){
    #---------------------------------------------------------------------------
    # возможный вариант, что телефона не было, юзер его ввёл и не сохраняя нажал "подтвердить"
    if(!$GLOBALS['__USER']['Mobile'])
    	return new gException('PHONE_NOT_SAVED', 'Для подтверждения, вначале сохраните настройки с введённым номером телефона');
    #-------------------------------------------------------------------------------
    $Mobile = $GLOBALS['__USER']['Mobile'];
    #---------------------------------------------------------------------------
    if (Is_Error(System_Load('modules/Authorisation.mod')))
	return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    // Защита от агрессивно настроенных
    $Result2 = CacheManager::get($CacheID2);
    if($Result2 == 'block'){
      #-------------------------------------------------------------------------------
      $Comp = Comp_Load('Formats/Date/Remainder',$Config['Interface']['Notes']['User']['MobileConfirmation']['ConfirmInterval']);
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------------
      return new gException('ERROR_SMS_SEND_INTERVAL', SPrintF("Вы уже отправили SMS сообщение с кодом подтверждения. Новое сообщение вы сможете отправить только через %s",$Comp));
      #-------------------------------------------------------------------------------
    }
    #-------------------------------------------------------------------------------
    $Executor = DB_Select('Users', Array('Sign', 'Mobile', 'GroupID'), Array('UNIQ', 'ID' => 100));
    if (!Is_Array($Executor))
	return ERROR | @Trigger_Error(500);
    #-------------------------------------------------------------------------------
    #-------------------------------------------------------------------------------
    $Confirm = rand(1000, 9999);
    $Confirm = Comp_Load('Passwords/Generator',4,FALSE,TRUE,FALSE);
    if(Is_Error($Confirm))
            return ERROR | @Trigger_Error(500);
    #-------------------------------------------------------------------------------
    CacheManager::add($CacheID, SPrintF('%s%s',$Confirm,$Mobile), 10*24*3600);
    #-------------------------------------------------------------------------------
    Debug(SPrintF('[comp/www/API/MobileConfirm]: confirm code = %s',$Confirm));
    #-------------------------------------------------------------------------------
    $Message = SPrintF('Ваш проверочный код: %s%s',$Confirm,($ServerSettings['Params']['CutSign'])?'':SPrintF('\r\n%s',$Executor['Sign']));
    $Comp = Comp_Load('Tasks/SMS',NULL,$Mobile,$Message,$GLOBALS['__USER']['ID'],TRUE,TRUE);
    if(!$Comp){
	#-----------------------------------------------------------------------------
	if(Is_String($Comp))
	    return new gException('ERROR_SMS_SEND_WITH_TEXT', SPrintF('Не удалось отправить SMS сообщение с кодом подтверждения (%s)',$Comp));
	#-----------------------------------------------------------------------------
	return new gException('ERROR_SMS_SEND', 'Не удалось отправить SMS сообщение c кодом подтверждения');
	#-----------------------------------------------------------------------------
    }
    #-------------------------------------------------------------------------------
    #-------------------------------------------------------------------------------
    $IsUpdate = DB_Update('Users', Array('MobileConfirmed' => 0), Array('ID' => $GLOBALS['__USER']['ID']));
    if(Is_Error($IsUpdate))
	return ERROR | @Trigger_Error(500);
    #-------------------------------------------------------------------------------
    CacheManager::add($CacheID2, 'block', IntVal($Config['Interface']['Notes']['User']['MobileConfirmation']['ConfirmInterval']));
    #-------------------------------------------------------------------------------
    return Array('Status' => 'Ok');
    #-------------------------------------------------------------------------------
}else{
    #-------------------------------------------------------------------------------
    if (Is_Error(System_Load('modules/Authorisation.mod')))
	return ERROR | @Trigger_Error(500);
    #-------------------------------------------------------------------------------
    if(Empty($Confirm))
	return new gException('ERROR_CODE_EMPTY', 'Введите код подтверждения полученный по SMS');
    #-------------------------------------------------------------------------------
    $Result = CacheManager::get($CacheID);
    if(SPrintF('%s%s',$Confirm,$GLOBALS['__USER']['Mobile']) == $Result) {
	#-------------------------------------------------------------------------------
	$IsUpdate = DB_Update('Users', Array('MobileConfirmed' => Time()), Array('ID' => $GLOBALS['__USER']['ID']));
	if (Is_Error($IsUpdate))
	    return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	// Отключаем все СМС уведомлепния в настройках
	$Notifies = $Config['Notifies'];
	foreach (Array_Keys($Notifies['Types']) as $TypeID) {
	    #---------------------------------------------------------------------------
	    $Where = SPrintF("`UserID` = %u AND `MethodID` = '%s' AND `TypeID` = '%s'", $GLOBALS['__USER']['ID'], 'SMS', $TypeID);
	    #-------------------------------------------------------------------------
	    $Count = DB_Count('Notifies', Array('Where' => $Where));
	    if (Is_Error($Count))
		return ERROR | @Trigger_Error(500);
	    #-------------------------------------------------------------------------
	    if (!$Count) {
		#-----------------------------------------------------------------------
		$INotify = Array(
		    #---------------------------------------------------------------------
		    'UserID' => $GLOBALS['__USER']['ID'],
		    'MethodID' => 'SMS',
		    'TypeID' => $TypeID
		);
		#-----------------------------------------------------------------------
		$IsInsert = DB_Insert('Notifies', $INotify);
		if (Is_Error($IsInsert))
		    return ERROR | @Trigger_Error(500);
	    }
	}
	#-------------------------------------------------------------------------------
	$Event = Array(
			'UserID'        => $GLOBALS['__USER']['ID'],
			'PriorityID'    => 'Billing',
			'Text'          => SPrintF('Мобильный телефон (%s) подтверждён',$GLOBALS['__USER']['Mobile'])
			);
	$Event = Comp_Load('Events/EventInsert',$Event);
	if(!$Event)
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	# flush cache
	$IsFlush = Comp_Load('www/CacheFlush',Array());
	if(!$IsFlush)
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	return Array('Status' => 'Ok');
	#-------------------------------------------------------------------------------
    }else{
	return new gException('BAD_COMFIRM_CODE', 'Введён неверный или просроченный код');
    }
    #-------------------------------------------------------------------------------
    #-------------------------------------------------------------------------------
    return Array('Status' => 'Error');
}
#-------------------------------------------------------------------------------
?>
