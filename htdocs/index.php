<?php

require_once '../include/gg2f.php';
require_once '../include/imaging.php';
require_once '../include/user.php';
require_once '../include/misc.php';
require_once '../include/secrets.php';


$htmlhead = <<<EOT
    <!doctype html>
    <meta charset=utf-8>
    <link rel=stylesheet href=style.css>
EOT;

$loginform = <<<EOT
    <form method=POST action=/>
        <h1>Welcome to sumochi</h1>
        <p>Sumochi uses your existing GG2 forum details. Log in with them below to add an achievements list to your forum signature. (If you remove it, just log in again - you won't lose your achievements)</p>
        <p>Once you've logged in for the first time, you will be able to use your forum details to earn achievements on approved GG2 servers.</p>
        <input type=hidden name=p value=dologin>
        Username: <input type=text name=username><br>
        Password: <input type=password name=password><br>
        <input type=submit>
    </form>
EOT;

switch (isset($_REQUEST['p']) ? $_REQUEST['p'] : '') {
    case 'api_give_achievement':
        if (user_check_logintoken($_GET['user'], $_GET['token'])) {
            if (in_array($_GET['a_key'], $permissable_keys, true)) {
                if (isset($_GET['a_icon'])) {
                    $result = user_give_achievement($_GET['user'], $_GET['a_id'], $_GET['a_name'], $_GET['a_key'], $_GET['a_icon']);
                } else {
                    $result = user_give_achievement($_GET['user'], $_GET['a_id'], $_GET['a_name'], $_GET['a_key']);
                }
                if ($result === FALSE) {
                    echo json_encode([
                        'errors' => ['already_has_achievement']
                    ]);
                } else if ($result === NULL) {
                    echo json_encode([
                        'errors' => ['unknown_error']
                    ]);
                } else {
                    echo json_encode([
                        'errors' => []
                    ]);
                }
            } else {
                echo json_encode([
                    'errors' => ['unknown_key']
                ]);
            }
        } else {
            echo json_encode([
                'errors' => ['invalid_token']
            ]);
        }
    break;
    case 'api_login':
        if (($PHPSESSID = gg2_login($_GET['user'], $_GET['password'])) !== FALSE) {
            if (user_exists($_GET['user'])) {
                if (in_array($_GET['key'], $permissable_keys, true)) {
                    $token = user_gen_logintoken($_GET['user'], $PHPSESSID, $_GET['key']);
                    echo json_encode([
                        'result' => [
                            'token' => $token
                         ],
                        'errors' => []
                    ]);
                } else {
                    echo json_encode([
                        'errors' => ['unknown_key']
                    ]);
                }
            } else {
                echo json_encode([
                    'errors' => ['no_sumochi_user']
                ]);
            }
        } else {
            echo json_encode([
                'errors' => ['gg2_login_failed']
            ]);
        }
    break;
    case 'display':
        $achievements = user_get_achievements($_GET['user']);
        
        // output signature image
        if ($achievements !== NULL) {
            render_profile($achievements);
        } else {
            header('Location: error.png');
            die();
        }
    break;
    case 'dologin':
        echo $htmlhead;
        $username = $_POST['username'];
        $password = $_POST['password'];
        if (($PHPSESSID = gg2_login($username, $password)) !== FALSE) {
            echo "Successful login!<br>";
            
            // create user file if non-existant
            user_create($username);
            
            // generate (self-referential) signature image URL
            $img_url = where_am_i() . '?p=display&user=' . urlencode($username);
            
            // generate (self-referential) promotional URL
            $url = where_am_i();
            
            // change signature
            $sig = '[url='.$url.'][img]'.$img_url.'[/img][/url]';
            gg2_change_signature($PHPSESSID, $sig);
            
            echo "Achievements list now in signature.<br>";
        } else {
            echo "Failed login!<br>";
        }
    break;
    case 'login':
    default:
        echo $htmlhead;
        echo $loginform;
    break;
}
