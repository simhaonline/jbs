<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
Header('HTTP/1.1 404 OK');
#-------------------------------------------------------------------------------
if(XML_HTTP_REQUEST)
	return new gException('PAGE_NOT_FOUND','Страница не найдена [404]');
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Main')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Страница не найдена [404]');
#-------------------------------------------------------------------------------
$DOM->AddHTML('Into',TemplateReplace('www.404'));
#-------------------------------------------------------------------------------
$Out = $DOM->Build();
#-------------------------------------------------------------------------------
if(Is_Error($Out))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------

?>
