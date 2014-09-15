<?php
	$keywords = array(
		"#define", "#elif", "#else", "#endif", "#error", "#if",
		"#ifdef", "#ifndef", "#include", "#include_next", "#line",
		"#pragma", "#undef", "__asm", "__based", "__cdecl",
		"__declspec", "__except", "__far", "__fastcall", "__finally",
		"__fortran", "__huge", "__inline", "__int16", "__int32",
		"__int64", "__int8", "__interrupt", "__leave", "__loadds",
		"__near", "__pascal", "__saveregs", "__segment", "__segname",
		"__self", "__stdcall", "__try", "__uuidof", "auto", "bool",
		"break", "case", "char", "const", "continue", "default",
		"defined", "do", "double", "else", "enum", "extern", "float",
		"for", "goto", "if", "int", "long", "register", "return",
		"short", "signed", "sizeof", "static", "struct", "switch",
		"typedef", "union", "unsigned", "void", "volatile", "while"
	);

	$reservedWords = array();
	$cppkeywords = array(
		"__multiple_inheritance", "__single_inheritance",
		"__virtual_inheritance", "catch", "class", "const_cast",
		"delete", "dynamic_cast", "explicit", "export", "false",
		"friend", "inline", "mutable", "namespace", "new", "operator",
		"private", "protected", "public", "reinterpret_cast",
		"static_cast", "template", "this", "throw", "true", "try",
		"typeid", "typename", "using", "virtual", "wchar_t"
	);
	$cppReservedWords = array();

	$coperators = array(
		'!', '%', '&', '*', '+', '-', '/', '<', '=', '>', '^', '|', '~'
	);
	$operators = array();

	for($i = 0; $i < count($keywords); $i++)
		$reservedWords[$keywords[$i]] = true;
	for($i = 0; $i < count($cppkeywords); $i++)
		$cppReservedWords[$cppkeywords[$i]] = true;
	for($i = 0; $i < count($coperators); $i++)
		$operators[$coperators[$i]] = true;

	$delimiters = '~!@%^&*()-+=|\/{}[]:;"\'<> ,	.?';

	$inComment = false;

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
		global $cppReservedWords;
		global $operators;

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
			} else if(isset($cppReservedWords[$temp]) && !$inString && !$inCharacter && !$inComment) {
				$formatted .= '<span class="cpp">' . htmlentities($temp) . '</span>';
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
			} else if(!$inString && !$inCharacter && !$inComment && isset($operators[$ch])) {
				$do_append = false;
				$formatted .= '<span class="operator">' . htmlentities($ch) . '</span>';
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
			.cpp {
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
			.operator {
				color: #008000;
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
		<div>Highlight C/C++ File (Version ALPHA)</div>
		<form method="post" enctype="multipart/form-data" action="<?php echo(htmlentities(basename($_SERVER['PHP_SELF']))); ?>"><div>
			<input type="hidden" name="MAX_FILE_SIZE" value="16777216" />
			<input type="file" name="file" />
			<input type="submit" name="upload" value="upload" />
		</div></form>
	</body>
</html>
