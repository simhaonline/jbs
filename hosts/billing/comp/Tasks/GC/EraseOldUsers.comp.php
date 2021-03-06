<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Params');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Tasks']['Types']['GC']['EraseOldUsersSettings'];
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// выбираем неактивных пользователей
$Where = Array(
			/* идентификатор больше 2000 - ниже, тока у системных */
			'`ID` > 2000',
			/* не защищённый */
			'`IsProtected` = "no"',
			/* не входил в биллинг больше чем настроено (год, по дефолту) */
			SPrintF('`EnterDate` < UNIX_TIMESTAMP() - %u * 24 * 3600',$Settings['InactiveDaysForUser']),
			/* зарегистрированный более этого же времени назад */
			SPrintF('`RegisterDate` < UNIX_TIMESTAMP() - %u * 24 * 3600',$Settings['InactiveDaysForUser']),
			/* нет заказов */
			'(SELECT COUNT(*) FROM `OrdersOwners` WHERE `UserID` = `Users`.`ID`) = 0',
			/* нет выписанных счетов на оплату */
			'(SELECT COUNT(*) FROM `InvoicesOwners` WHERE `UserID` = `Users`.`ID`) = 0',
			/* нет договоров с баллансом больше нуля */
			'(SELECT SUM(`Balance`) FROM `ContractsOwners` WHERE `UserID` = `Users`.`ID`) = 0',
			/* нет рефералов */
			'(SELECT COUNT(*) FROM `Users` WHERE `OwnerID` = `Users`.`ID`) = 0',
			/* нет свежих потстов в тикетницу */
			SPrintF('(SELECT MAX(`CreateDate`) FROM `EdesksMessagesOwners` WHERE `UserID` = `Users`.`ID`) < UNIX_TIMESTAMP() - %u * 24 * 3600 OR (SELECT MAX(`CreateDate`) FROM `EdesksMessagesOwners` WHERE `UserID` = `Users`.`ID`) IS NULL',$Settings['InactiveDaysForUser'],$Settings['InactiveDaysForUser']),
		);
#-------------------------------------------------------------------------------
$Users = DB_Select('Users', Array('ID','Email','Name','EnterDate','RegisterDate'),Array('Where'=>$Where,'Limits'=>Array(0,20)));
#-------------------------------------------------------------------------------
switch(ValueOf($Users)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return TRUE;
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
// перебираем полученных пользователей.
foreach($Users as $User){
	#-------------------------------------------------------------------------------
	# выбираем меньшую из дат, т.к. мог не входить никогда
	$EnterDate = (($User['EnterDate'] > $User['RegisterDate'])?$User['EnterDate']:$User['RegisterDate']);
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/GC/EraseOldUsers]: автоматическое удаление юзера (%s) не заходившего в биллинг %s дней',$User['Email'],Ceil((Time() - $EnterDate)/(24*3600))));
	#-------------------------------------------------------------------------------
	// удаляем юзера
	$Comp = Comp_Load('www/API/Delete',Array('TableID'=>'Users','RowsIDs'=>$User['ID']));
	#-------------------------------------------------------------------------
	switch(ValueOf($Comp)){
	case 'array':
		#-------------------------------------------------------------------------------
		$Event = Array(
				'PriorityID'    => 'Billing',
				'IsReaded'	=> ($Settings['IsEvent']?FALSE:TRUE),
				'Text'          => SPrintF('Удалён пользователь (%s / %s / #%s ) не заходивший в биллинг %s дней',$User['Email'],$User['Name'],$User['ID'],Ceil((Time() - $EnterDate)/(24*3600)))
			);
		$Event = Comp_Load('Events/EventInsert',$Event);
		if(!$Event)
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(500);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Count = DB_Count('Users',Array('Where'=>$Where));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/GC/EraseOldUsers]: осталось удалить %u юзеров',$Count));
#-------------------------------------------------------------------------------
return (($Count)?90:TRUE);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
