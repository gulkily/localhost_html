<?php

//Header('Content-type: text/html');

function GetScriptDir () { // returns base script directory.
	$scriptDir = '/home/ilyag/pollyanna';
	// this placeholder is populated by pages.pl when template is written

	if ($scriptDir == '$'.'scriptDirPlaceholderForTemplating') {
		// this is a sanity check to make sure the placeholder was correctly populated
		return;
	}

	if (index($scriptDir, '"') != -1) {
		// $scriptDir contains double-quote, this is bad, as it will break things

		return;
	}

	return $scriptDir;
} # GetScriptDir()

function GetDir ($dirName) {
	$dirLocation = GetDir2($dirName); #my
	if ($dirLocation && trim($dirLocation)) {
		if (is_dir($dirLocation)) {
			// cool
		} else {
			WriteLog('GetDir: warning: is_dir() was false for $dirLocation = ' . $dirLocation);
		}
		return $dirLocation;
	} else {
		WriteLog('GetDir: warning: $dirLocation was false for $dirName = ' . $dirName);
		// this is very bad
	}

	// should not be here ever
} # GetDir()

function RenameFile ($fileOld, $fileNew) {
	#todo sanity
	return rename($fileOld, $fileNew);
} # RenameFile()

function GetDir2 ($dirName) { # returns path to special directory specified
# 'html' = html root
# 'script'
# 'txt'
# 'image

	if (!$dirName) {
		WriteLog('GetDir: warning: $dirName missing');
		return '';
	}
	WriteLog('GetDir: $dirName = ' . $dirName);

	$scriptDir = GetScriptDir();
	if (preg_match('/^([0-9a-zA-Z_\/]+)$/', $scriptDir, $matches)) {
		$scriptDir = $matches[0];
		WriteLog('GetDir: $scriptDir sanity check passed');
	} else {
		WriteLog('GetDir: warning: sanity check failed on $scriptDir');
		return '';
	}
	WriteLog('GetDir: $scriptDir = ' . $scriptDir);

	if ($dirName == 'script') {
		WriteLog('GetDir: return ' . $scriptDir);
		return $scriptDir;
	}

	if ($dirName == 'html') {
		WriteLog('GetDir: return ' . $scriptDir . '/html');
		return $scriptDir . '/html';
	}

	if ($dirName == 'php') {
		WriteLog('GetDir: return ' . $scriptDir . '/html');
		return $scriptDir . '/html';
	}

	if ($dirName == 'txt') {
		WriteLog('GetDir: return ' . $scriptDir . '/html/txt');
		return $scriptDir . '/html/txt';
	}

	if ($dirName == 'image') {
		WriteLog('GetDir: return ' . $scriptDir . '/html/image');
		return $scriptDir . '/html/image';
	}

	if ($dirName == 'cache') {
		WriteLog('GetDir: return ' . $scriptDir . '/cache');
		return $scriptDir . '/cache';
	}

	if ($dirName == 'config') {
		WriteLog('GetDir: return ' . $scriptDir . '/config');
		return $scriptDir . '/config';
	}

	if ($dirName == 'default') {
		WriteLog('GetDir: return ' . $scriptDir . '/default');
		return $scriptDir . '/default';
	}

	if ($dirName == 'log') {
		WriteLog('GetDir: return ' . $scriptDir . '/log');
		return $scriptDir . '/log';
	}

	WriteLog('GetDir: warning: fallthrough on $dirName = ' . $dirName);
	return '';
} # GetDir()

function GetSqliteDbName () {
	# todo improve on this
	$cacheDir = GetDir('cache');
	$cacheVersion = GetMyCacheVersion();
	if ($cacheDir && file_exists($cacheDir)) {
		$sqliteDbName = $cacheDir . '/' . $cacheVersion . '/index.sqlite3';
		return $sqliteDbName;
	} else {
		WriteLog('GetSqliteDbName: sanity check FAILED: $cacheDir does not exist');
		return '';
	}
} # GetSqliteDbName()

function uc ($string) { # uppercase, perl port
	return strtoupper($string);
} # uc()

function lc ($string) { # lowercase, perl port
	return strtolower($string);
} # lc()

function SqliteEscape ($text) { # Escapes supplied text for use in sqlite query
# Just changes ' to ''
	WriteLog("SqliteEscape($text)");

	if (isset($text)) {
		$text = str_replace("'", "''", $text);
	} else {
		$text = '';
	}

	WriteLog('SqliteEscape: return ' . $text);

	return $text;
} # SqliteEscape()

function SqliteGetValue ($query) { # Returns the first column from the first row returned by sqlite $query
	WriteLog("SqliteGetValue($query)");

	$sqliteDir = GetSqliteDbName();

	#todo more sanity here

	$command = 'sqlite3 "' . $sqliteDir . '" "' . $query . '"';
	WriteLog('SqliteGetValue: $command = ' . $command);

	$result = `$command`;
	WriteLog('SqliteGetValue: $result = ' . $result);

	return $result;
} # SqliteGetValue()

function IsFingerprint($key) {
	if (preg_match('/^([A-F0-9]{16})$/', $key, $itemHashMatch)) {
		return 1;
	} else {
		return 0;
	}
} # IsFingerprint()

function DBGetAuthorAlias ($key) { # returns author's alias
// 	if (!IsFingerprint($key)) {
// 		WriteLog('DBGetAuthorAlias: warning: called with invalid parameter! returning');
// 		return;
// 	} #todo re-add this sanity check
	WriteLog("DBGetAuthorAlias($key)");

	$key = SqliteEscape($key);

	if ($key) {
		$query = "SELECT alias FROM author_alias WHERE key = '$key'";
		$returnValue = SqliteGetValue($query);

		WriteLog('DBGetAuthorAlias: $returnValue = ' . $returnValue);

		return $returnValue;
	} else {
		return "";
	}
} # DBGetAuthorAlias()

function GetAlias($fingerprint, $noCache = 0) { # ; Returns alias for an identifier
	WriteLog("GetAlias($fingerprint, $noCache)");

	WriteLog('GetAlias: calling DBGetAuthorAlias()');
	$alias = DBGetAuthorAlias($fingerprint);

	if ($alias) {
		$alias = trim($alias);
		if ($alias && length($alias) > 24) {
			$alias = substr($alias, 0, 24);
		}

		return $alias;
	} else {
		$alias = GetConfig('prefill_username'); #guest...
		return $alias;
	}
} # GetAlias()

function WriteLog ($text, $dontEscape = 0) { // writes to debug log if enabled
// the debug log is stored as a static variable in this function
// when a blank (false) argument is passed, returns entire log as html
// $dontEscape means don't escape html entities
	#file_put_contents('/home/toshiba/hike/log/log.log', time() . ':php:' . $text . "\n", FILE_APPEND);

	static $logText; # stores log
	if (!$logText) {
		# initialize
		$logText = '';
	}
	if (!$text) {
		# return entire log if text is blank
		return $logText;
	}
	if ($dontEscape) {
		$logText .= '<tt class=advanced>' . time() . ':' . $text . "<br></tt>\n";
	} else {
		$logText .= '<tt class=advanced>' . time() . ':' . htmlspecialchars($text) . "<br></tt>\n";
	}
} # WriteLog()

function GetMyCacheVersion () { // returns current cache version
	$myCacheVersion = 'b';
	return $myCacheVersion;
} # GetMyCacheVersion()

function GetMyVersion () { // returns current git commit id
// it is cached in config/admin/my_version
// otherwise it's looked up with: git rev-parse HEAD
	WriteLog('GetMyVersion()');
	static $myVersion; // store version for future lookups here
	if ($myVersion) {
		WriteLog('GetMyVersion: return from static: ' . $myVersion);
		return $myVersion;
	}

	$myVersion = GetConfig('admin/my_version');
	if (!$myVersion) {
		WriteLog('GetMyVersion: git rev-parse HEAD... ');
		$myVersion = `git rev-parse HEAD`;
		WriteLog('GetMyVersion: got ' . $myVersion);

		//save to config so that we don't have to call git next time
		//PutConfig('admin/my_version', $myVersion);
		PutConfig('admin/my_version', $myVersion);
	}

	$myVersion = trim($myVersion);
	return $myVersion;
} # GetMyVersion()

