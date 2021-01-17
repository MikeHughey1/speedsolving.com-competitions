<?php

require_once './sync/XF.php';
require_once '../competition_common/statFunctions.php';

if (!isset($app))
    $app = sync\XF::app();

$personInfo = sync\XF::user();
$currentUserId = $personInfo['id'];
    
$sessionType = "new";
if (isset($currentUserId) && !isset($_SESSION['new_login'])) {
    $_SESSION['new_login'] = 1;
    unset($_SESSION['old_login']);
    $freshSessionType = "new";
}

ob_start();

?>

<link rel="stylesheet" href="<?=sync\XF::webroot();?>/competition_common/style.css?version=1.8.9" type="text/css" />
<link rel="stylesheet" href="<?=sync\XF::webroot();?>/competition_common/cubing-icons.css?version=2" />

<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-1539656-3"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){ dataLayer.push(arguments); }
	gtag('js', new Date());
	gtag('config', 'UA-1539656-3');
</script>

<?php

$head = ob_get_contents();
ob_end_clean();

if (!isset($content))
    ob_start();