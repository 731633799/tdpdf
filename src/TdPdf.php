<?php
/**
 * TdPdf class
 * Author: TD DoubleY  <https://github.com/731633799>
 * Date: 2020/11/11
 * Email: 731633799@qq.com
 * License:  MIT
 **/
namespace tdy;

use setasign\Fpdi\Tfpdf\Fpdi;

class TdPdf extends Fpdi
{
    protected $fontFile="";
    protected $water_images=[];
    protected $water_image_position=[0,0,0,0];
    protected $water_rgba=[0,0,0,110];
    protected $temp_dir='';


    public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
    {
        parent::__construct($orientation, $unit, $size);
        if(!file_exists($this->fontFile)){
            $this->AddFont('DejaVuSerif-Bold', '', 'DejaVuSerif-Bold.ttf', true); // TODO: 添加默认字体
            $this->SetFont("DejaVuSerif-Bold", '', 14);                               // TODO: 设置字体
            $this->fontFile=$this->fonts['dejavuserif-bold']['ttffile'];
        }
        if(!$this->temp_dir){
            $this->temp_dir=sys_get_temp_dir();
        }
    }
    /**
     * 设置字体文件
     *@param string $fontFile ttf字体文件,请使用字体文件绝对路径
     *@param string $fontSize 设置字体大小
     */
    public function setFontTtfFile($fontFile,$fontSize=14)
    {
        if(file_exists($fontFile)){
            $fontFile && $this->fontFile=$fontFile;
            if(pathinfo($this->fontFile,PATHINFO_DIRNAME )){
                define('_SYSTEM_TTFONTS',pathinfo($this->fontFile,PATHINFO_DIRNAME ));
            }
            $font_name=pathinfo($this->fontFile,PATHINFO_BASENAME);
            $font_family=pathinfo($this->fontFile,PATHINFO_FILENAME);
            $this->AddFont($font_family, '', $font_name, true);
            $this->SetFont($font_family, '', $fontSize);
        }
    }

    /**
     * 系统不支持水印 重写方法
     */
    protected function _parsetmp($file)
    {
        return parent::_parsepng($file);
    }

    /**
     * 设置水印图片位置
     * @param int $x
     * @param int $y
     * @param int $w
     * @param int $h
     * */
    public function setTextWaterPosition($x=0,$y=0,$w=0,$h=0)
    {
       $this->water_image_position=[$x,$y,$w,$h];
    }

    /**
     * 设置水印文字颜色以及透明度
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param int $alpha
     * */
    public function setTextWaterColor($red=0,$green=0,$blue=0,$alpha=0)
    {
        $this->water_rgba=[$red,$green,$blue,$alpha];
    }

    /**
     * 设置水印文字 以及背景图片大小
     * @param string $text  水印文字
     * @param int $fontSize 字体大小
     * @param int $angle 文字倾斜角度
     * @param int $space 文字间距
     * @param int $water_image_width  水印背景图片宽
     * @param int $water_image_height 水印背景图片高
     * */
    public function textWater($text="TD水印",$fontSize=20,$angle=45,$space=100,$water_image_width=794,$water_image_height=1123)
    {
        $md5= md5($text);
        if(isset($this->water_images[$md5])){
            $temp=$this->water_images[$md5];
        }else{
            $img_buffer = imagecreatetruecolor($water_image_width, $water_image_height);
            $bg = imagecolorallocatealpha($img_buffer, 0, 0, 0, 127);//设置图片透明背景
            imagefill($img_buffer, 0, 0, $bg);     //填充背景
            $po = imagettfbbox($fontSize, $angle, $this->fontFile, $text);
            $font_width = ($po[2] - $po[0]) + 1;         //文字所占宽度
            $font_height = (-($po[5] - $po[3])) + 1;     //文字所占高度

            list($red,$green,$blue,$alpha)=$this->water_rgba;
            $transparent = imagecolorallocatealpha($img_buffer,$red,$green,$blue,$alpha); //文字颜色
            $x_length = $water_image_width;
            $y_length = $water_image_height;
            for ($x = 0; $x < $x_length + $font_width; $x) {
                for ($y = $font_height; $y <= $y_length + $font_width; $y) {
                    imagettftext($img_buffer, $fontSize, $angle, $x, $y, $transparent, $this->fontFile, $text);
                    $y += $font_height + $space;
                }
                $x += $font_width + $space;
            }
            $temp = tempnam($this->temp_dir,'png');
            imagealphablending($img_buffer, false);
            imagesavealpha($img_buffer,true);
            imagepng($img_buffer,$temp);
            imagedestroy($img_buffer);
        }
        list($x,$y,$w,$h)=$this->water_image_position;
        $this->image($temp, $x, $y, $w, $h);
    }

    /**
     *  重写图片方法
     * @param string $file  图片路径
     * @param int $x  图片x位置
     * @param int $y  图片y位置
     * @param int $w  图片宽
     * @param int $h  图片高
     * @param string $type  图片格式
     * @param string $link  图片超链接
     * @param int $angle    图片旋转角度
     */
    public function image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='',$angle=0)
    {
        if($angle){
            $extension=pathinfo($file,PATHINFO_EXTENSION);
            $extension=='jpg' && $extension='jpeg';
            $tem_name = tempnam($this->temp_dir,'png');
            $imag_fun=sprintf('imagecreatefrom%s',$extension);
            $img_buffer=$imag_fun($file);
            $bg = imagecolorallocatealpha($img_buffer, 0, 0, 0, 127);//设置图片透明背景
            $res=imagerotate($img_buffer,$angle,$bg);
            imagedestroy($img_buffer);
            imageantialias($res,true);
            imagesavealpha($res, true);
            imagepng($res,$tem_name);
            imagedestroy($res);
            $file=$tem_name;
            $type='png';
        }
        parent::image($file, $x, $y, $w, $h, $type, $link);
    }


    /**
     * 设置合成图片临时保存位置
     * @param string  $dir  文件夹路径
     * */
    public function setTmpDir($dir)
    {
        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }
        $this->temp_dir=$dir;
    }

    /**
     * 添加文字
     * @param string  $text 文字
     * @param int $h   cell高度
     * @param string $link   跳转链接
     */
    public function addText($text,$h='5',$link='')
    {
        $this->Write($h,$text,$link);
    }



    /*示例*/
    public function example()
    {
        $pdf = $this;
        $pdf->setFontTtfFile("E:/phpstudy_pro/WWW/video/Ali.ttf");
        $pages = $pdf->setSourceFile("pdfpdf.pdf");
        $pdf->AddPage();
        $tplId = $pdf->importPage(1);
        $pdf->useTemplate($tplId);
        $pdf->Write(5, "呵呵00");
        $pdf->image('QQ.png', 0, 0, 100, 100);
        $pdf->setTextWaterPosition(0, 0, 0, 0);
        $pdf->textWater();
        $pdf->image('qq.jpg', 0, 0, 0, 0,'','',100);
        $pdf->image('QQ.png', 0, 0, 0, 0,'','',100);
        $pdf->SetXY(100, 10);
        $pdf->addText("添加文字");
        $pdf->Output();
    }
}




