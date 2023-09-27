<?php
$addons->get_hooks(array(), array(

    'page'     => 'includes/poker_inc.php',
    'location'  => 'page_start'

));

$best_cards = array();
$final_cards = array();

function __( $str, $c = false )
{
    if ($c)
    {
        if ( strpos($c, 'addon-') !== false )
        {
            return T_( $str, 'addon', str_replace('addon-', '', $c) );
        }
        elseif ( strpos($c, 'theme-') !== false )
        {
            return T_( $str, 'theme', str_replace('theme-', '', $c) );
        }
    }

    return T_( $str );
}

function T_( $str, $t = false, $b = false )
{
    global $language;

    if ($t && $b)
    {
        switch ($t)
        {
            case 'theme':
                $translation_file = __DIR__ . '/../themes/' . $b . '/languages/' . $language . '.po';
                break;

            case 'addon':
                $translation_file = __DIR__ . '/../includes/addons/' . $b . '/languages/' . $language . '.po';
                break;
        }

        if ( ! file_exists($translation_file) )
            $translation_file = __DIR__ . '/../languages/' . $language . '.po';
    }
    else
        $translation_file = __DIR__ . '/../languages/' . $language . '.po';

    if (file_exists($translation_file))
    {
        $IDIOMA_CONTENT = file($translation_file);
        $num_lineas = count($IDIOMA_CONTENT);
    }
    else
        return $str;

    for ($i = 0; $i <= $num_lineas; $i++)
    {
        $linea1  = $IDIOMA_CONTENT[$i];
        $linea1  = rtrim($linea1);
        $string6 = substr($linea1, 0, 6);

        if ($string6 == 'msgid ')
        {
            $orig = str_replace($string6, '', $linea1);
            $orig = str_replace("\"", '', $orig);
           
            if ($orig == $str)
            {
                $linea2 = $IDIOMA_CONTENT[$i + 1];
                $linea2 = rtrim($linea2);
                $string7 = substr($linea2, 0, 7);

                if ($string7 == 'msgstr ') {
                    $trad = str_replace($string7, '', $linea2);
                    $trad = str_replace("\"", '', $trad);
                    return($trad);
                }
            }
            else
                $i = $i + 3;
        }
    }

    return($str);
}

function ops_rules_popup($array = array())
{
    global $opsTheme;
    return $opsTheme->viewPart('rules-popup');
}

function ops_login_popup($array = array())
{
    global $opsTheme;
    return $opsTheme->viewPart('login-popup');
}

function ops_register_popup($array = array())
{
    global $opsTheme, $addons;

    $inputs = array();

    if ( EMAILMOD == 1 )
        $inputs['email'] = $opsTheme->viewPart('register-modal-email-input');

    $inputs['more'] = $addons->get_hooks(
        array(
            'content' => ''
        ),
        array(
            'page'     => 'register_modal',
            'location' => 'more_inputs'
    ));

    $opsTheme->addVariable('inputs', $inputs);
    return $opsTheme->viewPart('register-popup');
}

function ops_main_menu_data()
{
    global $addons;

    $data = array();

    if (!isset($array['home']))
        $data['index'] = MENU_HOME;

    if (VALID == false && MEMMOD == 0 && !isset($array['login']))
        $data['login'] = MENU_LOGIN;

    if (VALID == false && !isset($array['register']))
        $data['create'] = MENU_CREATE;

    if (VALID == true)
    {
        if (!isset($array['lobby']))
            $data['lobby'] = MENU_LOBBY;
        
        if (!isset($array['rankings']))
            $data['rankings'] = MENU_RANKINGS;

        if (!isset($array['profile']))
            $data['myplayer'] = MENU_MYPLAYER;
    }

    $data = $addons->get_hooks(
        array(
            'content' => $data
        ),
        array(
            'page'        => 'general',
            'location'    => 'leftbar_array',
            'merge_array' => true
        )
    );

    if (!isset($array['rules']))
        $data['rules'] = MENU_RULES;
    
    if (!isset($array['faq']))
        $data['faq']   = MENU_FAQ;

    if (ADMIN == true && !isset($array['admin']))
        $data['admin'] = MENU_ADMIN;

    if (VALID == true && !isset($array['logout']))
        $data['logout'] = MENU_LOGOUT;

    $data = $addons->get_hooks(
        array(
            'content' => $data
        ),
        array(
            'page'     => 'general',
            'location' => 'leftbar_final_array',
        )
    );

    return $data;
}

function ops_main_menu($array = array())
{
    global $addons, $opsTheme;

    $sidebarArray = ops_main_menu_data();

    $sidebarContent = '';
    foreach ($sidebarArray as $sidebarUrl => $sidebarLabel)
    {
        $nurl = ( ! preg_match('/\.php$/i', $sidebarUrl) ) ? $sidebarUrl . '.php' : $sidebarUrl;

        $opsTheme->addVariable('sb_menu_url',   $nurl);
        $opsTheme->addVariable('sb_menu_label', $sidebarLabel);

        $opsTheme->addVariable('sbMenu', array(
            'url'  => $nurl,
            'slug' => str_ireplace('.php', '', $sidebarUrl),
            'text' => $sidebarLabel,
        ));

        $sidebarContent .= $opsTheme->viewPart('sidebar-each');
    }

    // addon hook for sidebar content
    $sidebarContent = $addons->get_hooks(
        array(
            'content' => $sidebarContent
        ),
        array(
            'page'     => 'general',
            'location' => 'leftbar_content'
        )
    );

    if (isset($array['only_content']))
        return $sidebarContent;

    $opsTheme->addVariable('sidebar_content',   $sidebarContent);

    return $opsTheme->viewPage('sidebar');
}

function ops_top_banner($array = array())
{
    global $opsTheme;
    return $opsTheme->viewPart( 'top-banner' );
}

function ops_leaderboard($array = array())
{
    global $pdo, $opsTheme;

    if (\OPSAddon::isActive('bots')):
        $query = "SELECT COUNT(*) AS num FROM " . DB_STATS . " WHERE player IN (SELECT username FROM " . DB_PLAYERS . " WHERE isbot = 0)";
    else:
        $query = "SELECT COUNT(*) AS num FROM " . DB_STATS;
    endif;

    if (isset($array['total']))
        $total = intval($array['total']);
    else
    {
        $totalQuery  = $pdo->query($query);
        $totalPages  = $totalQuery->fetch(PDO::FETCH_ASSOC);
        $total 		 = $totalPages['num'];
    }

    /* Setup vars for query. */
    $limit      = (isset($array['limit'])) ? intval($array['limit']) : 10; 	//how many items to show per page
    $page       = (isset($array['page']))  ? intval($array['page'])  : 1;

    if ( $page )
        $start = ( $page - 1 ) * $limit;
    else
        $start = 0;

    if ($page == 0)
        $page = 1;

    $result   = $pdo->query( str_ireplace( 'COUNT(*) AS num', '*', $query ) . " ORDER BY rank ASC LIMIT {$start}, {$limit}" );
    $leaderboard = '';

    $i = 0;
    while ($star = $result->fetch(PDO::FETCH_ASSOC))
    {
        $i++;
        $player = ops_get_player($star['player']);

        $opsTheme->addVariable('row', array(
            'i'     => $i,
            'rank'  => $star['rank'],
            'name'  => $player['username'],
            'chips' => money($star['winpot']),
            'photo' => display_ava_profiles($star['player'], true),
        ));

        $leaderboard .= $opsTheme->viewPart('leaderboard-row-each');
    }

    $opsTheme->addVariable( 'board', $leaderboard );

    return isset($array['is_page']) ? array(
        'content'    => isset($array['content_only']) ? $leaderboard : $opsTheme->viewPart( 'leaderboard' ),
        'pagination' => OPS_pagination(array(
            'page'       => $page,
            'start'      => $start,
            'limit'      => $limit,
            'total'      => $total,
            'adjacent'   => 3,
            'targetPage' => isset($array['targetPage']) ? $array['targetPage'] : 'rankings.php?1&'
        ))
    ) : $opsTheme->viewPart( 'leaderboard' );
}

function ops_ranking($array = array())
{
    global $pdo, $opsTheme;

    if (\OPSAddon::isActive('bots')):
        $query = "SELECT COUNT(*) AS num FROM " . DB_STATS . " WHERE player IN (SELECT username FROM " . DB_PLAYERS . ")";
    else:
        $query = "SELECT COUNT(*) AS num FROM " . DB_STATS;
    endif;

    if (isset($array['total']))
        $total = intval($array['total']);
    else
    {
        $totalQuery  = $pdo->query($query);
        $totalPages  = $totalQuery->fetch(PDO::FETCH_ASSOC);
        $total 		 = $totalPages['num'];
    }

    /* Setup vars for query. */
    $limit      = (isset($array['limit'])) ? intval($array['limit']) : 10; 	//how many items to show per page
    $page       = (isset($array['page']))  ? intval($array['page'])  : 1;

    if ( $page )
        $start = ( $page - 1 ) * $limit;
    else
        $start = 0;

    if ($page == 0)
        $page = 1;

    $result   = $pdo->query( str_ireplace( 'COUNT(*) AS num', '*', $query ) . " ORDER BY `rank` ASC LIMIT {$start}, {$limit}" );
    $rankList = '';

    while ($star = $result->fetch(PDO::FETCH_ASSOC))
    {
        $rank = $star['rank'];
        $name = $star['player'];
        $chips = $star['winpot'];
        $handsplayed = $star['handsplayed'];
        $handswon = $star['handswon'];
        $called = $star['called'];
        $checked = $star['checked'];
        $win = $star['winpot'];
        $played = $star['gamesplayed'];
        $tplayed = $star['tournamentsplayed'];
        $twon = $star['tournamentswon'];

        $opsTheme->addVariable('rank', $rank);
        $opsTheme->addVariable('name', $name);
        $opsTheme->addVariable('chips', $chips);
        $opsTheme->addVariable('handsplayed', $handsplayed);
        $opsTheme->addVariable('handswon', $handswon);
        $opsTheme->addVariable('called', $called);
        $opsTheme->addVariable('checked', $checked);
        $opsTheme->addVariable('played', $played);
        $opsTheme->addVariable('tournaments_played', $tplayed);
        $opsTheme->addVariable('tournaments_won', $twon);

        $rankList .= $opsTheme->viewPart('ranking-each');
    }

    return (isset($array['only_content'])) ? $rankList : array(
        'content'    => $rankList,
        'pagination' => OPS_pagination(array(
            'page'       => $page,
            'start'      => $start,
            'limit'      => $limit,
            'total'      => $total,
            'adjacent'   => 3,
            'targetPage' => isset($array['targetPage']) ? $array['targetPage'] : 'rankings.php?1&'
        ))
    );
}

