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
$UserID = (integer) @$Args['UserID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Columns = Array(
			'ID','RegisterDate','Name','GroupID','Email',
			'Sign','OwnerID','IsManaged','LayPayMaxDays',
			'LayPayMaxSumm','LayPayThreshold','EnterDate','EnterIP',
			'Rating','IsActive','LockReason','IsNotifies','IsHidden','IsProtected','AdminNotice','Params',
			'(SELECT COUNT(*) FROM `OrdersOwners` WHERE `OrdersOwners`.`UserID`=`Users`.`ID`) AS `NumOrders`',
			'(SELECT COUNT(*) FROM `OrdersOwners` WHERE `OrdersOwners`.`UserID`=`Users`.`ID` AND `OrdersOwners`.`StatusID`="Active") AS `NumActiveOrders`',
			'(SELECT SUM(`Summ`) FROM `InvoicesOwners` WHERE `InvoicesOwners`.`UserID`=`Users`.`ID`) AS `TotalPayments`',
			'(SELECT SUM(`Summ`) FROM `InvoicesOwners` WHERE `InvoicesOwners`.`UserID`=`Users`.`ID` AND `InvoicesOwners`.`StatusID`="Payed") AS `SummPayments`',
			'(SELECT SUM(`Balance`) FROM `ContractsOwners` WHERE `ContractsOwners`.`UserID`=`Users`.`ID`) AS `BalanceSumm`'
		);
