<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
require_once 'ImgCompress.php';
/**
 * 超级时光鸡
 * @author 即刻学术 www.ijkxs.com
 * @package custom
 */

class DyUtils {

    public static function formatDate($obj,$time, $format) {
        if (strtoupper($format) == 'NATURAL') {
            return self::naturalDate($time,"all");
        }else{
            //return self::naturalDate($time);//强制开启友好化格式化时间
            $obj->date($format);
        }
    }

    public static function unicodeDecode($unicode_str){
        $json = '{"str":"'.$unicode_str.'"}';
        $arr = json_decode($json,true);
        if(empty($arr)) return '';
        return html_entity_decode($arr['str']);
//        return htmlspecialchars($arr['str']);
    }


    public static function naturalDate($from,$level) {
        $now = time();
        $between = time() - $from;
        if ($level != "short"){
            if ($between > 31536000) {
                return date(I18n::dateFormat(), $from);
            } else if ($between > 0 && $between < 172800                                // 如果是昨天
                && (date('z', $from) + 1 == date('z', $now)                             // 在同一年的情况
                    || date('z', $from) + 1 == date('L') + 365 + date('z', $now))) {    // 跨年的情况
                return _e('昨天 %s', date('H:i', $from));
            }
        }
        $f = array(
            '31536000' => '%d 年前',
            '2592000' => '%d 个月前',
            '604800' => '%d 星期前',
            '86400' => '%d 天前',
            '3600' => '%d 小时前',
            '60' => '%d 分钟前',
            '1' => '%d 秒前',
        );
        if ($between == 0){
            return _e("刚刚");
        }
        foreach ($f as $k => $v) {
            if (0 != $c = floor($between / (int)$k)) {
                if ($c == 1) {//一倍整除
                    return _e(sprintf($v, $c));
                }
                return _e($v, $c);//多倍整除
            }
        }
        return "";
    }

    public static function avatarHtml($obj){
        $email = $obj->mail;
        $avatorSrc = DyUtils::getAvator($email,65);

        return '<img nogallery src="'.$avatorSrc.'" class="img-40px photo img-square normal-shadow">';


    }

    public static function getAvator($email,$size){
        $options = Helper::options();
        $cdnUrl = $options->CDNURL;
        if (@ in_array('emailToQQ',$options->featuresetup)){
            $str = explode('@', $email);
            if (@$str[1] == 'qq.com' && @ctype_digit($str[0]) && @strlen($str[0]) >=5
                && @strlen($str[0])<=11) {
                $avatorSrc = 'https://q.qlogo.cn/g?b=qq&nk='.$str[0].'&s=100';
            }else{
                $avatorSrc = DyUtils::getGravator($email,$cdnUrl,$size);
            }
        }else{
            $avatorSrc = DyUtils::getGravator($email,$cdnUrl,$size);
        }
        return $avatorSrc;
    }

    public static function getGravator($email,$host,$size){
        $options = Helper::options();
        $default = '';
        if (strlen($options->defaultAvator) > 0){
            $default = $options->defaultAvator;
        }
        $url = '/';//自定义头像目录,一般保持默认即可
        //$size = '40';//自定义头像大小
        $rating = Helper::options()->commentsAvatarRating;
        $hash = md5(strtolower($email));
        $avatar = $host . $url . $hash . '?s=' . $size . '&r=' . $rating . '&d=' . $default;
        return $avatar;
    }

    public static function sticky($archive){
        $db = Typecho_Db::get();
        $paded = $archive->request->get('page', 1);
        $sticky_post = $db->fetchRow($archive->select()->where('cid = ?', 10));
        $archive->push($sticky_post);
        //$select->where('table.contents.cid != ?', 10);

    }

    public static function loginAction($archive){
        $requestURL = $archive->request->getRequestUrl();

        $requestURL = str_replace('&_pjax=%23content', '', $requestURL);
        $requestURL = str_replace('?_pjax=%23content', '', $requestURL);
        $requestURL = str_replace('_pjax=%23content', '', $requestURL);

        return $archive->widget('Widget_Security')->getTokenUrl($archive->rootUrl.'/index.php/action/login');
    }

