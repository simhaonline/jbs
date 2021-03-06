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
$TableID 	=  (string) @$Args['TableID'];
$ColumnID	=  (string) @$Args['ColumnID'];
$RowID   	= (integer) @$Args['RowID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/www/Administrator/API/BooleanEdit]: TableID = %s; ColumnID = %s; RowID = %s',$TableID,$ColumnID,$RowID));
#-------------------------------------------------------------------------------
$Selected = DB_Select($TableID,$ColumnID,Array('UNIQ','ID'=>$RowID));
#-------------------------------------------------------------------------------
switch(ValueOf($Selected)){
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
$IsUpdate = DB_Update($TableID,Array($ColumnID=>($Selected[$ColumnID])?FALSE:TRUE),Array('ID'=>$RowID));
if(Is_Error($IsUpdate))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
