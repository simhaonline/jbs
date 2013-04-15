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
$IsExternal = !IsSet($Args);
#-------------------------------------------------------------------------------
if($IsExternal){
  #-----------------------------------------------------------------------------
  if(Is_Error(System_Load('modules/Authorisation.mod')))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Args = Args();
  # added by lissyara 2012-10-03 in 19:34 MSK
  if(!$GLOBALS['__USER']['IsAdmin'])
    return new gException('EXTERNAL_STATUS_SET_ONLY_FOR_ADMINS','Установка статусов доступна только персоналу');
}
#-------------------------------------------------------------------------------
$ModeID      =   (string) @$Args['ModeID'];
$StatusID    =   (string) @$Args['StatusID'];
$RowsIDs     =    (array) @$Args['RowsIDs'];
$Comment     =   (string) @$Args['Comment'];
if(IsSet($Args['IsNoTrigger'])){
	$IsNoTrigger	= (boolean)$Args['IsNoTrigger'];
}else{
	$IsNoTrigger	= false;
}
if(IsSet($Args['IsNotNotify'])){
	$IsNotNotify	= (boolean)$Args['IsNotNotify'];
}else{
	$IsNotNotify	= false;
}
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/www/API/StatusSet]: ModeID = %s; StatusID = %s; RowsIDs = %s; Comment = %s; IsNoTrigger = %s; IsNotNotify = %s',$ModeID,$StatusID,Is_Array($RowsIDs)?'Array':$RowsIDs,$Comment,$IsNoTrigger,$IsNotNotify));
#-------------------------------------------------------------------------------
$Regulars = Regulars();
#-------------------------------------------------------------------------------
if(!Preg_Match($Regulars['ID'],$ModeID))
  return ERROR | @Trigger_Error(201);
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Statuses = $Config['Statuses'][$ModeID];
#-------------------------------------------------------------------------------
if(!IsSet($Statuses[$StatusID]))
  return new gException('STATUS_NOT_FOUND','Выбранный статус не найден');
#-------------------------------------------------------------------------------
$Status = $Statuses[$StatusID];
#-------------------------------------------------------------------------------
if(Count($RowsIDs) < 1)
  return new gException('ROWS_NOT_SELECTED','Записи для установки статуса не указаны');
#-------------------------------------------------------------------------------
$Array = Array();
#-------------------------------------------------------------------------------
foreach($RowsIDs as $RowID)
  $Array[] = (integer)$RowID;