function index ($string, $needle) { // emulates perl's index(), returning -1 when not found
	if (is_string($string)) {
		WriteLog('index: sanity check passed');
	} else {
		WriteLog('index: warning: sanity check FAILED');
		return '';
	}
	$strpos = strpos($string, $needle);
	if ($strpos === false) {
		return -1;
	} else {
		return $strpos;
	}
} # index()

function length ($string) { // emulates perl's length()
	return strlen($string);
} # length()

function GpgParsePubkey ($filePath) { // #todo parse file with gpg public key
	return array();
} # GpgParsePubkey()

function GetFileHash ($fileName) { // returns hash of file contents
// function GetItemHash ($fileName) {
// function GetHash ($fileName) {
	WriteLog("GetFileHash($fileName)");

	if (!$fileName || !file_exists($fileName)) {
		WriteLog('GetFileHash: warning: $fileName failed sanity check');
		return '';
	}

	return sha1_file($fileName);
} # GetFileHash()

// function GetFileHash ($fileName) { // returns hash of file contents
// // GetItemHash GetHash
// 	WriteLog("GetFileHash($fileName)");
//
// 	if ((strtolower(substr($fileName, length($fileName) - 4, 4)) == '.txt')) {
// 		$fileContent = GetFile($fileName);
//
// 		while (index($fileContent, "\n-- \n") > -1) { #\n--
// 			// exclude signature from hash content
// 			$fileContent = substr($fileContent, 0, index($fileContent, "\n-- \n")); #\n--
// 		}
//
// 		$fileContent = trim($fileContent);
//
// 		return sha1($fileContent);
// 	} else {
// 		return sha1_file($fileName);
// 	}
// } # GetFileHash()
//
function file_force_contents ($dir, $contents) { // ensures parent directories exist before writing file
// #todo clean this function up

	WriteLog("file_force_contents($dir, $contents)");

	$parts = explode('/', $dir);
	$file = array_pop($parts);
	$dir = '';

	foreach($parts as $part) {
		if (!is_dir($dir .= "/$part")) {
			WriteLog("file_force_contents: mkdir($dir)");
			mkdir($dir);
		}
	}

	return file_put_contents("$dir/$file", $contents);
} # file_force_contents()

function DoUpdate () { // #todo #untested
	$pwd = getcwd();
	WriteLog('DoUpdate: $pwd = ' . $pwd);
	$scriptDir = GetScriptDir();
	WriteLog('DoUpdate: $scriptDir = ' . $scriptDir);

	if (file_exists($scriptDir . '/update.pl')) {
		WriteLog('DoUpdate: update.pl found, calling update.pl --all');

		WriteLog('DoUpdate: cd "' . $scriptDir . '" ; perl ./update.pl');
		WriteLog(`cd "$scriptDir" ; perl ./update.pl`);

		WriteLog('DoUpdate: cd "' . $pwd . '"');
		WriteLog(`cd "$pwd"`);
	}
} # DoUpdate()

function DoConfigDump () { // #todo #untested
	require_once('config.php');
	return WriteConfigDump();
} # DoConfigDump()

function DoUpgrade () {
	$pwd = getcwd();
	WriteLog('$pwd = ' . $pwd);
	$scriptDir = GetScriptDir();
	WriteLog('$scriptDir = ' . $scriptDir);

	if (file_exists($scriptDir . '/upgrade.pl')) {
		WriteLog('upgrade.pl found, calling upgrade.pl');
		WriteLog('cd "' . $scriptDir . '" ; perl ./upgrade.pl');
		WriteLog(`cd "$scriptDir" ; perl ./upgrade.pl`);
		WriteLog('cd "' . $pwd . '"');
		WriteLog(`cd "$pwd"`);
	}
} # DoUpgrade()

function DoReindex () {
	$pwd = getcwd();
	WriteLog('DoReindex: $pwd = ' . $pwd);
	$scriptDir = GetScriptDir();
	WriteLog('DoReindex: $scriptDir = ' . $scriptDir);

	if (file_exists($scriptDir . '/index.pl')) {
		WriteLog('DoReindex: index.pl found, calling index.pl');
		$commandReindex = 'cd "' . $scriptDir . '" ; perl -T "' . $scriptDir . '/index.pl" --chain --all';
		WriteLog('DoReindex: $commandReindex = ' . $commandReindex);
		$reindexLog = shell_exec($commandReindex);
		WriteLog('DoReindex: $reindexLog = ' . $reindexLog);
		WriteLog('DoReindex: cd "' . $pwd . '"');
		WriteLog(`cd "$pwd"`);

		if (0) { #remake some key pages after a reindex #todo
			$commandMakePages = 'cd "' . $scriptDir . '" ; perl -T "' . $scriptDir . '/pages.pl -M settings -M new';
			$makePagesLog = shell_exec($commandMakePages);
		}

		return $reindexLog;
	}
} # DoReindex()

function DoFlush () {
	$pwd = getcwd();
	WriteLog('$pwd = ' . $pwd);
	$scriptDir = GetScriptDir();
	WriteLog('$scriptDir = ' . $scriptDir);

	if (file_exists($scriptDir . '/query/flush_no_keep.sh')) {
		WriteLog('query/flush_no_keep.sh found, calling query/flush_no_keep.sh');
		WriteLog('cd "' . $scriptDir . '" ; query/flush_no_keep.sh');
		WriteLog(`cd "$scriptDir" ; query/flush_no_keep.sh`);
		WriteLog('cd "' . $pwd . '"');
		WriteLog(`cd "$pwd"`);
	}
// 	if (file_exists($scriptDir . '/archive.pl')) {
// 		WriteLog('archive.pl found, calling archive.pl');
// 		WriteLog('cd "' . $scriptDir . '" ; perl ./archive.pl');
//
// 		WriteLog(`cd "$scriptDir" ; perl ./archive.pl`);
//
// 		WriteLog('cd "' . $pwd . '"');
//
// 		WriteLog(`cd "$pwd"`);
// 	}
} # DoFlush()

function FixConfigName ($configName) { # prepend 'setting/' to config paths as appropriate
	$notSetting = array('query', 'res', 'sqlite3', 'string', 'setting', 'template', 'theme');
	$notSettingFlag = 0; # should NOT be prefixed with setting/
	foreach ($notSetting as $notSettingItem) {
		if ($configName != 'theme' && substr($configName, 0, length($notSettingItem)) == $notSettingItem) {
			$notSettingFlag = 1;
		}
	}
	if (!$notSettingFlag) {
		WriteLog('GetConfig: adding setting/ prefix to $configName = ' . $configName);
		$configName = 'setting/' . $configName;
	} else {
		WriteLog('GetConfig: NOT adding setting/ prefix to $configName = ' . $configName);
	}

	return $configName;
} # FixConfigName()

function PutConfig ($configKey, $configValue) { # writes config value to config storage
# sub SetConfig {
	$configName = FixConfigName($configName);
	WriteLog("PutConfig($configKey, $configValue)");
	$configDir = GetDir('config'); // config is stored here #todo unhardcode
	$putFileResult = PutFile("$configDir/$configKey", $configValue);
	GetConfig($configKey, 'unmemo');
	return $putFileResult;
} # PutConfig()

function split2 ($separator, $array) {
	return explode($separator, $array);
} # split2()

