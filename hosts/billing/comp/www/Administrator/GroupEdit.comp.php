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
$GroupID = (integer) @$Args['GroupID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($GroupID){
  #-----------------------------------------------------------------------------
  $Group = DB_Select('Groups','*',Array('UNIQ','ID'=>$GroupID));
  #-----------------------------------------------------------------------------
  switch(ValueOf($Group)){
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
}else{
  #-----------------------------------------------------------------------------
  $Group = Array(
    #---------------------------------------------------------------------------
    'ParentID'     => 1,
    'Name'         => 'Новая группа',
    'IsDefault'    => FALSE,
    'IsDepartment' => FALSE,
    'Comment'      => 'Очень нужная группа'
  );
}
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Title = ($GroupID?'Редактирование группы':'Добавление новой группы');
#-------------------------------------------------------------------------------
$DOM->AddText('Title',$Title);
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
$Groups = DB_Select('Groups','*');
#-------------------------------------------------------------------------------
switch(ValueOf($Groups)){
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
$Options = Array();
#-------------------------------------------------------------------------------
foreach($Groups as $Element)
  $Options[$Element['ID']] = $Element['Name'];
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'ParentID','style'=>'width: 100%;'),$Options,$Group['ParentID']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Группа родитель',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'Name',
    'value' => $Group['Name']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Название группы',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsDefault','value'=>'yes'));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Group['IsDefault'])
  $Comp->AddAttribs(Array('checked'=>'yes'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsDefault\'); return false;'),'Группа по умолчанию'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Input',Array('type'=>'checkbox','name'=>'IsDepartment','value'=>'yes'));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($Group['IsDepartment'])
  $Comp->AddAttribs(Array('checked'=>'yes'));
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsDepartment\'); return false;'),'Является отделом'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/TextArea',
  Array(
    'name'  => 'Comment',
    'style' => 'width:100%;',
    'rows'  => 5
  ),
  $Group['Comment']
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = 'Комментарий';
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => SPrintF("FormEdit('/Administrator/API/GroupEdit','GroupEditForm','%s');",$Title),
    'value'   => ($GroupID?'Сохранить':'Добавить')
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'GroupEditForm','onsubmit'=>'return false;'),$Comp);
#-------------------------------------------------------------------------------
if($GroupID){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'name'  => 'GroupID',
      'type'  => 'hidden',
      'value' => $GroupID
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Form->AddChild($Comp);
}
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------
?>
