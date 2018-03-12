<?php
/**
 * PLUGIN NAME: Plain Text Report
 * DESCRIPTION: Allows you to export data from an event for a particular record as plain text. (For concise printing, formatting and other purposes.)
 * VERSION: 1.0
 * AUTHOR: Pavel Dusek
 */
function checkGETVariables() {
	/** 
	 * Check if project id and record id have been set and whether project is longitudinal.
	 */
    if (!isset( $_GET['pid'] )) exit("<p>You have to set project ID!</p>");
    if (!isset( $_GET['record'] )) exit("<p>You have to set record ID!</p>");
    if (!REDCap::isLongitudinal()) exit("<p>Cannot get event names because this project is not longitudinal.</p>");
    return True;
}

function endsWith( $haystack, $needle ) {
	/** 
	 * Check if haystack ends with needle.
	 */
	$length = strlen( $needle );
	return $length === 0 || (substr( $haystack, -$length) === $needle);
}
function getChoices( $select_choices_or_calculations ) {
	/** 
	 * From REDCap select_choices_or_calculations field in DataDictionary, get dictionary of choices as an array.
	 */
	$parts = explode( "|", $select_choices_or_calculations );
	$dict = Array();
	foreach ($parts as $part) {
		$keyvalue = explode( ",", $part, 2);
		$dict[ trim($keyvalue[0]) ] = trim($keyvalue[1]);
	}
	return $dict;
}
require_once "../redcap_connect.php"; // Call the REDCap Connect file in the main "redcap" directory
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php'; // Display the project header

//Main plugin logic
echo "<h3 style='color:#800000;'> REDCap Plain Text Report Plugin </h3>";
if ( checkGETVariables() ) {
    if ( isset( $_GET['event'] ) ) {
        //run the plugin
	echo "<p>Project ID: $_GET[pid]</p>\n";
	echo "<p>Record ID: $_GET[record]</p>\n";
	echo "<p>Event: $_GET[event]</p>\n";
	echo "<h4>Data</h4>\n";
	$data = REDCap::getData($_GET['pid'], 'array', $_GET['record'], null, $_GET['event']); //get data for particular project, record and event
	$fields = REDCap::getDataDictionary($_GET['pid'], 'array');

	echo "<p>";
	foreach ( $data as $events ) {
		foreach ( $events as $keyvalue ) {
			foreach( $keyvalue as $key => $value ) {
				$fieldInfo = $fields[$key];
				if ($value && !endsWith($key, "_complete")) {
					switch ($fieldInfo['field_type']) { //change some fieldtypes to human readable values:
						case 'yesno': $value = ($value == '1')? "Ano":"Ne"; break;
						case 'dropdown': $choices = getChoices($fieldInfo['select_choices_or_calculations']); $value = $choices[$value]; break;
						case 'radio': $choices = getChoices($fieldInfo['select_choices_or_calculations']); $value = $choices[$value]; break;
						case 'checkbox':
							$choices = getChoices($fieldInfo['select_choices_or_calculations']);
							$selectedOptions = Array();
							foreach( $value as $key => $option ) {
								if ($option == 1) array_push($selectedOptions, $choices[$key]);
							}
							if (count($selectedOptions)>0) {
								$value = implode( ", ", $selectedOptions );
							}
							break;
						default: break;
					}
					$fieldName = htmlspecialchars($fieldInfo['field_label']);
					$value = htmlspecialchars($value);
					if ($value) echo "<strong>$fieldName:</strong> $value; ";
				}
			}
		}
	}
	echo "</p>";
    } else {
        //allow to set event
        $events = REDCap::getEventNames(True);
        echo "<form method='get'>\n";
	echo "<label>Project ID</label><input type='text' name='pid' value='$_GET[pid]' />\n";
	echo "<label>Record ID</label><input type='text' name='record' value='$_GET[record]' />\n";
        echo "\t<select name='event'>\n";
        foreach ($events as $event) {
            echo "\t\t<option value='$event'>$event</option>\n";
        }
        echo "\t</select>\n";
	echo "\t<input type='submit'value='submit' />\n";
        echo "</form>\n";
    }
}

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
?>
