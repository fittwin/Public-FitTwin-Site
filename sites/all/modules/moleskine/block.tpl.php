<?php 
// $Id: block.tpl.php,v 1.1.4.3 2008/07/13 05:53:13 couzinhub Exp $
?>

<div id="block-<?php print $block->module .'-'. $block->delta; ?>" class="block block-<?php print $block->module ?>">  
  <div class="blockinner">
    <h2 class="title"> <?php print $block->subject; ?> </h2>
    <div class="content">
      <?php print $block->content; ?>
    </div>    
  </div>
</div>