function GetConfig ($configKey, $token = 0) { // get value for config value $configKey
	WriteLog('GetConfig(' . $configKey . ', $token = ' . $token . ')');

	// config is stored in config/
	// if not found in config/ it looks in default/
	// if it is in default/, it is copied to config/

	// 	// memoize #todo
	// 	static $configLookup;
	// 	if (!isset($configLookup)) {
	// 		$configLookup = array();
	// 	}
	// 	if ($configKey == 'unmemo') {
	// 		// memo reset
	// 		$configLookup = array();
	// 		return;
	// 	}
	// 	if ($token == 'unmemo') {
	// 		// memo reset
	// 		unset($configLookup[$configKey]);
	// 		return;
	// 	}

	//#todo finish porting from perl
	// 	if ($token && $token == 'unmemo') {
	// 		WriteLog('GetConfig: unmemo requested, complying');
	// 		# unmemo token to remove memoized value
	// 		if (exists($configLookup{$configName})) {
	// 			delete($configLookup{$configName});
	// 		}
	// 	}
	//
	// 	if (exists($configLookup{$configName})) {
	// 		WriteLog('GetConfig: $configLookup already contains value, returning that...');
	// 		WriteLog('GetConfig: $configLookup{$configName} is ' . $configLookup{$configName});
	//
	// 		return $configLookup{$configName};
	// 	}

	$configDir = GetDir('config'); // config is stored here
	$defaultDir = GetDir('default'); // defaults are stored here
	$pwd = getcwd();

	$configKey = FixConfigName($configKey);
	$configName = $configKey;

	if (
		$token != 'no_theme_lookup' &&
		$configName != "setting/theme" &&
		substr($configName, 0, 6) != 'theme/' &&
		GetThemeAttribute($configName)
	) {
		#$configLookup{$configName} = GetThemeAttribute($configName);
		#return $configLookup{$configName};
		return GetThemeAttribute($configName);
	}

	WriteLog('GetConfig('.$configKey.'); $pwd = "' . $pwd . '", $configDir = "' . $configDir . '", $defaultDir = "' . $defaultDir . '", pwd = "' . getcwd() . '"');
	WriteLog('GetConfig: Checking in ' . $configDir . '/' . $configKey );

	if (file_exists($configDir . '/' . $configKey)) {
		WriteLog('GetConfig: found in config/');
		$configValue = file_get_contents($configDir . '/' . $configKey);
	} elseif (file_exists($defaultDir . '/' . $configKey)) {
		WriteLog('GetConfig: not found in config/, but found in default/');
		WriteLog("GetConfig: copy ($defaultDir/$configKey, $configDir/$configKey);"); // copy to config/
		#todo ensure subdirs exist

		{ #ensure necessary subdirs exist before trying to create file
			$dirs = explode('/', $configDir . '/' . $configKey);
			WriteLog('GetConfig: ensuring subdirs exist for $configDir = ' . $configDir . '/' . $configKey . '; count($dirs) = ' . count($dirs));
			array_pop($dirs); // pop the filename
			$dirPath = '';
			foreach ($dirs as $dir) {
				$dirPath .= array_shift($dirs) . '/';
				WriteLog('GetConfig: $dirPath = ' . $dirPath);
				if (!file_exists($dirPath)) {
					WriteLog('GetConfig: mkdir(' . $dirPath . ')');
					mkdir($dirPath);
				}
			}
		}

		copy ($defaultDir . '/' . $configKey, $configDir . '/' . $configKey); // copy to config/
		//#todo this copy should be copy_with_dir_creation
		$configValue = file_get_contents($defaultDir . '/' . $configKey);
	} else {
		// otherwise return empty string
		// WriteLog('GetConfig: warning: else, fallthrough, for ' . $configKey);
		$configValue = '';
	}
//
// 	// store in memo
// 	$configLookup[$configKey] = $configValue;

	WriteLog('GetConfig: $configValue: ' . $configValue);
	$configValue = trim($configValue); // remove trailing \n and any other whitespace
	WriteLog('GetConfig: $configValue after trim: ' . $configValue);
	WriteLog('GetConfig("' . $configKey . '") = "' . $configValue . '"), returning');
	// notify log of what we found
	return $configValue;
} // GetConfig()

function GetTemplate ($templateKey) { // get template from config tree
// looks in theme directory first, so config/theme/ > default/theme/ > config/ > default/

	WriteLog("GetTemplate($templateKey)");
	$templateContent = GetThemeAttribute("template/$templateKey");

	if (!$templateContent) {
		$templateContent = GetConfig("template/$templateKey");
	}

	if ($templateContent) {
		return $templateContent;
	} else {
		WriteLog('GetTemplate: warning: $templateContent was FALSE for $templateKey = ' . $templateKey);
	}
} # GetTemplate()

function GetFile ($file) { // gets file contents
	$file = trim($file);

	WriteLog('GetFile: $file = ' . $file);

	if (!$file || !file_exists($file)) {
		if (GetConfig('debug')) {
			$dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
			$caller = isset($dbt[1]['function']) ? $dbt[1]['function'] : 'caller_missing';
			WriteLog('GetFile: warning: $file was not provided; caller = ' . $caller);
		}
		return '';
	}

	$fileContents = file_get_contents($file);

	if ($fileContents) {
		WriteLog('GetFile: length($fileContents) = ' . length($fileContents));
	} else {
		WriteLog('GetFile: warning: $fileContents is FALSE');
	}

	return file_get_contents($file);
} # GetFile()

function PutFile ($file, $content) { // puts file contents
	WriteLog("PutFile($file, (\$content)");
	#WriteLog("PutFile($file, $content");

	if (index($file, '..') != -1) {
		WriteLog('PutFile: warning: sanity check failed, $file contains ..');
		return '';
	}

	$pathArray = explode('/', $file);
	$filePathComma = '';
	$filePath = '';
	while ($pathArray) {
		WriteLog('PutFile: $pathArray = ' . print_r($pathArray, 1));
		$filePath .= $filePathComma . array_shift($pathArray);
		WriteLog('PutFile: $filePath = ' . $filePath);

		if ($pathArray) {
			if ($filePath && ! file_exists($filePath)) {
				WriteLog("PutFile: mkdir($filePath)");
				mkdir($filePath);
			}
		}

		$filePath .= $filePathComma;
		WriteLog('PutFile: $filePathComma = ' . $filePathComma);
		$filePathComma = '/';
	}

	$fileTemp = $file . ".tmp"; # my
	WriteLog('PutFile: $fileTemp = ' . $fileTemp);

	$putFileResult = file_put_contents($fileTemp, $content);
	WriteLog('PutFile: $putFileResult = ' . $putFileResult);

	if (file_exists($fileTemp)) {
		#bug sometimes the file goes away between the if statement check and the rename
		# should try/catch the error or something?

		try {
			$renameResult = rename($fileTemp, $file);
		} catch (Exception $e) {
			WriteLog('PutFile: rename fail: $e->getMessage() = ' . $e->getMessage());
		} finally {
			WriteLog('PutFile: rename success: $renameResult = ' . $renameResult);
		}
	} else {
		WriteLog('PutFile: warning: file_exists($fileTemp) is FALSE');
		return '';
	}

	return $putFileResult; #todo && $renameResult ?
} # PutFile()

function GetCache ($cacheName) { // get cache contents by key/name
	// comes from cache/ directory, under current git commit
	// this keeps cache version-specific

	static $cacheDir;
	if (!$cacheDir) {
		$cacheDir = GetDir('cache');
	}
	$myVersion = GetMyCacheVersion();

	// cache name prefixed by current version
	$cacheName = $cacheDir . '/' . $myVersion . '/' . $cacheName;

	if (file_exists($cacheName)) {
		// return contents of file at that path
		return GetFile($cacheName);
	} else {
		return '';
	}
} # GetCache()

function PutCache ($cacheName, $content) { // stores value in cache
//#todo sanity checks and error handling
	WriteLog("PutCache($cacheName, $content)");

	static $cacheDir;
	if (!$cacheDir) {
		$cacheDir = GetDir('cache');
	}
	$myVersion = GetMyCacheVersion();

	$cacheName = $cacheDir . '/' . $myVersion . '/' . $cacheName;

	WriteLog('PutCache: $cacheName = ' . $cacheName);

	return PutFile($cacheName, $content);
} # PutCache()

function UnlinkCache ($cacheName) { // removes cache by unlinking file it's stored in
	WriteLog('UnlinkCache(' . $cacheName . ')');

	static $cacheDir;
	if (!$cacheDir) {
		$cacheDir = GetDir('cache');
	}

	$myVersion = GetMyCacheVersion();

	$cacheName = $cacheDir . '/' . $myVersion . '/' . $cacheName;

	if (file_exists($cacheName)) {
		unlink($cacheName);
	}
} # UnlinkCache()

function CacheExists ($cacheName) { // Check whether specified cache entry exists, return 1 (exists) or 0 (not)
	static $cacheDir;
	if (!$cacheDir) {
		$cacheDir = GetDir('cache');
	}

	$myVersion = GetMyCacheVersion();

	$cacheName = $cacheDir . '/' . $myVersion . '/' . $cacheName;

	if (file_exists($cacheName)) {
		return 1;
	} else {
		return 0;
	}
} # CacheExists()

