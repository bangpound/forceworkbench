<?php
require_once 'session.php';
require_once 'shared.php';
require_once 'header.php';
require_once 'soapclient/SforceApexClient.php';

//correction for dynamic magic quotes
if (isset($_POST['scriptInput']) && get_magic_quotes_gpc()) {
	$_POST['scriptInput'] = stripslashes($_POST['scriptInput']);
}

if (isset($_POST['execute'])) {
	$_SESSION['scriptInput'] = $_POST['scriptInput'];
	$_SESSION['LogCategory'] = $_POST['LogCategory'];
	$_SESSION['LogCategoryLevel'] = $_POST['LogCategoryLevel'];
} else if (!isset($_SESSION['LogCategory']) && !isset($_SESSION['LogCategoryLevel'])) {
	$_SESSION['LogCategory'] = $_SESSION['config']['defaultLogCategory'];
	$_SESSION['LogCategoryLevel'] = $_SESSION['config']['defaultLogCategoryLevel'];
}


?>
<form id="executeForm" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
	<table border="0">
	  <tr>
	    <td><p class='instructions'>Enter Apex code to be executed as an anonymous block:<p/></td>
	  </tr>
	  <tr>
	    <td align="right">
		    Log Category: 
			<select id="LogCategory" name="LogCategory">
				<?php		
				printSelectOptions($config['defaultLogCategory']['valuesToLabels'],$_SESSION['LogCategory']);
				?>
			</select>
	
			&nbsp;
			
			Log Level: 
			<select id="LogCategoryLevel" name="LogCategoryLevel">
				<?php
				printSelectOptions($config['defaultLogCategoryLevel']['valuesToLabels'],$_SESSION['LogCategoryLevel']);
				?>
			</select>
		</td>
	  </tr>
	  <tr>
	    <td colspan="2">
			
			<textarea id='scriptInput' name='scriptInput' cols='100' rows='<?php print $_SESSION['config']['textareaRows'] ?>' style='overflow: auto; font-family: monospace, courier;'><?php echo htmlspecialchars(isset($_SESSION['scriptInput'])?$_SESSION['scriptInput']:null,ENT_QUOTES,'UTF-8'); ?></textarea>
			<p/>
			<input type='submit' name="execute" value='Execute'/> <input type='reset' value='Reset'/>
			
		</td>
	  </tr>
	</table>
</form>


<script type="text/javascript">
 	document.getElementById('scriptInput').focus();
</script>


<?php
if (isset($_POST['execute']) && isset($_POST['scriptInput']) && $_POST['scriptInput'] != "") {
	print "<h2>Results</h2>";
	
	$apexConnection = new SforceApexClient($_POST['LogCategory'],$_POST['LogCategoryLevel']);
	
	try {
		$executeAnonymousResultWithDebugLog = $apexConnection->executeAnonymous($_POST['scriptInput']);
	} catch(Exception $e) {
		show_error($e->getMessage(),false,true);
	}
	
	if ($executeAnonymousResultWithDebugLog->executeAnonymousResult->success) {
		if (isset($executeAnonymousResultWithDebugLog->debugLog) && $executeAnonymousResultWithDebugLog->debugLog != "") {
			print("<pre>" . addLinksToUiForIds(htmlspecialchars($executeAnonymousResultWithDebugLog->debugLog,ENT_QUOTES,'UTF-8')) . '</pre>');
		} else {
			show_info("Execution was successful, but returned no results. Confirm log category and level.");
		}
		
	} else {
		$error = null;	
		
		if (isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->compileProblem)) {
			$error .=  "COMPILE ERROR: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->compileProblem;
		}
		
		if (isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->exceptionMessage)) {
			$error .= "\nEXCEPTION: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->exceptionMessage;
		}
		
		if (isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->exceptionStackTrace)) {
			$error .= "\nSTACKTRACE: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->exceptionStackTrace;
		}
		
			
		if (isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->line)) {
			$error .=  "\nLINE: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->line;
		}
		
		if (isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->column)) {
			$error .=  " COLUMN: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->column;
		}
		
		show_error($error);
		
		print('<pre style="color: red;">' . addLinksToUiForIds(htmlspecialchars($executeAnonymousResultWithDebugLog->debugLog,ENT_QUOTES,'UTF-8')) . '</pre>');
	}
	
//	print('<pre>');
//	print_r($executeAnonymousResultWithDebugLog);
//	print('</pre>');
} else if (isset($_POST['execute']) && isset($_POST['scriptInput']) && $_POST['scriptInput'] == "") {
	show_info("Anonymous block must not be blank.");
}


require_once 'footer.php';
?>
