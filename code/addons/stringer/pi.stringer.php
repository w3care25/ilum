<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Stringer Class
 *
 * @package		ExpressionEngine
 * @category	Plugin
 * @author		Piyush Patel
 * @copyright	Copyright (c) 2017, Zealousweb
 * @link		http://www.zealousweb.com/
 */

require PATH_THIRD.'stringer/config.php';

$plugin_info = array(
    'pi_name'           => ZEAL_S_NAME,
    'pi_version'        => ZEAL_S_VER,
    'pi_author'         => ZEAL_S_AUTHOR,
    'pi_author_url'     => ZEAL_S_AUTHOR_URL,
    'pi_description'    => ZEAL_S_DESC,
    'pi_usage'          => Stringer::usage()
);

class Stringer {
   
    public static $name         = ZEAL_S_NAME;
    public static $version      = ZEAL_S_VER;
    public static $author       = ZEAL_S_AUTHOR;
    public static $author_url   = ZEAL_S_AUTHOR_URL;
    public static $typography   = FALSE;

    public $return_data = "";

    // --------------------------------------------------------------------

    /**
     * Stringer
     *
     *  Purpose: If a field is stored as XHTML can reformat to plain text.
     *	Handy to use in form fields.
     *
     * @access  public
     * @return  string
     */
    public function __construct()
    {
		$str = ee()->TMPL->tagdata;
        $str = strip_tags($str);
        $newStr = $str;
        return $newStr;
       
    }  
    public function stripTags(){
        
        $str = ee()->TMPL->tagdata;
        // Allow tags with words, chars, append.
        $allowTag = ee()->TMPL->fetch_param('allow_tags');
        $words = ee()->TMPL->fetch_param('words');
        $append = ee()->TMPL->fetch_param('append', '');
        $chars = ee()->TMPL->fetch_param('chars');
        $cutoff = ee()->TMPL->fetch_param('cutoff');
        $chars_start = (ee()->TMPL->fetch_param('chars_start') ? ee()->TMPL->fetch_param('chars_start') : 0);
 
        if(isset($cutoff) && $cutoff != "") {
            $cutoff_content = $this->truncateCutoff($str, $cutoff, $words, $allowTag, $append);
            // Strip the HTML
            $newStr = (strpos($str, $cutoff) ? strip_tags($cutoff_content, $allowTag) : strip_tags($cutoff_content, $allowTag));
        } elseif (isset($chars) && $chars != "") {
            $str = strip_tags($str, $allowTag);
            if (str_word_count($str) <= $words ){
                $newStr = $str;
            } else {
                $newStr = $this->trunChars($str,$chars_start,$chars,$append);
            }
        } elseif (isset($words) && $words != "") {
            $str = strip_tags($str, $allowTag);
            if (str_word_count($str) <= $words ){
                $newStr = $str;
            } else {
                $newStr = $this->trunWords($str,$words,$append);
            }
        } else {
            $str = strip_tags($str, $allowTag);
            $newStr = $str;
        }
        return preg_replace('/\s\s+/', ' ', $newStr);
    }
    public function tags(){

        $str = ee()->TMPL->tagdata;   
         // Remove tags 
        $removeTag = ee()->TMPL->fetch_param('remove_tags');

        if(isset($removeTag) && $removeTag != "") {
            $removeTag = str_replace('>', '', $removeTag);
            $removeTag = str_replace('<', '', $removeTag);
            $removeTagArray = explode("|", $removeTag);
            $newStr = $this->removeTags($str, $removeTagArray); 
        }
        return $newStr;
    }
    // Remove tags call function for remove tag from content. 
    public function removeTags($str, $tags = array(), $invert = FALSE) 
    { 
         foreach($tags as $tag)
         {
             $str = preg_replace("/<\\/?" . $tag . "(.|\\s)*?>/", "", $str);
         }
         return $str; 
    } 

    // Truncate words with append functionality.
    public function trunWords($str, $limit, $append) {
        $num_words = str_word_count($str, 0);
        if ($num_words > $limit) {
            $words = str_word_count($str, 2);
            $pos = array_keys($words);
            $str = substr($str, 0, ($pos[$limit]-1)) . $append;
        }
        return $str;
    }

    // Truncate chars with char start and append functionality.
    public function trunChars($str, $chars_start, $limit, $append) {
        $str = substr((trim($str)), $chars_start, $limit) . $append;
        return $str;
    }

