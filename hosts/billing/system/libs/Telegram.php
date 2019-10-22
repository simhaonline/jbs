<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/

// отправка сообщения
function TgSendMessage($Settings,$ChatID,$Text = 'not set',$IsReply = FALSE){
	#-------------------------------------------------------------------------------
	// внутри всех функций прописываем подёргивание WEB-hook'a. ответ не интересен...
	TgRegWebHook($Settings);
	#-------------------------------------------------------------------------------
	$HTTP = TgBuild_HTTP($Settings);
	#-------------------------------------------------------------------------------
	$Query = Array('chat_id'=>$ChatID,'text'=>$Text,'disable_web_page_preview'=>'TRUE','parse_mode'=>'HTML');
	#-------------------------------------------------------------------------------
	// если надо показать меню что возможен ответ на сообщение
	if($IsReply)
		$Query['reply_markup'] = Json_Encode(Array('force_reply'=>TRUE));
	#-------------------------------------------------------------------------------
	$Result = HTTP_Send(SPrintF('/bot%s/sendMessage',$Settings['Params']['Token']),$HTTP,Array(),$Query);
	if(Is_Error($Result))
		return ERROR | @Trigger_Error('[TgSendMessage]: не удалось выполнить запрос к серверу');
	#-------------------------------------------------------------------------------
        $Result = Trim($Result['Body']);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Result = Json_Decode($Result,TRUE);
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[system/libs/Telegram]: $Result = %s',print_r($Result,true)));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(IsSet($Result['ok']) && $Result['ok']){
		#-------------------------------------------------------------------------------
		// возвращаем внутренний идентфикатор сообщения в телеграмме
		return Array($Result['result']['message_id']);
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[system/libs/Telegram]: $Result = %s',print_r($Result,true)));
		#-------------------------------------------------------------------------------
		// TODO по идее там есть человекочитемое сообщение о ошибке. надо словить и выдать в ответе
		if(IsSet($Result['error_code']) && $Result['error_code'] == 400){
			#-------------------------------------------------------------------------------
			// ругается на cущности. когда мусор типа <http://ya.ru/> воспринимается как тег
			return TRUE;
			#-------------------------------------------------------------------------------
		}elseif(IsSet($Result['error_code']) && $Result['error_code'] == 403){
			#-------------------------------------------------------------------------------
			// юзер залочил бота. по уму, надо бы куда-то деть ChatID или сразу выпилить из оповещений его
			return TRUE;
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			return FALSE;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}


#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// отправка файла
function TgSendFile($Settings,$ChatID,$Attachments = Array(),$IsReply = FALSE){
	#-------------------------------------------------------------------------------
	// внутри всех функций прописываем подёргивание WEB-hook'a. ответ не интересен...
	TgRegWebHook($Settings);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Boundary = SPrintF('------------------------%s',Md5(Rand()));
	#-------------------------------------------------------------------------------
	$Headers = Array(SPrintF('Content-Type: multipart/form-data; boundary=%s',$Boundary)/*,'Connection: keep-alive','Keep-Alive: 300'*/);
	#-------------------------------------------------------------------------------
	$HTTP = TgBuild_HTTP($Settings);
	$HTTP['Charset'] = '';
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[TgSendFile]: Attachments = %s',print_r($Attachments,true)));
	#-------------------------------------------------------------------------------
	// массив под идентифкаторы отправленных сообщений
	$Array = Array();
	#-------------------------------------------------------------------------------
	foreach ($Attachments as $Attachment){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[system/libs/Telegram]: обработка вложения (%s), размер (%s), тип (%s)',$Attachment['Name'],$Attachment['Size'],$Attachment['Mime']));
		#-------------------------------------------------------------------------------
		$Body = SPrintF("--%s\r\n",$Boundary);
		$Body = SPrintF("%sContent-Disposition: form-data; name=\"document\"; filename=\"%s\"\r\n",$Body,$Attachment['Name']);
		$Body = SPrintF("%sContent-Type: %s\r\n",$Body,$Attachment['Mime']);
		$Body = SPrintF("%s\r\n%s",$Body,base64_decode($Attachment['Data']));
		$Body = SPrintF("%s\r\n%s",$Body,$Attachment['Data']);
		$Body = SPrintF("%s\r\n--%s--\r\n\r\n",$Body,$Boundary);
		#-------------------------------------------------------------------------------
		$Query = Array('chat_id'=>$ChatID);
		#-------------------------------------------------------------------------------
		// если надо показать меню что возможен ответ на сообщение
		if($IsReply)
			$Query['reply_markup'] = Json_Encode(Array('force_reply'=>TRUE));
		#-------------------------------------------------------------------------------
		$Result = HTTP_Send(SPrintF('/bot%s/sendDocument',$Settings['Params']['Token']),$HTTP,$Query,$Body,$Headers);
		if(Is_Error($Result))
			return ERROR | @Trigger_Error('[TgSendFile]: не удалось выполнить запрос к серверу');
		#-------------------------------------------------------------------------------
        	$Result = Trim($Result['Body']);
		#-------------------------------------------------------------------------------
		$Result = Json_Decode($Result,TRUE);
		#-------------------------------------------------------------------------------
		if(IsSet($Result['ok']) && $Result['ok']){
			#-------------------------------------------------------------------------------
			$Array[] = $Result['result']['message_id'];
			#-------------------------------------------------------------------------------
			continue;
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[system/libs/Telegram]: $Result = %s',print_r($Result,true)));
			#-------------------------------------------------------------------------------
			// TODO по идее там есть человекочитемое сообщение о ошибке. надо словить и выдать в ответе
			return FALSE;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return $Array;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// скачиваем файл во временную директорию, отдаём его данные
function TgGetFile($Settings,$FileID){
	#-------------------------------------------------------------------------------
	// внутри всех функций прописываем подёргивание WEB-hook'a. ответ не интересен...
	TgRegWebHook($Settings);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$HTTP = TgBuild_HTTP($Settings);
	#-------------------------------------------------------------------------------
	$Query = Array('file_id'=>$FileID);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Result = HTTP_Send(SPrintF('/bot%s/getFile',$Settings['Params']['Token']),$HTTP,Array(),$Query);
	if(Is_Error($Result))
		return ERROR | @Trigger_Error('[TgSendMessage]: не удалось выполнить запрос к серверу');
	#-------------------------------------------------------------------------------
        $Result = Trim($Result['Body']);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Result = Json_Decode($Result,TRUE);
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[system/libs/Telegram]: $Result = %s',print_r($Result,true)));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(IsSet($Result['ok']) && $Result['ok']){
		#-------------------------------------------------------------------------------
		$Tmp = System_Element('tmp');
		if(Is_Error($Tmp))
			return ERROR | @Trigger_Error('[system/libs/Telegram]: не удалось найти временную папку');
		#-------------------------------------------------------------------------------
		// скачиваем файл во временную директорию
		$Context= Stream_Context_Create(Array('http'=>Array('timeout'=>2)));
		#-------------------------------------------------------------------------------
		$File	= @File_Get_Contents(SPrintF('https://api.telegram.org/file/bot%s/%s',$Settings['Params']['Token'],$Result['result']['file_path']),FALSE,$Context);
		#-------------------------------------------------------------------------------
		$FilePath = SPrintF('%s/files/%s',$Tmp,$Result['result']['file_id']);
		#-------------------------------------------------------------------------------
		$IsWrited = IO_Write($FilePath,$File,TRUE);
		if(Is_Error($IsWrited))
			return ERROR | @Trigger_Error('[system/libs/Telegram->TgGetFile]: не удалось сохранить файл');
		#-------------------------------------------------------------------------------
		if(FileSize($FilePath)){
			#-------------------------------------------------------------------------------
			return Array('size'=>FileSize($FilePath),'error'=>0,'tmp_name'=>$FilePath,'name'=>BaseName($Result['result']['file_path']));
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			return FALSE;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		// TODO по идее там есть человекочитемое сообщение о ошибке. надо словить и выдать в ответе
		return FALSE;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}


// внутренние функции
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
function TgBuild_HTTP($Settings){
	/******************************************************************************/
	$__args_types = Array('array');
	$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
	/******************************************************************************/
	$HTTP = Array(
			'Address'	=> $Settings['Address'],
			'Port'		=> 443,
			'Host'		=> $Settings['Address'],
			'Protocol'	=> 'ssl',
			);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return $HTTP;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// регистрируем WEB-hook для бота
function TgRegWebHook($Settings){
	#-------------------------------------------------------------------------------
	// проверяем, не прописывался ли веб-хук ранее
	$CacheID = Md5($Settings['Params']['Token']);
	#-------------------------------------------------------------------------------
	$Result = CacheManager::get($CacheID);
	if($Result){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[TgRegWebHook]: WebHook last register time = %s',Date('Y-m-d/H:i:s',$Result)));
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$HTTP = TgBuild_HTTP($Settings);
	#-------------------------------------------------------------------------------
	$Query = Array('url'=>SPrintF('https://%s/API/Telegram?Secret=%s',HOST_ID,$Settings['Params']['Secret']));
	#-------------------------------------------------------------------------------
	$Result = HTTP_Send(SPrintF('/bot%s/setWebhook',$Settings['Params']['Token']),$HTTP,Array(),$Query);
	if(Is_Error($Result))
		return ERROR | @Trigger_Error('[TgRegWebHook]: не удалось выполнить запрос к серверу');
	#-------------------------------------------------------------------------------
        $Result = Trim($Result['Body']);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Result = Json_Decode($Result,TRUE);
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[system/libs/Telegram]: $Result = %s',print_r($Result,true)));
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(IsSet($Result['ok']) && $Result['ok']){
		#-------------------------------------------------------------------------------
		CacheManager::add($CacheID, Time(), 30 * 24 * 3600);
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		// TODO по идее там есть человекочитемое сообщение о ошибке. надо словить и выдать в ответе
		Debug(SPrintF('[TgRegWebHook]: $Result = %s',print_r($Result,true)));
		#-------------------------------------------------------------------------------
		return FALSE;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// сохраняем MessageID, просто в файлик, нефига ради такого колонку в базе держать. пока, по крайней мере
function TgSaveThreadID($UserID,$TicketID,$MessageID,$TgMessageID){
	#-------------------------------------------------------------------------------
	$FileDB = TgCreateThreadDB();
	#-------------------------------------------------------------------------------
	# сохраняем переданные данные
	$IsWrite = IO_Write($FileDB,SPrintF("%s\t%s\t%s\t\t%s\n",$UserID,$TicketID,$MessageID,$TgMessageID));
	if(Is_Error($IsWrite))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// поиск тикета по номеру сообщения в телеграмме
function TgFindThreadID($TgMessageID){
	#-------------------------------------------------------------------------------
	$FileDB = TgCreateThreadDB();
	#-------------------------------------------------------------------------------
	// читаем файл
	$IsRead = IO_Read($FileDB);
	if(Is_Error($IsRead))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Lines = Explode("\n", Trim($IsRead));
	#-------------------------------------------------------------------------------
	foreach($Lines as $Line){
		#-------------------------------------------------------------------------------
		List($UserID,$TicketID,$MessageID,$TgMessageIDtmp) = Preg_Split("/[\s]+/",$Line);
		#-------------------------------------------------------------------------------
		if($TgMessageIDtmp == $TgMessageID)
			return Array('UserID'=>$UserID,'TicketID'=>$TicketID,'MessageID'=>$MessageID);
                #-------------------------------------------------------------------------------
        }
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return FALSE;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// создаём файл с базой соответствия сообщения тикетницы сообщениям телеграмма
function TgCreateThreadDB(){
	#-------------------------------------------------------------------------------
	$Tmp = System_Element('tmp');
	if(Is_Error($Tmp))
		return ERROR | @Trigger_Error('[SberBank_Get_Tmp]: не удалось найти временную папку');
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$FileDB = SPrintF('%s/Telegram.txt',$Tmp);
	#-------------------------------------------------------------------------------
	if(!File_Exists($FileDB)){
		#-------------------------------------------------------------------------------
		$IsWrite = IO_Write($FileDB,"#UserID\tTicketID\tMessageID\tTgMessageID\n");
		if(Is_Error($IsWrite))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	return $FileDB;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}



?>
