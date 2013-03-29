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
Debug(SPrintF('[comp/Services/Orders/OrdersHistory]: Params = %s',print_r($Params,true)));
#-------------------------------------------------------------------------------
$UserID = $GLOBALS['__USER']['ID'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# масив для вставки/обновления таблицы StatusesHistory
$IOrdersHistory = Array('StatusDate'=>Time());
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем, есть ли такой заказ в таблице с историей
$Where = Array(SPrintF('`UserID` = %u',$UserID),'`ServiceID` = 51000',SPrintF('`SchemeID` = %u',$Params['SchemeID']),SPrintF('`OrderID` = %u',$Params['OrderID']));
#-------------------------------------------------------------------------------
$OrdersHistory = DB_Select('OrdersHistory',Array('ID'),Array('UNIQ','Where'=>$Where));
switch(ValueOf($OrdersHistory)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	# проверяем, были ли такие же заказы
	$Count = DB_Count('OrdersHistory',Array('Where'=>Array(SPrintF('`UserID` = %u',$UserID),'`ServiceID` = 51000',SPrintF('`SchemeID` = %u',$Params['SchemeID']))));
	if(Is_Error($Count))
		return ERROR | Trigger_Error(500);
	#-------------------------------------------------------------------------------
	# проверяем, как много таких заказов можно делать
	if($Params['MaxOrders'] > 0 && $Count >= $Params['MaxOrders'])
		if(!$GLOBALS['__USER']['IsAdmin'])
			return new gException('TOO_MANY_ORDERS',SPrintF('Для данного тарифного плана существует ограничение на максимальное число заказов, равное %s. Ранее, вы уже делали заказы по данному тарифу, и больше сделать не можете. Выберите другой тарифный план.',$Params['MaxOrders']));
	#-------------------------------------------------------------------------------
	# вносим заказ в таблицу, если его там нет
	$IOrdersHistory['UserID']	= $UserID;
	$IOrdersHistory['Email']	= $GLOBALS['__USER']['Email'];
	$IOrdersHistory['ServiceID']	= $Params['ServiceID'];
	$IOrdersHistory['ServiceName']	= $Params['ServiceName'];
	$IOrdersHistory['SchemeID']	= $Params['SchemeID'];
	$IOrdersHistory['SchemeName']	= $Params['SchemeName'];
	$IOrdersHistory['OrderID']	= $Params['OrderID'];
	$IOrdersHistory['CreateDate']	= Time();
	#-------------------------------------------------------------------------------
	$IsInsert = DB_Insert('OrdersHistory',$IOrdersHistory);
	if(Is_Error($IsInsert))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'array':
	#-------------------------------------------------------------------------------
	# это вторичная проставка статуса для заказа. просто обновляем StatusDate
	$IsUpdate = DB_Update('OrdersHistory',$IOrdersHistory,Array('ID'=>$OrdersHistory['ID']));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------

?>
