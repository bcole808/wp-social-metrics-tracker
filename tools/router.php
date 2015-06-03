<?php

######### router.php #########
#
# This file implements basic server routing and rewrite rules;
# Run the following command from the project root:
#
# php -S localhost:5000 -t path/to/wordpress path/to/router.php
#
#          ^ hostname      ^ root   ^ server router
#
#############################


#############################
# Configure locations
#############################

$server_root = $_SERVER['DOCUMENT_ROOT'];
chdir($server_root);
$request_path = '/'.ltrim(parse_url($_SERVER['REQUEST_URI'])['path'],'/');


#############################
# Set Error Log Locations
#############################

error_reporting(E_ALL);

# Enable this line to output errors to a file instead of printing to the terminal
// ini_set('error_log', dirname(dirname(__FILE__)) . '/php_error_log.txt');

#############################
# Route non-PHP files
#############################
if (preg_match('/\/([_0-9a-zA-Z-]+\/)?(wp-.*)/', $request_path, $matches)) {
    $request_path = '/' . $matches[2];

    // The rewritten path is to a non-PHP file.  It's probably a static asset or theme asset. Load the file and return it.
    if (file_exists($server_root . $request_path) && is_file($server_root . $request_path) && !strpos($request_path, ".php")) {
        
        header("Content-Type: " . get_mime($matches[2]));
        return readfile($server_root . $request_path);

    } 
}


#############################
# Route PHP files / filter multisite URLs
#############################

// Ensure wp-admin ends in trailing slash
if (endsWith($_SERVER['REQUEST_URI'], 'wp-admin')) {
    header('Location: '.$_SERVER['REQUEST_URI'].'/', true, 301);
}

if (preg_match('/\/([_0-9a-zA-Z-]+\/)?(.*\.php)\/+$/', $request_path, $matches)) {
    // The path is to some PHP file.  Remove the leading blog prefix.
    // Logic below will load this PHP file.
    $request_path = '/' . $matches[2];
}
set_include_path(get_include_path().':'.__DIR__);
if (file_exists($server_root.$request_path)) {
    if (is_dir($server_root.$request_path))
        $request_path = rtrim($request_path,'/').'/index.php';
        if (strpos($request_path,'.php') === false)
        return false;
    else {
        chdir(dirname($server_root.$request_path));
        require_once $server_root.$request_path;
    }
} else {
    include_once 'index.php';
}


#############################
# Helper Functions
#############################

function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
}

function get_mime ($filename,$mode=0) {

    // mode 0 = full check
    // mode 1 = extension check only

    $mime_types = array(

        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.ms-powerpoint',


        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );
    
    $filename_parts = explode('.',$filename);
    $ext = strtolower(array_pop($filename_parts));

    if (array_key_exists($ext, $mime_types)) {
        return $mime_types[$ext];

    }

    if (function_exists('mime_content_type') && $mode==0) {
        $mimetype = mime_content_type($filename);
        return $mimetype;
    }

    if (function_exists('finfo_open') && $mode==0) {
        $finfo = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfo, $filename);
        finfo_close($finfo);
        return $mimetype;

    } else {
        return 'application/octet-stream';
    }
}
?>