<?php
	$keywords = array(
		"abstract", "boolean", "break", "byte", "case",
		"catch", "char", "class", "continue", "default",
		"do", "double", "else", "extends", "false",
		"final", "finally", "float", "for", "if",
		"implements", "import", "instanceof", "int",
		"interface", "length", "long", "native", "new",
		"null", "package", "private", "protected", "public",
		"return", "short", "static", "super", "switch",
		"synchronized", "this", "threadsafe", "throw", "throws",
		"transient", "true", "try", "void", "while"
	);

	$reservedWords = array();
	$classes = array();

	for($i = 0; $i < count($keywords); $i++)
		$reservedWords[$keywords[$i]] = true;

	$delimiters = '~!@%^&*()-+=|\\/{}[]:;"\'<> ,	.?';

	$inComment = false;

	if(!isset($java_classes))
		$java_classes = 'java.classes';

	$classesFile = file_get_contents($java_classes);
	$token = strtok($classesFile, " \r\n\t");
	while($token !== false) {
		$classes[$token] = true;
		$token = strtok(" \r\n\t");
	}

	function isDelimiter($c) {
		global $delimiters;
		if(($c == "\r") || ($c == "\n"))
			return true;
		return (strpos($delimiters, $c) !== false);
	}

	function isInsideString($line, $position) {
		if(strpos($line, '"') === false)
			return false;
		$isInString = false;
		for($i = 0; $i < $position; $i++) {
			$ch = $line[$i];
			if(($i > 0) && ($ch == '"') && ($line[$i - 1] == '\\'))
				continue;
			else if($ch == '"')
				$isInString = !$isInString;
		}
		return $isInString;
	}

	function isNumeric($line) {
		//for($i = 0; $i < strlen($line); $i++)
		//	if(($line[$i] < '0') || ($line[$i] > '9'))
		//		return false;
		if(($line[0] < '0') || ($line[0] > '9'))
			return false;
		return true;
	}


	function format_line($line) {
		global $inComment;
		global $reservedWords;
		global $classes;

		if($line == null || $line == '') {
			return '';
		}

		$formatted = '';

		$i = 0;
		$startAt = 0;
		$ch = '';
		$temp = '';

		$inString = false;
		$inCharacter = false;

		// parse line
		while($i < strlen($line)) {
			$temp = '';
			$ch = $line[$i];
			$startAt = $i;
			while($i < strlen($line) && !isDelimiter($ch)) {
				$temp .= $ch;
				$i++;
				if($i < strlen($line)) {
					$ch = $line[$i];
				}
			}
			if(strlen($temp) == 0)
				; // nothing
			else
			if(isset($reservedWords[$temp]) && !$inString && !$inCharacter && !$inComment) {
				$formatted .= '<span class="keyword">' . htmlentities($temp) . '</span>';
			} else if(isset($classes[$temp]) && !$inString && !$inCharacter && !$inComment) {
				$formatted .= '<span class="class">' . htmlentities($temp) . '</span>';
			} else if(isNumeric($temp) && !$inString && !$inCharacter && !$inComment) {
				$formatted .= '<span class="number">' . htmlentities($temp) . '</span>';
			} else
				$formatted .= htmlentities($temp);
			$i++; // because the last character read in the while-loop is not part of tempString

			$do_append = true;

			if(($i < strlen($line)) && ($ch == '/') && ($line[$i] == '/') && !$inString && !$inCharacter && !$inComment) {
				$formatted .= '<span class="comment">' . $ch . substr($line, $i) . '</span>';
				break;
			} else if(!$inComment && !$inCharacter && ($ch == '"')) {
				$do_append = false;
				if($i > 1) {
					if($line[$i - 2] == '\\') {
						if(($i > 2) && ($line[$i - 3] == '\\'))
							$do_append = false;
						else
							$do_append = true;
					}
				}
				if(!$do_append) {
					if(!$inString)
						$formatted .= '<span class="string">' . $ch;
					else
						$formatted .= $ch . '</span>';
					$inString = !$inString;
				}
			} else if(!$inComment && !$inString && ($ch == '\'')) {
				$do_append = false;
				if($i > 1) {
					if($line[$i - 2] == '\\') {
						if(($i > 2) && ($line[$i - 3] == '\\'))
							$do_append = false;
						else
							$do_append = true;
					}
				}
				if(!$do_append) {
					if(!$inCharacter)
						$formatted .= '<span class="string">' . $ch;
					else
						$formatted .= $ch . '</span>';
					$inCharacter = !$inCharacter;
				}
			} else if(!$inString && !$inCharacter && ($i < strlen($line)) && ($ch == '/') && ($line[$i] == '*')) {
				$do_append = false;
				$formatted .= '<span class="comment">' . $ch;
				$inComment = true;
			} else if(!$inString && !$inCharacter && ($i < strlen($line)) && ($ch == '*') && ($line[$i] == '/')) {
				$do_append = false;
				$formatted .= $ch . $line[$i] . '</span>';
				$inComment = false;
				$i++;
			}

			// append last character (not contained in tempString) if it was not
			// processed elsewhere
			// replace html-specific chars
			if($do_append && (($startAt + strlen($temp)) < strlen($line)))
				$formatted .= htmlentities($ch);
		}

		return $formatted;
	}

	if((isset($_REQUEST['upload']) && ($_FILES['file']['size'] > 0)) || isset($highlightfile)) {
		if(isset($_REQUEST['upload'])) {
			$name = $_FILES['file']['name'];
			$type = $_FILES['file']['type'];
			$size = $_FILES['file']['size'];
			$filename = $_FILES['file']['tmp_name'];
		} else {
			$filename = $highlightfile;
			$name = basename($filename);
		}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
		<title><?php echo(htmlentities($name)); ?></title>
		<style type="text/css"><!--
			pre {
				font-family: "Courier New", "Lucida Console", monospace;
			}
			
			.keyword {
				color: #0000FF
			}
			.class {
				color: #FF0000;
			}
			.comment {
				color: #008080;
			}
			.string {
				color: #808080;
			}
			.number {
				color: #FF0000;
			}
		--></style>
	</head>
	<body><pre><?php
		$file = fopen($filename, 'rt');
		while(($line = fgets($file)) !== false) {
			echo(format_line($line));
		}
		fclose($file);
?></pre></body>
</html>
<?php
	exit();
	}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
		<title>Hack your Life (IP: <?php echo($_SERVER['REMOTE_ADDR']); ?>)</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link href="../style.css" rel="stylesheet" type="text/css" />
		<link rel="shortcut icon" href="../sh.ico" type="image/x-icon" />
	</head>
	<body>
		<div>Highlight JAVA File (Version ALPHA)</div>
		<form method="post" enctype="multipart/form-data" action="<?php echo(htmlentities(basename($_SERVER['PHP_SELF']))); ?>"><div>
			<input type="hidden" name="MAX_FILE_SIZE" value="16777216" />
			<input type="file" name="file" />
			<input type="submit" name="upload" value="upload" />
		</div></form>
	</body>
</html>
