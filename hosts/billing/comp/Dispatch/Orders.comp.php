<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('IsSearch');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Statuses = $Config['Statuses']['HostingOrders'];
#-------------------------------------------------------------------------------
$Filters = Array('С заказами в статусе');
#-------------------------------------------------------------------------------
foreach(Array_Keys($Statuses) as $Key){
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/Dispatch/Orders]: $Key = %s; $Statuses[$Key][Name] = %s',$Key,$Statuses[$Key]['Name']));
	#-------------------------------------------------------------------------------
	$Orders = DB_Select('OrdersOwners',Array('ID','UserID'),Array('Where'=>SPrintF('`StatusID` = "%s"',$Key)));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Orders)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		continue 2;
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	$Filter = Array('Name'=>$Statuses[$Key]['Name'],'UsersIDs'=>Array());
	#-------------------------------------------------------------------------------
	if($IsSearch){
			#-------------------------------------------------------------------------------
			$UsersIDs = Array();
			#-------------------------------------------------------------------------------
			foreach($Orders as $Order)
				if(!In_Array($Order['UserID'],$UsersIDs))
					$UsersIDs[] = $Order['UserID'];
			#-------------------------------------------------------------------------------
			$Filter['UsersIDs'] = $UsersIDs;
			#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Filters[SPrintF('Orders_%s',$Key)] = $Filter;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Filters;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
