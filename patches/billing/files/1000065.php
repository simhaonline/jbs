<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$ConfigPath = SPrintF('%s/hosts/%s/config/Config.xml',SYSTEM_PATH,HOST_ID);
#-------------------------------------------------------------------------------
if(File_Exists($ConfigPath)){
	#-------------------------------------------------------------------------------
	$File = IO_Read($ConfigPath);
	if(Is_Error($File))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($File);
	if(Is_Exception($XML))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Config = $XML->ToArray();
	#-------------------------------------------------------------------------------
	$Config = $Config['XML'];
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Config = Array();
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(IsSet($Config['Tasks']['Types']['GC'])){
	#-------------------------------------------------------------------------------
	$GC = $Config['Tasks']['Types']['GC'];
	#-------------------------------------------------------------------------------
	$Items = Array('IsActive','Name');
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($GC) as $Item){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[patches/billing/files/1000065.php]: Item = %s',$Item));
		#-------------------------------------------------------------------------------
		if(Is_Array($GC[$Item]))
			continue;
		#-------------------------------------------------------------------------------
		if(!In_Array($Item,$Items)){
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[patches/billing/files/1000065.php]: found unused Item = %s',$Item));
			#-------------------------------------------------------------------------------
			UnSet($GC[$Item]);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Config['Tasks']['Types']['GC'] = $GC;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$File = IO_Write($ConfigPath,To_XML_String($Config),TRUE);
if(Is_Error($File))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$IsFlush = CacheManager::flush();
if(!$IsFlush)
	@Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
