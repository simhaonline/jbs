<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Args');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$Email      =  (string) @$Args['Email'];
$Password   =  (string) @$Args['Password'];
$IsRemember = (boolean) @$Args['IsRemember'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('classes/Session.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Regulars = Regulars();
#-------------------------------------------------------------------------------
// возможный варинт, что пустые и логин и пасс - с мобильной версии не понимают куда чё вводить
// редиректим таких на страницу логона
if(!$Password && !$Email)
	return Array('Status'=>'Ok','Home'=>'/EmptyLogon');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!Preg_Match($Regulars['Email'],$Email))
	return new gException('WRONG_EMAIL','Неверно указан электронный адрес');
#-------------------------------------------------------------------------------
if(!Preg_Match($Regulars['Password'],$Password))
	return new gException('WRONG_PASSWORD','Недопустимый пароль');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем перебор паролей
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Other']['PasswordBruteForce'];
#-------------------------------------------------------------------------------
$CacheID = Md5(SPrintF('Logon_%s',@$_SERVER['REMOTE_ADDR']));
#-------------------------------------------------------------------------------
$Result = CacheManager::get($CacheID);
#-------------------------------------------------------------------------------
if($Result){
	#-------------------------------------------------------------------------------
	if(In_Array(@$_SERVER['REMOTE_ADDR'],Explode(',',$Settings['ExcludeIPs']))){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/www/API/Logon]: перебор пароля для %s; с адреса %s (ИСКЛЮЧЕНИЕ); попыток %s',$Email,@$_SERVER['REMOTE_ADDR'],$Result));
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		if($Result > $Settings['BruteForceMaxAttempts']){
			#-------------------------------------------------------------------------------
			CacheManager::add($CacheID,$Result + 1,IntVal($Settings['BruteForcePeriod']));
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/www/API/Logon]: перебор пароля для %s; с адреса %s; попыток %s',$Email,@$_SERVER['REMOTE_ADDR'],$Result));
			#-------------------------------------------------------------------------------
			if($Settings['IsActive'])
				return new gException('BRUTE_PASSWORD_ATTEMPT','Попытка перебора пароля');
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Users = DB_Select('Users',Array('ID','Name','Email','Watchword','UniqID','IsActive','LockReason','EnterIP','EnterDate'),Array('SortOn'=>'ID','Where'=>SPrintF("Email = '%s'",StrToLower($Email))));
#-------------------------------------------------------------------------------
switch(ValueOf($Users)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	CacheManager::add($CacheID,$Result + 1,IntVal($Settings['BruteForcePeriod']));
	#-------------------------------------------------------------------------------
	return new gException('USER_NOT_FOUND','Пользователь не зарегистрирован в системе');
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$User = Current($Users);
#-------------------------------------------------------------------------------
if(!$User['IsActive']){
	#-------------------------------------------------------------------------------
	CacheManager::add($CacheID,$Result + 1,IntVal($Settings['BruteForcePeriod']));
	#-------------------------------------------------------------------------------
	return new gException('USER_UNACTIVE',($User['LockReason'])?$User['LockReason']:'Пользователь отключен');
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($User['Watchword'] != Md5($Password) && $User['Watchword'] != Sha1($Password)){
	#-------------------------------------------------------------------------------
	$UniqID = $User['UniqID'];
	#-------------------------------------------------------------------------------
	if($UniqID == 'no' || $UniqID != $Password){
		#-------------------------------------------------------------------------------
		CacheManager::add($CacheID,$Result + 1,IntVal($Settings['BruteForcePeriod']));
		#-------------------------------------------------------------------------------
		return new gException('PASSWORD_NOT_MATCHED','Введен неверный пароль');
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Time() - $User['EnterDate'] > 86400){
	#-------------------------------------------------------------------------------
	$IsUpdate = DB_Update('Users',Array('UniqID'=>Md5(UniqID('ID'))),Array('ID'=>$User['ID']));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$UserID = $User['ID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# для JBS-1243 - чтобы использовать при заказе с сайта. договор выбираем один, самый последний по дате
$Contracts = DB_Select('Contracts',Array('ID','Customer'),Array('UNIQ','IsDesc'=>TRUE,'SortOn'=>'StatusDate','Limits'=>Array(0,1),'Where'=>SPrintF("`UserID` = %u AND `TypeID` != 'NaturalPartner'",$UserID)));
#-------------------------------------------------------------------------------
switch(ValueOf($Contracts)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	$ContractID = 0;
	break;
case 'array':
	$ContractID = $Contracts['ID'];
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$SessionID = UniqID(SPrintF('%s%s',$IsRemember?'REMEBMER':'SESSION',MD5($UserID)));
#-------------------------------------------------------------------------------
$Session = new Session($SessionID);
#-------------------------------------------------------------------------------
$Session->Data['UsersIDs'] = Array($UserID);
#-------------------------------------------------------------------------------
$Session->Data['RootID'] = $UserID;
#-------------------------------------------------------------------------------
if(Is_Error($Session->Save()))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!SetCookie('SessionID',$SessionID,Time() + ($IsRemember?2678400:86400),'/'))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$_COOKIE['SessionID'] = $SessionID;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$User = Comp_Load('Users/Init',$User['ID']);
if(Is_Error($User))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!SetCookie('Email',$User['Email'],Time() + 31536000,'/'))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Array('UserID'=>$User['ID'],'Text'=>SPrintF('Пользователь вошел в систему с IP-адреса (%s)',$_SERVER['REMOTE_ADDR']));
#-------------------------------------------------------------------------------
$Event = Comp_Load('Events/EventInsert',$Event);
if(!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# если успешно зашёл, то обнуляем счётчик
CacheManager::add($CacheID,0,IntVal($Settings['BruteForcePeriod']));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#Debug(print_r(Array('Status'=>'Ok','SessionID'=>$SessionID,'User'=>$User,'Home'=>SPrintF('/%s/Home',$User['InterfaceID']),'ContractID'=>$ContractID,'UserID'=>$UserID),true));
return Array('Status'=>'Ok','SessionID'=>$SessionID,'User'=>$User,'Home'=>SPrintF('/%s/Home',$User['InterfaceID']),'ContractID'=>$ContractID,'UserID'=>$UserID);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
