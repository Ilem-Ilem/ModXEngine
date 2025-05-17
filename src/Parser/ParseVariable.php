<?php 

namespace ModXengine\Parser;

class ParseVariable{

    public static function transformContenVars(string $content, array $data, array $loopVars)
    {
        $global_data = $data;
       return preg_replace_callback(
            '/<:\s*(\w+)\s*:>/',
            function ($matches) use ($global_data, $loopVars){
                $var = $matches[1];
                if (isset($loopVars[$var]) || array_key_exists($var, $global_data)) {
                    return '<?php echo htmlspecialchars($' . $var . ', ENT_QUOTES, \'UTF-8\'); ?>';
                }
              
                return "<!-- Variable $var not found -->";
            },
            $content
        );
    }
}