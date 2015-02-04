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
$ISPswSchemeID = (string) @$Args['ISPswSchemeID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$ISPswScheme = DB_Select('ISPswSchemes','*',Array('UNIQ','ID'=>$ISPswSchemeID));
#-------------------------------------------------------------------------------
switch(ValueOf($ISPswScheme)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    #---------------------------------------------------------------------------
    $DOM = new DOM();
    #---------------------------------------------------------------------------
    $Links = &Links();
    # Коллекция ссылок
    $Links['DOM'] = &$DOM;
    #---------------------------------------------------------------------------
    if(Is_Error($DOM->Load('Window')))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $DOM->AddText('Title','Тариф ПО ISPsystem');
    #---------------------------------------------------------------------------
    $Table = Array('Общая информация');
    #---------------------------------------------------------------------------
    $Table[] = Array('Название тарифа',$ISPswScheme['Name']);
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Formats/Currency',$ISPswScheme['CostDay']);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Table[] = Array('Цена 1 дн.',$Comp);
    #---------------------------------------------------------------------------
    #---------------------------------------------------------------------------
    #---------------------------------------------------------------------------
#    $Comp = Comp_Load('Formats/Logic',$ISPswScheme['IsReselling']);
#    if(Is_Error($Comp))
#      return ERROR | @Trigger_Error(500);
#    #---------------------------------------------------------------------------
#    $Table[] = Array('Права реселлера',$Comp);
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Formats/Logic',$ISPswScheme['IsActive']);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Table[] = Array('Тариф активен',$Comp);
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Formats/Logic',$ISPswScheme['IsProlong']);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Table[] = Array('Возможность продления',$Comp);
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Formats/Logic',$ISPswScheme['IsSchemeChange']);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Table[] = Array('Возможность смены тарифа',$Comp);
    #---------------------------------------------------------------------------
    $Table[] = 'Общие ограничения';
    #---------------------------------------------------------------------------
    $Table[] = Array('Максимальное число заказов',SPrintF('%s',($ISPswScheme['MaxOrders'] > 0)?$ISPswScheme['MaxOrders']:'не ограничено'));
    #---------------------------------------------------------------------------
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Tables/Standard',$Table);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $DOM->AddChild('Into',$Comp);
    #---------------------------------------------------------------------------
    if(Is_Error($DOM->Build(FALSE)))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    return Array('Status'=>'Ok','DOM'=>$DOM->Object);
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
