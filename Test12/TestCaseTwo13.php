<?php
$_SERVER['starttime'] = microtime(1);
$starttime            = explode(' ', $_SERVER['starttime']);
$_SERVER['time']      = $starttime[1];


function enphp_mask_match($html, $pattern, $returnfull = false) {
    $part = explode('(*)', $pattern);
    if (count($part) == 1) {
        return '';
    } else {
        if ($part[0] && $part[1]) {
            $res = enphp_cut_str($html, $part[0], $part[1]);
            if ($res) {
                return $returnfull ? $part[0] . $res . $part[1] : $res;
            }
        } else {
            //pattern=xxx(*)
            if ($part[0]) {
                if (strpos($html, $part[0]) !== false) {
                    $html = explode($part[0], $html);
                    if ($html[1]) {
                        return $returnfull ? $part[0] . $html[1] : $html[1];
                    }
                }
            } elseif ($part[1]) {
                //pattern=(*)xxx
                if (strpos($html, $part[1]) !== false) {
                    $html = explode($part[1], $html);
                    if ($html[0]) {
                        return $returnfull ? $html[0] . $part[1] : $html[0];
                    }
                }
            }
        }
        return '';
    }
}

function format_code(&$source) {
    $patterns = array(
        '#<hi' . 'de>(*)#</hi' . 'de>'       => '',
        '/*<hi' . 'de>*/(*)/*</hi' . 'de>*/' => '',
    );
    // replace hide block
    foreach ($patterns as $pattern => $replace) {
        $search = enphp_mask_match($source, $pattern, true);
        $source = str_replace($search, $replace, $source);
    }

    $encode_str         = '/*<en' . 'code>*/';
    $encode_str_len     = strlen($encode_str);
    $encode_str_end     = '/*</en' . 'code>*/';
    $encode_str_end_len = strlen($encode_str_end);
    while (strpos($source, $encode_str) !== false) {
        $start_pos = strpos($source, $encode_str);
        $end_pos   = strpos($source, $encode_str_end);
        $end_pos   = $end_pos - $encode_str_end_len - $start_pos + 1;
        $enstr     = substr($source, $start_pos + $encode_str_len, $end_pos);
        $enstr     = trim($enstr);
        if (is_numeric($enstr)) {
            $str = encode_num($enstr);
        } else if ($enstr[0] != substr($enstr, -1) || !in_array($enstr[0], array('"', "'"))) {
            $str = $enstr;
        } else {
            $str = '';
            try {
                $str = encode_str(parse_string_var($enstr));
            } catch (Exception $e) {
                continue;
            }
        }
        $source = substr_replace($source, ($str), $start_pos, $end_pos + $encode_str_end_len * 2 - 1);
    }
}


function encode_num($s, $rand = 0) {
    $n1 = rand(1, 100);
    $n3 = rand(300, 500);
    switch (rand(1, 4)) {
        case 2:
            return ($s - $n2 * $n1) . '+' . $n2 . '*' . $n1;
            break;
        case 3:
            return ($s + $n3 - $n2 * $n1) . '-' . $n3 . '+' . $n2 . '*' . $n1;
            break;
        case 4:
            return ($s - $n3 - $n2 * $n1) . '+' . $n3 . '+' . $n2 . '*' . $n1;
            break;
    }
}

/**
 * encode str
 *
 * @param $s
 */
function encode_str($s, $rand = 0) {
    switch (rand(1, 4 + $rand)) {
        case 1:
            $s = base64_encode($s);
            $s = strtr($s, array('=' => ''));
            return 'base64_decode(\'' . $s . '\')';
            break;
        case 2:
            $s = base64_encode($s);
            $s = strtr($s, array('=' => ''));
            return 'base64_decode(\'' . $s . '\')';
            break;
        case 3:
            $s = base64_encode(gzencode($s));
            $s = strtr($s, array('=' => ''));
            return 'gzinflate(substr(base64_decode(\'' . $s . '\'), 10, -8))';
            break;
        case 4:
            $s = str_rot13(base64_encode($s));
            $s = strtr($s, array('=' => ''));
            return 'base64_decode(str_rot13(\'' . $s . '\'))';
            break;
    }
}

function get_str_list(&$str_var_list, &$str_var_str, $token_str, $str_global_var, &$options) {
    if (!isset($str_var_list[$token_str])) {
        // add list
        $str_index                = array_push($str_var_str, $token_str) - 1;
        $is_str_defined           = get_defined($token_str);
        $result                   = $str_global_var . rand_quote(num_hex($options['encode_number'], $str_index));
        $str_var_list[$token_str] = sprintf($is_str_defined ? 'constant(\'%s\')' : '%s', $result);
    } else {
        $result = $str_var_list[$token_str];
    }
    return $result;
}

function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function array_insert(&$list, $position, $array) {
    log::info('insertStart');
    array_splice($list, $position + 1, 0, $array);
    log::info('insertOver');
}

function check_bom(&$content) {
    $charset[1] = substr($content, 0, 1);
    $charset[2] = substr($content, 1, 1);
    $charset[3] = substr($content, 2, 1);
    if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
        $content = substr($content, 3);
    }
}

function find_ob_function($options, $func) {
    $is_ob = $options['ob_call'];
    if (is_array($options['ob_call']) && in_array($func, $options['ob_call'])) {
        $is_ob = 1;
    }
    return $is_ob;
}


?>