function StoreServerResponse ($message) { // adds server response message and returns message id
// stores message in cache/sm[message_id]
// returns message id which can be passed to next page load via ?message= parameter
	WriteLog("StoreServerResponse($message)");
	$message = trim($message);
	if ($message == '') {
		return;
	}

	#my
	$mesageId = '';

	#my
	$cookie = '0000000000000000';
	if (isset($_GET['cookie'])) {
		if (preg_match('/^([0-9A-F]{16})$/', $_GET['cookie'], $cookieMatch)) {
			$cookie = $cookieMatch[0];
		}
	}

	{
		# previous version, with a random component
		#$messageId = md5($message . time() . rand());

		# create a verifiable message id, allowing the server to later verify receipt of message
		$messageId = md5($message . time() . $cookie);
		$messageId = substr($messageId, 0, 8);
	}

	PutCache('response/' . $messageId, $message);
	WriteLog("StoreServerResponse: $messageId, cache written");
	return $messageId;
} # StoreServerResponse()

function RetrieveServerResponse ($messageId) { // retrieves response message for display by client and deletes it
	WriteLog("RetrieveServerResponse($messageId)");
	$message = GetCache('response/' . $messageId);
	if ($message) {
		if (!GetConfig('admin/php/debug')) {
			WriteLog("RetrieveServerResponse: Message found, removing.");
			// message was found, remove it
			// remove stored message if not in debug mode
			UnlinkCache('response/' . $messageId);
		} else {
			WriteLog("RetrieveServerResponse: Message found, not deleting because debug mode.");
			// $message .= '<font size="-2" title="This response message is sticky because admin/php/debug is true">*</font>';
		}
	} else {
		WriteLog('RetrieveServerResponse: warning: message not found!');
	}
	return $message;
} # RetrieveServerResponse()

function GetHtmlFilename ($hash) { // gets html filename based on hash
	// path for new html file
	$fileHtmlPath =
		substr($hash, 0, 2) .
		'/' .
		substr($hash, 2, 2) .
		'/' .
		substr($hash, 0, 8) .
		'.html'
	;

	return $fileHtmlPath;
} # GetHtmlFilename()

function RedirectWithResponse ($url, $message) { // redirects to page with server message parameter added to url
// calls StoreServerResponse($message)
// then creates url with message= parameter
// sends Location: header to redirect to said url

	WriteLog("RedirectWithResponse($url, $message)");

	// should only redirect once per session
	static $redirected;
	if (isset($redirected) && $redirected > 0) {
		WriteLog('RedirectWithResponse: warning: called more than once!');
		return;
	}
	if (!$redirected) {
		$redirected = 1;
	} else {
		$redirected++;
	}

	if (!$message) {
		// an empty message creates problems
		$message = ';';
	}

	if (headers_sent()) {
		// problem, can't redirect if headers already sent;
		// we will print a message instead, but this is definitely a problem

		WriteLog('RedirectWithResponse: warning: Trying to redirect when headers have already been sent!');
	}

	$responseId = StoreServerResponse($message);

	if (substr($url, 0, 1) == '/') {
	// todo perhaps account for './' also?
		$protocol = 'http';
		if (isset($_SERVER['HTTPS'])) {
			$protocol = 'https';
		}

		$urlAuthPrefix = '';
		/*if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
			$urlAuthPrefix = urlencode($_SERVER['PHP_AUTH_USER']) . ':' . urlencode($_SERVER['PHP_AUTH_PW']) . '@';
		}*/

		if (isset($_SERVER['HTTP_HOST'])) {
			$url = $protocol . '://' . $urlAuthPrefix . $_SERVER['HTTP_HOST'] . $url;
		}
		elseif (GetConfig('admin/my_domain')) {
			$url = 'http://' . $urlAuthPrefix . GetConfig('admin/my_domain') . $url;
		}
	}

	if (index($url, '?') < 0) {
		// no question mark, append ?message=
		$redirectUrl = $url . '?message=' . $responseId;
	} else {
		// there's already a question mark, we need to use the & syntax
		if (substr($url, strlen($url) - 1, 1) == '&' || substr($url, strlen($url) - 1, 1) == '?') {
			// query ends with & or ? already, we don't need to add one
			$redirectUrl = $url . 'message=' . $responseId;
		} else {
			// there's no & at the end, so append &message
			$redirectUrl = $url . '&message=' . $responseId;
		}
	}

	if (GetConfig('admin/php/debug') || GetConfig('admin/php/debug_server_response')) {
		if (!headers_sent()) {
			// #warning, this is not a good pattern, don't copy this code. the html will be printed unescaped.
			// doing it in this case because we want to make a clickable link
			WriteLog( '<a href="' . $redirectUrl . '">' . $redirectUrl . '</a>' . '<font color=red>' . '(redirect paused because admin/php/debug or admin/php/debug_server_response is true)' . '</font>' , 1 );

			// not templated because it is a debugging thing
			print '<div style="background-color: yellow; border: 3pt double red">';
			print 	'<font color=red size="+2">';
			print 		'DEBUG MODE: redirect paused; ';
			print 	'</font><br>';
			#print 	'<form action="' . $redirectUrl . '" method=GET><input type=submit value=Continue></form>';
			#doesn't work right without splitting the param
			print 	'<a href="' . $redirectUrl . '">Continue: ';
			print 		$redirectUrl;
			print 	'</a>';
			print 	'<br>';
			print 	'Message: <b>' . htmlspecialchars($message) . '</b>'; #todo remove dep
			print 	'<br>';
			print 	'Method: ' . ($_GET ? 'GET' : ($_POST ? 'POST' : 'OTHER??'));
			print 	'<br><hr>';
			print 	'<tt>';
			print 	'admin/php/debug=' . GetConfig('admin/php/debug') . '; <br>'; #todo remove dep
			print 	'admin/php/debug_server_response=' . GetConfig(' admin/php/debug_server_response') . '; '; #todo remove dep
			print 	'</tt>';
			print '</div><hr>';
		}
	} else {
		// do the redirect
		if (!headers_sent()) {
			if (0 & $message == 'Goodbye!') {
				# this de-authenticates http auth, but causes many problems
				header('WWW-Authenticate: Basic realm="Goodbye!"');
				header('HTTP/1.0 401 Unauthorized');
			} else {
				header('Location: ' . $redirectUrl);
			}
		} else {
			WriteLog('RedirectWithResponse: warning: wanted to send Location header, but headers already sent');
		}
	}
} # RedirectWithResponse()

