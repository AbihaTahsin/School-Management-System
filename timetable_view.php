<?php


	$currDir=dirname(__FILE__);
	include("$currDir/defaultLang.php");
	include("$currDir/language.php");
	include("$currDir/lib.php");
	@include("$currDir/hooks/timetable.php");
	include("$currDir/timetable_dml.php");

	// mm: can the current member access this page?
	$perm=getTablePermissions('timetable');
	if(!$perm[0]){
		echo error_message($Translation['tableAccessDenied'], false);
		echo '<script>setTimeout("window.location=\'index.php?signOut=1\'", 2000);</script>';
		exit;
	}

	$x = new DataList;
	$x->TableName = "timetable";

	// Fields that can be displayed in the table view
	$x->QueryFieldsTV = array(   
		"`timetable`.`id`" => "id",
		"`timetable`.`Time_Table`" => "Time_Table",
		"IF(    CHAR_LENGTH(`classes1`.`Name`), CONCAT_WS('',   `classes1`.`Name`), '') /* Class */" => "Class",
		"IF(    CHAR_LENGTH(`streams1`.`Name`), CONCAT_WS('',   `streams1`.`Name`), '') /* Stream */" => "Stream"
	);
	// mapping incoming sort by requests to actual query fields
	$x->SortFields = array(   
		1 => '`timetable`.`id`',
		2 => 2,
		3 => '`classes1`.`Name`',
		4 => '`streams1`.`Name`'
	);

	// Fields that can be displayed in the csv file
	$x->QueryFieldsCSV = array(   
		"`timetable`.`id`" => "id",
		"`timetable`.`Time_Table`" => "Time_Table",
		"IF(    CHAR_LENGTH(`classes1`.`Name`), CONCAT_WS('',   `classes1`.`Name`), '') /* Class */" => "Class",
		"IF(    CHAR_LENGTH(`streams1`.`Name`), CONCAT_WS('',   `streams1`.`Name`), '') /* Stream */" => "Stream"
	);
	// Fields that can be filtered
	$x->QueryFieldsFilters = array(   
		"`timetable`.`id`" => "ID",
		"`timetable`.`Time_Table`" => "Time Table",
		"IF(    CHAR_LENGTH(`classes1`.`Name`), CONCAT_WS('',   `classes1`.`Name`), '') /* Class */" => "Class",
		"IF(    CHAR_LENGTH(`streams1`.`Name`), CONCAT_WS('',   `streams1`.`Name`), '') /* Stream */" => "Stream"
	);

	// Fields that can be quick searched
	$x->QueryFieldsQS = array(   
		"`timetable`.`id`" => "id",
		"`timetable`.`Time_Table`" => "Time_Table",
		"IF(    CHAR_LENGTH(`classes1`.`Name`), CONCAT_WS('',   `classes1`.`Name`), '') /* Class */" => "Class",
		"IF(    CHAR_LENGTH(`streams1`.`Name`), CONCAT_WS('',   `streams1`.`Name`), '') /* Stream */" => "Stream"
	);

	// Lookup fields that can be used as filterers
	$x->filterers = array(  'Class' => 'Class', 'Stream' => 'Stream');

	$x->QueryFrom = "`timetable` LEFT JOIN `classes` as classes1 ON `classes1`.`id`=`timetable`.`Class` LEFT JOIN `streams` as streams1 ON `streams1`.`id`=`timetable`.`Stream` ";
	$x->QueryWhere = '';
	$x->QueryOrder = '';

	$x->AllowSelection = 1;
	$x->HideTableView = ($perm[2]==0 ? 1 : 0);
	$x->AllowDelete = $perm[4];
	$x->AllowMassDelete = true;
	$x->AllowInsert = $perm[1];
	$x->AllowUpdate = $perm[3];
	$x->SeparateDV = 1;
	$x->AllowDeleteOfParents = 0;
	$x->AllowFilters = 1;
	$x->AllowSavingFilters = 1;
	$x->AllowSorting = 1;
	$x->AllowNavigation = 1;
	$x->AllowPrinting = 1;
	$x->AllowCSV = 1;
	$x->RecordsPerPage = 10;
	$x->QuickSearch = 1;
	$x->QuickSearchText = $Translation["quick search"];
	$x->ScriptFileName = "timetable_view.php";
	$x->RedirectAfterInsert = "timetable_view.php?SelectedID=#ID#";
	$x->TableTitle = "TimeTable";
	$x->TableIcon = "resources/table_icons/data_chooser.png";
	$x->PrimaryKey = "`timetable`.`id`";

	$x->ColWidth   = array(  150, 150, 150);
	$x->ColCaption = array("Time Table", "Class", "Stream");
	$x->ColFieldName = array('Time_Table', 'Class', 'Stream');
	$x->ColNumber  = array(2, 3, 4);

	// template paths below are based on the app main directory
	$x->Template = 'templates/timetable_templateTV.html';
	$x->SelectedTemplate = 'templates/timetable_templateTVS.html';
	$x->TemplateDV = 'templates/timetable_templateDV.html';
	$x->TemplateDVP = 'templates/timetable_templateDVP.html';

	$x->ShowTableHeader = 1;
	$x->ShowRecordSlots = 0;
	$x->TVClasses = "";
	$x->DVClasses = "";
	$x->HighlightColor = '#FFF0C2';

	// mm: build the query based on current member's permissions
	$DisplayRecords = $_REQUEST['DisplayRecords'];
	if(!in_array($DisplayRecords, array('user', 'group'))){ $DisplayRecords = 'all'; }
	if($perm[2]==1 || ($perm[2]>1 && $DisplayRecords=='user' && !$_REQUEST['NoFilter_x'])){ // view owner only
		$x->QueryFrom.=', membership_userrecords';
		$x->QueryWhere="where `timetable`.`id`=membership_userrecords.pkValue and membership_userrecords.tableName='timetable' and lcase(membership_userrecords.memberID)='".getLoggedMemberID()."'";
	}elseif($perm[2]==2 || ($perm[2]>2 && $DisplayRecords=='group' && !$_REQUEST['NoFilter_x'])){ // view group only
		$x->QueryFrom.=', membership_userrecords';
		$x->QueryWhere="where `timetable`.`id`=membership_userrecords.pkValue and membership_userrecords.tableName='timetable' and membership_userrecords.groupID='".getLoggedGroupID()."'";
	}elseif($perm[2]==3){ // view all
		// no further action
	}elseif($perm[2]==0){ // view none
		$x->QueryFields = array("Not enough permissions" => "NEP");
		$x->QueryFrom = '`timetable`';
		$x->QueryWhere = '';
		$x->DefaultSortField = '';
	}
	// hook: timetable_init
	$render=TRUE;
	if(function_exists('timetable_init')){
		$args=array();
		$render=timetable_init($x, getMemberInfo(), $args);
	}

	if($render) $x->Render();

	// hook: timetable_header
	$headerCode='';
	if(function_exists('timetable_header')){
		$args=array();
		$headerCode=timetable_header($x->ContentType, getMemberInfo(), $args);
	}  
	if(!$headerCode){
		include_once("$currDir/header.php"); 
	}else{
		ob_start(); include_once("$currDir/header.php"); $dHeader=ob_get_contents(); ob_end_clean();
		echo str_replace('<%%HEADER%%>', $dHeader, $headerCode);
	}

	echo $x->HTML;
	// hook: timetable_footer
	$footerCode='';
	if(function_exists('timetable_footer')){
		$args=array();
		$footerCode=timetable_footer($x->ContentType, getMemberInfo(), $args);
	}  
	if(!$footerCode){
		include_once("$currDir/footer.php"); 
	}else{
		ob_start(); include_once("$currDir/footer.php"); $dFooter=ob_get_contents(); ob_end_clean();
		echo str_replace('<%%FOOTER%%>', $dFooter, $footerCode);
	}
?>