#-------------------------------------------------------------------------------
$User = DB_Select('Users',$Columns,Array('UNIQ','ID'=>$UserID));
#-------------------------------------------------------------------------------
switch(ValueOf($User)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$IsPermission = Permission_Check('UsersRead',(integer)$__USER['ID'],(integer)$User['ID']);
#-------------------------------------------------------------------------------
switch(ValueOf($IsPermission)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'false':
	return new gException('USER_MANAGMENT_DISABLED','Просмотр информации запрещен');
case 'true':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title',SPrintF('Пользователь #%u',$UserID));
#-------------------------------------------------------------------------------
$Table = Array('Общая информация');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Идентификатор',SPrintF('#%u',$UserID));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Date/Extended',$User['RegisterDate']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Дата регистрации',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Имя',$User['Name']);
#-------------------------------------------------------------------------------
$Sign = WordWrap($User['Sign'],100,"\n");
#-------------------------------------------------------------------------------
$Table[] = Array('Подпись',new Tag('PRE',Array('class'=>'Standard'),$Sign?$Sign:'-'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Контактная информация';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Contacts = DB_Select('Contacts','*',Array('Where'=>SPrintF('`UserID` = %u',$User['ID']),'SortOn'=>Array('MethodID','Address')));
#-------------------------------------------------------------------------------
switch(ValueOf($Contacts)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach($Contacts as $Contact){
	#-------------------------------------------------------------------------------
	$Address = ($Contact['IsPrimary'])?SPrintF('%s [*]',$Contact['Address']):$Contact['Address'];
	#-------------------------------------------------------------------------------
	// для телеграмма кастыль
	if(In_Array($Contact['MethodID'],Array('Telegram','Viber','VKontakte')) && $Contact['ExternalID'])
		$Address = SPrintF('ChatID = %s',$Contact['ExternalID']);
	#-------------------------------------------------------------------------------
	$RowName = ($Contact['IsHidden'])?SPrintF('%s [удалён]',$Config['Notifies']['Methods'][$Contact['MethodID']]['Name']):$Config['Notifies']['Methods'][$Contact['MethodID']]['Name'];
	$Table[] = Array($RowName,$Address);
	#-------------------------------------------------------------------------------
	if($Contact['Confirmed']){
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Date/Extended',$Contact['Confirmed']);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Table[] = Array(SPrintF('%s подтверждён',$Config['Notifies']['Methods'][$Contact['MethodID']]['Name']),$Comp);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$OwnerID = $User['OwnerID'];
#-------------------------------------------------------------------------------
if($OwnerID){
	#-------------------------------------------------------------------------------
	$Table[] = '-Партнерская программа';
	#-------------------------------------------------------------------------------
	$Owner = DB_Select('Users',Array('Name','Email'),Array('UNIQ','ID'=>$OwnerID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Owner)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		#-------------------------------------------------------------------------------
		$Table[] = Array('Партнер',SPrintF('%s (%s)',$Owner['Name'],$Owner['Email']));
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Logic',$User['IsManaged']);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Table[] = Array('Возможность управления',$Comp);
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = '-Условия отложенного платежа';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Максимальное кол-во дней',$User['LayPayMaxDays']);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$User['LayPayMaxSumm']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Максимальная сумма',$Comp);
#-------------------------------------------------------------------------------
#-----------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$User['LayPayThreshold']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Пороговая сумма',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# JBS-348
$Table[] = 'Активность пользователя';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Активных услуг',$User['NumActiveOrders']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$User['SummPayments']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Оплачено счетов на сумму',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($User['NumOrders'] != $User['NumActiveOrders'])
	$Table[] = Array('Всего заказано услуг',$User['NumOrders']);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$User['TotalPayments']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($User['TotalPayments'] != $User['SummPayments']){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Currency',$User['SummPayments']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Выписано счетов на сумму',$Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($User['BalanceSumm'] > 0){
	#-------------------------------------------------------------------------------
	$Table[] = 'Договора с ненулевым баллансом';
	#-------------------------------------------------------------------------------
	$Contracts = DB_Select('ContractsOwners',Array('ID','Customer','TypeID','Balance'),Array('Where'=>SPrintF('`UserID` = %u AND `Balance` != 0',$User['ID']),'SortOn'=>Array('TypeID','Customer')));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Contracts)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	foreach($Contracts as $Contract){
		#-------------------------------------------------------------------------------
		$Number = Comp_Load('Formats/Contract/Number',$Contract['ID']);
		if(Is_Error($Number))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Name = Comp_Load('Formats/String',SPrintF('#%s: %s',$Number,$Contract['Customer']),25);
		if(Is_Error($Name))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Currency',$Contract['Balance']);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Tr = new Tag('TR');
		$Tr->AddChild(new Tag('TD',Array(),$Name));
		$Tr->AddChild(new Tag('TD',Array('class'=>'Standard'),$Comp));
		#-------------------------------------------------------------------------------
		$Table[] = $Tr;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Currency',$User['BalanceSumm']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	if(SizeOf($Contracts) > 1)
		$Table[] = Array('Сумма балансов всех счетов',$Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Информация о работе в системе';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Date/Extended',$User['EnterDate']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Дата последнего входа',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Extension_Loaded('geoip') && (geoip_db_avail(GEOIP_COUNTRY_EDITION))){
	#-------------------------------------------------------------------------------
	$Country = geoip_country_name_by_name($User['EnterIP']);
	#-------------------------------------------------------------------------------
	if($GeoIP = @geoip_record_by_name($User['EnterIP']))
		if($GeoIP['city'])
			$City = $GeoIP['city'];
	#-------------------------------------------------------------------------------
	#Debug(SPrintF("[comp/www/UserInfo]: @geoip_record_by_name = %s",print_r(@geoip_record_by_name($User['EnterIP']),true)));
	#-------------------------------------------------------------------------------
	$CountryCode = @geoip_country_code3_by_name($User['EnterIP']);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#Debug(SPrintF("[comp/www/UserInfo]: @City = %s",print_r(@$City,true)));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# города бывают не всегда латиницей. пример - Орёл
if(IsSet($City))
	$City = @Iconv('','ISO-8859-1//IGNORE',$City);
#-------------------------------------------------------------------------------
#Debug(SPrintF("[comp/Edesks/Message]: City = %s",@$City));
#-------------------------------------------------------------------------------
$IPInfo = SPrintF('IP: %s %s %s',$User['EnterIP'],(IsSet($Country))?SPrintF(' / %s',$Country):'GeoIP not avalible',(IsSet($City))?SPrintF(' / %s',$City):'');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$UserInfoImage = SprintF('%s',HtmlSpecialChars($IPInfo,ENT_QUOTES));
#-------------------------------------------------------------------------------
if(IsSet($CountryCode) && $CountryCode){
	#-------------------------------------------------------------------------------
	$Flag = Comp_Load('Formats/CountryImage',$CountryCode,16,$UserInfoImage);
	if(Is_Error($Flag))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('IP-адрес последнего входа',new Tag('NOBODY',$Flag,new Tag('SPAN',SPrintF(' | %s',$User['EnterIP']))));
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Table[] = Array('IP-адрес последнего входа',$User['EnterIP']);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Служебная информация';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Рейтинг',$User['Rating']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$User['IsActive']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Активный пользователь',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($User['LockReason']){
	#-------------------------------------------------------------------------------
	$LockReason = Comp_Load('Formats/String',$User['LockReason'],25);
	if(Is_Error($LockReason))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Причина блокировки',$LockReason);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$User['IsNotifies']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Рассылать уведомления',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$User['IsHidden']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Скрытый пользователь',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$User['IsProtected']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Защищенный пользователь',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($GLOBALS['__USER']['IsAdmin']){
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('type'=>'button','onclick'=>SPrintF("ShowWindow('/Administrator/UserEdit',{UserID:'%u'});",$User['ID']),'value'=>'Редактировать'));
	#-------------------------------------------------------------------------------
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Div = new Tag('DIV',Array('align'=>'right'),$Comp);
	#-------------------------------------------------------------------------------
	$Table[] = $Div;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
