<?php
#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
#-------------------------------------------------------------------------------
class MySQL{
	#-------------------------------------------------------------------------------
	# Ссыклка на соединения
	private $Link = NULL;
	# Последний выполненный запрос
	private $Query = "";
	/*------------------------------------------------------------------------------
	      Задача:
	Создать объект MySQL.
	------------------------------------------------------------------------------*/
	function __construct($Settings = Array()){
		#-------------------------------------------------------------------------------
		$this->Settings = Array(
					'Server'	=> 'localhost',
					'Port'		=> 3306,
					'User'		=> 'root',
					'Password'	=> '*',
					'DbName'	=> 'UnSelected'
					);
		#-------------------------------------------------------------------------------
		Array_Union($this->Settings,$Settings);
		#-------------------------------------------------------------------------------
	}
	
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	public function GetLink() {
		#-------------------------------------------------------------------------------
		return $this->Link;
		#-------------------------------------------------------------------------------
	}
	
	#-------------------------------------------------------------------------------
	/*------------------------------------------------------------------------------
	      Задача:
	Открыть соединение с базой данных.
	------------------------------------------------------------------------------*/
	public function Open(){
		#-------------------------------------------------------------------------------
		$Settings = $this->Settings;
		#-------------------------------------------------------------------------------
		$Address = SPrintF('%s:%u',$Settings['Server'],$Settings['Port']);
		#-------------------------------------------------------------------------------
		$this->Link = @MySQL_Connect($Address,$User = $Settings['User'],$Settings['Password'],TRUE);
		#-------------------------------------------------------------------------------
		if(!Is_Resource($this->Link))
			return ERROR | @Trigger_Error(SPrintF('[MySQL->Open]: не возможно соединиться с (%s@%s)',$User,$Address));
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[MySQL->Open]: связь как (%s@%s) установлена',$User,$Address));
		#-------------------------------------------------------------------------------
		$Init = System_XML('config/MySqlInit.xml');
		#-------------------------------------------------------------------------------
		if(Is_Error($Init))
			return ERROR | @Trigger_Error('[MySQL->Open]: не возможно загрузить запросы инициализации');
		#-------------------------------------------------------------------------------
		foreach($Init as $Query)
			if(Is_Error($this->Query($Query)))
				return ERROR | @Trigger_Error('[MySQL->Open]: не удалось произвести инициализацию подключения');
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
	}
	
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	/*------------------------------------------------------------------------------
	      Задача:
	Закрыть соединение с базой данных.
	------------------------------------------------------------------------------*/
	public function Close(){
		#-----------------------------------------------------------------------------
		if(!Is_Resource($this->Link))
			return ERROR | @Trigger_Error('[MySQL->Close]: нет соединения с MySQL');
		#-------------------------------------------------------------------------------
		$IsClose = Mysql_Close($this->Link);
		if(!$IsClose)
			return ERROR | @Trigger_Error('[MySQL->Close]: ошибка закрытия соединения с MySQL');
		#-------------------------------------------------------------------------------
		Debug('[MySQL->Close]: закрываем соединение с MySQL');
		#-------------------------------------------------------------------------------
		$this->Link = NULL;
		#-------------------------------------------------------------------------------
	}
	
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	/*------------------------------------------------------------------------------
		Задача:
	Выбрать базу данных.
	------------------------------------------------------------------------------*/
	public function SelectDB(){
		/******************************************************************************/
		$__args_types = Array('string');
		#-------------------------------------------------------------------------------
		$__args__ = Func_Get_Args(); Eval(FUNCTION_INIT);
		/******************************************************************************/
		if(!Is_Resource($this->Link))
			return ERROR | @Trigger_Error('[MySQL->SelectDB]: нет соединения с MySQL');
		#-------------------------------------------------------------------------------
		$DbName = $this->Settings['DbName'];
		#-------------------------------------------------------------------------------
		$SqlResult = $this->Query(SPrintF('USE `%s`',$DbName));
		if(Is_Error($SqlResult))
			return ERROR | @Trigger_Error(SPrintF('[MySQL->SelectDB]: невозможно выбрать базу данных (%s)',$DbName));
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
	}
	
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	/*------------------------------------------------------------------------------
	      Задача:
	Получить строку ошибки.
	------------------------------------------------------------------------------*/
	public function GetError(){
		#-------------------------------------------------------------------------------
		if(!Is_Resource($this->Link))
			return ERROR | @Trigger_Error('[MySQL->GetError]: нет соединения с MySQL');
		#-------------------------------------------------------------------------------
		return Mysql_Error($this->Link);
		#-------------------------------------------------------------------------------
	}
	
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	/*------------------------------------------------------------------------------
	      Задача:
	Выполнить запрос в базу данных.
	------------------------------------------------------------------------------*/
	public function Query($Query){
		#-------------------------------------------------------------------------------
		if(!Is_String($Query))
			return ERROR | @Trigger_Error('[MySQL->Query]: первый параметр не является строкой');
		#-------------------------------------------------------------------------------
		if(!Is_Resource($this->Link))
			return ERROR | @Trigger_Error('[MySQL->Query]: нет соединения с MySQL');
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[MySQL->Query]: %s',$Query));
		#-------------------------------------------------------------------------------
		$this->Query = $Query;
		#-------------------------------------------------------------------------------
		$Result = Mysql_Query($Query,$this->Link);
		if($Result)
			return $Result;
		#-------------------------------------------------------------------------------
		$Error = $this->GetError();
		#-------------------------------------------------------------------------------
		return ERROR | @Trigger_Error(SPrintF('[MySQL->Query]: %s',$Error));
		#-------------------------------------------------------------------------------
	}
	
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	/*------------------------------------------------------------------------------
	      Задача:
	Получить последний выполненный запрос.
	------------------------------------------------------------------------------*/
	public function GetQuery(){
		#-------------------------------------------------------------------------------
		return $this->Query;
		#-------------------------------------------------------------------------------
	}
	
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	/*------------------------------------------------------------------------------
	      Задача:
	Проверить наличие соединения с базой данных.
	------------------------------------------------------------------------------*/
	public function IsConnected(){
		#-------------------------------------------------------------------------------
		if(!Is_Resource($this->Link))
			return FALSE;
		#-------------------------------------------------------------------------------
		return (bool)Mysql_Query('status',$this->Link);
		#-------------------------------------------------------------------------------
	}
	
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	static function Result($Resourse){
		#-------------------------------------------------------------------------------
		if(!Is_Resource($Resourse))
			return ERROR | @Trigger_Error('[MySQL->Result]: параметр не является ресурсом');
		#-------------------------------------------------------------------------------
		$Result = Array();
		#-------------------------------------------------------------------------------
		while($Row = Mysql_Fetch_Assoc($Resourse))
			$Result[] = $Row;
		#-------------------------------------------------------------------------------
		return $Result;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
