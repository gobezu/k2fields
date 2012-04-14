<?php
/**
 * @version		$Id: category.php 569 2010-09-23 12:50:28Z joomlaworks $
 * @package		K2
 * @author		JoomlaWorks http://www.joomlaworks.gr
 * @copyright	Copyright (c) 2006 - 2010 JoomlaWorks, a business unit of Nuevvo Webware Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
$tmpl = JRequest::getCmd('tmpl');
$isComponentOnly = $tmpl == 'component';
?>
<?php if (!$isComponentOnly): ?>
<!-- Start K2 Category Layout -->
<div id="k2Container" class="itemListView<?php if($this->params->get('pageclass_sfx')) echo ' '.$this->params->get('pageclass_sfx'); ?>">

	<?php if($this->params->get('show_page_title')): ?>
	<!-- Page title -->
	<div class="componentheading<?php echo $this->params->get('pageclass_sfx')?>">
		<?php echo $this->escape($this->params->get('page_title')); ?>
	</div>
	<?php endif; ?>

	<?php if($this->params->get('catFeedIcon')): ?>
	<!-- RSS feed icon -->
	<div class="k2FeedIcon">
		<a href="<?php echo $this->feed; ?>" title="<?php echo JText::_('K2_SUBSCRIBE_TO_THIS_RSS_FEED'); ?>">
			<span><?php echo JText::_('K2_SUBSCRIBE_TO_THIS_RSS_FEED'); ?></span>
		</a>
		<div class="clr"></div>
	</div>
	<?php endif; ?>

	<?php if(isset($this->category) || ( $this->params->get('subCategories') && isset($this->subCategories) && count($this->subCategories) )): ?>
	<!-- Blocks for current category and subcategories -->
	<div class="itemListCategoriesBlock">

		<?php if(isset($this->category) && ( $this->params->get('catImage') || $this->params->get('catTitle') || $this->params->get('catDescription') || $this->category->event->K2CategoryDisplay )): ?>
		<!-- Category block -->
		<div class="itemListCategory">

			<?php if(isset($this->addLink)): ?>
			<!-- Item add link -->
			<span class="catItemAddLink">
				<a class="modal" rel="{handler:'iframe',size:{x:990,y:650}}" href="<?php echo $this->addLink; ?>">
					<?php echo JText::_('K2_ADD_A_NEW_ITEM_IN_THIS_CATEGORY'); ?>
				</a>
			</span>
			<?php endif; ?>

			<?php if($this->params->get('catImage') && $this->category->image): ?>
			<!-- Category image -->
			<img alt="<?php echo K2HelperUtilities::cleanHtml($this->category->name); ?>" src="<?php echo $this->category->image; ?>" style="width:<?php echo $this->params->get('catImageWidth'); ?>px; height:auto;" />
			<?php endif; ?>

			<?php if($this->params->get('catTitle')): ?>
			<!-- Category title -->
			<h2><?php echo $this->category->name; ?><?php if($this->params->get('catTitleItemCounter')) echo ' ('.$this->pagination->total.')'; ?></h2>
			<?php endif; ?>

			<?php if($this->params->get('catDescription')): ?>
			<!-- Category description -->
			<p><?php echo $this->category->description; ?></p>
			<?php endif; ?>

			<!-- K2 Plugins: K2CategoryDisplay -->
			<?php echo $this->category->event->K2CategoryDisplay; ?>

			<div class="clr"></div>
		</div>
		<?php endif; ?>

		<?php if($this->params->get('subCategories') && isset($this->subCategories) && count($this->subCategories)): ?>
		<!-- Subcategories -->
		<div class="itemListSubCategories">
			<h3><?php echo JText::_('K2_CHILDREN_CATEGORIES'); ?></h3>

			<?php foreach($this->subCategories as $key=>$subCategory): ?>

			<?php
			// Define a CSS class for the last container on each row
			if( (($key+1)%($this->params->get('subCatColumns'))==0) || count($this->subCategories)<$this->params->get('subCatColumns') )
				$lastContainer= ' subCategoryContainerLast';
			else
				$lastContainer='';
			?>

			<div class="subCategoryContainer<?php echo $lastContainer; ?>"<?php echo (count($this->subCategories)==1) ? '' : ' style="width:'.number_format(100/$this->params->get('subCatColumns'), 1).'%;"'; ?>>
				<div class="subCategory">
					<?php if($this->params->get('subCatImage') && $subCategory->image): ?>
					<!-- Subcategory image -->
					<a class="subCategoryImage" href="<?php echo $subCategory->link; ?>">
						<img alt="<?php echo K2HelperUtilities::cleanHtml($subCategory->name); ?>" src="<?php echo $subCategory->image; ?>" />
					</a>
					<?php endif; ?>

					<?php if($this->params->get('subCatTitle')): ?>
					<!-- Subcategory title -->
					<h2>
						<a href="<?php echo $subCategory->link; ?>">
							<?php echo $subCategory->name; ?><?php if($this->params->get('subCatTitleItemCounter')) echo ' ('.$subCategory->numOfItems.')'; ?>
						</a>
					</h2>
					<?php endif; ?>

					<?php if($this->params->get('subCatDescription')): ?>
					<!-- Subcategory description -->
					<p><?php echo $subCategory->description; ?></p>
					<?php endif; ?>

					<!-- Subcategory more... -->
					<a class="subCategoryMore" href="<?php echo $subCategory->link; ?>">
						<?php echo JText::_('K2_VIEW_ITEMS'); ?>
					</a>

					<div class="clr"></div>
				</div>
			</div>
			<?php if(($key+1)%($this->params->get('subCatColumns'))==0): ?>
			<div class="clr"></div>
			<?php endif; ?>
			<?php endforeach; ?>

			<div class="clr"></div>
		</div>
		<?php endif; ?>

	</div>
	<?php endif; ?>

<?php endif; ?>

<?php 
        $isLeading = isset($this->leading) && count($this->leading);
        $isPrimary = isset($this->primary) && count($this->primary);
        $isSecondary = isset($this->secondary) && count($this->secondary);
        $isLinks = isset($this->links) && count($this->links);
        
        if ($isLeading || $isPrimary || $isSecondary || $isLinks): 
                $isTabular = '';
                if ($isLeading) $isTabular = $this->leading[0]->isItemlistTabular ? ' itemListTabular' : '';
                else if ($isPrimary) $isTabular = $this->primary[0]->isItemlistTabular ? ' itemListTabular' : '';
                else if ($isSecondary) $isTabular = $this->secondary[0]->isItemlistTabular ? ' itemListTabular' : '';
                else if ($isLinks) $isTabular = $this->links[0]->isItemlistTabular ? ' itemListTabular' : '';
        ?>
	<!-- Item list -->
        <div class="cat<?php echo $this->category->id ?>"><div class="itemList<?php echo $isTabular; ?>">

		<?php if(isset($this->leading) && count($this->leading)): ?>
		<!-- Leading items -->
		<div id="itemListLeading">
			<?php 
                        $tmplFile = preg_replace('\.php$', '', __FILE__);
                        foreach($this->leading as $key=>$item): 
			// Define a CSS class for the last container on each row
                        $lastContainer= $key+1 == count($this->leading) && !$isComponentOnly ? ' itemContainerLast' : '';
                        $firstContainer = ($key == 0 && !$isComponentOnly) ? ' itemContainerFirst' : '';
			?>
			
			<div class="itemContainer<?php echo $firstContainer.$lastContainer; ?>"<?php echo (count($this->leading)==1) ? '' : ' style="width:'.number_format(100/$this->params->get('num_leading_columns'), 1).'%;"'; ?>>
				<?php
					$this->item=$item;
                                        $tmplFile = JprovenUtility::findSubTemplate(__FILE__, $item);
                                        if (!empty($tmplFile[0])) $this->setLayout($tmplFile[0]);
                                        echo $this->loadTemplate($tmplFile[1]);
                                        if (!empty($tmplFile[0])) $this->setLayout($tmplFile[2]);
				?>
			</div>
			<?php if(($key+1)%($this->params->get('num_leading_columns'))==0): ?>
			<div class="clr"></div>
			<?php endif; ?>
			<?php endforeach; ?>
			<div class="clr"></div>
		</div>
		<?php endif; ?>

		<?php if(isset($this->primary) && count($this->primary)): ?>
		<!-- Primary items -->
		<div id="itemListPrimary">
			<?php foreach($this->primary as $key=>$item): ?>
			
			<?php
			// Define a CSS class for the last container on each row
			if( (($key+1)%($this->params->get('num_primary_columns'))==0) || count($this->primary)<$this->params->get('num_primary_columns') )
				$lastContainer= ' itemContainerLast';
			else
				$lastContainer='';
			?>
			
			<div class="itemContainer<?php echo $lastContainer; ?>"<?php echo (count($this->primary)==1) ? '' : ' style="width:'.number_format(100/$this->params->get('num_primary_columns'), 1).'%;"'; ?>>
				<?php
					// Load category_item.php by default
					$this->item=$item;
					echo $this->loadTemplate('item');
				?>
			</div>
			<?php if(($key+1)%($this->params->get('num_primary_columns'))==0): ?>
			<div class="clr"></div>
			<?php endif; ?>
			<?php endforeach; ?>
			<div class="clr"></div>
		</div>
		<?php endif; ?>

		<?php if(isset($this->secondary) && count($this->secondary)): ?>
		<!-- Secondary items -->
		<div id="itemListSecondary">
			<?php foreach($this->secondary as $key=>$item): ?>
			
			<?php
			// Define a CSS class for the last container on each row
			if( (($key+1)%($this->params->get('num_secondary_columns'))==0) || count($this->secondary)<$this->params->get('num_secondary_columns') )
				$lastContainer= ' itemContainerLast';
			else
				$lastContainer='';
			?>
			
			<div class="itemContainer<?php echo $lastContainer; ?>"<?php echo (count($this->secondary)==1) ? '' : ' style="width:'.number_format(100/$this->params->get('num_secondary_columns'), 1).'%;"'; ?>>
				<?php
					// Load category_item.php by default
					$this->item=$item;
					echo $this->loadTemplate('item');
				?>
			</div>
			<?php if(($key+1)%($this->params->get('num_secondary_columns'))==0): ?>
			<div class="clr"></div>
			<?php endif; ?>
			<?php endforeach; ?>
			<div class="clr"></div>
		</div>
		<?php endif; ?>

		<?php if(isset($this->links) && count($this->links)): ?>
		<!-- Link items -->
		<div id="itemListLinks">
			<h4><?php echo JText::_('K2_MORE'); ?></h4>
			<?php foreach($this->links as $key=>$item) { ?>

			<?php
			// Define a CSS class for the last container on each row
			if( (($key+1)%($this->params->get('num_links_columns'))==0) || count($this->links)<$this->params->get('num_links_columns') )
				$lastContainer= ' itemContainerLast';
			else
				$lastContainer='';
			?>

			<div class="itemContainer<?php echo $lastContainer; ?>"<?php echo (count($this->links)==1) ? '' : ' style="width:'.number_format(100/$this->params->get('num_links_columns'), 1).'%;"'; ?>>
				<?php
					// Load category_item_links.php by default
					$this->item=$item;
					echo $this->loadTemplate('item_links');
				?>
			</div>
			<?php if(($key+1)%($this->params->get('num_links_columns'))==0) { ?>
			<div class="clr"></div>
			<?php } ?>
			<?php } ?>
			<div class="clr"></div>
		</div>
		<?php endif; ?>

	</div></div>
	<?php endif; ?>
</div>

<!-- Pagination -->
<?php 
if(count($this->pagination->getPagesLinks()) && !$isComponentOnly) {
        if ($cid = JRequest::getInt('id', JRequest::getInt('cid', false))) {
                if ($isLeading) $catTitle = $this->leading[0]->categoryname;
                else if ($isPrimary) $catTitle = $this->primary[0]->categoryname;
                else if ($isSecondary) $catTitle = $this->secondary[0]->categoryname;
                else if ($isLinks) $catTitle = $this->links[0]->categoryname;
                $catTitle = JprovenUtility::nize($catTitle, 1);
        } else {
                $catTitle = ' '.JText::_('search results');
        }
?>
<?php if(JprovenUtility::plgParam('k2fields', 'k2', 'paginationmode', 'k2') == 'ajax') { ?>
<div class="k2Pagination">
        <?php 
        if($this->params->get('catPagination')) {
                $data = $this->pagination->getData();
                if ($data->next->base != null) {
                        $link = $data->next->link;
                        $limit = $this->pagination->limit;
                        $start = $this->pagination->limitstart;
                        $total = $this->pagination->total;
        ?>
        <button <?= ' link="'.$link.'" limit="'.$limit.'" start="'.$start.'" total="'.$total.'" ' ?> id="k2fPageBtn" class="k2fbtn k2fmoreitems" type="submit"><?= JText::_('Show more ').$catTitle ?></button>
        <?php 
                }
        } 
        ?>
        <div class="clr"></div>
</div>
<?php } else { ?>
	<?php if (count($this->pagination->getPagesLinks())) { ?>
	<div class="k2Pagination">
		<?php if ($this->params->get('catPagination')) echo $this->pagination->getPagesLinks(); ?>
		<div class="clr"></div>
		<?php if ($this->params->get('catPaginationResults')) echo $this->pagination->getPagesCounter(); ?>
	</div>
	<?php } ?>        

<?php 
        } 
?>
<?php 
} 
?>
<!-- End K2 Category Layout -->