    public function truncateCutoff($content, $cutoff, $words, $allow, $append) {
        $pos = strpos($content, $cutoff);
        if ($pos != FALSE) {
            $content = substr($content, 0, $pos) . $append;
        } elseif ($words != "") {
            $content = $this->trunWords(strip_tags($content, $allow), $words, '') . $append;
        }
        return $content;
    }
    // Sting Uppercase
    public function upperCase() {
        $str = ee()->TMPL->tagdata;
        return strtoupper($str);
    }

    // String Lowercase
    public function lowerCase() {
        $str = ee()->TMPL->tagdata;
         return strtolower($str);
    }

    // String First character Uppercase
    public function upperCaseFirst() {
        $str = ee()->TMPL->tagdata;
        return ucwords(strtolower($str));
    }

    // String length count
    public function charlength() {
        $str = ee()->TMPL->tagdata;
        return strlen($str);
    }

    // String words legnth count
    public function wordLength() {
        $str = ee()->TMPL->tagdata;
        return str_word_count($str);
    }

    // String find and replace function.
    public function find_replace(){
        $str = ee()->TMPL->tagdata;
        $find = ee()->TMPL->fetch_param('find');
        $replace = ee()->TMPL->fetch_param('replace');
        if ( $find == "|"){
            $findArray = array($find);
        } else {
            $findArray = explode("|", $find);
        }
        if ( $replace == "|"){
            $replaceArray = array($replace);
        } else {
            $replaceArray = explode("|", $replace);
        }
        return str_replace($findArray,$replaceArray,$str);
    }

