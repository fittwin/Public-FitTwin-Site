<?php 
// $Id: page.tpl.php,v 1.1.4.5 2008/08/23 18:54:30 couzinhub Exp $
?>
 
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language ?>" lang="<?php print $language->language ?>" dir="<?php print $language->dir ?>">

<head>
  <title><?php print $head_title; ?></title>
  <?php print $head; ?>
  <?php print $styles; ?>
  <?php print $scripts; ?>

	<!--[if lte IE 6]>
	  <style type="text/css" media="all">@import "<?php print base_path() . path_to_theme() ?>/ie6.css";</style>
	<![endif]-->
	<!--[if IE 7]>
  <style type="text/css" media="all">@import "<?php print base_path() . path_to_theme() ?>/ie7.css";</style>
	<![endif]-->
	
</head>

<?php /* different ids allow for separate theming of the home page */ ?>
<body>
  <div id="page">
    <div id="header">
	
      <?php if ($primary_links): ?>
        <?php print theme('links', $primary_links, array('class' => 'links primary-links')); ?>
      <?php endif; ?>
          
            
    </div> <!-- /header -->

      <div id="main" class="column">
			
			<div id="logo-title"><div id="logo-title-inner">
				
        <?php if ($logo): ?>
          <a href="<?php print $base_path; ?>" title="<?php print t('Home'); ?>">
            <img src="<?php print $logo; ?>" alt="<?php print t('Home'); ?>" id="logo" />
          </a>
        <?php endif; ?>
        
        		<div id="name-and-slogan">
        		<?php if ($site_name): ?>
        		  <h1 id='site-name'>
        		    <a href="<?php print $base_path ?>" title="<?php print t('Home'); ?>">
        		      <?php print $site_name; ?>
        		    </a>
        		  </h1>
        		<?php endif; ?>
        		
        		<?php if ($site_slogan): ?>
        		  <div id='site-slogan'>
        		    <?php print $site_slogan; ?>
        		  </div>
        		<?php endif; ?>
        		</div> <!-- /name-and-slogan -->
						<div class="clear-block"></div>
		    </div></div> <!-- /logo-title / logo-title-inner -->
			
				<div id="content">
        
      	<?php if ($breadcrumb): ?><?php print $breadcrumb; ?><?php endif; ?>
        <?php if ($mission): ?><div id="mission"><?php print $mission; ?></div><?php endif; ?>
        <?php if ($content_top):?><div id="content-top"><?php print $content_top; ?></div><?php endif; ?>
	        <?php print $feed_icons; ?>
        <?php if ($title): ?><h1 class="title"><?php print $title; ?></h1><?php endif; ?>
        <?php if ($tabs): ?><div class="tabs"><?php print $tabs; ?></div><?php endif; ?>
        <?php print $help; ?>
        <?php print $messages; ?>
        <?php print $content; ?>
        <?php if ($content_bottom): ?><div id="content-bottom"><?php print $content_bottom; ?></div><?php endif; ?>
      </div></div> <!-- /content /main -->

        <div id="sidebar-right" class="column sidebar">
					<div id="side-content">

					<?php if ($secondary_links): ?>
					<div id="secondary" class="block">
          	<h2>Secondary Links</h2>
            <?php print theme('links', $secondary_links, array('class' => 'links secondary-links')); ?>
					</div>
          <?php endif; ?>

          	<?php print $sidebar_right; ?>
	        </div> <!-- /side-content -->
		     </div> <!-- /sidebar-right -->


    <div id="footer-wrapper">
      <div id="footer">
        <?php print $footer_message; ?>
      </div> <!-- /footer -->
    </div> <!-- /footer-wrapper -->
    
    <?php print $closure; ?>
    
  </div> <!-- /page -->

</body>
</html>