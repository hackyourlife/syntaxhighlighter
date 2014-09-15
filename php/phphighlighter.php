<?php
	$keywords = array(
		'break', 'case', 'continue', 'default', 'do', 'else', 'elseif',
		'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile',
		'for', 'foreach', 'function', 'if', 'include', 'include_once',
		'require', 'require_once', 'return', 'switch', 'while',
		'$GLOBALS', '$HTTP_COOKIE_VARS', '$HTTP_ENV_VARS',
		'$HTTP_GET_VARS', '$HTTP_POST_FILES', '$HTTP_POST_VARS',
		'$HTTP_SERVER_VARS', '$HTTP_SESSION_VARS', '$PHP_SELF',
		'$_COOKIE', '$_ENV', '$_FILES', '$_GET', '$_POST',
		'$_REQUEST', '$_SERVER', '$_SESSION', '$argc', '$argv',
		'$this', 'NULL', '__autoload', '__call', '__clone',
		'__construct', '__destruct', '__get', '__set', '__sleep',
		'__wakeup', 'abstract', 'as', 'catch', 'cfunction', 'class',
		'declare', 'enddeclare', 'extends', 'false', 'final',
		'global', 'implements', 'interface', 'namespace',
		'old_function', 'parent', 'private', 'protected', 'public',
		'static', 'stdClass', 'throw', 'true', 'try', 'var'
	);

	$reservedWords = array();

	for($i = 0; $i < count($keywords); $i++)
		$reservedWords[strtoupper($keywords[$i])] = true;

	$delimiters = '~!@%^&*()-+=|\\/{}[]:;"\'<> ,	.?';

	$inComment = false;
	$inPHP = false;
	$inTag = false;
	$inHTMLComment = false;
	$inHTMLString = false;

	if(!isset($php_func))
		$php_func = 'php.func';
	if(!isset($php_const))
		$php_const = 'php.const';

	$functions = array();
	$functionsFile = file_get_contents($php_func);
	$token = strtok($functionsFile, " \r\n\t");
	while($token !== false) {
		$functions[strtoupper($token)] = true;
		$token = strtok(" \r\n\t");
	}

	$constants = array();
	$constantsFile = file_get_contents($php_const);
	$token = strtok($constantsFile, " \r\n\t");
	while($token !== false) {
		$constants[strtoupper($token)] = true;
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
		global $inHTMLComment;
		global $inHTMLString;
		global $inTag;
		global $inPHP;
		global $reservedWords;
		global $functions;
		global $constants;

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

		$htmlTagText = false;

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
			$i++; // because the last character read in the while-loop is not part of tempString
			if(((substr(strtoupper($line), $i - 1, 5)) == '<?PHP') && !$inPHP) {
				$formatted .= '<span class="phptag">' . htmlentities(substr($line, $i - 1, 5)) . '</span>';
				$inPHP = true;
				$i += 4;
			} else if((substr($line, $i - 1, 2) == '?>') && $inPHP && !$inString && !$inCharacter) {
				$formatted .= '<span class="phptag">' . htmlentities(substr($line, $i - 1, 2)) . '</span>';
				$inPHP = false;
				$i++;
			} else if($inPHP) { // PHP-Mode
				if(strlen($temp) == 0)
					; // nothing
				else
				if(($temp[0] == '$') && !$inString && !$inCharacter && !$inComment) {
					$formatted .= '<span class="variable">' . htmlentities($temp) . '</span>';
				} else if(isset($reservedWords[strtoupper($temp)]) && !$inString && !$inCharacter && !$inComment) {
					$formatted .= '<span class="keyword">' . htmlentities($temp) . '</span>';
				} else if(isset($functions[strtoupper($temp)]) && !$inString && !$inCharacter && !$inComment) {
					$formatted .= '<span class="function">' . htmlentities($temp) . '</span>';
				} else if(isset($constants[strtoupper($temp)]) && !$inString && !$inCharacter && !$inComment) {
					$formatted .= '<span class="constant">' . htmlentities($temp) . '</span>';
				} else if(isNumeric($temp) && !$inString && !$inCharacter && !$inComment) {
					$formatted .= '<span class="number">' . htmlentities($temp) . '</span>';
				} else
					$formatted .= htmlentities($temp);

				$do_append = true;

				if(($i < strlen($line)) && ($ch == '/') && ($line[$i] == '/') && !$inString && !$inCharacter && !$inComment) {
					$formatted .= '<span class="comment">' . htmlentities($ch . substr($line, $i)) . '</span>';
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
							$formatted .= '<span class="string">' . htmlentities($ch);
						else
							$formatted .= htmlentities($ch) . '</span>';
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
							$formatted .= '<span class="string">' . htmlentities($ch);
						else
							$formatted .= htmlentities($ch) . '</span>';
						$inCharacter = !$inCharacter;
					}
				} else if(!$inString && !$inCharacter && ($i < strlen($line)) && ($ch == '/') && ($line[$i] == '*')) {
					$do_append = false;
					$formatted .= '<span class="comment">' . htmlentities($ch);
					$inComment = true;
				} else if(!$inString && !$inCharacter && ($i < strlen($line)) && ($ch == '*') && ($line[$i] == '/')) {
					$do_append = false;
					$formatted .= htmlentities($ch . $line[$i]) . '</span>';
					$inComment = false;
					$i++;
				}

				// append last character (not contained in tempString) if it was not
				// processed elsewhere
				// replace html-specific chars
				if($do_append && (($startAt + strlen($temp)) < strlen($line)))
					$formatted .= htmlentities($ch);
			} else { // HTML-Mode
				//$formatted .= htmlentities($temp);
				$done = false;
				if(($startAt + strlen($temp)) < strlen($line) && !$inHTMLString) {
					$end = $startAt + strlen($temp) + 1;
					$done = true;
					if((($end + 2) < strlen($line)) && ($ch == '<') && ($line[$end + 1] == '-') && ($line[$end + 2] == '-') && !$inTag && !$inHTMLComment) {
						$inHTMLComment = true;
						$formatted .= htmlentities($temp) . '<span class="htmlcomment">' . htmlentities($ch);
					} else if(($ch == '<') && !$inTag) {
						$inTag = true;
						$formatted .= htmlentities($temp) . '<span class="tag">' . htmlentities($ch);
					} else if(($ch == '>') && $inTag) {
						$inTag = false;
						$formatted .= htmlentities($temp) . htmlentities($ch) . '</span>';
					} else if(($ch == '>') && $inHTMLComment) {
						$inHTMLComment = false;
						$formatted .= htmlentities($temp) . htmlentities($ch) . '</span>';
					} else
						$done = false;
				}
				if(!$done) {
					if($inTag && !$inHTMLComment && !$inHTMLString && ($ch == '=')) {
						$formatted .= '<span class="htmlproperty">' . htmlentities($temp) . htmlentities($ch) . '</span>';
					} else if($inTag && !$inHTMLComment && !$inHTMLString && ($ch == '"')) {
						$inHTMLString = true;
						$formatted .= htmlentities($temp) . '<span class="string">' . htmlentities($ch);
					} else if($inTag && !$inHTMLComment && $inHTMLString && ($ch == '"')) {
						$inHTMLString = false;
						$formatted .= htmlentities($temp) . htmlentities($ch) . '</span>';
					} else
						$formatted .= htmlentities($temp) . htmlentities($ch);
				}

			}
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
			
			.phptag {
				color: #FF0000;
			}
			.keyword {
				color: #0000FF
			}
			.function {
				color: #FF0000;
			}
			.constant {
				color: #00FF00;
			}
			.comment {
				color: #008080;
			}
			.string {
				color: #808080;
			}
			.variable {
				color: #FF8000;
			}
			.number {
				color: #FF0000;
			}
			.tag {
				color: #0000FF;
			}
			.htmlcomment {
				color: #008000;
			}
			.htmlproperty {
				color: #FF0000;
			}
		--></style>
	</head>
	<body><pre><?php
		$file = fopen($filename, 'rb');
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
		<div>Highlight PHP File (Version ALPHA)</div>
		<form method="post" enctype="multipart/form-data" action="<?php echo(htmlentities(basename($_SERVER['PHP_SELF']))); ?>"><div>
			<input type="hidden" name="MAX_FILE_SIZE" value="16777216" />
			<input type="file" name="file" />
			<input type="submit" name="upload" value="upload" />
		</div></form>
	</body>
</html>
