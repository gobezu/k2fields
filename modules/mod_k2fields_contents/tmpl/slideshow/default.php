<?php 

// no direct access
defined('_JEXEC') or die('Restricted access'); 
jimport( 'joomla.html.pane' );
$pane = JPane::getInstance('tabs');
echo $pane->startPane( "cat-pane-".$module->id );
foreach ($_list as $catId => $list) {
echo $pane->startPanel($list[0]->categoryname, "cat-page-".$catId );
?>


<!------------------------------------- THE CONTENT ------------------------------------------------->


<div id="lofass-<?php echo $module->id.'-'.$catId; ?>" class="lof-ass<?php echo $params->get('moduleclass_sfx','');?> moduleItemView" style="height:<?php echo $moduleHeight;?>; width:<?php echo $moduleWidth;?>">
<div class="lofass-container <?php echo $css3; ?> <?php echo $themeClass ;?> <?php echo $class;?>">
    <div class="preload"><div></div></div>
     <!-- MAIN CONTENT --> 
      <div class="lof-main-wapper" style="height:<?php echo (int)$params->get('main_height',300);?>px;width:<?php echo (int)$params->get('main_width',650);?>px;">
            <?php foreach( $list as $no => $row ): ?>
            <div class="lof-main-item<?php echo(isset($customSliderClass[$no])? " ".$customSliderClass[$no]:"" );?>">
             	<?php require($itemLayout); ?>
            </div> 
            <?php endforeach; ?>
		    
      </div>
      <!-- END MAIN CONTENT --> 
        <!-- NAVIGATOR -->
    	<?php if( $params->get('display_button',1) ) : ?>
                <div class="lof-buttons-control">
                  <a href="" onclick="return false;" class="lof-previous"><?php echo JText::_('Previous');?></a>
                  <a href="" class="lof-next"  onclick="return false;"><?php echo JText::_('Next');?></a>
                </div>
            <?php endif; ?>
        <?php if( $class ): ?>    
              <div class="lof-navigator-outer">
                    <ul class="lof-navigator">
                    <?php 
                    for ($i = 0, $n = count($list); $i < $n; $i++):
                            $row = $list[$i];
                        ?>
                            <li class="lof-navigator-item-<?php echo $i . ($i == 0 ? ' lof-navigator-item-first' : '').($i == $n - 1 ? ' lof-navigator-item-last' : '')  ?>">
                            <div>
                            	<?php if( $navEnableThumbnail && isset($row->thumbnail) ): ?>
                                 <?php echo $row->thumbnail; ?> 
                                <?php endif; ?> 
                                <?php if( $navEnableTitle ): ?>
                                <h4><?php echo $row->title;?></h4>
                                 <?php endif; ?> 
                                  <?php if( $navEnableDate ): ?> 
                                <span><?php echo $row->date; ?></span>
                                  <?php endif; ?> 
                                  <?php if( $navEnableCate ): ?> 
                              		 <br><span><b><?php echo JText::_("Publish In:");?></b></span>
                                     <a href="<?php $row->catlink;?>" title="<?php echo $row->category_title; ?>"><b><?php echo $row->category_title; ?></b></a>
                                
                                  <?php endif; ?> 
                            </div>    
                        </li>
                     <?php endfor; ?> 		
                    </ul>
              </div>
       <?php endif; ?>       
  </div>
 </div> 
<?php
echo $pane->endPanel();
}
echo $pane->endPane();
?>