#-------------------------------------------------------------------------------
$Rows = DB_Select(SPrintF('%sOwners',$ModeID),'*',Array('Where'=>SPrintF('`ID` IN (%s)',Implode(',',$Array)),'GroupBy'=>'ID'));
#-------------------------------------------------------------------------------
switch(ValueOf($Rows)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return new gException('ROW_NOT_FOUND','Записи для установки статуса не найдены');
  case 'array':
    #---------------------------------------------------------------------------
    if(Is_Error(DB_Transaction($TransactionID = UniqID('StatusSet'))))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Exceptions = Array();
    #---------------------------------------------------------------------------
    foreach($Rows as $Row){
      #-------------------------------------------------------------------------
      if($IsExternal && IsSet($GLOBALS['__USER'])){
        #-----------------------------------------------------------------------
        $IsPermission = Permission_Check(SPrintF('%sStatusSet',$ModeID),(integer)$GLOBALS['__USER']['ID'],(integer)$Row['UserID']);
        #-----------------------------------------------------------------------
        switch(ValueOf($IsPermission)){
          case 'error':
            return ERROR | @Trigger_Error(500);
          case 'exception':
            return ERROR | @Trigger_Error(400);
          case 'false':
            return ERROR | @Trigger_Error(700);
          case 'true':
            # No more...
          break;
          default:
            return ERROR | @Trigger_Error(101);
        }
      }
      #-------------------------------------------------------------------------------
      #-------------------------------------------------------------------------------
      if(!$IsNoTrigger){
        #-----------------------------------------------------------------------
        $Path = SPrintF('Triggers/Statuses/%s/%s',$ModeID,$StatusID);
        #-----------------------------------------------------------------------
        if(!Is_Error(System_Element(SPrintF('comp/%s.comp.php',$Path)))){
          #---------------------------------------------------------------------
          $Results = Comp_Load($Path,$Row,COMP_ALL_HOSTS);
          #---------------------------------------------------------------------
          switch(ValueOf($Results)){
            case 'error':
              return ERROR | @Trigger_Error(500);
            case 'array':
              #-----------------------------------------------------------------
              foreach($Results as $Result){
                #---------------------------------------------------------------
                switch(ValueOf($Result)){
                  case 'exception':
                    #-----------------------------------------------------------
                    if(Is_Error(DB_Roll($TransactionID)))
                      return ERROR | @Trigger_Error(500);
                    #-----------------------------------------------------------
                    return new gException('STATUS_SET_ERROR','Не удалось установить статус объекту',$Result);
                  case 'true':
                    # No more...
                  break;
                  default:
                    return ERROR | @Trigger_Error(101);
                }
              }
            break;
            default:
              return ERROR | @Trigger_Error(101);
          }
        }
      }
      #-------------------------------------------------------------------------
      $StatusDate = Time();
      #-------------------------------------------------------------------------
      $IsUpdate = DB_Update($ModeID,Array('StatusID'=>$StatusID,'StatusDate'=>$StatusDate),Array('ID'=>$Row['ID']));
      if(Is_Error($IsUpdate))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $IStatusHistory = Array('StatusDate'=>$StatusDate,'ModeID'=>$ModeID,'RowID'=>$Row['ID'],'StatusID'=>$StatusID,'Comment'=>$Comment);
      #-------------------------------------------------------------------------
      if(IsSet($GLOBALS['__USER'])){
        #-----------------------------------------------------------------------
        $__USER = $GLOBALS['__USER'];
        #-----------------------------------------------------------------------
        $IStatusHistory['Initiator'] = SPrintF('%s (%s)',$__USER['Name'],$__USER['Email']);
      }
      #-------------------------------------------------------------------------
      $IsInsert = DB_Insert('StatusesHistory',$IStatusHistory);
      if(Is_Error($IsInsert))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $Row = DB_Select(SPrintF('%sOwners',$ModeID),'*',Array('GroupBy'=>'ID','UNIQ','ID'=>$Row['ID']));
      #-------------------------------------------------------------------------
      switch(ValueOf($Row)){
        case 'error':
          return ERROR | @Trigger_Error(500);
        case 'exception':
          return ERROR | @Trigger_Error(400);
        case 'array':
          #---------------------------------------------------------------------
          if(!$IsNoTrigger && !$IsNotNotify) {
            try {
                $msgClass = SPrintF('%s%sMsg',$ModeID,$StatusID);

                if (class_exists($msgClass)) {
                    $msg = new $msgClass($Row, $Row['UserID']);
                    #-------------------------------------------------------------------
                    $IsSend = NotificationManager::sendMsg($msg);
                    #-------------------------------------------------------------------
                    switch(ValueOf($IsSend)){
                      case 'error':
                        return ERROR | @Trigger_Error(500);
                      case 'exception':
                        # No more...
                      break;
                      case 'true':
                        # No more...
                      break;
                      default:
                        return ERROR | @Trigger_Error(101);
                    }
                }
            }
            catch (Exception $e) {
                Debug("Couldn't load dispatcher class: ".$msgClass.' . Message: '.$e->getTraceAsString());
            }
          }
        break;
        default:
          return ERROR | @Trigger_Error(101);
      }
    }
    #---------------------------------------------------------------------------
    if(Is_Error(DB_Commit($TransactionID)))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    return Array('Status'=>'Ok');
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
