$yt = new \common\extend\video\yt\YouTubeDownloader();

$links = $yt->getDownloadLinks("https://www.youtube.com/watch?v=WRwtU5BEMJw", 'mp4');

var_dump($links); exit();