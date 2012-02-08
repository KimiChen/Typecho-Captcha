<?php

class Captcha_Action extends Typecho_Widget implements Widget_Interface_Do
{
    public function action()
    {
        /** 防止跨站 */
        $referer = $this->request->getReferer();
        if (empty($referer)) {
            exit;
        }
        
        $refererPart = parse_url($referer);
        $currentPart = parse_url(Helper::options()->siteUrl);
        
        if ($refererPart['host'] != $currentPart['host'] ||
        0 !== strpos($refererPart['path'], $currentPart['path'])) {
            exit;
        }
    
        require_once 'Captcha/securimage/securimage.php';
        $img = new securimage();

        $dir = dirname(__FILE__) . '/securimage/';

        $options = Typecho_Widget::widget('Widget_Options');

        $fontsArray = array('04b03.ttf', 'AHGBold.ttf', 'atkinsoutlinemedium-regular.ttf', 'decorative-stylisticblackout-regular.ttf', 'okrienhmk.ttf', 'ttstepha.ttf', 'vtckomixationhand.ttf');
        $fontsKey = array_rand($fontsArray);
        $fontsFile = $dir . 'fonts/' . $fontsArray[$fontsKey];


        //验证码字体
        $fontsFile = $dir . 'fonts/'.$options->plugin('Captcha')->ttf_file;
        $img->ttf_file = $fontsFile;

        //验证码背景
        if($options->plugin('Captcha')->is_background) {
            $img->background_directory = $dir . '/backgrounds/';
        }
        //背景颜色
        $img->image_bg_color = new Securimage_Color($options->plugin('Captcha')->image_bg_color);

        //验证码颜色
        $img->text_color = new Securimage_Color($options->plugin('Captcha')->text_color);

        //自定义验证码
        $img->use_wordlist = $options->plugin('Captcha')->use_wordlist;
        $img->wordlist = explode("\n", $options->plugin('Captcha')->wordlist);
        $img->wordlist_file = $dir . 'words/words.txt';

        //干扰线颜色
        $img->line_color = new Securimage_Color($options->plugin('Captcha')->line_color);

        //干扰线、扭曲度
        $img->num_lines = $options->plugin('Captcha')->num_lines;
        $img->perturbation = $options->plugin('Captcha')->perturbation;

        //签名内容、颜色、字体
        $img->signature_color = new Securimage_Color($options->plugin('Captcha')->signature_color);
        $img->image_signature = $options->plugin('Captcha')->image_signature;
        $img->signature_font = $dir . 'fonts/'.$options->plugin('Captcha')->signature_font;

        //高度宽度
        $img->image_height = $options->plugin('Captcha')->image_height;
        $img->image_width = $options->plugin('Captcha')->image_width;

        $img->show('');
    }
}
