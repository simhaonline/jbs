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
$Events = (array) @$Args['RowsIDs'];
#-------------------------------------------------------------------------------
if(Count($Events) < 1)
  return new gException('EVENTS_NOT_SELECTED','События не выбраны');
#-------------------------------------------------------------------------------
$Array = Array();
#-------------------------------------------------------------------------------
foreach($Events as $TaskID)
  $Array[] = (integer)$TaskID;
#-------------------------------------------------------------------------------
$IsUpdate = DB_Update('Events',Array('IsReaded'=>TRUE),Array('Where'=>SPrintF('`ID` IN (%s)',Implode(',',$Array))));
if(Is_Error($IsUpdate))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------

?>
