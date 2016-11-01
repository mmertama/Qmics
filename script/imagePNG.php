<?php
$id = $_GET['id'];
$auth = $_GET['auth'];
$info = $_GET['info'];
$fname = $_GET['fname'];
$data = $_GET['data'];

//if($id !== 'PNG')
//	die();
	
	
$data = "iVBORw0KGgoAAAANSUhEUgAAADAAAAAwBAMAAAHSK9QiAAAAMFBMVEUbHC4rRyd7YGucoZ6Pp3p1
aXrDqbJFUlJHQlUIAAhGGEkGHhBAiCyEwDnNVbdzaj6SyIqHAAAEiklEQVR42h2ScWwTVRzHfy0s
RbHLOuluxIzR1mshsLFx6xZijEhpgRix7e3uNscgesR/2fV6e72VCWgmTjfYrbRHs5gIAgayllHS
lYSomS2bf7jFSWsTU7a0HQkhzGoENDEpXvf75eXl+/u+z/u9X/LAShBA2S1gbcPBarWDu+sU7KY8
YFfqdgoB5aaA2ocD4+TgGGMBkuqkKCN0Ur2UxwJtTGcTvgsohjJoDWC1UGcVz47cHOYHC0K+Ggxw
I0XZ9oMPvTyCU4DQwHkPDgOn4ZVsFga+vtHYWK8UdcuLGNzSNTZs1kE4vPNgGGCjLvVjeAJqNj75
WdnClZiCPieHlJDlWDAIp12us1qtNjoeVATnYnxbvtNGgxXhcyEUqa6I2xdA1zs6GolEo+X5ZkU0
jR5dWCgvlZfMFwDr7R1dWFgqP282B6Cxt7cpUt5UTl1LBiDRwVBl7dJ0IBAKQYLZR42WN60LBKZD
cGe3nVq64l1/O6Q4WIed8nr5dcqpALS0trYyDLMLx/HPIRZci3hcjemh09tpInk+xXtxHOLBC8F0
JlOoqjjPhgTh6jdV97xe4dmaU0xnHur1imNUHDRUde+pV/BAPBAIpIuZgl6oFWCb0mQI/fNi09Uf
EFSlUul0emWl8OtDAQxdjA9d0j2IXYkhWJdKFYsrK6ul1ZIAXQrjOXXqr6eXaxGsTyXvK85KqVTi
oe9PhGFfYS8wHNugMMnkfTb9YbqU5sHP1agiupvbgkFZAmXcAJssFl8VvDxs3+Hn5iI/va88VAXT
04FQkp0t8oLAg/ifn2uamZkZ0idUUBcKTTtmZ2c/5XkTXK8BSZYlee22a6HAdF1dHc/zXhMwKiZh
0HAag6YH4zR9WJ9+h15ENfUg9e8xH6J7aI6upxGhIWRWPi6zQRYOhpkJwxgnHZk5Mjc4cy7/ZdAf
FBPDCbhl3mN+h3bRZxwjthFCzSbYcWL8uEJ0jbkkvzQ89dvjI78MzlXnN8fEuIi9hMGd52+YW2hX
+0CbynGyXd2uYWV2jXBJLkmUhi+ezB5+PLgYzV9Xi/HN9eF60DxvpVtpF4Fo35sqotGxgdCwGlbN
glPemZvMTWZHlSw/rs5HS/NzzR+EEdSbW/pbSIbmbGcc/+6/eXiZmCSW26pY6JFHciO5hcJCqVwq
58snyqXmE835CQTI3NHfocyBaOTg2peXb7S/YBtZz0ew/WKlhzb3cYVY3VJaWm1+8kc+XCFa+p39
TpojOfpt23nH4rsqNkEoPbbKOx9M5iK5SPZcQZuNlhqyRx/N/y55APF7P3OanOY+82J/D71su0Qk
3vvWepmArbEdGrFWrN1SGMxUl6KPxj55TZhCCoG/biX3kW7yLd5tOkAe20/ZjG24nbKCP9atFvWi
XpuLPmzIRh+FPXeFCeYWXiE6KRNFdv19gOwij9ncNmNlWWFY3R2vEN8XGjLabENmwnPXMmaRcLB4
Gd7AG0yG5AC+lfwC99mRkpgV+mq79d16URCHRGF4aMooGVWeKbdEgcq01+Qyu0gf6aORTaP8k/G2
oFVN/A+O8QFEc1LxTwAAAABJRU5ErkJggg==";	

$encoded = base64_decode($data);
$im = imagecreatefromstring($encoded);
$im = imagecreatefrompng("./fb_icon.png");



if($im !== false){
	header("Content-Type: image/png");
	//header('Content-Disposition:attachment;filename="' . $fname . '"');
	header('Content-Disposition:inline;filename="prince"');
	imagepng($im);
    imagedestroy($im);
	}
	
?>
