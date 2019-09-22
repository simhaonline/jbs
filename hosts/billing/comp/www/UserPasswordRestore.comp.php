<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$Address	=  (string) @$Args['Address'];
$Protect	= (integer) @$Args['Protect'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Address = StrToLower($Address);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// это вторая стадия, уже восстановление идёт
if($Address){
	#-------------------------------------------------------------------------------
	if(Is_Error($DOM->Load('Window')))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$DOM->AddText('Title',SPrintF('Восстановление пароля для %s',$Address));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/UserPasswordRestore]: восстановление пароля для контакта: %s',$Address));
	#-------------------------------------------------------------------------------
	// проверить присланный код
	$Comp = Comp_Load('Protect',$Protect);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	if(!$Comp)
		return new gException('WRONG_PROTECT_CODE','Введенный Вами защитный код неверен, либо устарел. Пожалуйста, обновите страницу и введите его заново.');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// ключик для кэша, по IP адресу
	$CacheID = Md5($_SERVER['REMOTE_ADDR']);
	#-------------------------------------------------------------------------------
	// массив идентификаторов, для передачи на следующую стадию
	$ContactsIDs = Array();
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Regulars = Regulars();
	#-------------------------------------------------------------------------------
	// 1. если есть собака - это жаббер или почта
	if(!Preg_Match($Regulars['Email'],$Address)){
		#-------------------------------------------------------------------------------
		// убираем мусор из телефона
		$Address = Preg_Replace('/[^0-9]/','',$Address);
		#-------------------------------------------------------------------------------
		// 2. если нет собаки - это телефон
		if(!Preg_Match($Regulars['SMS'],$Address))
			return new gException('WRONG_ADDRESS','Введенный Вами контактный адрес не является адресом электронной почты или мобильным телефоном');
		#-------------------------------------------------------------------------------	
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// строим таблицу в которой покажем контакты на которые возможно выслать новый пароль
	$Table = Array('Отметьте адрес на который будет выслан пароль');
	#-------------------------------------------------------------------------------
	$Tr = new Tag('TR');
	#-------------------------------------------------------------------------------
	$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center'),'Зарегистрирован'));
	$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center'),'Последний вход'));
	$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center'),'Тип'));
	$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center'),'Адрес'));
	$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center'),'-'));
	#-------------------------------------------------------------------------------
	$Table[] = $Tr;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// смотрим таблицу контаков, вначале в лоб
	// TODO рассмотреть возможность искать по части адреса
	$Columns = Array(
			'*','(SELECT `RegisterDate` FROM `Users` WHERE `Users`.`ID` = `Contacts`.`UserID`) AS `RegisterDate`',
			'(SELECT `EnterDate` FROM `Users` WHERE `Users`.`ID` = `Contacts`.`UserID`) AS `EnterDate`',
			'(SELECT `IsHidden` FROM `Users` WHERE `Users`.`ID` = `Contacts`.`UserID`) AS `IsHidden`',
			'(SELECT `IsProtected` FROM `Users` WHERE `Users`.`ID` = `Contacts`.`UserID`) AS `IsProtected`',
			);
	#-------------------------------------------------------------------------------
	$Where = SPrintF('`UserID` IN (SELECT `UserID` FROM `Contacts` WHERE `Address` = "%s")',$Address);
	#-------------------------------------------------------------------------------
	$Users = DB_Select('Contacts',$Columns,Array('Where'=>$Where,'SortOn'=>Array('UserID','MethodID','Address')));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Users)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('WRONG_ADDRESS','Введенный контактный адрес не найден ни у одного клиента. Обновите изображение и попробуйте снова');
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// перебираем найденных юзеров, достаём их контакты и строим таблицу
	foreach($Users as $User){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/www/UserPasswordRestore]: построение строки таблицы для %u/%s/%s',$User['ID'],$User['MethodID'],$User['Address']));
		#-------------------------------------------------------------------------------
		$Tr = new Tag('TR');
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$RegisterDate = Comp_Load('Formats/Date/Extended',$User['RegisterDate']);
		if(Is_Error($RegisterDate))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Tr->AddChild(new Tag('TD',Array('class'=>'Transparent'),$RegisterDate));
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$EnterDate = Comp_Load('Formats/Date/Extended',$User['EnterDate']);
		if(Is_Error($EnterDate))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Tr->AddChild(new Tag('TD',Array('class'=>'Transparent'),$RegisterDate));
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Tr->AddChild(new Tag('TD',Array('class'=>'Transparent'),$User['MethodID']));
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		// если это не тот адрес что был введён пользователем - требуется обфускация
		if($User['Address'] != $Address){
			#-------------------------------------------------------------------------------
			if(Preg_Match($Regulars['Email'],$User['Address']))
				$Display = Preg_Replace('/(?!^).(?=[^@]+@)/','*',$User['Address']);
			#-------------------------------------------------------------------------------
			if(Preg_Match($Regulars['SMS'],$User['Address']))
				$Display = SubStr_Replace($User['Address'],'***',4,3);
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			$Display = $Address;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Tr->AddChild(new Tag('TD',Array('class'=>'Transparent'),$Display));
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Prompt = 'Отметтьте тот адрес на который вы хотите получить новый пароль';
		#-------------------------------------------------------------------------------
		$IsDisabled = FALSE;
		#-------------------------------------------------------------------------------
		// адрес не подтверждён и не логин
		if(!$User['Confirmed'] && !$User['IsPrimary']){
			#-------------------------------------------------------------------------------
			$Prompt = 'Этот адрес не был подтверждён пользователем, его нельзя использовать для восстановления пароля';
			#-------------------------------------------------------------------------------
			$IsDisabled = TRUE;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		// для адреса отключены уведомления
		/* по здравому размышлению решил что надо дать такую возможность - всё таки не просто оповещение а востановление пароля
		if(!$User['IsActive']){
			#-------------------------------------------------------------------------------
			$Prompt = 'Для этого адреса отключены уведомления, его нельзя использовать для восстановления пароля';
			#-------------------------------------------------------------------------------
			$IsDisabled = TRUE;
			#-------------------------------------------------------------------------------
		}
		*/
		#-------------------------------------------------------------------------------
		// этому юзеру нельзя восстаналивать пароль
		if($User['IsProtected']){
			#-------------------------------------------------------------------------------
			$Prompt = 'Это защищённый пользователь, ему нельзя сбрасывать пароль';
			#-------------------------------------------------------------------------------
			$IsDisabled = TRUE;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Attribs = Array('type'=>'checkbox');
		#-------------------------------------------------------------------------------
		// чекбокс для указания куда востанавливаем
		$Checkbox = Comp_Load('Form/Input',Array('name'=>'ContactsIDs[]','type'=>'checkbox','prompt'=>$Prompt,'value'=>$User['ID']));
		if(Is_Error($Checkbox))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		// дисамблим, если надо
		if($IsDisabled){
			#-------------------------------------------------------------------------------
			$Checkbox->AddAttribs(Array('disabled'=>TRUE));
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			// передаём идентифкатор контакта и юзера на следующую стадию восстановления пароля
			$ContactsIDs[$User['ID']] = $User['UserID'];
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Tr->AddChild(new Tag('TD',Array('class'=>'Transparent'),$Checkbox));
		#-------------------------------------------------------------------------------
		$Table[] = $Tr;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'type'		=> 'button',
				'onclick'	=> 'UserPasswordRestore();',
				'value'		=> 'Выслать',
				'size'		=> 15
				)
			);
	#-------------------------------------------------------------------------------
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = $Comp;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// кэшируем идентификаторы на 5 минут
	CacheManager::add($CacheID,$ContactsIDs,300);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	// первый заход на страницу
	if(Is_Error($DOM->Load('Main')))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$DOM->AddText('Title','Восстановление пароля');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Prompt = "Введите контактный адрес который вы помните. Допускается ввод:\n1. почтовых адресов в формате user@domain.su\n2. аккаунтов Jabber в формате user@domain.su\n3. телефонных номеров в формате +7-926-123-45-67";
	$Comp = Comp_Load('Form/Input',Array('name'=>'Address','prompt'=>$Prompt,'type'=>'text'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table = Array(Array('Контактный адрес',$Comp));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Img = new Tag('IMG',Array('id'=>'Protect','OnClick'=>'ReloadProtect()','style'=>'cursor:pointer;','align'=>'left','width'=>80,'height'=>30,'alt'=>'Включите отображение картинок','src'=>SPrintF('/Protect?Rand=%u',Rand(1000,9999))));
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>'Protect','type'=>'text'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Защитный код',$Img);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Цифры на изображении',$Comp);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load(
			'Form/Input',
			Array(
				'type'		=> 'button',
				'onclick'	=> "ShowWindow('/UserPasswordRestore',FormGet(form));",
				'value'		=> 'Восстановить',
				'size'		=> 15
				)
			);
	#-------------------------------------------------------------------------------
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Table[] = $Comp;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/www/UserPasswordRestore]: $Table = %s',print_r($Table,true)));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>SPrintF('UserPasswordRestoreForm%s',($Address)?2:1),'onsubmit'=>'return false;'),$Comp);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/UserPasswordRestore.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
// разное поведение на первый заход и на указанынй адрес, просто странциа и окно
if($Address){
	#-------------------------------------------------------------------------------
	if(Is_Error($DOM->Build(FALSE)))
		return ERROR | @Trigger_Error(500);
	#---------------------------------------------------------------------------
	#---------------------------------------------------------------------------
	return Array('Status'=>'Ok','DOM'=>$DOM->Object);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Out = $DOM->Build();
	#-------------------------------------------------------------------------------
	if(Is_Error($Out))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return $Out;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
