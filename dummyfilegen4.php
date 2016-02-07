<?php
/*
Fake File Generator in php. 

Copyright (C) 2016  Nikos K. Kantarakias (aka nikant)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

=========================================================================   
Legal Disclaimer

Information contained in this application has been made available under the following condition.
Viewing of any of the content or use of this application implies acceptance of the following:

- This application is written for information purposes only.
- All information is provided "AS IS" and without warranty, express or implied. The author makes no claims, promises,
  or guarantees about the accuracy, completeness, or adequacy of the contents of this application, and expressly
  disclaims liability for errors and omissions in the content.
- No warranty of any kind, implied, expressed, or statutory, including but not limited to the warranties of non-infringement
  of third party rights, title, merchantability, fitness for a particular purpose or freedom from virus, is given with respect to the contents of this application.
- In no way will the author be liable for any damages, including without limitation direct or indirect, special, incidental, 
  or consequential damages, losses or expenses arising in connection with this application or use thereof or inability
  to use by any party, or reliance on the contents of this application, or in connection with any failure of performance, error,
  omission, interruption, defect, delay or failure in operation or transmission, computer virus or line or system failure,
  even if the author, its representatives, are advised of the possibility of such damages, losses or expense, hyperlinks
  to other internet resources are at your own risk; the content, accuracy, opinions expressed, and other links provided
  by these resources are not investigated, verified, monitored, or endorsed by the author.
  This exclusion clause shall take effect to the fullest extent permitted by law.
=========================================================================   

If you find errors please fix and notify!


Multiple credits since this file is a patchwork from multiple sources
Thanks goes to:
mgutt http://stackoverflow.com/users/318765/mgutt - http://stackoverflow.com/questions/2623590/php-script-to-generate-a-file-with-random-data-of-given-name-and-size
dhaupin http://stackoverflow.com/users/2418655/dhaupin - http://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
Armand Niculescu - http://www.media-division.com/php-download-script-with-resume-option/ (this script however doesn't support resume in files)

*/

// hide notices
//ini_set('error_reporting', E_ALL & ~E_NOTICE);

//- turn off compression on the server
ini_set('zlib.output_compression', 'Off');


// Returns filesystem-safe string after cleaning, filtering, and trimming input
function str_file_filter($str, $sep = '_', $strict = false, $trim = 248)
{
    
    $str = strip_tags(htmlspecialchars_decode(strtolower($str))); // lowercase -> decode -> strip tags
    $str = str_replace("%20", ' ', $str); // convert rogue %20s into spaces
    $str = preg_replace("/%[a-z0-9]{1,2}/i", '', $str); // remove hexy things
    $str = str_replace("&nbsp;", ' ', $str); // convert all nbsp into space
    $str = preg_replace("/&#?[a-z0-9]{2,8};/i", '', $str); // remove the other non-tag things
    $str = preg_replace("/\s+/", $sep, $str); // filter multiple spaces
    $str = preg_replace("/\.+/", '.', $str); // filter multiple periods
    $str = preg_replace("/^\.+/", '', $str); // trim leading period
    
    if ($strict) {
        $str = preg_replace("/([^\w\d\\" . $sep . ".])/", '', $str); // only allow words and digits
    } else {
        $str = preg_replace("/([^\w\d\\" . $sep . "\[\]\(\).])/", '', $str); // allow words, digits, [], and ()
    }
    
    $str = preg_replace("/\\" . $sep . "+/", $sep, $str); // filter multiple separators
    $str = substr($str, 0, $trim); // trim filename to desired length, note 255 char limit on windows
    
    return $str;
}


// Returns full file name including fallback and extension
function str_file($str, $sep = '_', $ext = '', $default = '', $trim = 248)
{
    
    // Run $str and/or $ext through filters to clean up strings
    $str = str_file_filter($str, $sep);
    $ext = '.' . str_file_filter($ext, '', true);
    
    // Default file name in case all chars are trimmed from $str, then ensure there is an id at tail
    if (empty($str) && empty($default)) {
        $str = 'no_name__' . date('Y-m-d_H-m_A') . '__' . uniqid();
    } elseif (empty($str)) {
        $str = $default;
    }
    
    // Return completed string
    if (!empty($ext)) {
        return $str . $ext;
    } else {
        return $str;
    }
}

