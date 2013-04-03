<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Params');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(!IsSet($Params['DefaultTime']))
	$Params['DefaultTime'] = 3600;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(IsSet($Params['ExecutePeriod']) && $Params['ExecutePeriod']){
	#-------------------------------------------------------------------------------
	# работаем с периодом
	$Time = Explode(":",$Params['ExecutePeriod']);
	#-------------------------------------------------------------------------------
	if(IsSet($Time[1])){
		#-------------------------------------------------------------------------------
		if((integer)$Time[0] >= 0 && (integer)$Time[0] <= 23 && (integer)$Time[1] >= 0 && (integer)$Time[1] <= 59){
			#-------------------------------------------------------------------------------
			if((integer)$Time[0] > 0)
				return (integer)$Time[0] * 3600 + (integer)$Time[1] * 60;
			#-------------------------------------------------------------------------------
			return (integer)$Time[1] * 60;
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			return $Params['DefaultTime'];
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		return $Params['DefaultTime'];
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	# работаем с абсолютным временем
	$Time = Explode(":",$Params['ExecuteTime']);
	#-------------------------------------------------------------------------------
	if(IsSet($Time[1])){
		#-------------------------------------------------------------------------------
		# если задана периодичность в днях - добавляем период, дней
		$ExecuteDay = Date('j') + (IsSet($Params['ExecuteDays'])?IntVal($Params['ExecuteDays']):0);
		#-------------------------------------------------------------------------------
		# если задан точный день месяца - устанавливаем его
		$ExecuteDay = IsSet($Params['ExecuteDayOfMonth'])?IntVal($Params['ExecuteDayOfMonth']):$ExecuteDay;
		#-------------------------------------------------------------------------------
		# если задана периодичнось в месяцах, добавляем период, месяцев
		$ExecuteMonth = Date('n') + (IsSet($Params['ExecuteMonths'])?IntVal($Params['ExecuteMonths']):0);
		#-------------------------------------------------------------------------------
		# если задан точный месяц - устанавливаем его
		$ExecuteMonth = IsSet($Params['ExecuteMonth'])?IntVal($Params['ExecuteMonth']):$ExecuteMonth;
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		if((integer)$Time[0] >= 0 && (integer)$Time[0] <= 23 && (integer)$Time[1] >= 0 && (integer)$Time[1] <= 59){
			#-------------------------------------------------------------------------------
			return MkTime((integer)$Time[0],(integer)$Time[1],0,$ExecuteMonth,$ExecuteDay,Date('Y'));
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			return $Params['DefaultTime'];
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		return $Params['DefaultTime'];
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------

?>
