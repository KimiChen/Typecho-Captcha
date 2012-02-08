<?php
/**
 * 验证插件提供一个机器难以识别的图片，以防止机器人的评论攻击，同时也可以防止跨站的评论提交.
 * 验证码插件是基于<a href="http://www.phpcaptcha.org">securimage</a>开发的.
 * 
 * @package Typecho Captcha
 * @author qining & Kimi
 * @version 1.0.0
 * @link http://typecho.org
 */
class Captcha_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        if (!function_exists('gd_info')) {
            throw new Typecho_Plugin_Exception(_t('对不起, 您的主机不支持 gd 扩展, 无法正常使用此功能'));
        }
    
        Typecho_Plugin::factory('Widget_Feedback')->comment = array('Captcha_Plugin', 'filter');
        Typecho_Plugin::factory('Widget_Feedback')->trackback = array('Captcha_Plugin', 'filter');
        Typecho_Plugin::factory('Widget_XmlRpc')->pingback = array('Captcha_Plugin', 'filter');
        
        Helper::addAction('captcha', 'Captcha_Action');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {

        $dir = dirname(__FILE__) . '/securimage/';
        $fileList = getDirlist($dir . 'fonts');

        $image_height = new Typecho_Widget_Helper_Form_Element_Text('image_height', NULL, '80',
        _t('验证码图片高度'), _t('默认值为80'));
        $form->addInput($image_height);

        $image_width = new Typecho_Widget_Helper_Form_Element_Text('image_width', NULL, '215',
        _t('验证码图片宽度'), _t('默认值为215'));
        $form->addInput($image_width);

        $image_bg_color = new Typecho_Widget_Helper_Form_Element_Text('image_bg_color', NULL, '#ffffff',
        _t('背景颜色'), _t('默认值为#ffffff'));
        $form->addInput($image_bg_color);

        $is_background = new Typecho_Widget_Helper_Form_Element_Radio('is_background', 
        array('1' => '启用',
        '0' => '不启用'),
        1, '是否启用背景图片', '如果启用背景图片，请自行修改插件目录下securimage/backgrounds/目录中的背景图片');
        $form->addInput($is_background);

        $num_lines = new Typecho_Widget_Helper_Form_Element_Text('num_lines', NULL, '3',
        _t('干扰线数量'), _t('最小值为0，必须是整数'));
        $form->addInput($num_lines);

        $line_color = new Typecho_Widget_Helper_Form_Element_Text('line_color', NULL, '#666666',
        _t('干扰线颜色'), _t('默认值为#666666'));
        $form->addInput($line_color);

        $perturbation = new Typecho_Widget_Helper_Form_Element_Text('perturbation', NULL, '0.3',
        _t('验证码扭曲指数'), _t('取值范围从0-1.0，默认为0.3'));
        $form->addInput($perturbation);

        $text_color = new Typecho_Widget_Helper_Form_Element_Text('text_color', NULL, '#666666',
        _t('验证码颜色'), _t('默认值为#666666'));
        $form->addInput($text_color);

        $ttf_file = new Typecho_Widget_Helper_Form_Element_Select('ttf_file', $fileList,
        'stxingkai.ttf', '验证码字体', '如果使用中文验证码，请务必选择中文字体文件，否则无法演示验证码');
        $form->addInput($ttf_file);


        $image_signature = new Typecho_Widget_Helper_Form_Element_Text('image_signature', NULL, 'ccvita.com',
        _t('签名内容'));
        $form->addInput($image_signature);

        $signature_color = new Typecho_Widget_Helper_Form_Element_Text('signature_color', NULL, '#666666',
        _t('签名颜色'), _t('默认值为#666666'));
        $form->addInput($signature_color);

        $signature_font = new Typecho_Widget_Helper_Form_Element_Select('signature_font', $fileList,
        'AHGBold.ttf', '签名字体', '如果使用中文签名，请务必选择中文字体文件，否则无法演示验证码');
        $form->addInput($signature_font);


        $use_wordlist = new Typecho_Widget_Helper_Form_Element_Radio('use_wordlist', 
        array('1' => '启用',
        '0' => '不启用'),
        0, '是否自定义验证码文本', '如果自定义验证码文本');
        $form->addInput($use_wordlist);


        $wordlist = new Typecho_Widget_Helper_Form_Element_Textarea('wordlist', NULL, "中文\n验证码",
        _t('验证码文本'), _t('自定义验证码内容，每行一个<br><img src="' . Typecho_Common::url('/action/captcha', Helper::options()->index) 
        . '" alt="captcha" onclick="this.src = this.src + \'?\' + Math.random()" style="cursor: pointer" title="' . _t('点击图片刷新验证码') . '" />'));
        $form->addInput($wordlist);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    public static function output()
    {
        echo '<img src="' . Typecho_Common::url('/action/captcha', Helper::options()->index) 
        . '" alt="captcha" onclick="this.src = this.src + \'?\' + Math.random()" style="cursor: pointer" title="' . _t('点击图片刷新验证码') . '" /><br />'
        . '<input type="text" class="captcha" name="captcha_code" /> <strong>' . _t('请输入验证码') . '</strong>';
    }
    
    /**
     * 评论过滤器
     * 
     * @access public
     * @param array $comment 评论结构
     * @param Typecho_Widget $post 被评论的文章
     * @param array $result 返回的结果上下文
     * @param string $api api地址
     * @return void
     */
    public static function filter($comment, $post, $result)
    {
        $captchaCode = Typecho_Request::getInstance()->captcha_code;
        if (empty($captchaCode)) {
            throw new Typecho_Widget_Exception(_t('请输入验证码'));
        }
        
        require_once 'Captcha/securimage/securimage.php';
        $img = new securimage();

        if (!$img->check($captchaCode)) {
            throw new Typecho_Widget_Exception(_t('验证码错误, 请重新输入'));
        }
    
        return $comment;
    }
}




function getDirlist($dirPath) {
	$fileList = array();

	if ( ($dh = opendir($dirPath)) !== false) {
		while (($file = readdir($dh)) !== false) {
			if ($file != '.' && $file != '..') {
				$fileList[$file] = $file;
			}
		}
		closedir($dh);
	}

	return $fileList;
}