function ops_minify_html($input)
{
    if (trim($input) === "")
        return $input;

    $input = preg_replace_callback('#<([^\/\s<>!]+)(?:\s+([^<>]*?)\s*|\s*)(\/?)>#s', function($matches){
        return '<' . $matches[1] . preg_replace('#([^\s=]+)(\=([\'"]?)(.*?)\3)?(\s+|$)#s', ' $1$2', $matches[2]) . $matches[3] . '>';
    }, str_replace("\r", "", $input));

    if (strpos($input, ' style=') !== false)
    {
        $input = preg_replace_callback('#<([^<]+?)\s+style=([\'"])(.*?)\2(?=[\/\s>])#s', function($matches){
            return '<' . $matches[1] . ' style=' . $matches[2] . ops_minify_css($matches[3]) . $matches[2];
        }, $input);
    }

    if (strpos($input, '</style>') !== false)
    {
      $input = preg_replace_callback('#<style(.*?)>(.*?)</style>#is', function($matches){
        return '<style' . $matches[1] .'>'. ops_minify_css($matches[2]) . '</style>';
      }, $input);
    }

    if (strpos($input, '</script>') !== false)
    {
      $input = preg_replace_callback('#<script(.*?)>(.*?)</script>#is', function($matches){
        return '<script' . $matches[1] .'>'. ops_minify_js($matches[2]) . '</script>';
      }, $input);
    }

    return preg_replace(
        array(
            '#<(img|input)(>| .*?>)#s',
            '#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|\s*$#s',
            '#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)#s',
            '#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s',
            '#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?<!\>)\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s',
            '#(<!--.*?-->)|(<[^\/]*?>)\s+(<\/.*?>)#s',
            '#<(img|input)(>| .*?>)<\/\1>#s',
            '#(&nbsp;)&nbsp;(?![<\s])#',
            '#(?<=\>)(&nbsp;)(?=\<)#',
            '#\s*<!--(?!\[if\s).*?-->\s*|(?<!\>)\n+(?=\<[^!])#s'
        ),
        array(
            '<$1$2</$1>',
            '$1$2$3',
            '$1$2$3',
            '$1$2$3$4$5',
            '$1$2$3$4$5$6$7',
            '$1$2$3',
            '<$1$2',
            '$1 ',
            '$1',
            ""
        ),
    $input);
}

function ops_minify_css($input)
{
    if (trim($input) === "") return $input;
    return preg_replace(
        array(
            // Remove comment(s)
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
            // Remove unused white-space(s)
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~+]|\s*+-(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
            // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
            '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
            // Replace `:0 0 0 0` with `:0`
            '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
            // Replace `background-position:0` with `background-position:0 0`
            '#(background-position):0(?=[;\}])#si',
            // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
            '#(?<=[\s:,\-])0+\.(\d+)#s',
            // Minify string value
            '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
            '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
            // Minify HEX color code
            '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
            // Replace `(border|outline):none` with `(border|outline):0`
            '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
            // Remove empty selector(s)
            '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
        ),
        array(
            '$1',
            '$1$2$3$4$5$6$7',
            '$1',
            ':0',
            '$1:0 0',
            '.$1',
            '$1$3',
            '$1$2$4$5',
            '$1$2$3',
            '$1:0',
            '$1$2'
        ),
    $input);
}

// JavaScript Minifier
function ops_minify_js($input)
{
    if (trim($input) === "") return $input;
    return preg_replace(
        array(
            // Remove comment(s)
            '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
            // Remove white-space(s) outside the string and regex
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
            // Remove the last semicolon
            '#;+\}#',
            // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
            '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
            // --ibid. From `foo['bar']` to `foo.bar`
            '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i'
        ),
        array(
            '$1',
            '$1$2',
            '}',
            '$1$3',
            '$1.$3'
        ),
    $input);
}

function ops_add_stat($id)
{
    global $pdo, $plyrname;
    $u = $pdo->prepare("UPDATE " . DB_STATS . " SET $id = $id + 1 WHERE player = ?");
    $u->execute(array($plyrname));
    return true;
}

function ops_get_statpot()
{
    global $pdo, $plyrname;
    $q = $pdo->prepare("SELECT * FROM " . DB_STATS . " WHERE player = ?");
    $q->execute(array( $plyrname ));

    if ( !$q->rowCount() )
        return false;

    $f = $q->fetch(PDO::FETCH_ASSOC);
    return $f['winpot'];
}

function ops_set_statpot($money)
{
    global $pdo, $plyrname;
    $q = $pdo->prepare("UPDATE " . DB_STATS . " SET winpot = ? WHERE player = ?");
    $q->execute(array( $money, $plyrname ));

    return true;
}

function ops_add_statpot($money)
{
    global $pdo, $plyrname;
    $q = $pdo->prepare("UPDATE " . DB_STATS . " SET winpot = winpot + ? WHERE player = ?");
    $q->execute(array( $money, $plyrname ));

    return true;
}

function ops_subtract_statpot($money)
{
    global $pdo, $plyrname;
    $q = $pdo->prepare("UPDATE " . DB_STATS . " SET winpot = winpot - ? WHERE player = ?");
    $q->execute(array( $money, $plyrname ));

    return true;
}

function ops_get_player($id)
{
    global $pdo;
    $q = $pdo->prepare("SELECT * FROM " . DB_PLAYERS . " WHERE username = ?");
    $q->execute(array( $id ));

    return $q->fetch( PDO::FETCH_ASSOC );
}

function OPS_pagination($array = array())
{
    extract($array);
    $prev     = $page - 1;
    $next     = $page + 1;
    $lastPage = ceil($total / $limit);
    $lpm1     = $lastPage - 1;

    $pagination = '';

    if ( $lastPage > 1 )
    {
        $pagination .= "
        
    <nav align=\"right\">
      <ul class=\"pagination\"> 
        
        ";
        //previous button
        if ($page > 1)
        {
            $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=\"$targetPage&page=1\"><span aria-hidden=\"true\">&laquo;</span></a></li>";
        }
        else
        {
            $pagination .= "<li class=\"page-item\"><a class=\"page-link\"><span aria-hidden=\"true\">&laquo;</span></a></li>";
        }
        
        //pages
        if ( $lastPage < 7 + ($adjacent * 2) )  //not enough pages to bother breaking it up
        {
            for ($counter = 1; $counter <= $lastPage; $counter++)
            {
                if ($counter == $page)
                {
                    $pagination .= "<li class=\"page-item active\"><a class=\"page-link\"><span>$counter</span></a></li>";
                }
                else
                {
                    $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=\"$targetPage&page=$counter\">$counter</a></li>";
                }
            }
        }
        elseif ( $lastPage > 5 + ($adjacent * 2) )  //enough pages to hide some
        {
            //close to beginning; only hide later pages
            if ( $page < 1 + ($adjacent * 2) )
            {
                for ($counter = 1; $counter < 4 + ($adjacent * 2); $counter++)
                {
                    if ($counter == $page)
                    {
                        $pagination .= "<li class=\"page-item active\"><a class=\"page-link\"><span>$counter</span></a></li>";
                    }
                    else
                    {
                        $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=\"$targetPage&page=$counter\">$counter</a></li>";
                    }
                }

                $pagination .= "<li class=\"page-item\"><span>...</span></li>";
                $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=\"$targetPage&page=$lpm1\">$lpm1</a></li>";
                $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=\"$targetPage&page=$lastPage\">$lastPage</a></li>";
            }
            //in middle; hide some front and some back
            elseif ($lastPage - ($adjacent * 2) > $page && $page > ($adjacent * 2))
            {
                $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=\"$targetPage&page=1\">1</a></li>";
                $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=\"$targetPage&page=2\">2</a></li>";
                $pagination .= "<li class=\"page-item\"><span>...</span></li>";

                for ($counter = $page - $adjacent; $counter <= $page + $adjacent; $counter++)
                {
                    if ($counter == $page)
                    {
                        $pagination .= "<li class=\"page-item active\"><a class=\"page-link\"><span>$counter</span></a></li>";
                    }
                    else
                    {
                        $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=\"$targetPage&page=$counter\">$counter</a></li>";
                    }
                }

                $pagination .= "<li class=\"page-item\"><span>...</span></li>";
                $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=\"$targetPage&page=$lpm1\">$lpm1</a></li>";
                $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=\"$targetPage&page=$lastPage\">$lastPage</a></li>";
            }
            //close to end; only hide early pages
            else
            {
                $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=\"$targetPage&page=1\">1</a></li>";
                $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=\"$targetPage&page=2\">2</a></li>";
                $pagination .= "<li class=\"page-item\"><span>...</span></li>";

                for ($counter = $lastPage - (2 + ($adjacent * 2)); $counter <= $lastPage; $counter++)
                {
                    if ($counter == $page)
                    {
                        $pagination .= "<li class=\"page-item active\"><a class=\"page-link\"><span>$counter</span></a></li>";
                    }
                    else
                    {
                        $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=\"$targetPage&page=$counter\">$counter</a></li>";
                    }
                }
            }
        }
        
        //next button
        if ($page < $counter - 1)
        {
            $pagination .= "<li class=\"page-item\"><a class=\"page-link\" href=\"$targetPage&page=$lastPage\"><span aria-hidden=\"true\">&raquo;</span></a></li>";
        }
        else
        {
            $pagination .= "<li class=\"page-item\"><a class=\"page-link\"><span aria-hidden=\"true\">&raquo;</span></a></li>";
        }

        $pagination .= "
        
      </ul>
    </nav>  
        ";
    }

    return $pagination;
}

