<?php echo $enableImageLink? '<a href="'.$row->link.'" title="'.$row->title.'">'.$row->mainImage.'</a>':$row->mainImage; ?> 
 <div class="lof-description">
<h4><a target="_<?php echo $openTarget ;?>" title="<?php echo $row->title;?>" href="<?php echo $row->link;?>"><?php echo $row->title;?></a></h4>
<?php if( $row->description != '...') : ?>
<p><?php echo $row->description;?></p>

<?php endif; ?>
</div>