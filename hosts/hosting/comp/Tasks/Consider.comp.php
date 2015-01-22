<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Where = Array('`ConsiderTypeID` = "Daily"','`Code` != "Default"','`IsProlong` = "yes"','`IsHidden` = "no"');
#-------------------------------------------------------------------------------
$Services = DB_Select('Services',Array('ID','Code','Name'),Array('Where'=>$Where));
switch(ValueOf($Services)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return MkTime(4,15,0,Date('n'),Date('j')+1,Date('Y'));
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$CurrentDay = (integer)(Time()/86400);
#-------------------------------------------------------------------------------
foreach($Services as $Service){
	#-------------------------------------------------------------------------------
	#if($Service['Code'] != 'DNSmanager')
	#	continue;
	#-------------------------------------------------------------------------------
	$Where = SPrintF("`StatusID` = 'Active' AND `ConsiderDay` < %u",$CurrentDay);
	#-------------------------------------------------------------------------------
	$Columns = Array(
			'ID','UserID','OrderID','ContractID','ConsiderDay','SchemeID',
			SPrintF('(SELECT `IsAutoProlong` FROM `Orders` WHERE `%sOrdersOwners`.`OrderID`=`Orders`.`ID`) AS `IsAutoProlong`',$Service['Code'])
			);
	#-------------------------------------------------------------------------------
	$ServiceOrders = DB_Select(SPrintF('%sOrdersOwners',$Service['Code']),$Columns,Array('Where'=>$Where,'Limit'=>Array('Start'=>0,'Length'=>5)));
	#-------------------------------------------------------------------------------
	switch(ValueOf($ServiceOrders)){
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
	#-------------------------------------------------------------------------------
	foreach($ServiceOrders as $ServiceOrder){
		#-------------------------------------------------------------------------------
		$OrderID	= (integer)$ServiceOrder['OrderID'];
		$ServiceOrderID	= (integer)$ServiceOrder['ID'];
		#------------------------------TRANSACTION--------------------------------------
		if(Is_Error(DB_Transaction($TransactionID = UniqID('OrdersConsider'))))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Where = SPrintF('`OrderID` = %u AND `DaysRemainded` > 0 AND `ID` = (SELECT MIN(`ID`) FROM `OrdersConsider` WHERE `OrderID` = %u AND `DaysRemainded` > 0)',$OrderID,$OrderID);
		#-------------------------------------------------------------------------------
		$OrdersConsider = DB_Select('OrdersConsider','*',Array('UNIQ','Where'=>$Where));
		#-------------------------------------------------------------------------------
		switch(ValueOf($OrdersConsider)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			#-------------------------------------------------------------------------------
			# check AutoProlongation
			if($ServiceOrder['IsAutoProlong']){
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[comp/Orders/Consider]: autoprolongation for %s',$ServiceOrder['OrderID']));
				#-------------------------------------------------------------------------------
				$ServiceScheme = DB_Select(SPrintF('%sSchemes',$Service['Code']),'MinDaysPay',Array('UNIQ','ID'=>$ServiceOrder['SchemeID']));
				#-------------------------------------------------------------------------------
				switch(ValueOf($ServiceScheme)){
				case 'error':
					return ERROR | @Trigger_Error(500);
				case 'exception':
					return ERROR | @Trigger_Error(400);
				case 'array':
					#-------------------------------------------------------------------------------
					$ServiceOrderPay = Comp_Load(SPrintF('www/API/%sOrderPay',$Service['Code']),Array(SPrintF('%sOrderID',$Service['Code'])=>$ServiceOrderID,'DaysPay'=>$ServiceScheme['MinDaysPay'],'IsNoBasket'=>TRUE,'PayMessage'=>'Автоматическое продление заказа, оплата с баланса договора'));
					#-------------------------------------------------------------------------------
					switch(ValueOf($ServiceOrderPay)){
					case 'error':
						return ERROR | @Trigger_Error(500);
					case 'exception':
						#-------------------------------------------------------------------------------
						$Event = Array('UserID'=>$ServiceOrder['UserID'],'Text'=>SPrintF('Не удалость автоматически оплатить заказ, причина (%s)',$ServiceOrderPay->String));
						$Event = Comp_Load('Events/EventInsert',$Event);
						if(!$Event)
							return ERROR | @Trigger_Error(500);
						#-------------------------------------------------------------------------------
						$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>SPrintF('%sOrders',$Service['Code']),'StatusID'=>'Suspended','RowsIDs'=>$ServiceOrderID,'Comment'=>SPrintF('Срок действия заказа окончен/%s',$ServiceOrderPay->String)));
						#-------------------------------------------------------------------------------
						switch(ValueOf($Comp)){
						case 'error':
							return ERROR | @Trigger_Error(500);
						case 'exception':
							return ERROR | @Trigger_Error(400);
						case 'array':
							# No more...
							break 4;
						default:
							return ERROR | @Trigger_Error(101);
						}
						#-------------------------------------------------------------------------------
					case 'array':
						# No more...
						break 3;
					default:
						return ERROR | @Trigger_Error(101);
					}
					#-------------------------------------------------------------------------------
				default:
					return ERROR | @Trigger_Error(101);
				}
				#-------------------------------------------------------------------------------
			}else{	# autoprolongation -> no autoprolongation
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[comp/Orders/Consider]: NO AutoProlongation for %s',$ServiceOrder['OrderID']));
				#-------------------------------------------------------------------------------
				$Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>SPrintF('%sOrders',$Service['Code']),'StatusID'=>'Suspended','RowsIDs'=>$ServiceOrderID,'Comment'=>'Срок действия заказа окончен/Автопродление отключено'));
				switch(ValueOf($Comp)){
				case 'error':
					return ERROR | @Trigger_Error(500);
				case 'exception':
					return ERROR | @Trigger_Error(400);
				case 'array':
					# No more...
					break;
				default:
					return ERROR | @Trigger_Error(101);
				}
				#-------------------------------------------------------------------------------
				break;
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		case 'array':
			#-------------------------------------------------------------------------------
			$IsUpdate = DB_Update('OrdersConsider',Array('DaysRemainded'=>$OrdersConsider['DaysRemainded']-1),Array('ID'=>$OrdersConsider['ID']));
			if(Is_Error($IsUpdate))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$DaysConsidered = (integer)$OrdersConsider['DaysConsidered'];
			#-------------------------------------------------------------------------------
			if($DaysConsidered){
				#-------------------------------------------------------------------------------
				$CurrentMonth = (Date('Y') - 1970)*12 + (integer)Date('n');
				#-------------------------------------------------------------------------------
				$Number = Comp_Load('Formats/Order/Number',$ServiceOrder['OrderID']);
				if(Is_Error($Number))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				$IWorkComplite = Array(
							'ContractID'	=> $ServiceOrder['ContractID'],
							'Month'		=> $CurrentMonth,
							'ServiceID'	=> $Service['ID'],
							'Comment'	=> SPrintF('№%s',$Number),
							'Amount'	=> 1,
							'Cost'		=> $OrdersConsider['Cost'],
							'Discont'	=> $OrdersConsider['Discont']
							);
				#-------------------------------------------------------------------------------
				$IsInsert = DB_Insert('WorksComplite',$IWorkComplite);
				if(Is_Error($IsInsert))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				$IsUpdate = DB_Update('OrdersConsider',Array('DaysConsidered'=>$DaysConsidered-1),Array('ID'=>$OrdersConsider['ID']));
				if(Is_Error($IsUpdate))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$ConsiderDay = (integer)$ServiceOrder['ConsiderDay'];
		#-------------------------------------------------------------------------------
		$ConsiderDay = ($ConsiderDay?$ConsiderDay+1:$CurrentDay);
		#-------------------------------------------------------------------------------
		$IsUpdate = DB_Update(SPrintF('%sOrders',$Service['Code']),Array('ConsiderDay'=>$ConsiderDay),Array('ID'=>$ServiceOrderID));
		if(Is_Error($IsUpdate))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		if(Is_Error(DB_Commit($TransactionID)))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return 60;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
