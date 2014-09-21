<html lang="en">
<head>

  <meta charset="utf-8" />
  <title>jonpierce.net | LastFM Reader</title>
  
</head>
<body>
<?php

  $file = 'lastfm.class.php';
  $fh = fopen($file, 'r');
  highlight_string(fread($fh, filesize($file)));
  fclose($fh);

?>
</body>
</html>