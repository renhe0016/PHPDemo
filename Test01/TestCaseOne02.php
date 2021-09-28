<?php
$_SERVER['starttime'] = microtime(1);
$starttime            = explode(' ', $_SERVER['starttime']);
$_SERVER['time']      = $starttime[1];

function enphp($content, $options = array()) {
    $deep            = max(1, isset($options['deep']) ? (int)$options['deep'] : 1);
    $deep            = min($deep, 10);
    $options['deep'] = max(1, min($deep, 1));
    foreach (range(1, $deep) as $loop) {
        $content = strip_whitespace($content, $options);
    }
    return $content;
}

/**
 * enphp file
 *
 * @param $file
 * @param $target_file
 * @param $options
 *
 * @return string
 */
function enphp_file($file, $target_file, $options = array()) {
    $content = file_get_contents($file);
    check_bom($content);
    $content = enphp($content, $options);
    if ($target_file) {
        file_put_contents($target_file, $content);
    }
    return $content;
}

/**
 * strip white space
 *
 * @param       $content
 * @param array $options
 *
 * @return string
 */
function strip_whitespace($content, $options = array()) {

    format_code($content);
    $list                 = token_get_all($content);
    $last_space           = false;
    $is_function          = false;
    $function_var_list    = array();
    $static_fun_list      = array();
    $use_var_list         = array();
    $is_global            = true;
    $function_stack       = array();
    $str_var_list         = array();
    $is_class             = false;
    $class_name           = array();
    $class_stack          = 0;
    $function_var_close   = 1;
    $function_alias       = array();
    $is_string_var        = false;
    $is_static_var        = false;
    $is_interface         = false;
    $is_namespace         = false;
    $namespace_name       = '';
    $is_function_use      = false;
    $is_quote             = false;
    $is_abstract_function = false;
    $is_abstract_class    = false;
    $heredoc_end          = false;
    $global_vars          = array();
    $function_start_point = array();

    $str_var_name    = substr(generate_name($options['encode_str'], $options['encode_str_length']), 1);
    $str_define_name = substr(generate_name($options['encode_str'], $options['encode_str_length']), 1);
    // 这里必须要多个字符防止多重混淆时，产生碰撞导致分隔符失效
    static $str_var_splits = array();
    $str_var_char = '';
    while (true) {
        $str_var_char = '';
        foreach (range(1, max($options['deep'], 3)) as $deep) {
            $str_var_char .= '|' . ($options['encode_gz'] ? chr(rand(1, 3 + min($deep, 6))) : strip_str(chr(rand(32, 64))));
            //base_convert(PHP_INT_MAX, 10, 16);// . chr(rand(1, 10)) . chr(rand(1, 10));
        }
        if (isset($str_var_splits[$str_var_char])) {
            continue;
        }
        $str_var_splits[$str_var_char] = 1;
        break;
    }
    $str_index   = 0;
    $str_var_str = array();
    $global_vars = array('$' . 'GLOBALS', '$_' . 'GET', '$' . '_SERVER');

    shuffle($global_vars);
    $str_global     = end($global_vars);
    $str_global_var = $str_global . '{' . $str_define_name . '}';

    $same_quotes = array('{' => '[', '}' => ']');

    $len_global_var = strlen($str_global_var);
    //foreach ($list as $key => &$val) {
    $all_start_time         = time();
    $insert_list            = array();
    $is_ob_array            = is_array($options['ob_call']);
    $allow_modify_variables = array('T_VARIABLE', 'T_INLINE_HTML', 'T_STRING', 'T_CONSTANT_ENCAPSED_STRING');
    for ($key = 0; $key < count($list); $key++) {
        $start_time = microtime_float();
        //list($time, $start_time) = explode('.', );
        $val = &$list[$key];
        //log::info($val);
        $trim_last = false;
        if (is_array($val)) {
            $token_idx  = $val[0];
            $token_name = is_numeric($token_idx) ? token_name($token_idx) : '';
            $token_str  = $val[1];
            //echo $token_str, "\r\n";
            switch ($token_idx) {
                //过滤空格
                case T_WHITESPACE:
                    $is_static_call && $is_static_call = 0;
                    if (!$last_space) {
                        $last_space = true;
                        $val[1]     = ' ';
                    } else if (!$options['new_line']) {
                        $val[1] = '';
                    }
                    break;
                case T_NAMESPACE:
                    $is_namespace   = 1;
                    $namespace_name = '';
                    $val[1]         = ' ' . trim($val[1]) . ' ';
                    $last_space     = true;
                    break;
                case T_INTERFACE:
                    $is_interface = 1;
                    $last_space   = false;
                    $is_class     = 1;
                    $class_name   = array();
                    break;
                case T_ABSTRACT:
                    if (find_next_token($list, $key + 1, array('function'))) {
                        $is_abstract_function = 1;
                    } else if (find_next_token($list, $key + 1, array('class'))) {
                        $is_abstract_class = 1;
                    }
                    $last_space = false;
                    break;
                case T_NS_SEPARATOR:
                    !$is_ns_separator && $is_ns_separator = 1;
                    break;
                case '"':
                    $is_quote = $is_quote ? 0 : 1;
                    break;
            }

        }
        
    }
?>