function OPS_mail($to, $subject, $body)
{
    // send email
    $mailfrom = $_SERVER['HTTP_HOST'];
    $mailfrom = str_replace('www.', '', $mailfrom);
    $mailf    = explode('/', $mailfrom);
    $from     = SMTP_USER;//'support@' . $mailf[0];

    if (SMTP_ON === 'yes')
    {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try
        {
            switch (SMTP_ENCRYPT)
            {
                case 'TLS':
                    $encryption = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    break;
                
                case 'SSL':
                    $encryption = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    break;
                
                default:
                    $encryption = false;
                    break;
            }

            //Server settings
            // $mail->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = (SMTP_AUTH === 'yes') ? true : false;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = $encryption;
            $mail->Port       = SMTP_PORT;

            if (! $encryption)
                $mail->SMTPAutoTLS = false;

            // From
            // $mail->setFrom($from, $mailf[0]);
            $mail->setFrom($from, TITLE);
            //Recipients
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $body;

            $mail->send();
            return true;
        }
        catch (PHPMailer\PHPMailer\Exception $e)
        {
            error_log($mail->ErrorInfo);
            return false;
        }
    }
    else
    {
        $headers = 'From: '.$from. "\r\n" .
           'Reply-To: '.$from . "\r\n" .
           'X-Mailer: PHP/' . phpversion();

        if (mail($to, $subject, $body, $headers))
            return true;
        else
            return false;
    }

    return false;
}

function OPS_sitelog($player, $log)
{
    if (empty($player) || empty($log))
        return false;

    global $pdo;
    $time = date('Y-m-d H:i:s');
    $pdo->query("INSERT INTO " . DB_SITELOG . " (player, log, dt) VALUES ('{$player}', '{$log}', '{$time}')");
    return true;
}

function poker_seat_circle_html($i, $isJs = false)
{
    global $pdo, $opsTheme, $addons, $gameID;
    
    $opsTheme->addVariable('seat_number', $i);
    $html = $opsTheme->viewPart('poker-player-circle');

    if ($isJs)
        $html = str_replace("'", "\'", $html);

    $html = $addons->get_hooks(
        array(
            'index'   => $i,
            'content' => $html,
            'is_js'   => $isJs
        ),

        array(
            'page'      => 'includes/poker_inc.php',
            'location'  => 'each_seat_circle',
        )
    );

    return $html;
}

//This functions checks and makes sure the email address that is being added to database is valid in format. 
function check_email_address($email)
{
    // First, we check that there's one @ symbol, and that the lengths are right
    if (!preg_match("/^[^@]{1,64}@[^@]{1,255}$/i", $email))
    {
        // Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
        return false;
    }

    // Split it into sections to make life easier
    $email_array = explode("@", $email);
    $local_array = explode(".", $email_array[0]);

    for ($i = 0; $i < sizeof($local_array); $i++)
    {
        if (! preg_match("/^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$/", $local_array[$i]))
        {
            return false;
        }
    }

    if (!preg_match("/^\[?[0-9\.]+\]?$/", $email_array[1]))
    {
        // Check if domain is IP. If not, it should be valid domain name
        $domain_array = explode(".", $email_array[1]);

        if (sizeof($domain_array) < 2)
        {
            return false; // Not enough parts to domain
        }

        for ($i = 0; $i < sizeof($domain_array); $i++)
        {
            if (! preg_match("/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$/", $domain_array[$i]))
            {
                return false;
            }
        }
    }

    return true;
}

