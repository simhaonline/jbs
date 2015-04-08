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
$DSSchemeID = (string) @$Args['DSSchemeID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DSScheme = DB_Select('DSSchemesOwners','*',Array('UNIQ','ID'=>$DSSchemeID));
#-------------------------------------------------------------------------------
switch(ValueOf($DSScheme)){
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
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Тариф выделенного сервера сервера');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table = Array('Общая информация');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Название тарифа',$DSScheme['Name']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$DSScheme['CostDay']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Цена 1 дн.',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$DSScheme['CostInstall']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Стоимость установки/подключения',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ServersGroup = DB_Select('ServersGroups','*',Array('UNIQ','Where'=>SPrintF('`ID` = (SELECT `ServersGroupID` FROM `Servers` WHERE `ID` = %u)',$DSScheme['ServerID'])));
if(!Is_Array($ServersGroup))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Группа серверов',$ServersGroup['Name']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($GLOBALS['__USER']['IsAdmin']){
	#-------------------------------------------------------------------------------
	$Table[] = Array('Всего серверов',$DSScheme['NumServers']);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Свободных серверов',$DSScheme['RemainServers']);
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Formats/Logic',$DSScheme['IsCalculateNumServers']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Table[] = Array('Автоподсчёт серверов',$Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$DSScheme['IsActive']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Тариф активен',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$DSScheme['IsProlong']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Возможность продления',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Минимальное кол-во дней оплаты',$DSScheme['MinDaysPay']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Максимальное кол-во дней оплаты',$DSScheme['MaxDaysPay']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($DSScheme['MaxOrders'])
	$Table[] = Array('Максимальное число заказов',$DSScheme['MaxOrders']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Технические характеристики сервера';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Процессор',$DSScheme['CPU']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Объём оперативной памяти, Mb',$DSScheme['ram']);
#-------------------------------------------------------------------------------
$Table[] = Array('Тип RAID контроллера',$DSScheme['raid']);
#-------------------------------------------------------------------------------
$Table[] = Array('Характеристики жёстких дисков',$DSScheme['disks']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Прочая информация';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = Array('Скорость канала, мегабит',$DSScheme['chrate']);
#-------------------------------------------------------------------------------
$Table[] = Array('Месячный трафик, Gb',$DSScheme['trafflimit']);
#-------------------------------------------------------------------------------
$Table[] = Array('Соотношения трафика, in/out',$DSScheme['traffcorrelation']);
#-------------------------------------------------------------------------------
$Table[] = Array('Предустановленная ОС',$DSScheme['OS']);
#-------------------------------------------------------------------------------
$Table[] = 'Дополнительная информация';
#-------------------------------------------------------------------------------
$Table[] = new Tag('TD',Array('class'=>'Standard','colspan'=>2),$DSScheme['UserComment']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($GLOBALS['__USER']['IsAdmin']){
	#-------------------------------------------------------------------------------
	$Table[] = 'Административный комментарий';
	#-------------------------------------------------------------------------------
	$Table[] = new Tag('TD',Array('class'=>'Standard','colspan'=>2),$DSScheme['AdminComment']);
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