    /**
     * @param $url
     * @return array 返回是的歌单解析信息的数组
     */
    public static function parseMusicUrl($url){
        $result = array();
        //如果为空，返回一个默认正确的URL
        $url=trim($url);
        if(empty($url))
            $url = 'http://music.163.com/#/my/m/music/playlist?id=883542351';

        $media='netease';$id='883542351';$type='playlist';
        if(strpos($url,'163.com')!==false){
            $media='netease';
            if(preg_match('/playlist\?id=(\d+)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
            elseif(preg_match('/toplist\?id=(\d+)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
            elseif(preg_match('/album\?id=(\d+)/i',$url,$id))list($id,$type)=array($id[1],'album');
            elseif(preg_match('/song\?id=(\d+)/i',$url,$id))list($id,$type)=array($id[1],'song');
            elseif(preg_match('/artist\?id=(\d+)/i',$url,$id))list($id,$type)=array($id[1],'artist');
        }
        elseif(strpos($url,'qq.com')!==false){
            $media='tencent';
            if(preg_match('/playlist\/([^\.]*)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
            elseif(preg_match('/album\/([^\.]*)/i',$url,$id))list($id,$type)=array($id[1],'album');
            elseif(preg_match('/song\/([^\.]*)/i',$url,$id))list($id,$type)=array($id[1],'song');
            elseif(preg_match('/singer\/([^\.]*)/i',$url,$id))list($id,$type)=array($id[1],'artist');
        }
        elseif(strpos($url,'xiami.com')!==false){
            $media='xiami';
            if(preg_match('/collect\/(\w+)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
            elseif(preg_match('/album\/(\w+)/i',$url,$id))list($id,$type)=array($id[1],'album');
            elseif(preg_match('/[\/.]\w+\/[songdem]+\/(\w+)/i',$url,$id))list($id,$type)=array($id[1],'song');
            elseif(preg_match('/artist\/(\w+)/i',$url,$id))list($id,$type)=array($id[1],'artist');
            if(!preg_match('/^\d*$/i',$id,$t)){
                $data=curl($url);
                preg_match('/'.$type.'\/(\d+)/i',$data,$id);
                $id=$id[1];
            }
        }
        elseif(strpos($url,'kugou.com')!==false){
            $media='kugou';
            if(preg_match('/special\/single\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
            elseif(preg_match('/#hash\=(\w+)/i',$url,$id))list($id,$type)=array($id[1],'song');
            elseif(preg_match('/album\/[single\/]*(\d+)/i',$url,$id))list($id,$type)=array($id[1],'album');
            elseif(preg_match('/singer\/[home\/]*(\d+)/i',$url,$id))list($id,$type)=array($id[1],'artist');
        }
        elseif(strpos($url,'baidu.com')!==false){
            $media='baidu';
            if(preg_match('/songlist\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'playlist');
            elseif(preg_match('/album\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'album');
            elseif(preg_match('/song\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'song');
            elseif(preg_match('/artist\/(\d+)/i',$url,$id))list($id,$type)=array($id[1],'artist');
        }
        else{//输入的地址不能匹配到上述的第三方音乐平台
            $result = array(
                'title' =>  '歌曲名',
                'author' => '歌手',
                'url' => $url
            );
            return $result;
        }
        $result = array(
            'media' =>  $media,
            'id' => $id,
            'type' => $type
        );
        return $result;
    }

    public static function getImageNumRandomArray($NeedSize,$imageNum){
        $indexNumberArray = array();
        if ($NeedSize > $imageNum){
            //这种情况下图片重复是不可避免的，原因是缩略图的数目不够
            /*for($i = 0;$i<$options->pageSize - $options->RandomPicAmnt ;$i++){
                $indexNumberArray[] = random_int(1, $options->RandomPicAmnt);
            }*/
            while (count($indexNumberArray) < $NeedSize){
                $number = rand(1, $imageNum);
                $indexNumberArray[] = $number;
            }
        }else{
            while (count($indexNumberArray) < $NeedSize){
                $number = rand(1, $imageNum);
                $flag = false;//当前生成的数字是否已经存在了
                foreach ($indexNumberArray as $value){
                    if ($value == $number){
                        $flag = true;
                        break;
                    }
                }
                if (!$flag){
                    $indexNumberArray[] = $number;
                }
            }
        }
        //print_r($indexNumberArray);
        return $indexNumberArray;
    }

    /**
     * 16进制颜色转rgb颜色
     * @param $hexColor
     * @return string
     */
    public static function hex2rgb($hexColor) {
        $color = str_replace('#', '', $hexColor);
        if (strlen($color) > 3) {

            $rgb = hexdec(substr($color, 0, 2)) . "," . hexdec(substr($color, 2, 2)). "," . hexdec(substr($color, 4, 2));
        } else {
            $color = $hexColor;
            $r = substr($color, 0, 1) . substr($color, 0, 1);
            $g = substr($color, 1, 1) . substr($color, 1, 1);
            $b = substr($color, 2, 1) . substr($color, 2, 1);
            $rgb = hexdec($r) . "," . hexdec($g) . "," . hexdec($b);
        }
        return $rgb;
    }


    /**
     * 替换默认的preg_replace_callback函数
     * @param $pattern
     * @param $callback
     * @param $subject
     * @return string
     */
    public static function handle_preg_replace_callback($pattern, $callback, $subject){
        return self::handleHtml($subject,function ($content) use ($callback, $pattern) {
            return preg_replace_callback($pattern,$callback, $content);
        });
    }


    public static function handle_preg_replace($pattern, $replacement, $subject){
        return self::handleHtml($subject,function ($content) use ($replacement, $pattern) {
            return preg_replace($pattern,$replacement, $content);
        });
    }

    /**
     * 处理 HTML 文本，确保不会解析代码块中的内容
     * @param $content
     * @param callable $callback
     * @return string
     */
    public static function handleHtml($content, $callback) {
        $replaceStartIndex = array();
        $replaceEndIndex = array();
        $currentReplaceId = 0;
        $replaceIndex = 0;
        $searchIndex = 0;
        $searchCloseTag = false;
        $contentLength = strlen($content);
        while (true) {
            if ($searchCloseTag) {
                $tagName = substr($content, $searchIndex, 4);
                if ($tagName == "<cod") {
                    $searchIndex = strpos($content, '</code>', $searchIndex);
                    if (!$searchIndex) {
                        break;
                    }
                    $searchIndex += 7;
                } elseif ($tagName == "<pre") {
                    $searchIndex = strpos($content, '</pre>', $searchIndex);
                    if (!$searchIndex) {
                        break;
                    }
                    $searchIndex += 6;
                } elseif ($tagName == "<kbd") {
                    $searchIndex = strpos($content, '</kbd>', $searchIndex);
                    if (!$searchIndex) {
                        break;
                    }
                    $searchIndex += 6;
                } elseif ($tagName == "<scr") {
                    $searchIndex = strpos($content, '</script>', $searchIndex);
                    if (!$searchIndex) {
                        break;
                    }
                    $searchIndex += 9;
                } elseif ($tagName == "<sty") {
                    $searchIndex = strpos($content, '</style>', $searchIndex);
                    if (!$searchIndex) {
                        break;
                    }
                    $searchIndex += 8;
                } else {
                    break;
                }


                if (!$searchIndex) {
                    break;
                }
                $replaceIndex = $searchIndex;
                $searchCloseTag = false;
                continue;
            } else {
                $searchCodeIndex = strpos($content, '<code', $searchIndex);
                $searchPreIndex = strpos($content, '<pre', $searchIndex);
                $searchKbdIndex = strpos($content, '<kbd', $searchIndex);
                $searchScriptIndex = strpos($content, '<script', $searchIndex);
                $searchStyleIndex = strpos($content, '<style', $searchIndex);
                if (!$searchCodeIndex) {
                    $searchCodeIndex = $contentLength;
                }
                if (!$searchPreIndex) {
                    $searchPreIndex = $contentLength;
                }
                if (!$searchKbdIndex) {
                    $searchKbdIndex = $contentLength;
                }
                if (!$searchScriptIndex) {
                    $searchScriptIndex = $contentLength;
                }
                if (!$searchStyleIndex) {
                    $searchStyleIndex = $contentLength;
                }
                $searchIndex = min($searchCodeIndex, $searchPreIndex, $searchKbdIndex, $searchScriptIndex, $searchStyleIndex);
                $searchCloseTag = true;
            }
            $replaceStartIndex[$currentReplaceId] = $replaceIndex;
            $replaceEndIndex[$currentReplaceId] = $searchIndex;
            $currentReplaceId++;
            $replaceIndex = $searchIndex;
        }

        $output = "";
        $output .= substr($content, 0, $replaceStartIndex[0]);
        for ($i = 0; $i < count($replaceStartIndex); $i++) {
            $part = substr($content, $replaceStartIndex[$i], $replaceEndIndex[$i] - $replaceStartIndex[$i]);
            if (is_array($callback)) {
                $className = $callback[0];
                $method = $callback[1];
                $renderedPart = call_user_func($className.'::'.$method, $part);
            } else {
                $renderedPart = $callback($part);
            }
            $output.= $renderedPart;
            if ($i < count($replaceStartIndex) - 1) {
                $output.= substr($content, $replaceEndIndex[$i], $replaceStartIndex[$i + 1] - $replaceEndIndex[$i]);
            }
        }
        $output .= substr($content, $replaceEndIndex[count($replaceStartIndex) - 1]);
        return $output;
    }


    public static function returnImageSrcWithSuffix($imageSrc = null,$cdnType = null, $width = 0, $height = 0){
        $isLocal = true;
        $options = Helper::options();
        if ($options->cdn_add == ""){
            return $imageSrc;
        }
        if($imageSrc!=null && strpos($imageSrc,$options->rootUrl) === false){//不是本地服务器图片
            $isLocal = false;
//            echo $imageSrc."|".$options->rootUrl;
        }else{//替换图片的域名地址为云存储空间地址
            $cdnArray = explode("|",$options->cdn_add);
//            echo $options->rootUrl . "|" .trim($cdnArray[0])."|".$imageSrc;
            $imageSrc = str_ireplace($options->rootUrl,trim($cdnArray[0]),$imageSrc);
            $cdnType = trim($cdnArray[1]);
        }
        return $imageSrc . self::getImageAddOn($options,$isLocal,$cdnType,$width,$height);
    }

    /**
     * 云存储选项
     * @param array $options 后台选项设置
     * @param bool $isLocal 是否是本地服务器图片
     * @param String $cdnType 云服务商类型
     * @param int $width 目标图片的宽度
     * @param int $height 目标图片的高度
     * @param string $location 是文章的图片还是别的，如首页头图
     * @return string
     */
    public static function getImageAddOn($options,$isLocal = false,$cdnType = null, $width = 0, $height = 0,$location
    = "index"){
        $addOn = "";//图片后缀
        if (!$isLocal){//不是本地服务器图片
            return $addOn;
        }
        if ($options->cdn_add!=""){//开启了镜像存储的功能

            if ($cdnType == null){//如果参数中没有cdnType，这里会进行获取cdn类型
                $cdnArray = explode("|",$options->cdn_add);
                $cdnType = trim($cdnArray[1]);
            }

            if (@in_array('0',$options->cloudOptions)){//启用了图片处理
                if ($cdnType == "ALIOSS" || $cdnType == "UPYUN"){//阿里云和又拍云
                    $addOn .= "!";//分隔符
                }else if ($cdnType == "QINIU"){//七牛云
                    $addOn .= "?";//分隔符
                }else if ($cdnType == "QCLOUD"){
                    $addOn .= "?imageMogr2";
                }
                if ($location == "post"){//为文章中的图片增加自定义后缀
                    $addOn .= trim($options->imagePostSuffix);
                }
                if (!($width == 0 && $height == 0)){
                    if ($height == 0){//根据宽度尺寸进行缩放
                        if ($cdnType == "UPYUN"){
                            $addOn .= "/fw/$width";
                        }else if ($cdnType == "ALIOSS"){//阿里云
                            $addOn .= "/x-oss-process=image/resize,w_$width";
                        }else if ($cdnType == "QINIU"){//七牛云
                            $addOn .=  "/imageView2/2/w/$width?imageslim";
                        }else if ($cdnType == "QCLOUD"){//腾讯云
                            $addOn .=  "/scrop/".$width."x";
                        }
                    }else if ($width === 0){//根据高度尺寸进行缩放
                        if ($cdnType == "UPYUN"){
                            $addOn .= "/fh/$height";
                        }else if ($cdnType == "ALIOSS"){
                            $addOn .= "/x-oss-process=image/resize,h_$height";
                        }else if ($cdnType == "QINIU"){//七牛云
                            $addOn .=  "/imageView2/2/h/$height";
                        }else if ($cdnType == "QCLOUD"){//腾讯云
                            $addOn .=  "/scrop/x".$height;
                        }
                    }else{//按照固定的宽高进行缩放
                        if ($cdnType == "UPYUN"){
                            $addOn .= "/fwfh/".$width."x".$height;
                        }else if ($cdnType == "ALIOSS"){
                            $addOn .= "/x-oss-process=image/resize,m_lfit,h_".$height.",w_".$width;
                        }else if ($cdnType == "QINIU"){//七牛云
                            $addOn .=  "/imageView2/2/w/".$width."/h/".$height;
                        }else if ($cdnType == "QCLOUD"){//腾讯云
                            $addOn .=  "/scrop/".$width."x".$height;
                        }
                    }
                }
                //todo:添加图片质量参数

                //添加图片无损压缩参数
                if ($cdnType == "UPYUN"){
                    $addOn .= "/compress/true";
                }else if ($cdnType == "ALIOSS"){

                }else if ($cdnType == "QINIU"){//七牛云
                    $addOn .=  "?imageslim";
                }
            }
        }
        return $addOn;
    }



    public static function returnDivLazyLoadHtml($originalSrc,$width,$height){
        $options = Helper::options();
        $placeholder = DyUtils::choosePlaceholder($options);
        $lazyLoadHtml = "";

        $originalSrc = self::returnImageSrcWithSuffix($originalSrc,null,$width,$height);

        if (in_array('lazyload',$options->featuresetup)){
            $imageSrc = $placeholder;
            $lazyLoadHtml = 'data-original="'.$originalSrc.'"';
        }else{
            $imageSrc = $originalSrc;
        }



        return $lazyLoadHtml. ' style="background-image: url('.$imageSrc.')"';
    }

    public static function returnImageLazyLoadHtml($base64 = false,$originalSrc,$width,$height){
        $options = Helper::options();
        $placeholder = DyUtils::choosePlaceholder($options);
        if (!$base64){
            $originalSrc = self::returnImageSrcWithSuffix($originalSrc,null,$width,$height);
        }

        if (in_array('lazyload',$options->featuresetup)){
            $imageSrc = $placeholder;
            $lazyLoadHtml = 'data-original="'.$originalSrc.'"';
        }else{
            $lazyLoadHtml = "";
            $imageSrc = $originalSrc;
        }

        return $lazyLoadHtml. ' src="'.$imageSrc.'"';
    }

    public static function choosePlaceholder($options){
        if (@in_array("opacityMode",$options->indexsetup)){//透明模式
            return Handsome_Config::OPACITY_PLACEHOLDER;
        }else{//普通占位符
            return Handsome_Config::NORMAL_PLACEHOLDER;

        }
    }

    public static function  getSj1ImageNum(){
        try{
            $basedir = dirname(dirname(__FILE__))."/usr/img/sj";
            $arr = scandir($basedir);
            $image = count(preg_grep("/^\d+\.jpg$/", $arr));
            return $image;
        }catch (Exception $e){
            print_r($e);
            return 5;
        }
    }

    /**
     * 获取右侧边栏的图片数目
     * @return int
     */
    public static function  getSj2ImageNum(){
        try{
            $basedir = dirname(dirname(__FILE__))."/usr/img/sj2";
            $arr = scandir($basedir);
            $image = count(preg_grep("/^\d+\.jpg$/", $arr));
            return $image;
        }catch (Exception $e){
            print_r($e);
            return 5;
        }
    }

    /**
     * 返回运行时间
     */
    public static function getOpenDays(){
        $options = Helper::options();
        $oldtime = $options->startTime;
        try{
            $catime = strtotime($oldtime);
            $now = time();
            $difference = $now - $catime;
            $year = floor($difference/31536000);
            if ($year >=1){
                $difference = $difference - $year * 31536000;
                $day = floor($difference/86400);
                return sprintf(_e("%d年%d天"),$year,$day);
            }else{//小于一年
                $day = floor($difference/86400);
                return sprintf(_e("%d天"),$day);
            }
        }catch (Exception $exception){
            return "null";
        }

    }

    /**
     * 返回最后更新时间
     */
    public static function getLatestTime($obj){
        $recent = $obj->widget('Widget_Contents_Post_Recent','pageSize=1');
        if($recent->have()){
            while($recent->next()){
                return DyUtils::naturalDate($recent->modified,"short");
            }
        }

    }

    public static function hEcho($text){
        if (strtoupper(Handsome_Config::HANDSOME_DEBUG_DISPLAY) == 'ON'){
            echo $text;
        }
    }

    public static function print_r($text){
        if (strtoupper(Handsome_Config::HANDSOME_DEBUG_DISPLAY) == 'ON'){
            print_r ($text);
        }
    }

    public static function var_dump($text){
        if (strtoupper(Handsome_Config::HANDSOME_DEBUG_DISPLAY) == 'ON'){
            var_dump ($text);
        }
    }

    public static function returnDefaultIfEmpty($target, $default){
        if (trim($target) == ""){
            return $default;
        }else{
            return $target;
        }
    }

    public static function getWordsOfContentPost($content){
        return mb_strlen(trim($content),"utf8");
    }

    /**
     * @param $blogUrl
     * @param $name
     * @param $pic
     * @param $type string,表示$pic内容是网络地址，local表示$pic内容是本地图片
     * @param string $suffix 图片后缀
     * @return string
     */
    public static function uploadPic($blogUrl, $name, $pic,$type,$suffix){
        $DIRECTORY_SEPARATOR = "/";
        $childDir = $DIRECTORY_SEPARATOR.'usr'.$DIRECTORY_SEPARATOR.'uploads' . $DIRECTORY_SEPARATOR .'time' .$DIRECTORY_SEPARATOR;
        $dir = __TYPECHO_ROOT_DIR__ . $childDir;
        if (!file_exists($dir)){
            mkdir($dir, 0777, true);
        }
        $fileName = $name. $suffix;
        $file = $dir .$fileName;
        //TODO:支持图片压缩
        if ($type == "web"){
            //开始捕捉
            $img = self::getDataFromWebUrl($pic);
        }else{
            $img = $pic;//本地图片直接就是二进制数据
        }
        $fp2 = fopen($file , "a");
        fwrite($fp2, $img);
        fclose($fp2);

        //压缩图片
        (new Imgcompress($file,1))->compressImg($file);

        return $blogUrl.$childDir.$fileName;
    }

    public function returnBlogUrl(){

    }

    public static  function getDataFromWebUrl($url){
        $file_contents = "";
        if (function_exists('file_get_contents')) {
            $file_contents = @file_get_contents($url);
        }
        if ($file_contents == "") {
            $ch = curl_init();
            $timeout = 30;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $file_contents = curl_exec($ch);
            curl_close($ch);
        }
        return $file_contents;
    }

    public static function isPluginAvailable($className,$dirName){
        if (class_exists($className)) {
            $plugins = Typecho_Plugin::export();
            $plugins = $plugins['activated'];
            if (is_array($plugins) && array_key_exists($dirName, $plugins)) {
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }

    }

    public static function isNotice2(){
        $DIRECTORY_SEPARATOR = "/";
        $childDir = $DIRECTORY_SEPARATOR.'usr'.$DIRECTORY_SEPARATOR.'themes' . $DIRECTORY_SEPARATOR .'handsome'
            .$DIRECTORY_SEPARATOR;
        $dir = __TYPECHO_ROOT_DIR__ . $childDir;
        $path = $dir."license";
        //检查license文件
        if (file_exists($path)){
            return false;
        }else{
            $body = file_get_contents($path);
            echo $body ;//输入文件内容
            if ($body == md5(Handsome_Config::AUTH)){
                return true;
            }else{
                return false;
            }
        }
    }


    /**
     * 加密算法
     * @param $data 明文数据
     * @return string
     */
    public static function encodeData($data){
        return self::md5($data);
//        return sha1(self::md5($data));
    }


    /**
     * md5 加密，加入特定字符串，避免太过于简单导致的问题
     * @param $data
     * @return string
     */
    public static function md5($data){
        return md5("handsome!@#$%^&*()-=+@#$%$".$data."handsome!@#$%^&*()-=+@#$%$");
    }

    /**
     * 二维数组去重
     * @param $arr
     * @param $key
     * @return array
     */
    public static function array_unset_tt($arr, $key)
    {
        //建立一个目标数组
        $res = array();
        foreach ($arr as $value) {
            //查看有没有重复项
            if (isset($res[$value[$key]])) {
                //有：销毁
                unset($value[$key]);
            } else {
                $res[$value[$key]] = $value;
            }
        }
        return $res;
    }

    public static function remove_last_comma($content){
        if (substr($content,-1) == ","){
            return substr($content,0,strlen($content)-1);
        }else{
            return $content;
        }
    }
}

/* 解析头像 */
function ParseAvatar($mail, $re = 0, $id = 0)
{
    global $options;
    // 多个头像源，可以自己换
    $JGravatars = array(
        'gravatar.helingqi.com/wavatar' => '禾令奇（默认）',
        'www.gravatar.com/avatar' => 'gravatar的www源',
        'cn.gravatar.com/avatar' => 'gravatar的cn源',
        'secure.gravatar.com/avatar' => 'gravatar的secure源',
        'sdn.geekzu.org/avatar' => '极客族',
        'cdn.v2ex.com/gravatar' => 'v2ex源',
        'dn-qiniu-avatar.qbox.me/avatar' => '七牛源[不建议]',
        'gravatar.loli.net/avatar' => 'loli.net源',
    );
    $a = $JGravatars[0];
    $b = 'https://' . $a . '/';
    $c = strtolower($mail);
    $d = md5($c);
    $f = str_replace('@qq.com', '', $c);
    if (strstr($c, "qq.com") && is_numeric($f) && strlen($f) < 11 && strlen($f) > 4) {
        $g = '//thirdqq.qlogo.cn/g?b=qq&nk=' . $f . '&s=100';
        if ($id > 0) {
            $g = $options->rootUrl . '?id=' . $id . '" data-type="qqtx';
        }
    } else {
        $g = $b . $d . '?d=mm';
    }
    if ($re == 1) {
        return $g;
    } else {
        echo $g;
    }
}

//获取Gravatar头像 QQ邮箱取用qq头像
function getGravatar($email, $s = 96, $d = 'mp', $r = 'g', $img = false, $atts = array())
{
    preg_match_all('/((\d)*)@qq.com/', $email, $vai);
    if (empty($vai['1']['0'])) {
        $url = 'https://cdn.v2ex.com/gravatar/';
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=$s&d=$d&r=$r";
        if ($img) {
            $url = '<img src="' . $url . '"';
            foreach ($atts as $key => $val)
                $url .= ' ' . $key . '="' . $val . '"';
            $url .= ' />';
        }
    } else {
        $url = 'https://q2.qlogo.cn/headimg_dl?dst_uin=' . $vai['1']['0'] . '&spec=100';
    }
    return $url;
}

/**
 * 评论者主页链接新窗口打开
 * 调用<?php CommentAuthor($comments); ?>
 */
function CommentAuthor($obj, $autoLink = NULL, $noFollow = NULL)
{    //后两个参数是原生函数自带的，为了保持原生属性，我并没有删除，原版保留
    $options = Helper::options();
    $autoLink = $autoLink ? $autoLink : $options->commentsShowUrl;    //原生参数，控制输出链接（开关而已）
    $noFollow = $noFollow ? $noFollow : $options->commentsUrlNofollow;    //原生参数，控制输出链接额外属性（也是开关而已...）
    if ($obj->url && $autoLink) {
        echo '<a href="' . $obj->url . '"' . ($noFollow ? ' rel="external nofollow"' : NULL) . (strstr($obj->url, $options->index) == $obj->url ? NULL : ' target="_blank"') . '>' . $obj->author . '</a>';
    } else {
        echo $obj->author;
    }
}