<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('ClauseID');
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Links = &Links();
#-------------------------------------------------------------------------------
$DOM = &$Links['DOM'];
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/ClauseRating.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
$ClauseRating = DB_Select('ClausesRating',Array('AVG(`Rating`) as `Rating`'),Array('UNIQ','Where'=>SPrintF('`ClauseID` = %u',$ClauseID),'GroupBy'=>'ClauseID'));
#-------------------------------------------------------------------------------
switch(ValueOf($ClauseRating)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    # No more...
  break;
  case 'array':
    $Table[] = Array('Рейтинг',new Tag('TD',Array('id'=>'ClauseRating','class'=>'Standard'),SPrintF('%01.2f',$ClauseRating['Rating'])));
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Count = DB_Count('ClausesRating',Array('Where'=>SPrintF("`ClauseID` = %u AND `IP` = '%s'",$ClauseID,$_SERVER['REMOTE_ADDR'])));
if(Is_Error($Count))
  return ERROR | Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!$Count){
  #-----------------------------------------------------------------------------
  $Options = Array('NONE'=>'нет оценки',1=>'1 Ужасно',2=>'2 Плохо',3=>'3 Нормально',4=>'4 Хорошо',5=>'5 Отлично');
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load('Form/Select',Array('name'=>'Rating','onchange'=>SPrintF('ClauseSetRating(%u,value,this);',$ClauseID)),$Options);
  if(Is_Error($Comp))
    return ERROR | Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Table[] = Array('Ваша оценка',$Comp);
  #-----------------------------------------------------------------------------
  $Table[] = new Tag('SPAN',Array('class'=>'Comment'),'Ваше мнение очень важно для нас');
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table,'Оценка статьи');
if(Is_Error($Comp))
  return ERROR | Trigger_Error(500);
#-------------------------------------------------------------------------------
return $Comp;
#-------------------------------------------------------------------------------

?>