    // Find string and explode in giver seperater
    public function explode(){

        $separator = ee()->TMPL->fetch_param('separator');
        $str = ee()->TMPL->fetch_param('string');
        $explore_data = [];
        $i = $j = 1;

        //check seperater value exist or not
        if($separator != ""){
            $explore_data = explode($separator,$str);
        }

        //creating a blank array
        for($j=1; $j<=20; $j++){
            $explore_output[0]["string".$j] = "";
        }

        //creating a tag
        foreach($explore_data as $key => $value) {
            $explore_output[0]["string".$i] = $value;
            $i = $i+1;
        }
        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $explore_output);
    }

    // Number formate for decimal
    public function numberFormat(){
        $str = ee()->TMPL->tagdata;
        $decimals = ee()->TMPL->fetch_param('decimals') ? (int)ee()->TMPL->fetch_param('decimals') : 0;
        $dec_point = ee()->TMPL->fetch_param('dec_point') ? ee()->TMPL->fetch_param('dec_point') : ".";
        $thousands_sep = ee()->TMPL->fetch_param('thousands_sep') ? ee()->TMPL->fetch_param('thousands_sep') : ",";
        return number_format((float)$str,$decimals,$dec_point,$thousands_sep);  
    }

    // String trim in all cases left, right 
    public function trim()
    {       
        $str = ee()->TMPL->tagdata;

        $sides  = ee()->TMPL->fetch_param('side');
        
        switch ($sides)
        {
            case "left":
                return ltrim($str);
                break;
                
            case "right":
                return rtrim($str);
                break;
                
            default:
                return trim($str);
        }
        
    }

    // Slug generate as per needed
    public function slug()
    {
        $separator = ee()->TMPL->fetch_param('separator') ? ee()->TMPL->fetch_param('separator') : "-";
        $case = ee()->TMPL->fetch_param('separator') ? ee()->TMPL->fetch_param('case') : "LOWER";
        
        switch (strtoupper($case)) {
            case "UPPER":
                $string = strtoupper(ee()->TMPL->tagdata);
                break;
                
            case "NOCHANGE":
                $string = ee()->TMPL->tagdata;
                break;
                
            default:
                $string = strtolower(ee()->TMPL->tagdata);
        }
        
        return preg_replace('/[^A-Za-z0-9-]+/', $separator, $string);
    }
    
    // Wordwrap 
    public function wordwrap()
    {
        $width = ee()->TMPL->fetch_param('width') ? ee()->TMPL->fetch_param('width') : 75;
        $break = ee()->TMPL->fetch_param('break') ? ee()->TMPL->fetch_param('break') : "\n";
        $cut = (ee()->TMPL->fetch_param('cut') == "true") ? TRUE : FALSE;
        return wordwrap(ee()->TMPL->tagdata,$width,$break,$cut);
    }
    public function hash(){
        $str = ee()->TMPL->tagdata;
        $algo = ee()->TMPL->fetch_param('algo');
        switch ($algo)
        {
            case "decode":
                return base64_decode($str);
                break;
                
            case "encode":
                return base64_encode($str);
                break;
                
            default:
                $hashAlgos = hash_algos();
                if(in_array($algo, $hashAlgos)){
                    $hashString = hash($algo, $str,false); 
                } else {
                    $hashString = hash('md5', $str,false);
                }
                return $hashString;
        }
        
    }

    // --------------------------------------------------------------------

    /**
     * Usage
     *
     * This function describes how the plugin is used.
     *
     * @access  public
     * @return  string
     */
    public static function usage()
    {
        ob_start();  ?>

     Stringer strips the HTML from your content.
    its allow all the html tag from your content and you are remove specific tag from your content.
    Stringer allow you to create excerpt from your content and append, cutoff, limit character and words.

    <!-- Only Allow tag in strip tags string-->
    {exp:stringer:striptags allow_tags="<p>|<em>|<strong>"}
        {your_content}
    {/exp:stringer:striptags}

    <!-- remove tag from string -->
    {exp:stringer:tags remove_tags="<a>|<blockquote>|<p>"}
        {your_content}
    {/exp:stringer:tags}

    <!-- Allow strip tag with append and limit words -->
    {exp:stringer:striptags allow_tags="<a>|<blockquote>|<p>" append="..." words="50"}
        {your_content}
    {/exp:stringer:striptags}

    <!-- Cut off string with allow tag , append -->
    {exp:stringer:striptags allow_tags="<a>" cutoff="<!-- More -->"  append="..."}
        {your_content}
    {/exp:stringer:striptags}
    
    <!-- Allow strip tag with append and  chars start to limit chars -->
    {exp:stringer:striptags allow_tags="<p>" chars_start="10" chars="50" append="..."}
        {your_content}
    {/exp:stringer:striptags}
    
    Strigner provides the following useful text manipulations....

    {exp:stringer:uppercase}this string is uppercase.{/exp:stringer:uppercase}

    {exp:stringer:lowercase}THIS STRING IS LOWERCASE{/exp:stringer:lowercase}

    {exp:stringer:uppercasefirst}this string first character upppercase{/exp:stringer:uppercasefirst}

    {exp:stringer:charlength}number of characters in a string.{/exp:stringer:charlength}

    {exp:stringer:wordlength}string total words length{/exp:stringer:wordlength}

    {exp:stringer:find_replace find="text|and" replace="string|or"}Multiple text find and replace{/exp:stringer:find_replace}

    {exp:stringer:find_replace find="|" replace="/"}slash|replace|{/exp:stringer:find_replace}

    {exp:stringer:numberformat decimals="2" dec_point="." thousands_sep=","}1234567890{/exp:stringer:numberformat}

    {exp:stringer:trim side="left"} string trimming! {/exp:stringer:trim} - 
    - side[left,right,both]
    
    {exp:stringer:slug separator="-" case="lower"}Generate Slug{/exp:stringer:slug}
    - case [=lower, upper, nochange]
    
    {exp:stringer:wordwrap width="5" break="<br>" cut="true"}This is wordwarp string{/exp:stringer:wordwrap}

    {exp:stringer:hash algo="md5"}password{/exp:stringer:hash}
      algo [="md2", "md4", "md5", "sha1", "sha224", "sha256", "sha384", "sha512", "ripemd128", "ripemd160", "ripemd256", "ripemd320", "whirlpool", "tiger128,3", "tiger160,3", "tiger192,3", "tiger128,4", "tiger160,4", "tiger192,4", "snefru", "snefru256", "gost", "gost-crypto", "adler32", "crc32", "crc32b", "fnv132", "fnv1a32", "fnv164", "fnv1a64", "joaat", "haval128,3", "haval160,3", "haval192,3", "haval224,3", "haval256,3", "haval128,4", "haval160,4", "haval192,4", "haval224,4", "haval256,4", "haval128,5", "haval160,5", "haval192,5", "haval224,5", "haval256,5"]
    <?php
        $buffer = ob_get_contents();
        ob_end_clean();

        return $buffer;
    }
    // END
}
/* End of file pi.stringer.php */
/* Location: ./system/user/addons/stringer/pi.stringer.php */