function file_rand($filename, $filesize, $extrabytes, $extrasize)
{
    $startfilesize = $filesize;
    if ($extrasize > 0) {
        $filesize = $filesize - $extrasize;
        $h = hex2bin($extrabytes);
    }
    
    if ($filesize > 1024) {
        for ($i = 0; $i < floor($filesize / 1024); $i++) {
            $h .= openssl_random_pseudo_bytes(1023) . PHP_EOL;
        }
        $filesize = $filesize - (1024 * $i);
    }
    $mod = $filesize % 2;
    $h .= openssl_random_pseudo_bytes(($filesize - $mod));
    if ($mod) {
        $h .= substr(uniqid(), 0, 1);
    }
    
    // Disable Output Buffering
    @ob_end_clean();
    
    header("Pragma: public");
    header("Expires: -1");
    header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    header("Content-Type: application/octet-stream; ");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . $startfilesize);
    
    print $h;
    
    ob_flush();
    flush();
    
}

function file_rand_compressible($filename, $filesize, $extrabytes, $extrasize)
{
    $startfilesize = $filesize;
    if ($extrasize > 0) {
        $filesize = $filesize - $extrasize;
        $h = hex2bin($extrabytes);
    }
    
    if ($filesize > 1024) {
        for ($i = 0; $i < floor($filesize / 1024); $i++) {
            $h .= bin2hex(openssl_random_pseudo_bytes(511)) . PHP_EOL;
        }
        $filesize = $filesize - (1023 * $i);
    }
    $mod = $filesize % 2;
    $h .= bin2hex(openssl_random_pseudo_bytes(($filesize - $mod) / 2));
    if ($mod) {
        $h .= substr(uniqid(), 0, 1);
    }
    
    // Disable Output Buffering
    @ob_end_clean();
    
    header("Pragma: public");
    header("Expires: -1");
    header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    header("Content-Type: application/octet-stream; ");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . $startfilesize);
    
    print $h;
    
    ob_flush();
    flush();
    
}

function check_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


if (isset($_POST['submit'])) {
    $error        = '';
    $headerbytes  = '';
    $headersize   = 0;
    
    // multiple sanitizations and some kills for the paranoid
    $file_name    = check_input(($_POST['filename']));
    $size         = check_input(($_POST['filesize']));
    $fileext      = check_input(($_POST['fileext']));
    $compressible = check_input(($_POST['compressible']));

    /*    
    if ($file_name == '' || preg_match('/\s/m', $file_name)) {
        $error .= "<p><strong>Invalid file name.</strong></p>\n";
    }
    */
    
    if ($size == '' || preg_match('/\s/m', $size)) {
        $error .= "<p><strong>Invalid size.</strong></p>\n";
    }
    if (!ctype_digit($size)) {
        $error .= "<p><strong>File size is not a number.</strong></p>\n";
    }
    
    $fallback_str = 'generated_' . date('Y-m-d_H-m_A');
    $file_name    = str_file($file_name, '_', $fileext, $fallback_str);
    
    if (($size < 12) && ($size > 10485760)) {
        $error .= "<p><strong>Size is wrong!</strong></p>\n";
    }
    
    // These are generic file headers for the begining of each file. They are not detailed identification headers.
    switch ($fileext) {
        case "zip":
        case "docx":
        case "xlsx":
        case "odt":
        case "ods":
            $headerbytes = "504B0304";
            $headersize  = 4;
            break;
        case "7z":
            $headerbytes = "377ABCAF271C";
            $headersize  = 6;
            break;
        case "rar":
            $headerbytes = "526172211A0700";
            $headersize  = 7;
            break;
        case "pdf":
            $headerbytes = "25504446";
            $headersize  = 4;
            break;
        case "doc":
        case "xls":
            $headerbytes = "D0CF11E0A1B11AE1";
            $headersize  = 8;
            break;
        case "rtf":
            $headerbytes = "7B5C72746631";
            $headersize  = 6;
            break;
        case "noheader":
            $headerbytes = "";
            $headersize  = 0;
            break;
        default:
            die("<p><strong>Something is wrong!</strong></p>\n");
            break;
    }
    
    
    if ($error == '') {
        // sleep for 5 seconds
        sleep(5);
        if (isset($compressible) && ($compressible == "no")) {
            file_rand($file_name, $size, $headerbytes, $headersize);
        } elseif (isset($compressible) && ($compressible == "yes")) {
            file_rand_compressible($file_name, $size, $headerbytes, $headersize);
        }
    } else {
        echo $error;
    }
}

?>
