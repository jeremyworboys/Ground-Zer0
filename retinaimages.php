<?php

    /* Version: 1.3 - now with more cache */

    define('DEBUG',              false);    // Write debugging information to a log file
    define('SEND_ETAG',          true);     // You will want to disable this if you load balance multiple servers
    define('SEND_EXPIRES',       true);     //
    define('SEND_CACHE_CONTROL', true);     //
    define('CACHE_TIME',         24*60*60); // default: 1 day

    $document_root  = $_SERVER['DOCUMENT_ROOT'];
    $requested_uri  = parse_url(urldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH);
    $requested_file = basename($requested_uri);
    $source_file    = $document_root.$requested_uri;
    $source_ext     = strtolower(pathinfo($source_file, PATHINFO_EXTENSION));

    if (DEBUG) {
        $_debug_fh = fopen('retinaimages.log', 'a');
        fwrite($_debug_fh, "* * * * * * * * * * * * * * * * * * * * * * * * * * * *\n\n");
        fwrite($_debug_fh, print_r($_COOKIE, true)."\n\n");
        fwrite($_debug_fh, "document_root:     {$document_root}\n");
        fwrite($_debug_fh, "requested_uri:     {$requested_uri}\n");
        fwrite($_debug_fh, "requested_file:    {$requested_file}\n");
        fwrite($_debug_fh, "source_file:       {$source_file}\n");
        fwrite($_debug_fh, "source_ext:        {$source_ext}\n");
    }

    // Image was requested
    if (in_array($source_ext, array('png', 'gif', 'jpg', 'jpeg', 'bmp'))) {

        // Check if DPR is high enough to warrant retina image
        if (DEBUG) { fwrite($_debug_fh, "devicePixelRatio:  {$_COOKIE['devicePixelRatio']}\n"); }
        if (isset($_COOKIE['devicePixelRatio']) && intval($_COOKIE['devicePixelRatio']) > 1) {
            // Check if retina image exists
            $retina_file = pathinfo($source_file, PATHINFO_DIRNAME).'/'.pathinfo($source_file, PATHINFO_FILENAME).'@2x.'.pathinfo($source_file, PATHINFO_EXTENSION);
            if (DEBUG) { fwrite($_debug_fh, "retina_file:       {$retina_file}\n"); }
            if (file_exists($retina_file)) {
                $source_file = $retina_file;
            }
        }


        // Send cache headers
        if (SEND_CACHE_CONTROL) {
            header('Cache-Control: private, max-age='.CACHE_TIME, true);
        }
        if (SEND_EXPIRES) {
            date_default_timezone_set('GMT');
            header('Expires: '.gmdate('D, d M Y H:i:s', time()+CACHE_TIME).' GMT', true);
        }
        if (SEND_ETAG) {
            $etag = '"'.filemtime($source_file).fileinode($source_file).'"';
            header("ETag: $etag", true);

            if (DEBUG) {
                fwrite($_debug_fh, "generated etag:    {$etag}\n");
                if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
                    fwrite($_debug_fh, "received etag:     {$_SERVER['HTTP_IF_NONE_MATCH']}\n\n");
                }
            }

            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && ($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
                // File in cache hasn't change
                header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($source_file)).' GMT', true, 304);
                exit();
            }
        }
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === filemtime($source_file))) {
            // File in cache hasn't change
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($source_file)).' GMT', true, 304);
            exit();
        }

        // Send image headers
        if (in_array($source_ext, array('png', 'gif', 'jpeg', 'bmp'))) {
            header("Content-Type: image/".$source_ext, true);
        }
        else {
            header("Content-Type: image/jpeg", true);
        }
        header('Content-Length: '.filesize($source_file), true);

        // Close debug session if open
        if (DEBUG) {
            fwrite($_debug_fh, "sending file:      {$source_file}\n\n");
            fclose($_debug_fh);
        }

        // Send file
        readfile($source_file);
        exit();
    }

    // DPR value was sent
    elseif(isset($_GET['devicePixelRatio'])) {
        $dpr = $_GET['devicePixelRatio'];

        // Validate value before setting cookie
        if (''.intval($dpr) !== $dpr) {
            $dpr = '1';
        }

        setcookie('devicePixelRatio', $dpr);
        exit();
    }

    // Respond with an empty content
    header('HTTP/1.1 204 No Content', true);