function GetDialogX ( # body, title, headings, status, menu
	$windowBody,
	$windowTitle = '',
	$columnHeadings = '',
	$windowStatus = '',
	$windowMenubarContent = ''
) { // returns html for window template
# function GetWindowTemplate {
// uses template/window/standard.template by default

	// stores number of columns if they exist
	// if no columns, remains at 0
	// whether there are columns or not determines:
	// * column headers
	// * colspan= in non-column cells
	$contentColumnCount = 0;

	// base template
	$windowTemplate = GetTemplate('html/window/standard.template');

	$showButtons = GetConfig('html/window_titlebar_buttons'); # titlebar hide and skip buttons;
	WriteLog('GetDialogX: $showButtons = ' . $showButtons);

	// titlebar, if there's a title
	if ($windowTitle) {
		//
		// 		if ($showButtons && GetConfig('admin/js/dragging')) {
		// 			WriteLog('GetDialogX: $showButtons = ' . $showButtons . '; $windowTitle = ' . $windowTitle . '; dragging = ' . GetConfig('admin/js/dragging'));
		//
		// 			$btnCloseCaption = '{-}'; # needs to match one other place in dragging.js #collapseButton
		// 			$windowTitlebar = GetTemplate('html/window/titlebar_with_button.template'); #window_titlebar_buttons
		//
		// 			$windowTitlebar = InjectJs($windowTitlebar, array('titlebar_with_button'));
		//
		// 			$windowTitlebar = str_replace('$windowTitle', $windowTitle, $windowTitlebar);
		// 			#$windowTitlebar = str_replace('$windowAnchor', $windowAnchor, $windowTitlebar);
		// 			$windowTemplate = str_replace('$windowTitlebar', $windowTitlebar, $windowTitlebar);
		// 			$windowTemplate = str_replace('$btnCloseCaption', $btnCloseCaption, $windowTitlebar);
		// 			#$contentColumnCount = 2;
		// 		} else {
		// 			$windowTitlebar = GetTemplate('html/window/titlebar.template');
		// 			#
		// 			#			if (GetConfig('admin/js/enable') && GetConfig('admin/js/dragging')) {
		// 			#				$windowTitlebar = AddAttributeToTag($windowTemplate, 'a href=#$windowAnchor', 'onfocus', 'document.title=this.innerHTML;');
		// 			#				$windowTitlebar = AddAttributeToTag($windowTemplate, 'a href=#$windowAnchor', 'onclick', 'document.title=this.innerHTML;');
		// 			#			}
		// 			#
		// 			$windowTitlebar = str_replace('$windowTitle', $windowTitle, $windowTitlebar);
		// 			$windowTitlebar = str_replace('$windowAnchor', $windowAnchor, $windowTitlebar);
		// 			$windowTemplate = str_replace('$windowTitlebar', $windowTitlebar, $windowTitlebar);
		// 		}
		if (1 || $showButtons && GetConfig('admin/js/dragging')) {
			$windowTitlebar = GetTemplate('html/window/titlebar.template');
			$windowTitlebar = str_replace('$windowTitle', $windowTitle, $windowTitlebar);

			$windowTemplate = str_replace('$windowTitlebar', $windowTitlebar, $windowTemplate);
		} else {
		}
	} else {
		$windowTemplate = str_replace('$windowTitlebar', '', $windowTemplate);
	}

	// menubar, if there is menubar content
	if ($windowMenubarContent) {
		$windowMenubar = GetTemplate('html/window/menubar.template');
		$windowMenubar = str_replace('$windowMenubarContent', $windowMenubarContent, $windowMenubar);

		$windowTemplate = str_replace('$windowMenubar', $windowMenubar, $windowTemplate);
	} else {
		$windowTemplate = str_replace('$windowMenubar', '', $windowTemplate);
		//#todo currently results in an empty menubar
	}

	// column headings from the $columnHeadings variable
	if ($columnHeadings) {
		$windowHeaderTemplate = GetTemplate('html/window/header_wrapper.template');
		$windowHeaderColumns = '';
		$columnsArray = explode(',', $columnHeadings);

		$printedColumnsCount = 0;
		foreach ($columnsArray as $columnCaption) {
			$printedColumnsCount++;

			$columnHeaderTemplate = GetTemplate('html/window/header_column.template');
			if ($printedColumnsCount >= count($columnsArray)) {
				$columnCaption .= '<br>'; //# for no-table browsers
			}

			$columnHeaderTemplate = str_replace('$headerCaption', $columnCaption, $columnHeaderTemplate);
			$windowHeaderColumns .= $columnHeaderTemplate;
		}

		$windowHeaderTemplate = str_replace('$windowHeadings', $windowHeaderColumns, $windowHeaderTemplate);
		$windowTemplate = str_replace('$windowHeader', $windowHeaderTemplate, $windowTemplate);

		$contentColumnCount = count($columnsArray);
	} else {
		$windowTemplate = str_replace('$windowHeader', '', $windowTemplate);
		$contentColumnCount = 0;
	}

	// main window content, aka body
	if ($windowBody) {
		if (index(strtolower($windowBody), '<tr') == -1) {
			// put content into a table row and cell if missing
			$windowBody = '<tr class=content><td>' . $windowBody . '</td></tr>';
		}

		$windowTemplate = str_replace('$windowBody', $windowBody, $windowTemplate);
	} else {
		$windowTemplate = str_replace('$windowBody', '', $windowTemplate);
	}

	// status bar
	if ($windowStatus) {
		$windowStatusTemplate = GetTemplate('html/window/status.template');
		$windowStatusTemplate = str_replace('$windowStatus', $windowStatus, $windowStatusTemplate);
		$windowTemplate = str_replace('$windowStatus', $windowStatusTemplate, $windowTemplate);
	} else {
		$windowTemplate = str_replace('$windowStatus', '', $windowTemplate);
	}

	// fill in the column count if necessary
	if ($contentColumnCount) {
		$windowTemplate = str_replace('$contentColumnCount', $contentColumnCount, $windowTemplate);
	} else {
		$windowTemplate = str_replace('$contentColumnCount', '', $windowTemplate);
	}

	$windowTemplate = str_replace('$colorWindow', GetThemeColor('window'), $windowTemplate);
	$windowTemplate = str_replace('$colorTitlebarText', GetThemeColor('titlebar_text'), $windowTemplate);
	$windowTemplate = str_replace('$colorTitlebar', GetThemeColor('titlebar'), $windowTemplate);
	$windowTemplate = str_replace('$windowAnchor', '', $windowTemplate);

	return $windowTemplate;
} # GetDialogX()

function GetActiveThemes () { # return list of active themes (config/setting/theme)
# function GetThemes () {
# function ListThemes () {
# function GetThemeList () {
# function GetThemesList () {
# function GetActiveThemesList () {
	WriteLog('GetActiveThemes()');
	$themesValue = GetConfig('theme');
	if ($themesValue) {
		#$themesValue =~ s/[\s]+/ /g; # strip extra whitespace and convert to spaces
		$themesValue = preg_replace('/[\s]+/', ' ', $themesValue);
		$activeThemes = explode(' ', $themesValue); # split by spaces
		foreach ($activeThemes as $themeName) {
			#todo some validation
		}
		return $activeThemes;
	} else {
		$dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
		$caller = isset($dbt[1]['function']) ? $dbt[1]['function'] : 'caller_missing';
		WriteLog('GetActiveThemes: warning: $themesValue is FALSE; caller = ' . $caller);
		return '';
	}
} # GetActiveThemes()

function GetThemeAttribute ($attributeName) { // returns a config overlay value from config/theme/...
// uses GetConfig(), which means look first in config/ and then in default/

	WriteLog('GetThemeAttribute(' . $attributeName . ')');

	#$themesValue = GetConfig('theme');
	#$themesValue = preg_replace('/[\s]+/', ' ', $themesValue);
	#$activeThemes = explode(' ', $themesValue);
	$activeThemes = GetActiveThemes();

	foreach ($activeThemes as $themeName) {
		$attributePath = 'theme/' . $themeName . '/' . $attributeName;

		#todo sanity checks
		$attributeValue = GetConfig($attributePath, 'no_theme_lookup');

		WriteLog('GetThemeAttribute: $attributeName = ' . $attributeName . '; $themeName = ' . $themeName . '; $attributePath = ' . $attributePath);

		if ($attributeValue && trim($attributeValue) != '') {
			WriteLog('GetThemeAttribute: ' . $attributeName . ' + ' . $themeName . ' -> ' . $attributePath . ' -> length($attributeValue) = ' . length($attributeValue));
			if ($attributeName == 'additional.css') {
				$returnValue .= $attributeValue || '';
				$returnValue .= "\n";
				if (GetConfig('html/css_theme_concat')) {
					# nothing
					# concatenate all the selected themes' css together
				} else {
					break;
				}
			} else {
				$returnValue = $attributeValue || '';
				break;
			}
		} # if ($attributeValue)
	} # foreach $themeName (@activeThemes)

	WriteLog('GetThemeAttribute: $attributeName: ' . $attributeName . '; $attributePath: ' . $attributePath . '; $attributeValue: ' . $attributeValue);

	return $attributeValue;
} # GetThemeAttribute()

function GetThemeColor ($colorName) { // returns theme color based on setting/theme
	WriteLog('GetThemeColor: $colorName = ' . $colorName);
	
	if (GetConfig('html/monochrome')) { # GetThemeColor()
		WriteLog('GetThemeColor: config/html/mourn = TRUE');

		if (index(lc($colorName), 'text') != -1 || index(lc($colorName), 'link') != -1) {
			if (index(lc($colorName), 'back') != -1) {
				return GetConfig('html/color/background'); # #BackgroundColor
			} else {
				return GetConfig('html/color/text'); # #TextColor
			}
		} else {
			return GetConfig('html/color/background'); # #BackgroundColor
		}
	}

	if (GetConfig('html/mourn')) { # GetThemeColor()
		WriteLog('GetThemeColor: config/html/mourn = TRUE');

		if (index(lc($colorName), 'text') != -1 || index(lc($colorName), 'link') != -1) {
			if (index(lc($colorName), 'back') != -1) {
				return '#000000'; # #BackgroundColor
			} else {
				return '#c0c0c0'; # #TextColor
			}
		} else {
			return '#000000'; # #BackgroundColor
		}
	}

	$colorName = 'color/' . $colorName;
	$color = GetThemeAttribute($colorName);

	if (!$color) {
		$color = '#00ff00';
		WriteLog("GetThemeColor: WARNING: Value for $colorName not found");
	}

	if (preg_match('/^[0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F]$/', $color)) {
	// color value looks like a 6-digit hex value without a # prefix, so add the prefix
		WriteLog('GetThemeColor: Color found missing its # prefix: ' . $color);

		$color = '#' . $color;

		WriteLog('GetThemeColor: Prefix added: ' . $color);
	} else {
		WriteLog('GetThemeColor: Found nice color: ' . $color);
	}

	WriteLog('GetThemeColor: Returning for ' . $colorName . ': ' . $color);

	return $color;
} # GetThemeColor()

