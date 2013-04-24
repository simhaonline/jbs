<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','Email','Theme','Message','Heads','ID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
Debug(SPrintF('[comp/Tasks/Email]: отправка письма для (%s), тема (%s)',$Email,$Theme));
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = $Email;
#-------------------------------------------------------------------------------
$IsMail = @Mail($Email,Mb_Encode_MimeHeader($Theme),$Message,$Heads);
if(!$IsMail)
	return ERROR | @Trigger_Error('[comp/Tasks/Email]: ошибка отправки сообщения, проверьте работу функии mail в PHP');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods']['Email']['IsEvent'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Array(
		'UserID'	=> $ID,
		'Text'		=> SPrintF('Сообщение для (%s) по электронной почте с темой (%s) успешно отправлено',$Email,$Theme)
		);
$Event = Comp_Load('Events/EventInsert',$Event);
#-------------------------------------------------------------------------------
if(!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------

?>
