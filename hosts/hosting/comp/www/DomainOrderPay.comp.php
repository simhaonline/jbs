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
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$DomainOrderID  = (integer) @$Args['DomainOrderID'];
$OrderID        = (integer) @$Args['OrderID'];
$YearsPay       = (integer) @$Args['YearsPay'];
$IsChange       = (boolean) @$Args['IsChange'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Columns = Array('ID','ContractID','OrderID','UserID','DomainName','ExpirationDate','StatusID','SchemeID','(SELECT `GroupID` FROM `Users` WHERE `DomainsOrdersOwners`.`UserID` = `Users`.`ID`) as `GroupID`','(SELECT `IsPayed` FROM `Orders` WHERE `Orders`.`ID` = `DomainsOrdersOwners`.`OrderID`) as `IsPayed`','(SELECT `Balance` FROM `Contracts` WHERE `DomainsOrdersOwners`.`ContractID` = `Contracts`.`ID`) as `ContractBalance`','(SELECT `Name` FROM `DomainsSchemes` WHERE `DomainsSchemes`.`ID` = `SchemeID`) as `SchemeName`');
#-------------------------------------------------------------------------------
$Where = ($DomainOrderID?SPrintF('`ID` = %u',$DomainOrderID):SPrintF('`OrderID` = %u',$OrderID));
#-------------------------------------------------------------------------------
$DomainOrder = DB_Select('DomainsOrdersOwners',$Columns,Array('UNIQ','Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($DomainOrder)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    #---------------------------------------------------------------------------
    $UserID = (integer)$DomainOrder['UserID'];
    #---------------------------------------------------------------------------
    $IsPermission = Permission_Check('DomainsOrdersRead',(integer)$GLOBALS['__USER']['ID'],$UserID);
    #---------------------------------------------------------------------------
    switch(ValueOf($IsPermission)){
      case 'error':
        return ERROR | @Trigger_Error(500);
      case 'exception':
        return ERROR | @Trigger_Error(400);
      case 'false':
        return ERROR | @Trigger_Error(700);
      case 'true':
        #-----------------------------------------------------------------------
        $DOM = new DOM();
        #-----------------------------------------------------------------------
        $Links = &Links();
        # Коллекция ссылок
        $Links['DOM'] = &$DOM;
        #-----------------------------------------------------------------------
        if(Is_Error($DOM->Load('Window')))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
        $DOM->AddText('Title',SPrintF('Оплата заказа домена %s.%s',$DomainOrder['DomainName'],$DomainOrder['SchemeName']));
        #-----------------------------------------------------------------------
        $DOM->AddChild('Head',new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/DomainOrderPay.js}')));
        #-----------------------------------------------------------------------
        $StatusID = $DomainOrder['StatusID'];
        #-----------------------------------------------------------------------
        if(!In_Array($StatusID,Array('Waiting','Active','Suspended')))
          return new gException('ORDER_CAN_NOT_PAY','Заказ домена не может быть оплачен');
        #-----------------------------------------------------------------------
        $__USER = $GLOBALS['__USER'];
        #-----------------------------------------------------------------------
        $DomainScheme = DB_Select('DomainsSchemes','*',Array('UNIQ','ID'=>$DomainOrder['SchemeID']));
        #-----------------------------------------------------------------------
        switch(ValueOf($DomainScheme)){
          case 'error':
            return ERROR | @Trigger_Error(500);
          case 'exception':
            return ERROR | @Trigger_Error(400);
          case 'array':
            #-------------------------------------------------------------------
            $Table = Array();
            #-------------------------------------------------------------------
            $Comp = Comp_Load('Formats/Currency',$DomainScheme['CostOrder']);
            if(Is_Error($Comp))
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
            $Table[] = Array('Стоимость заказа (в год)',$Comp);
            #-------------------------------------------------------------------
            $Comp = Comp_Load('Formats/Currency',$DomainScheme['CostProlong']);
            if(Is_Error($Comp))
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
            $Table[] = Array('Стоимость продления (в год)',$Comp);
            #-------------------------------------------------------------------
            $Comp = Comp_Load(
              'Form/Input',
              Array(
                'name'  => 'DomainOrderID',
                'type'  => 'hidden',
                'value' => $DomainOrder['ID']
              )
            );
            if(Is_Error($Comp))
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
            $ExpirationDate = $DomainOrder['ExpirationDate'];
            #-------------------------------------------------------------------
            $Form = new Tag('FORM',Array('name'=>'DomainOrderPayForm','onsubmit'=>'return false;'),$Comp);
            #-------------------------------------------------------------------
            if($YearsPay){
              #-----------------------------------------------------------------
              $Comp = Comp_Load(
                'Form/Input',
                Array(
                  'name'  => 'YearsPay',
                  'type'  => 'hidden',
                  'value' => $YearsPay
                )
              );
              if(Is_Error($Comp))
                return ERROR | @Trigger_Error(500);
              #-----------------------------------------------------------------
              $Form->AddChild($Comp);
              #-----------------------------------------------------------------
              $IsPayed = $DomainOrder['IsPayed'];
              #-----------------------------------------------------------------
              if($IsPayed){
                #---------------------------------------------------------------
                if(!$DomainScheme['IsProlong'])
                  return new gException('SCHEME_NOT_ALLOW_PROLONG','Тарифный план заказа домена не позволяет продление');
                #---------------------------------------------------------------
                $YearsRemainder = Date('Y',$ExpirationDate) - Date('Y') - 1;
                #---------------------------------------------------------------
                if($YearsRemainder >= $DomainScheme['MaxActionYears'])
                  return new gException('DOMAIN_ORDER_ON_MAX_YEARS_1','Доменное имя уже зарегистрировано на максимальное кол-во лет');
              }else{
                #---------------------------------------------------------------
                if($YearsPay < $DomainScheme['MinOrderYears'])
                  return new gException('YEARS_PAY_MIN_ORDER_YEARS','Кол-во лет оплаты меньше, чем допустимое значение лет заказа, определённое в тарифном плане');
                #---------------------------------------------------------------
                if($YearsPay > $DomainScheme['MaxActionYears'])
                  return new gException('YEARS_PAY_MAX_ACTION_YEARS','Кол-во лет оплаты больше, чем допустимое значение, опредлёенное в тарифном плане');
              }
              #-----------------------------------------------------------------
              $DomainsBonuses = Array();
              #-----------------------------------------------------------------
              if(Is_Error(DB_Transaction($TransactionID = UniqID('DomainOrderPay'))))
                return ERROR | @Trigger_Error(500);
              #-----------------------------------------------------------------
              $Columns = Array('(SELECT `SchemeID` FROM `HostingOrders` WHERE `HostingOrders`.`OrderID` = `Basket`.`OrderID`) as `SchemeID`','Amount');
              #-----------------------------------------------------------------
              $Basket = DB_Select('Basket',$Columns,Array('Where'=>SPrintF('(SELECT `ServiceID` FROM `Orders` WHERE `Orders`.`ID` = `OrderID`) = 10000 AND (SELECT `ContractID` FROM `Orders` WHERE `Orders`.`ID` = `OrderID`) = %u',$DomainOrder['ContractID'])));
              #-----------------------------------------------------------------
              switch(ValueOf($Basket)){
                case 'error':
                  return ERROR | @Trigger_Error(500);
                case 'exception':
                  # No more...
                break;
                case 'array':
                  #-------------------------------------------------------------
                  $Entrance = Tree_Path('Groups',(integer)$DomainOrder['GroupID']);
                  #-------------------------------------------------------------
                  switch(ValueOf($Entrance)){
                    case 'error':
                      return ERROR | @Trigger_Error(500);
                    case 'exception':
                      return ERROR | @Trigger_Error(400);
                    case 'array':
                      #---------------------------------------------------------
                      foreach($Basket as $Order){
                        #-------------------------------------------------------
                        $HostingDomainPolitic = DB_Select('HostingDomainsPolitics','*',Array('SortOn'=>'Discont','Where'=>SPrintF('(`GroupID` IN (%s) OR `UserID` = %u) AND (`SchemeID` = %u OR `SchemeID` IS NULL) AND `DaysPay` <= %u AND EXISTS(SELECT * FROM `DomainsSchemesGroupsItems` WHERE `DomainsSchemesGroupsItems`.`DomainsSchemesGroupID` = `DomainsSchemesGroupID` AND `SchemeID` = %u)',Implode(',',$Entrance),$DomainOrder['UserID'],$Order['SchemeID'],$Order['Amount'],$DomainOrder['SchemeID'])));
                        #-------------------------------------------------------
                        switch(ValueOf($HostingDomainPolitic)){
                          case 'error':
                            return ERROR | @Trigger_Error(500);
                          case 'exception':
                            # No more...
                          break;
                          case 'array':
                            #---------------------------------------------------
                            $HostingDomainPolitic = Current($HostingDomainPolitic);
                            #---------------------------------------------------
                            $IDomainBonus = Array(
                              #-------------------------------------------------
                              'UserID'        => $DomainOrder['UserID'],
                              'SchemeID'      => $DomainOrder['SchemeID'],
                              'YearsReserved' => 1,
                              'OperationID'   => 'Order',
                              'Discont'       => $HostingDomainPolitic['Discont'],
                              'Comment'       => 'Назначен доменной политикой'
                            );
                            #---------------------------------------------------
                            $IsInsert = DB_Insert('DomainsBonuses',$IDomainBonus);
                            if(Is_Error($IsInsert))
                              return ERROR | @Trigger_Error(500);
                          break;
                          default:
                            return ERROR | @Trigger_Error(101);
                        }
                      }
                      #---------------------------------------------------------
                    break 2;
                    default:
                      return ERROR | @Trigger_Error(101);
                  }
                default:
                  return ERROR | @Trigger_Error(101);
              }
              #-----------------------------------------------------------------
              $CostPay = 0.00;
              #-----------------------------------------------------------------
              $YearsRemainded = $YearsPay;
              #-----------------------------------------------------------------
              while($YearsRemainded){
                #---------------------------------------------------------------
                $CurrentCost = $DomainScheme[(!$IsPayed && $YearsPay - $YearsRemainded < $DomainScheme['MinOrderYears']?'CostOrder':'CostProlong')];
                #---------------------------------------------------------------
                $Where = SPrintF("`UserID` = %u AND ((`SchemeID` = %u OR %u IN (SELECT `SchemeID` FROM `DomainsSchemesGroupsItems` WHERE `DomainsSchemesGroupsItems`.`DomainsSchemesGroupID` = `DomainsBonuses`.`DomainsSchemesGroupID`)) OR ISNULL(`SchemeID`) AND ISNULL(`DomainsSchemesGroupID`)) AND `YearsRemainded` > 0",$UserID,$DomainScheme['ID'],$DomainScheme['ID']);
                #---------------------------------------------------------------
                $DomainBonus = DB_Select('DomainsBonuses','*',Array('IsDesc'=>TRUE,'SortOn'=>'Discont','Where'=>$Where));
                #---------------------------------------------------------------
                switch(ValueOf($DomainBonus)){
                  case 'error':
                    return ERROR | @Trigger_Error(500);
                  case 'exception':
                    #-----------------------------------------------------------
                    $CostPay += $YearsRemainded*$CurrentCost;
                    #-----------------------------------------------------------
                    $YearsRemainded = 0;
                  break;
                  case 'array':
                    #-----------------------------------------------------------
                    $DomainBonus = Current($DomainBonus);
                    #-----------------------------------------------------------
                    $Discont = (1 - $DomainBonus['Discont']);
                    #-----------------------------------------------------------
                    if($DomainBonus['YearsRemainded'] - $YearsRemainded < 0){
                      #---------------------------------------------------------
                      $CostPay += $DomainBonus['YearsRemainded']*$CurrentCost*$Discont;
                      #---------------------------------------------------------
                      $UDomainBonus = Array('YearsRemainded'=>0);
                      #---------------------------------------------------------
                      $YearsRemainded -= $DomainBonus['YearsRemainded'];
                      #---------------------------------------------------------
                      $Comp = Comp_Load('Formats/Percent',$DomainBonus['Discont']);
                      if(Is_Error($Comp))
                        return ERROR | @Trigger_Error(500);
                      #---------------------------------------------------------
                      $Tr = new Tag('TR');
                      #---------------------------------------------------------
                      foreach(Array($DomainBonus['YearsRemainded'],$Comp) as $Text)
                        $Tr->AddChild(new Tag('TD',Array('class'=>'Standard','align'=>'right'),$Text));
                      #---------------------------------------------------------
                      $DomainsBonuses[] = $Tr;
                    }else{
                      #---------------------------------------------------------
                      $CostPay += $YearsRemainded*$CurrentCost*$Discont;
                      #---------------------------------------------------------
                      $UDomainBonus = Array('YearsRemainded'=>$DomainBonus['YearsRemainded'] - $YearsRemainded);
                      #---------------------------------------------------------
                      $Comp = Comp_Load('Formats/Percent',$DomainBonus['Discont']);
                      if(Is_Error($Comp))
                        return ERROR | @Trigger_Error(500);
                      #---------------------------------------------------------
                      $Tr = new Tag('TR');
                      #---------------------------------------------------------
                      foreach(Array($YearsRemainded,$Comp) as $Text)
                        $Tr->AddChild(new Tag('TD',Array('class'=>'Standard','align'=>'right'),$Text));
                      #---------------------------------------------------------
                      $DomainsBonuses[] = $Tr;
                      #---------------------------------------------------------
                      $YearsRemainded = 0;
                    }
                    #-----------------------------------------------------------
                    $IsUpdate = DB_Update('DomainsBonuses',$UDomainBonus,Array('ID'=>$DomainBonus['ID']));
                    if(Is_Error($IsUpdate))
                      return ERROR | @Trigger_Error(500);
                  break;
                  default:
                    return ERROR | @Trigger_Error(101);
                }
              }
              #-----------------------------------------------------------------
              if(Is_Error(DB_Roll($TransactionID)))
                return ERROR | @Trigger_Error(500);
              #-----------------------------------------------------------------
              $CostPay = Round($CostPay,2);
              #-----------------------------------------------------------------
              $Table[] = Array('Кол-во лет',$YearsPay);
              #-----------------------------------------------------------------
              if(Count($DomainsBonuses)){
                #---------------------------------------------------------------
                $Tr = new Tag('TR');
                #---------------------------------------------------------------
                foreach(Array('Лет','Скидка') as $Text)
                  $Tr->AddChild(new Tag('TD',Array('class'=>'Head'),$Text));
                #---------------------------------------------------------------
                Array_UnShift($DomainsBonuses,$Tr);
                #---------------------------------------------------------------
                $Comp = Comp_Load('Tables/Extended',$DomainsBonuses,'Бонусы',Array('style'=>'100%'));
                if(Is_Error($Comp))
                  return ERROR | @Trigger_Error(500);
                #---------------------------------------------------------------
                $Table[] = new Tag('DIV',Array('align'=>'center'),$Comp);
              }
              #-----------------------------------------------------------------
              $Comp = Comp_Load('Formats/Currency',$CostPay);
              if(Is_Error($Comp))
                return ERROR | @Trigger_Error(500);
              #-----------------------------------------------------------------
              $Table[] = Array('Всего к оплате',$Comp);
              #-----------------------------------------------------------------
              $Div = new Tag('DIV',Array('align'=>'right','class'=>'Standard'));
#-------------------------------------------------------------------------------
$Parse = <<<EOD
<NOBODY>
 <SPAN>C </SPAN>
 <A href="/Clause?ClauseID=Contracts/Enclosures/Types/DomainRules/Content" target="blank">условиями</A>
 <SPAN> оказания услуг ознакомлен</SPAN>
</NOBODY>
EOD;
#-------------------------------------------------------------------------------
              $Div->AddHTML($Parse);
              #-----------------------------------------------------------------
              $Table[] = $Div;
              #-----------------------------------------------------------------
              $Table[] = new Tag('DIV',Array('align'=>'right','style'=>'font-size:10px;'),$CostPay > $DomainOrder['ContractBalance']?'[заказ будет добавлен в корзину]':'[заказ будет оплачен с баланса договора]');
              #-----------------------------------------------------------------
              $Div = new Tag('DIV',Array('align'=>'right'));
              #-----------------------------------------------------------------
              if($IsChange){
                #---------------------------------------------------------------
                $Comp = Comp_Load(
                  'Form/Input',
                  Array(
                    'type'    => 'button',
                    'onclick' => 'WindowPrev();',
                    'value'   => 'Изменить период'
                  )
                );
                if(Is_Error($Comp))
                  return ERROR | @Trigger_Error(500);
                #---------------------------------------------------------------
                $Div->AddChild($Comp);
              }
              #-----------------------------------------------------------------
              $Comp = Comp_Load(
                'Form/Input',
                Array(
                  'type'    => 'button',
                  'onclick' => 'DomainOrderPay();',
                  'value'   => 'Продолжить'
                )
              );
              if(Is_Error($Comp))
                return ERROR | @Trigger_Error(500);
              #-----------------------------------------------------------------
              $Div->AddChild($Comp);
              #-----------------------------------------------------------------
              $Table[] = $Div;
            }else{
              #-----------------------------------------------------------------
              $Table = Array();
              #-----------------------------------------------------------------
              if($DomainOrder['IsPayed']){
                #---------------------------------------------------------------
                if(!$DomainScheme['IsProlong'])
                  return new gException('SCHEME_NOT_ALLOW_PROLONG','Тарифный план заказа домена не позволяет продление');
                #---------------------------------------------------------------
                $DaysToProlong = $DomainScheme['DaysToProlong'];
                #---------------------------------------------------------------
                if(($ExpirationDate - Time())/86400 > $DaysToProlong)
                  return new gException('PROLONG_IS_EARLY',SPrintF('Заказ домена может быть продлен только за %u дн. до окончания',$DaysToProlong));
                #---------------------------------------------------------------
                $Options = Array();
                #---------------------------------------------------------------
		if(($ExpirationDate - Time())/86400 > $DaysToProlong){
		  $YearsRemainder = Date('Y',$ExpirationDate) - Date('Y');
		}else{
		  $YearsRemainder = 0;
		}
                #---------------------------------------------------------------
                if($YearsRemainder >= $DomainScheme['MaxActionYears'])
                  return new gException('DOMAIN_ORDER_ON_MAX_YEARS_2','Доменное имя уже зарегистрировано на максимальное кол-во лет');
                #---------------------------------------------------------------
                for($Years=1;$Years<=$DomainScheme['MaxActionYears'] - $YearsRemainder;$Years++)
                  $Options[$Years] = $Years;
              }else{
                #---------------------------------------------------------------
                $Options = Array();
                #---------------------------------------------------------------
                for($Years=$DomainScheme['MinOrderYears'];$Years<=$DomainScheme['MaxActionYears'];$Years++)
                  $Options[$Years] = $Years;
              }
              #-----------------------------------------------------------------
              if(Count($Options) < 2){
                #---------------------------------------------------------------
                $Comp = Comp_Load('www/DomainOrderPay',Array('DomainOrderID'=>$DomainOrder['ID'],'YearsPay'=>Current($Options)));
                if(Is_Error($Comp))
                  return ERROR | @Trigger_Error(500);
                #---------------------------------------------------------------
                return $Comp;
              }
              #-----------------------------------------------------------------
              $Comp = Comp_Load('Form/Select',Array('name'=>'YearsPay'),$Options);
              if(Is_Error($Comp))
                return ERROR | @Trigger_Error(500);
              #-----------------------------------------------------------------
              $Table[] = Array('Кол-во лет',$Comp);
              #-----------------------------------------------------------------
              $Comp = Comp_Load(
                'Form/Input',
                Array(
                  'type'    => 'button',
                  'onclick' => "ShowWindow('/DomainOrderPay',FormGet(form));",
                  'value'   => 'Продолжить'
                )
              );
              if(Is_Error($Comp))
                return ERROR | @Trigger_Error(500);
              #-----------------------------------------------------------------
              $Table[] = $Comp;
            }
            #-------------------------------------------------------------------
            $Comp = Comp_Load('Tables/Standard',$Table);
            if(Is_Error($Comp))
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
            $Form->AddChild($Comp);
            #-------------------------------------------------------------------
            $Comp = Comp_Load(
              'Form/Input',
              Array(
                'type'  => 'hidden',
                'name'  => 'IsChange',
                'value' => 'true'
              )
            );
            if(Is_Error($Comp))
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
            $Form->AddChild($Comp);
            #-------------------------------------------------------------------
            $DOM->AddChild('Into',$Form);
            #-------------------------------------------------------------------
            if(Is_Error($DOM->Build(FALSE)))
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
            return Array('Status'=>'Ok','DOM'=>$DOM->Object);
          default:
            return ERROR | @Trigger_Error(101);
        }
      default:
        return ERROR | @Trigger_Error(101);
    }
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