function GetTime () { // returns time()
// why this wrapper? so that we can use a different base for epoch time than 1970-01-01
	return time();
} # GetTime()

function AddAttributeToTag ($html, $tag, $attributeName, $attributeValue) { // adds attr=value to html tag;
	WriteLog('AddAttributeToTag() begin');

	$tagAttribute = '';
	if ($attributeValue == '') {
		WriteLog('AddAttributeToTag: value is empty string');
		// no value
		$tagAttribute = $attributeName;
	}
	elseif (preg_match('/\s/', $attributeValue) || index($attributeValue, "'") != -1 || index($attributeValue, '(') != -1 || index($attributeValue, ')') != -1) {
		WriteLog('AddAttributeToTag: whitespace match TRUE');
		// attribute value contains whitespace, must be enclosed in double quotes
		$tagAttribute = $attributeName . '="' . $attributeValue . '"';
	}
	else {
		WriteLog('AddAttributeToTag: whitespace match FALSE');
		$tagAttribute = $attributeName . '=' . $attributeValue . '';
	}

	WriteLog('AddAttributeToTag: $tagAttribute is ' . $tagAttribute);

	{ #todo this is sub-optimal, and tag case is not preserved
		$htmlBefore = $html;
		$html = str_ireplace('<' . $tag . ' ', '<' . $tag . ' ' . $tagAttribute . ' ', $html);
		if ($html == $htmlBefore) {
			$html = str_ireplace('<' . $tag . '', '<' . $tag . ' ' . $tagAttribute . ' ', $html);
		}
		if ($html == $htmlBefore) {
			$html = str_ireplace('<' . $tag . '>', '<' . $tag . ' ' . $tagAttribute . '>', $html);
		}
		if ($html == $htmlBefore) {
			$html = str_ireplace('<' . $tag . '>', '<' . $tag . ' ' . $tagAttribute . '>', $html);
		}
		if ($html == $htmlBefore) {
			WriteLog('AddAttributeToTag: warning: nothing was changed');
		}
	}

// 	// #todo this is sub-optimal
// 	$html = preg_replace("/\<$tag\w/i", "<$tag $tagAttribute ", $html);
// 	$html = preg_replace("/\<$tag/i", "<$tag $tagAttribute ", $html); // is this right/necessary? #todo
// 	$html = preg_replace("/\<$tag\>/i", "<$tag $tagAttribute>", $html);

	return $html;
} # AddAttributeToTag()

function GetClockFormattedTime () { // returns current time in appropriate format from config
//formats supported: union, epoch (default)

	WriteLog("GetClockFormattedTime()");

	$clockFormat = GetConfig('html/clock_format');

	if ($clockFormat == '24hour') {
		$time = GetTime();

		// #todo make it perl-equivalent with localtime($time)
		$hours = strftime('%H', $time);
		$minutes = strftime('%M', $time);
		// $seconds = strftime('%S', $time);

		// $clockFormattedTime = $hours . ':' . $minutes . ':' . $seconds;
		$clockFormattedTime = $hours . ':' . $minutes;

		WriteLog("GetClockFormattedTime: return $clockFormattedTime");

		return $clockFormattedTime;
	}

	if ($clockFormat == 'union') {
		// union square clock format
		$time = GetTime() - 3600 * 4; // hard-coded correction, should be timezone convert #todo

		// #todo make it perl-equivalent with localtime($time)
		$hours = strftime('%H', $time);
		$minutes = strftime('%M', $time);
		$seconds = strftime('%S', $time);

		$milliseconds = '000';
		$hoursR = 23 - $hours;
		if ($hoursR < 10) {
			$hoursR = '0' . $hoursR;
		}

		$minutesR = 59 - $minutes;
		if ($minutesR < 10) {
			$minutesR = '0' . $minutesR;
		}

		$secondsR = 59 - $seconds;
		if ($secondsR < 10) {
			$secondsR = '0' . $secondsR;
		}

		#
		# if (milliseconds < 10) {
		# 	milliseconds = '00' + '' + milliseconds;
		# } else if (milliseconds < 100) {
		# 	milliseconds = '0' + '' + milliseconds;
		# }
		#

		$clockFormattedTime = $hours . $minutes . $seconds . $milliseconds . $secondsR . $minutesR . $hoursR;

		WriteLog("GetClockFormattedTime: return $clockFormattedTime");

		return $clockFormattedTime;
	}

	// default is epoch

	WriteLog("GetClockFormattedTime: return default, aka epoch, aka GetTime()");

	return GetTime();
} # GetClockFormattedTime()

function IsItem ($string) { # returns 1 if parameter is in item hash format (40 or 8 lowercase hex chars), 0 otherwise
	WriteLog("IsItem($string)");

	if (!$string) {
		WriteLog("IsItem: NO STRING!");
		return 0;
	}

	if (preg_match('/^([0-9a-f]{40})$/', $string, $matches)) {
		WriteLog("IsItem: matched 40 chars $1");
		return $matches[0];
	}

	if (preg_match('/^([0-9a-f]{8})$/', $string, $matches)) {
		WriteLog("IsItem: matched 8 chars $1");
		return $matches[0];
	}

	WriteLog("IsItem: NO MATCH!");
	return 0;
} # IsItem()

function setcookie2 ($key, $value, $updateCurrent = 0) { // sets cookie with ie3 compatibility
	WriteLog('setcookie2(' . $key . ',' . $value . ')');

	$cookieDateFormat = "D, d-M-Y H:i:s";
	$cookieDate = date($cookieDateFormat, time() + 86400*2*365) . ' GMT';
	// timezone hard-coding is not important here

	Header('Set-Cookie: ' . $key . '=' . $value . '; expires=' . $cookieDate . '; path=/', false);

	if ($updateCurrent) {
		$_COOKIE[$key] = $value;
	}
} # setcookie()

function unsetcookie2 ($key) { // remove cookie in most compatible way
	WriteLog('unsetcookie2(' . $key . ')');

	Header("Set-Cookie: $key=deleted; expires=Thu, 01-Jan-1970 00:00:01 GMT; path=/", false);
} # unsetcookie2()

function IndexTextFile ($filePath) {
	WriteLog('IndexTextFile($filePath = ' . $filePath . ')');

	$scriptDir = GetScriptDir();
	$pwd = getcwd();

	if (!$scriptDir || !$pwd) {
		WriteLog('IndexTextFile: warning: sanity check failed, no $scriptDir or no $pwd');
		return '';
	}

	if (file_exists($filePath)) {
		WriteLog("IndexTextFile: cd $scriptDir ; ./index.pl \"$filePath\"");
		WriteLog(`cd $scriptDir ; ./index.pl "$filePath"`);

		#WriteLog(`find html/txt -printf '%C@ %p\n' | grep "\.txt$" | sort | tail -n 5 | cut -d ' ' -f 2- | xargs ./index.pl`);

		if ($pwd) {
			WriteLog("IndexTextFile: cd $pwd");
			WriteLog(`cd $pwd`);
		}

		$newHash = GetFileHash($filePath); # my
		if ($newHash) {
			return $newHash;
		} else {
			WriteLog('IndexTextFile: warning: $newHash is FALSE');
			return '';
		}
	} else {
		WriteLog('IndexTextFile: warning: $filePath does not exist');
		return '';
	}

	//
	// 	WriteLog("IndexTextFile: cd $scriptDir ; ./pages.pl \"$hash\"");
	// 	WriteLog(`cd $scriptDir ; ./pages.pl "$hash"`);
} # IndexTextFile()

