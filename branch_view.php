<?php


	$currDir=dirname(__FILE__);
	include("$currDir/defaultLang.php");
	include("$currDir/language.php");
	include("$currDir/lib.php");
	@include("$currDir/hooks/branch.php");
	include("$currDir/branch_dml.php");

	// mm: can the current member access this page?
	$perm=getTablePermissions('branch');
	if(!$perm[0]){
		echo error_message($Translation['tableAccessDenied'], false);
		echo '<script>setTimeout("window.location=\'index.php?signOut=1\'", 2000);</script>';
		exit;
	}

	$x = new DataList;
	$x->TableName = "branch";

	// Fields that can be displayed in the table view
	$x->QueryFieldsTV = array(   
		"`branch`.`id`" => "id",
		"`branch`.`Name`" => "Name",
		"`branch`.`AccountNumber`" => "AccountNumber"
	);
	// mapping incoming sort by requests to actual query fields
	$x->SortFields = array(   
		1 => '`branch`.`id`',
		2 => 2,
		3 => 3
	);

	// Fields that can be displayed in the csv file
	$x->QueryFieldsCSV = array(   
		"`branch`.`id`" => "id",
		"`branch`.`Name`" => "Name",
		"`branch`.`AccountNumber`" => "AccountNumber"
	);
	// Fields that can be filtered
	$x->QueryFieldsFilters = array(   
		"`branch`.`id`" => "ID",
		"`branch`.`Name`" => "Name",
		"`branch`.`AccountNumber`" => "AccountNumber"
	);

	// Fields that can be quick searched
	$x->QueryFieldsQS = array(   
		"`branch`.`id`" => "id",
		"`branch`.`Name`" => "Name",
		"`branch`.`AccountNumber`" => "AccountNumber"
	);

	// Lookup fields that can be used as filterers
	$x->filterers = array();

	$x->QueryFrom = "`branch` ";
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
	$x->ScriptFileName = "branch_view.php";
	$x->RedirectAfterInsert = "branch_view.php?SelectedID=#ID#";
	$x->TableTitle = "Branch";
	$x->TableIcon = "resources/table_icons/card_bank.png";
	$x->PrimaryKey = "`branch`.`id`";
	$x->DefaultSortField = '1';
	$x->DefaultSortDirection = 'desc';

	$x->ColWidth   = array(  150, 150);
	$x->ColCaption = array("Name", "AccountNumber");
	$x->ColFieldName = array('Name', 'AccountNumber');
	$x->ColNumber  = array(2, 3);

	// template paths below are based on the app main directory
	$x->Template = 'templates/branch_templateTV.html';
	$x->SelectedTemplate = 'templates/branch_templateTVS.html';
	$x->TemplateDV = 'templates/branch_templateDV.html';
	$x->TemplateDVP = 'templates/branch_templateDVP.html';

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
		$x->QueryWhere="where `branch`.`id`=membership_userrecords.pkValue and membership_userrecords.tableName='branch' and lcase(membership_userrecords.memberID)='".getLoggedMemberID()."'";
	}elseif($perm[2]==2 || ($perm[2]>2 && $DisplayRecords=='group' && !$_REQUEST['NoFilter_x'])){ // view group only
		$x->QueryFrom.=', membership_userrecords';
		$x->QueryWhere="where `branch`.`id`=membership_userrecords.pkValue and membership_userrecords.tableName='branch' and membership_userrecords.groupID='".getLoggedGroupID()."'";
	}elseif($perm[2]==3){ // view all
		// no further action
	}elseif($perm[2]==0){ // view none
		$x->QueryFields = array("Not enough permissions" => "NEP");
		$x->QueryFrom = '`branch`';
		$x->QueryWhere = '';
		$x->DefaultSortField = '';
	}
	// hook: branch_init
	$render=TRUE;
	if(function_exists('branch_init')){
		$args=array();
		$render=branch_init($x, getMemberInfo(), $args);
	}

	if($render) $x->Render();

	// hook: branch_header
	$headerCode='';
	if(function_exists('branch_header')){
		$args=array();
		$headerCode=branch_header($x->ContentType, getMemberInfo(), $args);
	}  
	if(!$headerCode){
		include_once("$currDir/header.php"); 
	}else{
		ob_start(); include_once("$currDir/header.php"); $dHeader=ob_get_contents(); ob_end_clean();
		echo str_replace('<%%HEADER%%>', $dHeader, $headerCode);
	}

	echo $x->HTML;
	// hook: branch_footer
	$footerCode='';
	if(function_exists('branch_footer')){
		$args=array();
		$footerCode=branch_footer($x->ContentType, getMemberInfo(), $args);
	}  
	if(!$footerCode){
		include_once("$currDir/footer.php"); 
	}else{
		ob_start(); include_once("$currDir/footer.php"); $dFooter=ob_get_contents(); ob_end_clean();
		echo str_replace('<%%FOOTER%%>', $dFooter, $footerCode);
	}
?>