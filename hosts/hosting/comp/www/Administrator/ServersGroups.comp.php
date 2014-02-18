<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Base')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddAttribs('MenuLeft',Array('args'=>'Administrator/AddIns'));
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Дополнения → Мастера настройки → Группы серверов');
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Buttons/Standard',Array('onclick'=>"ShowWindow('/Administrator/ServersGroupEdit');"),'Новая группа серверов','Add.gif');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Buttons/Panel',Array('Comp'=>$Comp,'Name'=>'Новая группа серверов'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Super','ServersGroups');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody->AddChild($Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tab','Administrator/Masters',$NoBody);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
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

?>