function MakePage ($pageName) {
	#todo sanity checks
	$scriptDir = GetScriptDir();
	$pwd = getcwd();

	WriteLog("cd $scriptDir ; ./pages.pl \"$pageName\"");
	WriteLog(`cd $scriptDir ; ./pages.pl "$pageName"`);

	if ($pwd) {
		WriteLog("cd $pwd");
		WriteLog(`cd $pwd`);
	}
//
// 	WriteLog("cd $scriptDir ; ./pages.pl \"$hash\"");
// 	WriteLog(`cd $scriptDir ; ./pages.pl "$hash"`);
} # IndexNewFile()

require_once('store_new_comment.php');

require_once('process_new_comment.php');

function GetItemPlaceholderPage ($comment, $hash, $fileUrlPath, $filePath) { # generate temporary placeholder page for comment
# this page is typically overwritten later by the proper page generator
# but this gives us somewhere to go if the generator fails for any reason
# and allows us to acknowledge message receipt to the user

	// escape comment for output as html
	$commentHtml =                             #todo make this more readable
		nl2br(                                 # replace \n with <br>
			str_replace(                       # preserve indentation
				'  ',
				' &nbsp;',
				htmlspecialchars(              # escape <>&"
					wordwrap(                  # wrap to 80 columns
						trim($comment),
						80,
						' ',
						true
					),
					ENT_QUOTES|ENT_SUBSTITUTE,
					"UTF-8"
				)
			),
			0
		)
	;

	// template for temporary placeholder for html file
	// overwritten later by update.pl
	$commentHtmlTemplate = GetTemplate('html/item_processing.template');

	if (GetConfig('debug')) {
		$commentHtmlTemplate = str_replace('</head>', '<meta http-equiv=refresh content=5></head>', $commentHtmlTemplate);
	}

	// get theme name from config and associated background and foreground colors

	$themesValue = GetConfig('theme');
	$themesValue = preg_replace('/[\s]+/', ' ', $themesValue);
	$activeThemes = explode(' ', $themesValue);
	$themeName = $activeThemes[0];
	WriteLog('$themeName = ' . $themeName);

	{ // color values
		$colorBackground = GetConfig('theme/' . $themeName . '/color/background');
		$colorWindow = GetConfig('theme/' . $themeName . '/color/window');
		$colorText = GetConfig('theme/' . $themeName . '/color/text');
		$colorLink = GetConfig('theme/' . $themeName . '/color/text');
		$colorVlink = GetConfig('theme/' . $themeName . '/color/text');

		WriteLog('GetItemPlaceholderPage: $colorWindow = ' . $colorWindow);
		WriteLog('GetItemPlaceholderPage: $colorText = ' . $colorText);

		// replace placeholders with colors in template
		$commentHtmlTemplate = str_replace('$colorBackground', $colorBackground, $commentHtmlTemplate);
		$commentHtmlTemplate = str_replace('$colorWindow', $colorWindow, $commentHtmlTemplate);
		$commentHtmlTemplate = str_replace('$colorText', $colorText, $commentHtmlTemplate);
		$commentHtmlTemplate = str_replace('$colorLink', $colorVlink, $commentHtmlTemplate);
		$commentHtmlTemplate = str_replace('$colorVlink', $colorVlink, $commentHtmlTemplate);
		$commentHtmlTemplate = str_replace('$hash', $hash, $commentHtmlTemplate);
	}

	$commentHtml = '<pre>' . "\n" . $commentHtml . "\n" . '</pre>';

	// insert html-ized comment into template
	$commentHtmlTemplate = str_replace('</body>', $commentHtml . '</body>', $commentHtmlTemplate);

	if ($hash && $fileUrlPath) {
		#my
		$commentInfo = '';

		#todo sanity checks

		#$fileTxtPath = str_replace(GetDir('txt'), '', $filePath); #my

		$commentInfo .= 'Hash: ' . $hash . '(' . substr($hash, 0, 8) . ')' . '<br>';
		$commentInfo .= 'URL: ' . '<a href="' . $fileUrlPath . '">' . $fileUrlPath . '</a>' . '<br>';
		#$commentInfo .= 'Text: ' . '<a href="/txt/' . $fileTxtPath . '">/txt/' . $fileTxtPath . '</a>' . '<br>';

		$commentHtmlTemplate = str_replace('Message received, and scheduled to be posted.', 'Message received, and scheduled to be posted.<br><br>' . $commentInfo, $commentHtmlTemplate);
	}

	return $commentHtmlTemplate;
} # GetItemPlaceholderPage()

function ProcessNewCommentReturnItemUrl ($comment, $replyTo) { // saves new comment to .txt file and calls indexer
	$hash = ''; // hash of new comment's contents
	$fileUrlPath = ''; // path file should be stored in based on $hash
	$scriptDir = GetScriptDir();

	WriteLog('ProcessNewCommentReturnItemUrl(...)');

	if ($comment) {
		$fileName = StoreNewComment($comment, $replyTo); // ProcessNewCommentReturnItemUrl()
	}

	if ($fileName) {
		// remember current working directory, we'll need it later
		$pwd = getcwd(); #my
		WriteLog('ProcessNewCommentReturnItemUrl: $pwd = ' . $pwd);

		// script directory is one level up from current directory,
		// which we expect to be called "html"
		$scriptDir = GetScriptDir(); #my
		WriteLog('ProcessNewCommentReturnItemUrl: $scriptDir = ' . $scriptDir);

		// $txtDir is where the text files live, in html/txt
		$txtDir = $pwd . '/txt/'; #my
		WriteLog('ProcessNewCommentReturnItemUrl: $txtDir = ' . $txtDir);

		// $htmlDir is the same as current directory
		$htmlDir = $pwd . '/'; #my
		WriteLog('ProcessNewCommentReturnItemUrl: $htmlDir = ' . $htmlDir);


		// now we can get the "proper" hash,
		// which is for some reason different from sha1($comment), as noted above
		$hash = GetFileHash($fileName);
		WriteLog('ProcessNewCommentReturnItemUrl: $hash = ' . $hash);

		// hash-named files are stored under /ab/cd/ two-level directory prefix
		{ // create prefix subdirectories under txt/
			if (!file_exists($txtDir . substr($hash, 0, 2))) {
				mkdir($txtDir . substr($hash, 0, 2));
			}

			if (!file_exists($txtDir . substr($hash, 0, 2) . '/' . substr($hash, 2, 2))) {
				mkdir($txtDir . substr($hash, 0, 2) . '/' . substr($hash, 2, 2));
			}
		}
		{ // create prefix subdirectories under ./ (html/)
			if (!file_exists('./' .substr($hash, 0, 2))) {
				mkdir('./' . substr($hash, 0, 2));
			}

			if (!file_exists('./' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2))) {
				mkdir('./' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2));
			}
		}

		// path for new txt file
		$filePath =
			$txtDir .
			substr($hash, 0, 2) .
			'/' .
			substr($hash, 2, 2) .
			'/' .
			$hash . '.txt'
		;

		$fileHtmlPath = './' . GetHtmlFilename($hash); // path for new html file
		$fileUrlPath = '/' . GetHtmlFilename($hash); // client's (browser's) path to html file
		// save new post to txt file

		WriteLog("ProcessNewCommentReturnItemUrl: file_put_contents($filePath, $comment);");

		file_put_contents($filePath, $comment);
		// this could probably just be a rename() #todo

		// check if html file already exists. if it does, leave it alone
		if (!file_exists($fileHtmlPath)) {
			$commentHtmlTemplate = GetItemPlaceholderPage($comment, $hash, $fileUrlPath, $filePath);

			// store file
			WriteLog("ProcessNewCommentReturnItemUrl: file_put_contents($fileHtmlPath, $commentHtmlTemplate)");

			file_put_contents($fileHtmlPath, $commentHtmlTemplate);
		}

		if (GetConfig('admin/php/post/index_file_on_post') && isset($filePath)) { # ProcessNewCommentReturnItemUrl()
			if (!file_exists($filePath)) {
				WriteLog('ProcessNewCommentReturnItemUrl: warning: file_exists($filePath) is FALSE');
			} else {
				$newFileHash = IndexTextFile($filePath);
				if ($newFileHash) {
					MakePage($newFileHash);
				} else {
					WriteLog('ProcessNewCommentReturnItemUrl: warning: $newFileHash is false after IndexTextFile()');
				}
			}
		} # index_file_on_post

		if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
			$referer = $_SERVER['HTTP_REFERER'];

			// #todo uncomment this once this script is working
			//header('Location: ' . $referer);
		} else {
			// #todo uncomment this once this script is working
			//header('Location: /write.html');
		}

		WriteLog('ProcessNewCommentReturnItemUrl: $fileUrlPath = ' . $fileUrlPath);

		return $fileUrlPath;
	} # isset($comment) && $comment

	WriteLog('ProcessNewCommentReturnItemUrl: return $hash = ' . $hash);

	return $hash;
} # ProcessNewCommentReturnItemUrl()

