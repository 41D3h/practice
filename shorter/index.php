<?php
require_once('connect_DB.php');
header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Connection: close");

define('URL', 'http://localhost');
$url = $_SERVER['REQUEST_URI'];

var_dump($_REQUEST);
if (!empty($_REQUEST)) {
    $slug = urldecode(trim($_REQUEST['url']));
} else {
    $slug = parse_url($url, PHP_URL_PATH);
}

var_dump($slug);

if ($slug == '/') {?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Thy short link</title>
    </head>
    <body>
    <form name="lnk">
        <input type="url" id="link" name="url">
        <input type="submit" id="submit" onclick="submit">
        <span id="response"> </span>
    </form>

    <script type="application/javascript">
        let btn = document.querySelector('#submit');
        btn.disabled = true;

        document.querySelector('#link').addEventListener('focusout', function () {
            if (lnk.checkValidity()) {
                btn.disabled = false;
            } else {
                btn.disabled = true;
            }
        })

        let response = document.querySelector('#response');

        btn.addEventListener('click', function () {
            let xmlhttp = new XMLHttpRequest();

            let URI = document.querySelector('#link').value;
            let data = new FormData();
            data.append("url", encodeURI(URI));

            xmlhttp.open("POST", 'index.php', true);

            xmlhttp.onreadystatechange = function() {
                if(xmlhttp.readyState == XMLHttpRequest.DONE && xmlhttp.status == 200) {
                    alert(xmlhttp.response);
                }
            }

            xmlhttp.send(data);
        });
    </script>
    </body>
    </html><?php
    exit();
} else {
    if (filter_var($slug, FILTER_VALIDATE_URL) !== false) {

        $url = $slug;
        unset($slug);

        if (strpos($url, URL) === 0) {
            die($url);
        }

        function next_symbol(&$str) {
            if ($str == '9') {
                $str = 'a';
                return 'a';
            }
            if ($str == 'z') {
                $str = 'A';
                return 'A';
            }
            if ($str == 'Z') {
                $str = '1';
                return '1';
            }
            return $str++;
        }

        function get_nextURL($str) {
            $splitted_url = str_split($str);
            $Length = count($splitted_url);
            if (preg_match('/^Z*$/', $str)) {
                return str_repeat('1', $Length + 1);
            }
            while ('Z' == $splitted_url[--$Length]) {
                next_symbol($splitted_url[$Length]);
            }
            next_symbol($splitted_url[$Length]);
            return implode($splitted_url);
        }

        try {
            $existing_url = $pdo->prepare('SELECT slug FROM redirect WHERE url = :url LIMIT 1');
            $existing_url->execute([
                'url' => $url
            ]);

            $result = $existing_url->fetchAll(PDO::FETCH_ASSOC);

            if (empty($result) !== true) $slug = $result[0]['slug'];
            unset($result);
        } catch (PDOException $e) {
            echo $e;
        }

        if (isset($slug)) {
            echo URL . $slug;
            exit();
        } else {
            $result = $pdo->query('SELECT slug, url FROM redirect ORDER BY date DESC, slug DESC LIMIT 1');

            $amount = $pdo->query('SELECT COUNT(slug) FROM redirect ORDER BY date DESC, slug DESC LIMIT 1');
            $amount = $amount->fetchColumn();

            if ($result && $amount > 0) {
                $slug = get_nextURL($result->fetch(PDO::FETCH_ASSOC)['slug']);

                unset($result);
                unset($amount);
                $save = $pdo->prepare('INSERT INTO redirect (slug, url, date, hits) VALUES (:slug, :url, NOW(), 0)');
                $is_saved = $save->execute([
                    'slug' => $slug,
                    'url' => $url
                ]);
                if ($is_saved) echo URL . $slug;
            }
        }

    } else {
        $redirect = $pdo->prepare('SELECT url FROM redirect WHERE slug = :slug LIMIT 1');
        $redirect->execute([
            'slug' => $slug
        ]);
        $redirect_result = $redirect->fetch(PDO::FETCH_ASSOC)['url'];

        $redirect = $pdo->prepare('SELECT COUNT(url) FROM redirect WHERE slug = :slug');
        $redirect->execute([
            'slug' => $slug
        ]);
        $redirects_amount = $redirect->fetchColumn();

        if ($redirect_result && $redirects_amount > 0) {
            $redirects = $pdo->prepare('UPDATE redirect SET hits = hits + 1 WHERE slug = :slug');
            $redirects->execute([
                'slug' => $slug
            ]);

            $url = $redirect_result;
            unset($redirect_result);
            unset($redirects_amount);
        } else {
            $url = URL;
        }
        header('Location: ' . $url);
    }
    unset($pdo);
}
?>