function get_user_ip_addr()
{
    if(!empty($_SERVER['HTTP_CLIENT_IP']))
    {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    $ips = explode(',', $ip);
    return $ips[0];
}

function file_get_contents_su($url, $download = false)
{
    $post_data = array(
        'ip'      => get_user_ip_addr(),
        'domain'  => preg_replace('/[^A-Za-z0-9-.]/i', '', $_SERVER['SERVER_NAME']),
        'license' => LICENSEKEY,
        'version' => SCRIPTVERSIO,
    );
    if ($download) $post_data['download'] = true;

    $context = array(
        'http' => array(
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($post_data)
        ),
        'ssl' => array(
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ),
    );

    $fgc = @file_get_contents($url, false, stream_context_create($context));
    if (empty($fgc)) return '{}';

    return $fgc;
}

function file_get_contents_ssl($url, $post_data = false)
{
    $context = array(
        'ssl' => array(
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ),
    );

    if (is_array($post_data))
    {
        $context['http'] = array(
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($post_data)
        );
    }

    $fgc = @file_get_contents($url, false, stream_context_create($context));
    if (empty($fgc)) return '{}';

    return $fgc;
}

function transfer_from($val)
{
    if (! is_numeric($val))
        return 0;

    if ($val == 0)
        return $val;

    if (SMALLBETFUNC == 1)
    {
        $val = $val / 1000;
    }
    elseif (SMALLBETFUNC == 2)
    {
        $val = $val / 100;
    }
    elseif (SMALLBETFUNC == 3)
    {
        $val = $val / 10;
    }

    return $val;
}

function transfer_to($val)
{
    if (! is_numeric($val))
        return 0;

    if (SMALLBETFUNC == 1)
    {
        $val = $val * 1000;
    }
    elseif (SMALLBETFUNC == 2)
    {
        $val = $val * 100;
    }
    elseif (SMALLBETFUNC == 3)
    {
        $val = $val * 10;
    }

    return $val;
}

function moneynumber($val)
{
    if (! is_numeric($val))
        return 0;

    if (SMALLBETFUNC == 1) $val = ($val / 1000);
    if (SMALLBETFUNC == 2) $val = ($val / 100);
    if (SMALLBETFUNC == 3) $val = ($val / 10);

    return $val;
}

function money($val, $showPrefix = true, $customPrefix = false)
{
    $prefix = ($showPrefix) ? ($customPrefix ? $customPrefix : MONEY_PREFIX) : '';

    if (is_numeric($val))
    {
        if (SMALLBETFUNC == 1) $val = ($val / 1000);
        if (SMALLBETFUNC == 2) $val = ($val / 100);
        if (SMALLBETFUNC == 3) $val = ($val / 10);
        if ($val > 1000000000)
        {
            $money = $prefix . number_format(($val / 1000000000) , 0, MONEY_DECIMA, MONEY_THOUSA) . ' B';
        }
        elseif ($val > 100000000)
        {
            $money = $prefix . number_format(($val / 1000000) , 0, MONEY_DECIMA, MONEY_THOUSA) . ' M';
        }
        elseif ($val > 1000000)
        {
            $money = $prefix . number_format(($val / 1000000) , 1, MONEY_DECIMA, MONEY_THOUSA) . ' M';
        }
        elseif (SMALLBETFUNC == 1)
        {
            $money = $prefix . number_format($val, 2, MONEY_DECIMA, MONEY_THOUSA);
        }
        else
        {
            if ($pos = strpos($val, '.'))
            {
                $len = strlen($val);
                $dec = $len - ($pos + 1);
                $money = $prefix . number_format($val, $dec, MONEY_DECIMA, MONEY_THOUSA);
            }
            else
                $money = $prefix . number_format($val, 0, MONEY_DECIMA, MONEY_THOUSA);
        }
    }
    elseif ($val == 'FOLD')
    {
        $money = $val;
    }
    else
    {
        $money = $prefix . '0';
    }

    return $money;
}

function money_small($val, $showPrefix = true, $customPrefix = false)
{
    $prefix = ($showPrefix) ? ($customPrefix ? $customPrefix : MONEY_PREFIX) : '';

    if (!is_numeric($val)) return false;
    $val = str_replace('F', '', $val);
    if (SMALLBETFUNC == 1) $val = ($val / 1000);
    if (SMALLBETFUNC == 2) $val = ($val / 100);
    if (SMALLBETFUNC == 3) $val = ($val / 10);
    if ($val > 999999999)
    {
        $money = $prefix . number_format(($val / 1000000000) , 1, MONEY_DECIMA, MONEY_THOUSA) . 'B';
    }
    elseif ($val > 99999999)
    {
        $money = $prefix . number_format(($val / 1000000) , 0, MONEY_DECIMA, MONEY_THOUSA) . 'M';
    }
    elseif ($val > 999999)
    {
        $money = $prefix . number_format(($val / 1000000) , 1, MONEY_DECIMA, MONEY_THOUSA) . 'M';
    }
    elseif ($val > 999)
    {
        if (round($val) % 1000 == 0)
        {
            $money = $prefix . number_format(($val / 1000) , 0, MONEY_DECIMA, MONEY_THOUSA) . 'K';
        }
        else
        {
            $money = $prefix . number_format(($val / 1000) , 1, MONEY_DECIMA, MONEY_THOUSA) . 'K';
        }
    }
    elseif (SMALLBETFUNC == 1)
    {
        $money = $prefix . number_format($val, 2, MONEY_DECIMA, MONEY_THOUSA);
    }
    else
    {
        if ($pos = strpos($val, '.'))
        {
            $len = strlen($val);
            $dec = $len - ($pos + 1);
            $money = $prefix . number_format($val, $dec, MONEY_DECIMA, MONEY_THOUSA);
        }
        else
            $money = $prefix . number_format($val, 0, MONEY_DECIMA, MONEY_THOUSA);
    }

    return $money;
}

function get_ava($usr)
{
    global $pdo;
    $usrq = $pdo->prepare("select avatar from " . DB_PLAYERS . " where username = '" . $usr . "' ");
    $usrq->execute();

    if ( !$usrq->rowCount() )
        return '';

    $usrr = $usrq->fetch(PDO::FETCH_ASSOC);
    $avatar = $usrr['avatar'];
    return $avatar;
}

function display_ava($usr)
{
    global $pdo;
    $usrq = $pdo->prepare("select avatar from " . DB_PLAYERS . " where username = '" . $usr . "' ");
    $usrq->execute();
    $usrr = $usrq->fetch(PDO::FETCH_ASSOC);
    $avatar = '<img src="images/avatars/' . $usrr['avatar'] . '" border="0">';
    return $avatar;
}

function display_ava_profile($usr)
{
    global $pdo;
    $time = time();
    $usrq = $pdo->prepare("select avatar from " . DB_PLAYERS . " where username = '" . $usr . "' ");
    $usrq->execute();
    $usrr = $usrq->fetch(PDO::FETCH_ASSOC);
    $avatar = '<img src="images/avatars/' . $usrr['avatar'] . '?x=' . $time . '" border="0">';
    return $avatar;
}

function display_ava_profiles($usr, $full = false)
{
    global $pdo;
    $time = time();
    $usrq = $pdo->prepare("select avatar from " . DB_PLAYERS . " where username = '" . $usr . "' ");
    $usrq->execute();

    if (!$usrq->rowCount())
        return '';
    
    $usrr = $usrq->fetch(PDO::FETCH_ASSOC);
    return $full ? 'images/avatars/' . $usrr['avatar'] : $usrr['avatar'];
}

function sys_msg($msg, $gameID)
{
    global $pdo;
    $chtq = $pdo->query("select * from " . DB_LIVECHAT . " where gameID = " . $gameID);
    $chtr = $chtq->fetch(PDO::FETCH_ASSOC);
    $time = time();
    $time+= 2;
    $c2 = addslashes($chtr['c2']);
    $c3 = addslashes($chtr['c3']);
    $c4 = addslashes($chtr['c4']);
    $c5 = addslashes($chtr['c5']);
    $msg = str_ireplace(array("'", "\\'"), '&apos;', $msg);
    $msg = '<p>' . $msg . '</p>';
    $chtq = $pdo->query("select * from " . DB_LIVECHAT . " where gameID = " . $gameID);

    if ($chtq->rowCount() > 0)
        $result = $pdo->exec("update " . DB_LIVECHAT . " set updatescreen = " . $time . ", c1 = '" . $c2 . "', c2 = '" . $c3 . "', c3 = '" . $c4 . "', c4 = '" . $c5 . "', c5  = '" . $msg . "' where gameID = " . $gameID);
    else
        $result = $pdo->exec("insert into " . DB_LIVECHAT . " set updatescreen = '" . $time . "', c1 = '" . $c2 . "', c2 = '" . $c3 . "', c3 = '" . $c4 . "', c4 = '" . $c5 . "', c5 = '" . $msg . "', gameID = '" . $gameID . "' ");

    return true;
}

function poker_log($playername, $msg, $gameID)
{
    if (strlen($msg) < 1)
        return false;

    global $pdo, $opsTheme;
    $time  = time();
    $time += 2;
    $plyrF = array(
        'ID'     => '',
        'avatar' => ''
    );

    $chtq = $pdo->query("SELECT * FROM " . DB_LIVECHAT . " WHERE gameID = " . $gameID);

    if ( !$chtq->rowCount() )
        return false;

    $chtr = $chtq->fetch(PDO::FETCH_ASSOC);

    $c2  = addslashes($chtr['c2']);
    $c3  = addslashes($chtr['c3']);
    $c4  = addslashes($chtr['c4']);
    $c5  = addslashes($chtr['c5']);
    $msg = addslashes($msg);

    if (strlen($playername) > 0)
    {
        $plyrQ = $pdo->query("SELECT ID, avatar FROM " . DB_PLAYERS . " WHERE username = '$playername'");

        if ($plyrQ->rowCount() == 1)
            $plyrF = $plyrQ->fetch(PDO::FETCH_ASSOC);
    }

    $msg = '<user>
    <id>' . $plyrF['ID'] . '</id>
    <name>' . $playername . '</name>
    <avatar>' . $plyrF['avatar'] . '</avatar>
</user>
<message>' . $msg . '</message>';

    $chtq = $pdo->query("SELECT * FROM " . DB_LIVECHAT . " WHERE gameID = " . $gameID);

    if ($chtq->rowCount() > 0)
        $result = $pdo->exec("UPDATE " . DB_LIVECHAT . " SET updatescreen = " . $time . ", c1 = '" . $c2 . "', c2 = '" . $c3 . "', c3 = '" . $c4 . "', c4 = '" . $c5 . "', c5  = '" . $msg . "' WHERE gameID = " . $gameID);
    else
        $result = $pdo->exec("INSERT INTO " . DB_LIVECHAT . " SET updatescreen = '" . $time . "', c1 = '" . $c2 . "', c2 = '" . $c3 . "', c3 = '" . $c4 . "', c4 = '" . $c5 . "', c5 = '" . $msg . "', gameID = '" . $gameID . "' ");

    return true;
}

function get_log_html($player, $message)
{
    global $opsTheme;
    $opsTheme->addVariable('chatter', [
        'name' => $player
    ]);
    $opsTheme->addVariable('message', $message);
    return $opsTheme->viewPart('poker-log-message');
}

function get_ip($usr)
{
    global $pdo;
    $ipq = $pdo->prepare("select ipaddress from " . DB_PLAYERS . " where username = '" . $usr . "' ");
    $ipq->execute();
    $ipr = $ipq->fetch(PDO::FETCH_ASSOC);
    return $ipr['ipaddress'];
}

function getplayerid($plyrname)
{
    global $tpr;
    $i = 1;
    while ($i < 11)
    {
        if ($plyrname == $tpr['p' . $i . 'name'])
        {
            return $i;
        }

        $i++;
    }

    return;
}

function get_num_players()
{
    $i = 1;
    $x = 0;
    while ($i < 11)
    {
        if (get_name($i) != '' && (get_pot($i) > 0 || get_bet($i) > 0) && get_pot($i) != 'BUSTED') $x++;
        $i++;
    }

    return $x;
}

function get_all_players()
{
    $i = 1;
    $x = 0;
    while ($i < 11)
    {
        if (get_name($i) != '') $x++;
        $i++;
    }

    return $x;
}

function last_player()
{
    $i = 1;
    while ($i < 11)
    {
        if ((get_name($i) != '') && ((get_pot($i) > 0) || (get_bet($i) > 0))) return $i;
        $i++;
    }

    return;
}

function in_game($i)
{
    $check = false;
    if (
        (get_name($i) != '')
        && (
            (get_bet($i) > 0)
            || (get_pot($i) > 0)
        )
        && (get_bet($i) != 'FOLD'))
        $check = true;

    return $check;
}

function in_gametot($i)
{
    $check = false;
    if (get_name($i) != '') $check = true;
    return $check;
}

function get_num_allin()
{
    $i = 1;
    $x = 0;
    while ($i < 11)
    {
        if ((get_name($i) != '') && (get_pot($i) == 0) && (get_bet($i) > 0) && (get_bet($i) != 'FOLD')) $x++;
        $i++;
    }

    return $x;
}

function get_num_left()
{
    $i = 1;
    $x = 0;
    while ($i < 11)
    {
        if ((get_name($i) != '') && ((get_bet($i) > 0) || (get_pot($i) > 0)) && (get_bet($i) != 'FOLD')) $x++;
        $i++;
    }

    return $x;
}

function check_bets()
{
    global $tablebet;
    $i = 1;
    $check = true;
    while ($i < 11)
    {
        if ((get_name($i) != '') && (get_pot($i) > 0) && (get_bet($i) != 'FOLD'))
        {
            if (get_bet($i) < $tablebet) $check = false;
        }

        $i++;
    }

    return $check;
}

function is_interplay($hand)
{
    if ($hand >= HAND_INTERPLAY_START && $hand <= HAND_INTERPLAY_END)
        return true;
    
    return false;
}

function is_straddling($hand)
{
    if ($hand >= HAND_STRADDLE_START && $hand <= HAND_STRADDLE_END)
        return true;
    
    return false;
}

function is_straddle($var)
{
    if (defined('ISSTRADDLE') && ISSTRADDLE === 'yes' && $var['is_straddle'] == 1)
        return true;
    
    return false;
}

function ops_calc( $string )
{
    $array = explode('x', $string);
    $count = count($array);

    if ($count == 2)
        return ops_calc_vars($array[0]) * ops_calc_vars($array[1]);

    return 0;
}
function  ops_calc_vars( $string )
{
    global $BB, $SB, $tablepot;

    if (! preg_match('/[A-Za-z]/i', $string))
    {
        if (strpos($string, '.'))
            return floatval($string);
        else
            return intval($string);
    }
    
    if ($string === 'BB')
        return $BB;
    elseif ($string === 'SB')
        return $SB;
    elseif ($string === 'POT')
        return $tablepot;
    
    return 1;
}

function roundpot($pot)
{
    $diff = $pot - floor($pot);
    if ($diff > 0.5)
    {
        $pot = floor($pot) + 1;
    }
    else
    {
        $pot = floor($pot);
    }

    return $pot;
}

function find_winners($game_style)
{
    $i   = 1;
    $x   = 0;
    $pts = 0;

    global $best_cards;
    global $final_cards;
    global $addons;

    $multiwin = array();
    while ($i < 11)
    {
        $winpts = 0;
        if (in_game($i) == true)
        {
            if ($game_style == 'o')
            {
                $winpts = $addons->get_hooks(
                    array(
                        'player'  => $i,
                        'content' => $winpts
                    ),
                    array(
                        'page'     => 'includes/poker_inc.php',
                        'location' => 'omaha_logic'
                    )
                );
            }
            else
            {
                $winpts = evaluate_texas_hand($i);
            }

            if ($winpts > $pts)
            {
                $x           = 0;
                $multiwin[0] = $i;
                $multiwin[1] = '';
                $multiwin[2] = '';
                $multiwin[3] = '';
                $multiwin[4] = '';
                $pts         = $winpts;
                $final_cards = $best_cards;
            }
            elseif ($winpts == $pts && $winpts > 0)
            {
                $x++;
                $multiwin[$x] = $i;
            }
        }

        $i++;
    }

    return array(
        isset($multiwin[0]) ? $multiwin[0] : '',
        isset($multiwin[1]) ? $multiwin[1] : '',
        isset($multiwin[2]) ? $multiwin[2] : '',
        isset($multiwin[3]) ? $multiwin[3] : '',
        isset($multiwin[4]) ? $multiwin[4] : '',
        isset($multiwin[5]) ? $multiwin[5] : ''
    );
}

function decrypt_card($encrypted)
{
    $cards = array(
        'AD',
        '2D',
        '3D',
        '4D',
        '5D',
        '6D',
        '7D',
        '8D',
        '9D',
        '10D',
        'JD',
        'QD',
        'KD',
        'AC',
        '2C',
        '3C',
        '4C',
        '5C',
        '6C',
        '7C',
        '8C',
        '9C',
        '10C',
        'JC',
        'QC',
        'KC',
        'AH',
        '2H',
        '3H',
        '4H',
        '5H',
        '6H',
        '7H',
        '8H',
        '9H',
        '10H',
        'JH',
        'QH',
        'KH',
        'AS',
        '2S',
        '3S',
        '4S',
        '5S',
        '6S',
        '7S',
        '8S',
        '9S',
        '10S',
        'JS',
        'QS',
        'KS'
    );
    $i = 0;
    $stack = explode(':', $encrypted);
    foreach($cards as $i => $card)
    {
        if (isset($stack[0], $stack[1]) && (md5($stack[1] . 'pokerpro' . $card) == $stack[0]) && (sizeof($stack) == 2))
        {
            return $cards[$i];
        }
    }

    return false;
}

function encrypt_card($plain)
{
    $plain = 'pokerpro' . $plain;
    $card = '';
    
    for ($i = 0; $i < 10; $i++):
        $card .= mt_rand();
    endfor;

    $salt = substr(md5($card), 0, 2);
    $card = md5($salt . $plain) . ':' . $salt;
    return $card;
}

function soundPath($s)
{
    global $opsTheme;
    $sound = $opsTheme->themeDir . '/sounds/' . $s . '.mp3';

    if ( !file_exists($sound) ):
        $sound = str_replace(
            $opsTheme->theme,
            $opsTheme->defaultTheme,
            strtolower($sound)
        );
    endif;

    return preg_replace('/.*?\/themes/i', 'themes', $sound);
}

function last_bet()
{
    global $tpr;
    $lb = explode('|', $tpr['lastbet']);
    $lb1 = $lb[0];

    if (is_numeric($lb1) && $lb1 > 0 && empty($tpr["p{$lb1}name"]))
    {
        global $pdo;
        $nxt = nextplayer($lb1);

        if (! empty($nxt))
        {
            $lastbet = "{$nxt}|" . $tpr["p{$nxt}bet"];
            $pdo->query("UPDATE " . DB_POKER . " SET lastbet = '{$lastbet}' WHERE gameID = " . $tpr['gameID']);
        }
    }

    return $lb1;
}

function ots($num)
{
    if ($num == 11) $num = 1;
    if ($num == 0) $num = 10;
    return $num;
}

function nextplayer($player)
{
    global $tpr;
    $time = time();
    $i = $player;
    $z = 0;
    while ($z < 10)
    {
        $i++;
        $test = ots($i);
        if (($tpr['p' . $test . 'name'] != '') && ($tpr['p' . $test . 'pot'] != 'BUSTED') && (($tpr['p' . $test . 'pot'] > 0) || ($tpr['p' . $test . 'bet'] > 0)) && ($tpr['p' . $test . 'pot'] != '') && (get_bet($test) != 'FOLD'))
        {
            return $test;
        }

        $i = $test;
        $z++;
    }

    return '';
}

function nextdealer($player)
{
    global $tpr;
    $time = time();
    $i = $player;
    $z = 0;
    while ($z < 10)
    {
        $i++;
        $test = ots($i);
        if (($tpr['p' . $test . 'name'] != '') && ($tpr['p' . $test . 'pot'] > 0))
        {
            return $test;
        }

        $i = $test;
        $z++;
    }
}

function insert_cards($select, $pos, $gameID)
{
    global $pdo;
    $pdo->query("UPDATE " . DB_POKER . " SET " . $pos . " = '" . encrypt_card($select) . "' WHERE gameID = {$gameID}");
    return true;
}

function deal($numplayers, $gameID, $game_style)
{
    /* test scenario */
    /*$cards = array(
        '5D' => 'card1',
        'QH' => 'card2',
        'QS' => 'card3',
        '7D' => 'card4',
        '4S' => 'card5',
        'QD' => 'p1card1',
        '9C' => 'p1card2',
        '5S' => 'p2card1',
        '8C' => 'p2card2',
        'JH' => 'p3card1',
        'QC' => 'p3card2'
    );
    foreach ($cards as $card => $cardPos)
    {
        insert_cards($card, $cardPos, $gameID);
    }
    return false;/*
    /* test scenario */

    $cards = array(
        'AD',
        '2D',
        '3D',
        '4D',
        '5D',
        '6D',
        '7D',
        '8D',
        '9D',
        '10D',
        'JD',
        'QD',
        'KD',
        'AC',
        '2C',
        '3C',
        '4C',
        '5C',
        '6C',
        '7C',
        '8C',
        '9C',
        '10C',
        'JC',
        'QC',
        'KC',
        'AH',
        '2H',
        '3H',
        '4H',
        '5H',
        '6H',
        '7H',
        '8H',
        '9H',
        '10H',
        'JH',
        'QH',
        'KH',
        'AS',
        '2S',
        '3S',
        '4S',
        '5S',
        '6S',
        '7S',
        '8S',
        '9S',
        '10S',
        'JS',
        'QS',
        'KS'
    );

    if ($game_style == GAME_TEXAS)
    {
        $cardpos = array(
            'card1',
            'card2',
            'card3',
            'card4',
            'card5',
            'p1card1',
            'p1card2',
            'p2card1',
            'p2card2',
            'p3card1',
            'p3card2',
            'p4card1',
            'p4card2',
            'p5card1',
            'p5card2',
            'p6card1',
            'p6card2',
            'p7card1',
            'p7card2',
            'p8card1',
            'p8card2',
            'p9card1',
            'p9card2',
            'p10card1',
            'p10card2'
        );
        $numcards = ($numplayers * 2) + 5;
    }
    else
    {
        $cardpos = array(
            'card1',
            'card2',
            'card3',
            'card4',
            'card5',
            'p1card1',
            'p1card2',
            'p1card3',
            'p1card4',
            'p2card1',
            'p2card2',
            'p2card3',
            'p2card4',
            'p3card1',
            'p3card2',
            'p3card3',
            'p3card4',
            'p4card1',
            'p4card2',
            'p4card3',
            'p4card4',
            'p5card1',
            'p5card2',
            'p5card3',
            'p5card4',
            'p6card1',
            'p6card2',
            'p6card3',
            'p6card4',
            'p7card1',
            'p7card2',
            'p7card3',
            'p7card4',
            'p8card1',
            'p8card2',
            'p8card3',
            'p8card4',
            'p9card1',
            'p9card2',
            'p9card3',
            'p9card4',
            'p10card1',
            'p10card2',
            'p10card3',
            'p10card4'
        );

        $numcards = ($numplayers * 4) + 5;
    }

    $i = 0;
    $pick = array();
    while ($i < $numcards)
    {
        $select = $cards[mt_rand(0, 51)];
        if (! in_array($select, $pick))
        {
            $pick[$i] = $select;
            insert_cards($select, $cardpos[$i], $gameID);
            $i++;
        }
    }
}

function get_bet_math($pnum)
{
    global $tpr;
    $pbet = (isset($tpr['p' . $pnum . 'bet'])) ? $tpr['p' . $pnum . 'bet'] : '';
    $bet = str_replace('F', '', $pbet);
    return intval($bet);
}

function fetch_bet_math($pnum)
{
    global $tpr;
    $bet = (isset($tpr['p' . $pnum . 'bet'])) ? $tpr['p' . $pnum . 'bet'] : '';
    if (substr($bet, 0, 1) == 'F') $bet = 0;
    if ($bet == '') $bet = 0;
    return $bet;
}

function get_bet($pnum)
{
    global $tpr;
    $bet = (isset($tpr['p' . $pnum . 'bet'])) ? $tpr['p' . $pnum . 'bet'] : '';
    if (substr($bet, 0, 1) == 'F')
    {
        if (substr($bet, 1, 1) != '')
        {
            $bet = 'FOLD';
        }
        else
        {
            $bet = 0;
        }
    }

    if ($bet == '') $bet = 0;
    return $bet;
}

function get_pot($pnum)
{
    global $tpr;
    $pot = (isset($tpr['p' . $pnum . 'pot'])) ? $tpr['p' . $pnum . 'pot'] : '';
    if (!is_numeric($pot)) $pot = 0;
    return $pot;
}

function get_name($pnum)
{
    global $tpr;
    $name = (isset($tpr['p' . $pnum . 'name'])) ? $tpr['p' . $pnum . 'name'] : '';
    return $name;
}

function distpot($game_style)
{
    global $tpr, $pdo, $gameID, $addons;
    $pots = array(
        '',
        get_pot(1),
        get_pot(2),
        get_pot(3),
        get_pot(4),
        get_pot(5),
        get_pot(6),
        get_pot(7),
        get_pot(8),
        get_pot(9),
        get_pot(10)
    );

    $wins    = array();
    $winners = find_winners($game_style);

    $totalWinnerBets = 0;
    $z = 0;
    while (isset($winners[$z]) && $winners[$z] != '')
    {
        $totalWinnerBets += get_bet_math($winners[$z]);
        $z++;
    }

    $z = 0;
    while (isset($winners[$z]) && $winners[$z] != '')
    {
        $winner     = $winners[$z];
        $winnerName = get_name($winner);
        $winnerBet  = get_bet($winner);
        $cut        = $totalWinnerBets > 0 ? $winnerBet / $totalWinnerBets : 0;

        $pots[$winner] += $winnerBet;

        for ($i = 1; $i < 11; $i++)
        { 
            $loserPot = get_pot($i);
            $loserBet = get_bet_math($i) * $cut;
            
            if ($winner != $i && $loserBet > 0 && !in_array($i, $winners))
            {
                if ($winnerBet >= $loserBet)
                {
                    $pots[$winner] += $loserBet;
                }
                else
                {
                    $pots[$winner] += $winnerBet;
                    $pots[$i]      += ($loserBet - $winnerBet);
                }

                $wins[$winner] = array(
                    'name' => $winnerName,
                    'pot'  => $pots[$winner]
                );
            }
        }

        $z++;
    }

    $pots = $addons->get_hooks(
        array(
            'content' => $pots,
            'winners' => $wins
        ),
        array(
            'page'     => 'includes/poker_inc.php',
            'location' => 'distpots'
        )
    );

    $result = $pdo->query("UPDATE " . DB_POKER . " SET p1pot = '" . roundpot($pots[1]) . "', p2pot = '" . roundpot($pots[2]) . "', p3pot = '" . roundpot($pots[3]) . "', p4pot = '" . roundpot($pots[4]) . "', p5pot = '" . roundpot($pots[5]) . "', p6pot = '" . roundpot($pots[6]) . "' , p7pot = '" . roundpot($pots[7]) . "' , p8pot = '" . roundpot($pots[8]) . "' , p9pot = '" . roundpot($pots[9]) . "' , p10pot = '" . roundpot($pots[10]) . "'  WHERE gameID = " . $gameID);
}

function evaluate_texas_hand($player)
{
    global $cardr;
    global $tablecards;
    $points     = 0;
    $hand       = array(
        $tablecards[0],
        $tablecards[1],
        $tablecards[2],
        $tablecards[3],
        $tablecards[4],
        decrypt_card($cardr['p' . $player . 'card1']),
        decrypt_card($cardr['p' . $player . 'card2'])
    );
    $flush      = array();
    $values     = array();
    $sortvalues = array();
    $hcs        = array();
    $orig       = array(
        'J',
        'Q',
        'K',
        'A'
    );
    $change     = array(
        11,
        12,
        13,
        14
    );
    $i          = 0;
    while (isset($hand[$i]) && $hand[$i] != '') {
        if (strlen($hand[$i]) == 2) {
            $flush[$i]      = substr($hand[$i], 1, 1);
            $values[$i]     = str_replace($orig, $change, substr($hand[$i], 0, 1));
            $sortvalues[$i] = $values[$i];
        } else {
            $flush[$i]      = substr($hand[$i], 2, 1);
            $values[$i]     = str_replace($orig, $change, substr($hand[$i], 0, 2));
            $sortvalues[$i] = $values[$i];
        }
        $i++;
    }
    sort($sortvalues);
    $pairmatch = '';
    $ispair    = array_count_values($values);
    $results   = array_count_values($ispair);
    $i         = 0;
    if (isset($results['2']) && $results['2'] == 1)
        $res = '1pair';
    if (isset($results['2']) && $results['2'] > 1)
        $res = '2pair';
    if (isset($results['3']) && $results['3'] > 0)
        $res = '3s';
    if (isset($results['4']) && $results['4'] > 0)
        $res = '4s';
    if ((isset($results['2'], $results['3']) && ($results['3'] > 0) && ($results['2'] > 0)) || (isset($results['3']) && $results['3'] > 1))
        $res = 'FH';
    $i         = 2;
    $z         = 0;
    $y         = 0;
    $multipair = array();
    while ($i < 15) {
        if (isset($ispair[$i]) && $ispair[$i] == 2) {
            $multipair[$z] = $i;
            $highpair      = $i;
            $z++;
        }
        if (isset($ispair[$i]) && $ispair[$i] == 3) {
            $threepair[$y] = $i;
            $high3pair     = $i;
            $y++;
        }
        $i++;
    }
    $bw = 6;
    $n  = 0;
    while ((isset($sortvalues[$bw]) && $sortvalues[$bw] != '') && ($n < 5)) {
        if (isset($res) && $res === '3s')
        {
            if (! in_array($sortvalues[$bw], $threepair))
            {
                $hcs[$n] = $sortvalues[$bw];
                $n++;
            }
        }
        else
        {
            if (! in_array($sortvalues[$bw], $multipair))
            {
                $hcs[$n] = $sortvalues[$bw];
                $n++;
            }
        }
        $bw--;
    }
    $h1    = isset($hcs[0]) ? $hcs[0] : 0;
    $h2    = (isset($hcs[1]) ? $hcs[1] : 0) / 10;
    $h3    = (isset($hcs[2]) ? $hcs[2] : 0) / 100;
    $h4    = (isset($hcs[3]) ? $hcs[3] : 0) / 1000;
    $h5    = (isset($hcs[4]) ? $hcs[4] : 0) / 10000;
    $high1 = $h1;
    $high2 = $h1 + $h2;
    $high3 = $h1 + $h2 + $h3;
    $high5 = $h1 + $h2 + $h3 + $h4 + $h5;
    if (isset($res) && ($res == '1pair' || $res == '2pair' || $res == 'FH')) {
        if ($res == '1pair') {
            $points = (($highpair * 10) + ($high3));
        }
        if ($res == '2pair') {
            sort($multipair);
            $pairs = count($multipair);
            if ($pairs == 3) {
                $pr1 = $multipair[2];
                $pr2 = $multipair[1];
            } else {
                $pr1 = $multipair[1];
                $pr2 = $multipair[0];
            }
            $points = ((($pr1 * 100) + ($pr2 * 10)) + $high1);
        }
        if ($res == 'FH') {
            sort($multipair);
            sort($threepair);
            $pairs  = count($multipair);
            $threes = count($threepair);
            if ($pairs == 1) {
                $pr1 = $multipair[0];
            } else {
                $pr1 = $multipair[1];
            }
            if ($threes == 1) {
                $kry1 = $threepair[0];
            } else {
                $kry1 = $threepair[1];
                $kry2 = $threepair[0];
            }
            if (isset($kry2, $pr1) && $kry2 > $pr1)
                $pr1 = $kry2;
            $points = (($kry1 * 1000000) + ($pr1 * 100000));
        }
    }
    if (isset($res) && $res == '3s') {
        $i = 2;
        while ($i < 15) {
            if (isset($ispair[$i]) && $ispair[$i] == 3) {
                $points = ($i * 1000) + $high2;
            }
            $i++;
        }
    }
    if (isset($res) && $res == '4s') {
        $i = 2;
        while ($i < 15) {
            if ($ispair[$i] == 4) {
                $points = $i * 10000000 + $high1;
            }
            $i++;
        }
    }
    $flushsuit = '';
    $isflush   = array_count_values($flush);
    if (isset($isflush['D']) && $isflush['D'] > 4)
        $flushsuit = 'D';
    if (isset($isflush['C']) && $isflush['C'] > 4)
        $flushsuit = 'C';
    if (isset($isflush['H']) && $isflush['H'] > 4)
        $flushsuit = 'H';
    if (isset($isflush['S']) && $isflush['S'] > 4)
        $flushsuit = 'S';
    if (isset($flushsuit) && $flushsuit != '') {
        $res        = $flushsuit . ' FLUSH DETECTED';
        $i          = 0;
        $x          = 0;
        $flusharray = array();
        while ($i < 7) {
            if ($flush[$i] == $flushsuit) {
                $flusharray[$x] = $values[$i];
                $x++;
            }
            $i++;
        }
        sort($flusharray);
        $basic    = 250000;
        $z        = count($flusharray) - 1;
        $c1       = $flusharray[$z] * 1000;
        $s1       = $flusharray[$z];
        $c2       = $flusharray[$z - 1] * 100;
        $s2       = $flusharray[$z - 1];
        $c3       = $flusharray[$z - 2] * 10;
        $s3       = $flusharray[$z - 2];
        $c4       = $flusharray[$z - 3];
        $s4       = $flusharray[$z - 3];
        $c5       = $flusharray[$z - 4] / 10;
        $s5       = $flusharray[$z - 4];
        $points   = $basic + $c1 + $c2 + $c3 + $c4 + $c5;
        $flushstr = false;
        $i        = 0;
        $x        = 0;
        while (isset($flusharray[$i]) && $flusharray[$i] != '') {
            if (isset($flusharray[$i], $flusharray[($i + 1)]) && $flusharray[$i] == ($flusharray[$i + 1] - 1)) {
                $x++;
                $h = $flusharray[$i] + 1;
            }
            $i++;
        }
        if ($x > 3)
            $points = $h * 100000000;
        if (($x > 3) && ($h == 14))
            $points = $h * 1000000000;
    }
    if (isset($flushsuit) && $flushsuit == '') {
        $straight = false;
        $i        = 0;
        $count    = 0;
        if ((isset($sortvalues[6]) && $sortvalues[6] == 14) && (isset($sortvalues[0]) && $sortvalues[0] == 2))
            $count = 1;
        while (isset($sortvalues[$i]) && $sortvalues[$i] != '') {
            if (isset($sortvalues[$i], $sortvalues[($i + 1)]) && ($sortvalues[$i]) == ($sortvalues[$i + 1] - 1)) {
                $count++;
                if ($count > 3) {
                    $straight = true;
                    $res      = 'STRAIGHT';
                    $h        = $sortvalues[$i] + 1;
                    $points   = $h * 10000;
                }
            } elseif (isset($sortvalues[$i], $sortvalues[($i + 1)]) && ($sortvalues[$i]) != ($sortvalues[$i + 1])) {
                $count = 0;
            }
            $i++;
        }
    }
    if (isset($res) && $res == '') {
        $points = $high5;
    }
    $tname = get_name($player);
    return $points;
}

function evaluatewin($player, $game_style)
{
    global $pdo, $tablecards, $cardr, $final_cards, $plyrname;
    $points = 0;

    if ( empty($player) )
        return '';

    if ($game_style == GAME_TEXAS) {
        $hand       = array(
            $tablecards[0],
            $tablecards[1],
            $tablecards[2],
            $tablecards[3],
            $tablecards[4],
            decrypt_card($cardr['p' . $player . 'card1']),
            decrypt_card($cardr['p' . $player . 'card2'])
        );
    } else {
        $hand = $final_cards;
    }

    $flush      = array();
    $values     = array();
    $sortvalues = array();
    $orig       = array(
        'J',
        'Q',
        'K',
        'A'
    );
    $change     = array(
        11,
        12,
        13,
        14
    );
    $i = 0;
    while (isset($hand[$i]) && $hand[$i] != '') {
        if (strlen($hand[$i]) == 2) {
            $flush[$i]      = substr($hand[$i], 1, 1);
            $values[$i]     = str_replace($orig, $change, substr($hand[$i], 0, 1));
            $sortvalues[$i] = $values[$i];
        } else {
            $flush[$i]      = substr($hand[$i], 2, 1);
            $values[$i]     = str_replace($orig, $change, substr($hand[$i], 0, 2));
            $sortvalues[$i] = $values[$i];
        }
        $i++;
    }
    sort($sortvalues);
    $pairmatch     = '';
    $ispair        = array_count_values($values);
    $results       = array_count_values($ispair);
    $i             = 0;
    $outputvalues  = array(
        '',
        '',
        '2s',
        '3s',
        '4s',
        '5s',
        '6s',
        '7s',
        '8s',
        '9s',
        '10s',
        'Jacks',
        'Queens',
        'Kings',
        'Aces'
    );
    $outputvalues2 = array(
        '',
        '',
        2,
        3,
        4,
        5,
        6,
        7,
        8,
        9,
        10,
        'Jack',
        'Queen',
        'King',
        'Ace'
    );
    if (isset($results['2']) && $results['2'] == 1)
        $res = '1pair';
    if (isset($results['2']) && $results['2'] > 1)
        $res = '2pair';
    if (isset($results['3']) && $results['3'] > 0)
        $res = '3s';
    if (isset($results['4']) && $results['4'] > 0)
        $res = '4s';
    if ((isset($results['2'], $results['3']) && ($results['3'] > 0) && ($results['2'] > 0)) || (isset($results['3']) && $results['3'] > 1))
        $res = 'FH';
    if (isset($res) && ($res == '1pair' || $res == '2pair' || $res == 'FH')) {
        $i         = 2;
        $z         = 0;
        $y         = 0;
        $multipair = array();
        while ($i < 15) {
            if (isset($ispair[$i]) && $ispair[$i] == 2) {
                $multipair[$z] = $i;
                $z++;
            }
            if (isset($ispair[$i]) && $ispair[$i] == 3) {
                $threepair[$y] = $i;
                $y++;
            }
            $i++;
        }
        $HCS = array();
        if (isset($res) && $res == '1pair') {
            if ($multipair[0] == 2)
                $Xres = $outputvalues[2];
            if ($multipair[0] == 3)
                $Xres = $outputvalues[3];
            if ($multipair[0] == 4)
                $Xres = $outputvalues[4];
            if ($multipair[0] == 5)
                $Xres = $outputvalues[5];
            if ($multipair[0] == 6)
                $Xres = $outputvalues[6];
            if ($multipair[0] == 7)
                $Xres = $outputvalues[7];
            if ($multipair[0] == 8)
                $Xres = $outputvalues[8];
            if ($multipair[0] == 9)
                $Xres = $outputvalues[9];
            if ($multipair[0] == 10)
                $Xres = $outputvalues[10];
            if ($multipair[0] == 11)
                $Xres = $outputvalues[11];
            if ($multipair[0] == 12)
                $Xres = $outputvalues[12];
            if ($multipair[0] == 13)
                $Xres = $outputvalues[13];
            if ($multipair[0] == 14)
                $Xres = $outputvalues[14];
            $res = ' ' . WIN_PAIR . ' ' . $Xres;
            ops_add_stat('play_1pair');
        }
        if (isset($res) && $res == '2pair') {
            sort($multipair);
            $pairs = count($multipair);
            if ($pairs == 3) {
                $pr1 = $multipair[2];
                $pr2 = $multipair[1];
            } else {
                $pr1 = $multipair[1];
                $pr2 = $multipair[0];
            }
            if ($pr1 == 3)
                $Xres = $outputvalues[3];
            if ($pr1 == 4)
                $Xres = $outputvalues[4];
            if ($pr1 == 5)
                $Xres = $outputvalues[5];
            if ($pr1 == 6)
                $Xres = $outputvalues[6];
            if ($pr1 == 7)
                $Xres = $outputvalues[7];
            if ($pr1 == 8)
                $Xres = $outputvalues[8];
            if ($pr1 == 9)
                $Xres = $outputvalues[9];
            if ($pr1 == 10)
                $Xres = $outputvalues[10];
            if ($pr1 == 11)
                $Xres = $outputvalues[11];
            if ($pr1 == 12)
                $Xres = $outputvalues[12];
            if ($pr1 == 13)
                $Xres = $outputvalues[13];
            if ($pr1 == 14)
                $Xres = $outputvalues[14];
            if ($pr2 == 2)
                $Xres2 = $outputvalues[2];
            if ($pr2 == 3)
                $Xres2 = $outputvalues[3];
            if ($pr2 == 4)
                $Xres2 = $outputvalues[4];
            if ($pr2 == 5)
                $Xres2 = $outputvalues[5];
            if ($pr2 == 6)
                $Xres2 = $outputvalues[6];
            if ($pr2 == 7)
                $Xres2 = $outputvalues[7];
            if ($pr2 == 8)
                $Xres2 = $outputvalues[8];
            if ($pr2 == 9)
                $Xres2 = $outputvalues[9];
            if ($pr2 == 10)
                $Xres2 = $outputvalues[10];
            if ($pr2 == 11)
                $Xres2 = $outputvalues[11];
            if ($pr2 == 12)
                $Xres2 = $outputvalues[12];
            if ($pr2 == 13)
                $Xres2 = $outputvalues[13];
            $res = ' ' . WIN_2PAIR . ' ' . $Xres . ' and ' . $Xres2;
            ops_add_stat('play_2pair');
        }
        if (isset($res) && $res == 'FH') {
            $res = ' ' . WIN_FULLHOUSE;
            ops_add_stat('play_fullhouse');
        }
    }
    if (isset($res) && $res == '3s') {
        $i = 2;
        while ($i < 15) {
            if (isset($ispair[$i]) && $ispair[$i] == 3) {
                $res = ' ' . WIN_SETOF3 . ' ' . $outputvalues[$i];
            }
            $i++;
        }
        ops_add_stat('play_3ofakind');
    }
    if (isset($res) && $res == '4s') {
        while ($i < 15) {
            if (isset($ispair[$i]) && $ispair[$i] == 4) {
                $res = ' ' . WIN_SETOF4 . ' ' . $outputvalues[$i];
            }
            $i++;
        }
        ops_add_stat('play_4ofakind');
    }
    $flushsuit = '';
    $isflush   = array_count_values($flush);
    if (isset($isflush['D']) && $isflush['D'] > 4)
        $flushsuit = 'D';
    if (isset($isflush['C']) && $isflush['C'] > 4)
        $flushsuit = 'C';
    if (isset($isflush['H']) && $isflush['H'] > 4)
        $flushsuit = 'H';
    if (isset($isflush['S']) && $isflush['S'] > 4)
        $flushsuit = 'S';
    if (isset($flushsuit) && $flushsuit != '') {
        $i          = 0;
        $x          = 0;
        $flusharray = array();
        while ($i < 7) {
            if (isset($flush[$i]) && $flush[$i] == $flushsuit) {
                $flusharray[$x] = $values[$i];
                $x++;
            }
            $i++;
        }
        sort($flusharray);
        $z        = count($flusharray) - 1;
        $res      = ' ' . $outputvalues2[$flusharray[$z]] . ' ' . WIN_FLUSH;
        ops_add_stat('play_flush');
        $flushstr = false;
        $i        = 0;
        $x        = 0;
        while (isset($flusharray[$i]) && $flusharray[$i] != '') {
            if (isset($flusharray[$i], $flusharray[($i + 1)]) && $flusharray[$i] == ($flusharray[$i + 1] - 1)) {
                $x++;
                $h = $flusharray[$i] + 1;
            }
            $i++;
        }
        if ($x > 3)
        {
            $res = ' ' . $outputvalues2[$flusharray[$z]] . ' ' . WIN_STRAIGHT_FLUSH;
            ops_add_stat('play_straightflush');
        }
        if (($x > 3) && ($h == 14))
        {
            $res = ' ' . WIN_ROYALFLUSH;
            ops_add_stat('play_royalflush');
        }
    }
    if (isset($flushsuit) && $flushsuit == '') {
        $lows     = false;
        $straight = false;
        $i        = 0;
        $count    = 0;
        if (isset($sortvalues[6], $sortvalues[0]) && ($sortvalues[6] == 14) && ($sortvalues[0] == 2)) {
            $count = 1;
            $lows  = true;
        }
        while (isset($sortvalues[$i]) && $sortvalues[$i] != '') {
            if (isset($sortvalues[$i], $sortvalues[($i + 1)]) && ($sortvalues[$i]) == ($sortvalues[$i + 1] - 1)) {
                $count++;
                if ($count > 3) {
                    $straight = true;
                    $h        = $sortvalues[$i] + 1;
                    $res      = ' ' . $outputvalues2[$h] . ' ' . WIN_STRAIGHT;
                    if (($lows == true) && ($h == 5) && ($count == 4))
                        $res = ' low straight';

                    ops_add_stat('play_straight');
                }
            } elseif (isset($sortvalues[$i], $sortvalues[($i + 1)]) && ($sortvalues[$i]) != $sortvalues[$i + 1]) {
                $count = 0;
            }
            $i++;
        }
    }
    if (isset($res) && $res == '') {
        $hc1 = $sortvalues[6];
        $res = ' ' . $outputvalues2[$hc1] . ' ' . WIN_HIGHCARD;
    }
    return isset($res) ? $res : '';
}

function find_rand($min = null, $max = null)
{
    static $seeded;
    if (!isset($seeded))
    {
        mt_srand((double)microtime() * 1000000);
        $seeded = true;
    }

    if (isset($min) && isset($max))
    {
        if ($min >= $max)
        {
            return $min;
        }
        else
        {
            return mt_rand($min, $max);
        }
    }
    else
    {
        return mt_rand();
    }
}

function validate_password($plain, $encrypted)
{
    if (($plain != '') && ($encrypted != ''))
    {
        $stack = explode(':', $encrypted);
        if (sizeof($stack) != 2) return false;
        if ((md5($stack[1] . 'pwd' . $plain) == $stack[0]) || (md5($stack[1] . $plain) == $stack[0]))
        {
            return true;
        }
    }

    return false;
}

function encrypt_password($plain)
{
    $password = '';
    for ($i = 0; $i < 10; $i++)
    {
        $password.= find_rand();
    }

    $salt = substr(md5($password) , 0, 2);
    $password = md5($salt . 'pwd' . $plain) . ':' . $salt;
    return $password;
}

function randomcode($length, $type = 'mixed')
{
    if (($type != 'mixed') && ($type != 'chars') && ($type != 'digits')) return false;
    $rand_value = '';
    while (strlen($rand_value) < $length)
    {
        if ($type == 'digits')
        {
            $char = find_rand(0, 9);
        }
        else
        {
            $char = chr(find_rand(0, 255));
        }

        if ($type == 'mixed')
        {
            if (preg_match('/^[a-z0-9]$/i', $char)) $rand_value.= $char;
        }
        elseif ($type == 'chars')
        {
            if (preg_match('/^[a-z]$/i', $char)) $rand_value.= $char;
        }
        elseif ($type == 'digits')
        {
            if (preg_match('/^[0-9]$/', $char)) $rand_value.= $char;
        }
    }

    return $rand_value;
}

function ops_langs()
{
	$languages = array(
		'ab' => 'Abkhazian',
		'aa' => 'Afar',
		'af' => 'Afrikaans',
		'ak' => 'Akan',
		'sq' => 'Albanian',
		'am' => 'Amharic',
		'an' => 'Aragonese',
		'hy' => 'Armenian',
		'as' => 'Assamese',
		'av' => 'Avaric',
		'ae' => 'Avestan',
		'ay' => 'Aymara',
		'az' => 'Azerbaijani',
		'bm' => 'Bambara',
		'ba' => 'Bashkir',
		'eu' => 'Basque',
		'be' => 'Belarusian',
		'bn' => 'Bengali',
		'bh' => 'Bihari languages',
		'bi' => 'Bislama',
		'bs' => 'Bosnian',
		'br' => 'Breton',
		'bg' => 'Bulgarian',
		'my' => 'Burmese',
		'ca' => 'Catalan, Valencian',
		'km' => 'Central Khmer',
		'ch' => 'Chamorro',
		'ce' => 'Chechen',
		'ny' => 'Chichewa, Chewa, Nyanja',
		'zh' => 'Chinese',
		'cu' => 'Church Slavonic, Old Bulgarian, Old Church Slavonic',
		'cv' => 'Chuvash',
		'kw' => 'Cornish',
		'co' => 'Corsican',
		'cr' => 'Cree',
		'hr' => 'Croatian',
		'cs' => 'Czech',
		'da' => 'Danish',
		'dv' => 'Divehi, Dhivehi, Maldivian',
		'nl' => 'Dutch, Flemish',
		'dz' => 'Dzongkha',
		'en' => 'English',
		'eo' => 'Esperanto',
		'et' => 'Estonian',
		'ee' => 'Ewe',
		'fo' => 'Faroese',
		'fj' => 'Fijian',
		'fi' => 'Finnish',
		'fr' => 'French',
		'ff' => 'Fulah',
		'gd' => 'Gaelic, Scottish Gaelic',
		'gl' => 'Galician',
		'lg' => 'Ganda',
		'ka' => 'Georgian',
		'de' => 'German',
		'ki' => 'Gikuyu, Kikuyu',
		'el' => 'Greek (Modern)',
		'kl' => 'Greenlandic, Kalaallisut',
		'gn' => 'Guarani',
		'gu' => 'Gujarati',
		'ht' => 'Haitian, Haitian Creole',
		'ha' => 'Hausa',
		'he' => 'Hebrew',
		'hz' => 'Herero',
		'hi' => 'Hindi',
		'ho' => 'Hiri Motu',
		'hu' => 'Hungarian',
		'is' => 'Icelandic',
		'io' => 'Ido',
		'ig' => 'Igbo',
		'id' => 'Indonesian',
		'ia' => 'Interlingua (International Auxiliary Language Association)',
		'ie' => 'Interlingue',
		'iu' => 'Inuktitut',
		'ik' => 'Inupiaq',
		'ga' => 'Irish',
		'it' => 'Italian',
		'ja' => 'Japanese',
		'jv' => 'Javanese',
		'kn' => 'Kannada',
		'kr' => 'Kanuri',
		'ks' => 'Kashmiri',
		'kk' => 'Kazakh',
		'rw' => 'Kinyarwanda',
		'kv' => 'Komi',
		'kg' => 'Kongo',
		'ko' => 'Korean',
		'kj' => 'Kwanyama, Kuanyama',
		'ku' => 'Kurdish',
		'ky' => 'Kyrgyz',
		'lo' => 'Lao',
		'la' => 'Latin',
		'lv' => 'Latvian',
		'lb' => 'Letzeburgesch, Luxembourgish',
		'li' => 'Limburgish, Limburgan, Limburger',
		'ln' => 'Lingala',
		'lt' => 'Lithuanian',
		'lu' => 'Luba-Katanga',
		'mk' => 'Macedonian',
		'mg' => 'Malagasy',
		'ms' => 'Malay',
		'ml' => 'Malayalam',
		'mt' => 'Maltese',
		'gv' => 'Manx',
		'mi' => 'Maori',
		'mr' => 'Marathi',
		'mh' => 'Marshallese',
		'ro' => 'Moldovan, Moldavian, Romanian',
		'mn' => 'Mongolian',
		'na' => 'Nauru',
		'nv' => 'Navajo, Navaho',
		'nd' => 'Northern Ndebele',
		'ng' => 'Ndonga',
		'ne' => 'Nepali',
		'se' => 'Northern Sami',
		'no' => 'Norwegian',
		'nb' => 'Norwegian Bokmål',
		'nn' => 'Norwegian Nynorsk',
		'ii' => 'Nuosu, Sichuan Yi',
		'oc' => 'Occitan (post 1500)',
		'oj' => 'Ojibwa',
		'or' => 'Oriya',
		'om' => 'Oromo',
		'os' => 'Ossetian, Ossetic',
		'pi' => 'Pali',
		'pa' => 'Panjabi, Punjabi',
		'ps' => 'Pashto, Pushto',
		'fa' => 'Persian',
		'pl' => 'Polish',
		'pt' => 'Portuguese',
		'qu' => 'Quechua',
		'rm' => 'Romansh',
		'rn' => 'Rundi',
		'ru' => 'Russian',
		'sm' => 'Samoan',
		'sg' => 'Sango',
		'sa' => 'Sanskrit',
		'sc' => 'Sardinian',
		'sr' => 'Serbian',
		'sn' => 'Shona',
		'sd' => 'Sindhi',
		'si' => 'Sinhala, Sinhalese',
		'sk' => 'Slovak',
		'sl' => 'Slovenian',
		'so' => 'Somali',
		'st' => 'Sotho, Southern',
		'nr' => 'South Ndebele',
		'es' => 'Spanish, Castilian',
		'su' => 'Sundanese',
		'sw' => 'Swahili',
		'ss' => 'Swati',
		'sv' => 'Swedish',
		'tl' => 'Tagalog',
		'ty' => 'Tahitian',
		'tg' => 'Tajik',
		'ta' => 'Tamil',
		'tt' => 'Tatar',
		'te' => 'Telugu',
		'th' => 'Thai',
		'bo' => 'Tibetan',
		'ti' => 'Tigrinya',
		'to' => 'Tonga (Tonga Islands)',
		'ts' => 'Tsonga',
		'tn' => 'Tswana',
		'tr' => 'Turkish',
		'tk' => 'Turkmen',
		'tw' => 'Twi',
		'ug' => 'Uighur, Uyghur',
		'uk' => 'Ukrainian',
		'ur' => 'Urdu',
		'uz' => 'Uzbek',
		've' => 'Venda',
		'vi' => 'Vietnamese',
		'vo' => 'Volap_k',
		'wa' => 'Walloon',
		'cy' => 'Welsh',
		'fy' => 'Western Frisian',
		'wo' => 'Wolof',
		'xh' => 'Xhosa',
		'yi' => 'Yiddish',
		'yo' => 'Yoruba',
		'za' => 'Zhuang, Chuang',
		'zu' => 'Zulu'
	);

	foreach ($languages as $lang_id => $lang_text)
	{
		if (! file_exists( __DIR__ . '/../languages/' . $lang_id . '.po' ))
		{
			unset($languages[$lang_id]);
			continue;
		}
	}

	$GLOBALS['languages'] = $languages;
}

ops_langs();

?>