function GetItemHtmlLink ($hash, $linkCaption, $hashAnchor) { # $hash, [link caption], [#anchor] ; returns <a href=...
# sub GetItemLink {
# sub GetLink {
	#my $hash = shift;

	if ($hash = IsItem($hash)) {
		#ok
	} else {
		WriteLog('GetItemHtmlLink: warning: sanity check failed on $hash');
		return '';
	}

	if ($hash) {
		#todo templatize this
		#my $linkCaption = shift;
		if (!$linkCaption) {
			$linkCaption = substr($hash, 0, 8) . '..';
		}

		$shortHash = substr($hash, 0, 8); #my

		#my $hashAnchor = shift;
		if ($hashAnchor) {
			if (substr($hashAnchor, 0, 1) != '#') {
				$hashAnchor = '#' . $hashAnchor;
			}
		} else {
			$hashAnchor = '';
		}

		$linkCaption = HtmlEscape($linkCaption);

		$htmlFilename = GetHtmlFilename($hash); #my
		$linkPath = $htmlFilename; #my
		if (GetConfig('admin/php/enable') && GetConfig('admin/php/url_alias_friendly')) {
			$linkPath = substr($hash, 0, 8);
		}

		$itemLink = ''; #my

		if (
			GetConfig('html/overline_links_with_missing_html_files') &&
			! file_exists(GetDir('html') . '/' . $htmlFilename)
		) {
			# html file does't exist, annotate link to indicate this
			# the html file may be generated as needed
			$itemLink = '<a href="/' . $linkPath . $hashAnchor . '" style="text-decoration: overline">' . $linkCaption . '</a>';
		} else {
			# html file exists, nice
			$itemLink = '<a href="/' . $linkPath . $hashAnchor . '">' . $linkCaption . '</a>';
		}

		if (GetConfig('admin/js/enable') && GetConfig('admin/js/dragging')) {
			# add onclick event to spawn a dialog in-page instead of navigating
			# to a different page if those libraries are available and the setting is enabled
			$itemLink = AddAttributeToTag(
				$itemLink,
				'a ',
				'onclick',
				"
					if (
						(!window.GetPrefs || GetPrefs('draggable_spawn')) &&
						(window.FetchDialogFromUrl) &&
						document.getElementById
					) {
						if (document.getElementById('$shortHash')) {
							SetActiveDialog(document.getElementById('$shortHash'));
							return false;
						} else {
							return FetchDialogFromUrl('/dialog/$htmlFilename');
						}
					}
				"
			);
		} # if (GetConfig('admin/js/enable') && GetConfig('admin/js/dragging'))

		return $itemLink;
	} else {
		WriteLog('GetItemHtmlLink: warning: no $hash after first sanity check!');
		return '';
	}
} # GetItemHtmlLink()

// function UrlEncode () {
// ^-- use built-in urlencode() --^

//
// function GetPageHeader ($pageType, $title) { # $pageType, $title ; returns html for page header
//     if (!$title) {
//         $title = ucfirst($pageType);
//     }
//
//     if (
//         !$pageType ||
//             (index($pageType, ' ') != -1)
//     ) {
//         WriteLog('GetPageHeader: warning: $pageType failed sanity check; caller = ' . join(',', caller));
//     }
//
//     if (!$pageType) {
//         WriteLog('GetPageHeader: warning: $pageType missing, setting to default');
//         $pageType = 'default';
//     }
//
// 	$dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
// 	$caller = isset($dbt[1]['function']) ? $dbt[1]['function'] : null;
//
//     WriteLog("GetPageHeader($pageType) ; caller = " . $caller);
//
//     if (defined($title)) {
//         $title;
//     } else {
//         $title = '';
//     }
//
//     my $txtIndex = "";
//     #my $styleSheet = GetStylesheet();
//     my $styleSheet = ''; #todo
//
//     my $introText = trim(GetString('page_intro/' . $pageType));
//     if (!$introText) {
//         $introText = trim(GetString('page_intro/default'));
//     }
//
//     # Get the HTML page template
//     my $htmlStart = GetTemplate('html/htmlstart.template');
//     # and substitute $title with the title
//
//     my $titleHtml = $title;
//
//     $htmlStart = str_replace('$titleHtml', $titleHtml, $htmlStart);
//     $htmlStart = str_replace('$title', $title, $htmlStart);
//
//     if (GetConfig('admin/offline/enable')) {
//         $htmlStart = AddAttributeToTag(
//             $htmlStart,
//             'html',
//             'manifest',
//             '/cache.manifest'
//         );
//     }
//
//     if (GetConfig('html/prefetch_enable')) {
//         #todo add more things to this template and make it not hard-coded
//         my $prefetchTags = GetTemplate('html/prefetch_head.template');
//         $htmlStart = str_replace('</head>', $prefetchTags . "\n" . '</head>', $htmlStart);
//     }
//
//     #top menu
//     my $topMenuTemplate = '';
//     if (GetConfig('html/menu_top')) {
//         require_once('widget/menu.pl');
//         $topMenuTemplate = GetMenuTemplate($pageType); #GetPageHeader()
//
//         # if (GetConfig('admin/js/enable') && GetConfig('admin/js/dragging') && GetConfig('admin/js/controls_header')) {
//         # 	my $dialogControls = GetTemplate('html/widget/dialog_controls.template'); # GetPageHeader()
//         # 	$dialogControls = GetDialogX($dialogControls, 'Controls'); # GetPageHeader()
//         # 	#$dialogControls = '<span class=advanced>' . $dialogControls . '</span>';
//         # 	$topMenuTemplate .= $dialogControls;
//         # }
//     }
//
//     if (GetConfig('admin/js/enable') && GetConfig('admin/js/dragging') && GetConfig('admin/js/dialog_properties')) {
//         my $dialogStyle = GetTemplate('html/widget/dialog_style.template'); # GetPageHeader()
//         $dialogStyle = GetDialogX($dialogStyle, 'Dialog');
//         $topMenuTemplate .= $dialogStyle;
//     }
//
//     #	my $noJsIndicator = '<noscript><a href="/profile.html">Profile</a></noscript>';
//     #todo profile link should be color-underlined like other menus
//     {
//         if (GetConfig('html/logo_enabled')) {
//             state $logoText;
//             if (!defined($logoText)) {
//                 $logoText = GetConfig('html/logo_text');
//                 if (!$logoText) {
//                     $logoText = '';
//                 }
//             }
//             my $logoTemplate = GetDialogX('<a href="/" class=logo>Home</a>', $logoText);
//             $htmlStart .= $logoTemplate;
//         }
//     }
//
//     if ($pageType ne 'item') {
//         $htmlStart =~ s/\$topMenu/$topMenuTemplate/g;
//     } else {
//         $htmlStart =~ s/\$topMenu//g;
//     }
//
//     $htmlStart =~ s/\$styleSheet/$styleSheet/g;
//     # $htmlStart =~ s/\$titleHtml/$titleHtml/g;
//     # $htmlStart =~ s/\$title/$title/g;
//
//     $htmlStart =~ s/\$introText/$introText/g;
//
//     if (GetConfig('admin/js/enable') && GetConfig('admin/js/loading')) { #begin loading
//         $htmlStart = InjectJs2($htmlStart, 'after', '<body>', qw(loading_begin));
//
//         # # #todo #templatize #hide #loading
//         #$htmlStart .= '<style><!-- .dialog {display: none !important; } --></style>';
//     }
//
//     $htmlStart = FillThemeColors($htmlStart);
//     $txtIndex .= $htmlStart;
//
//     return $txtIndex;
// } # GetPageHeader()
