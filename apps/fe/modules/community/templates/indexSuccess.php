<?php echo use_helper('DeppNews'); ?>

<ul class="float-container tools-container" id="content-tabs">
	<li class="current"><h2>Comunit&agrave;</h2></li>
</ul>



<div class="tabbed float-container" id="content">
 <div id="main">
 
 <div class="W73_100" style="font-size:14px;">
     
     Testo di presentazione della pagina con spiegazione delle cose i cantiere e link a un post del blog per i commenti 
     Testo di presentazione della pagina con spiegazione delle cose i cantiere e link a un post del blog per i commenti 
     Testo di presentazione della pagina con spiegazione delle cose i cantiere e link a un post del blog per i commenti 
     Testo di presentazione della pagina con spiegazione delle cose i cantiere e link a un post del blog per i commenti 
     
  </div>
  <p>&nbsp;</p>

   <div class="W52_100 float-right">
   <div class="section-box">
	<h3>gli atti pi&ugrave; seguiti dagli utenti</h3>
	<div id="atti_community">				
	  <?php echo include_component('community','attiutenti', array('type' => 'voti')); ?>	
	</div> 
	
      </div>	
      
       <p>&nbsp;</p>	
       
      <div class="section-box">
	<h3>i parlamentari pi&ugrave; monitorati</h3>
        <div id="monitor_community">				
	  <?php echo include_component('community','boxparlamentari', array('type' => 'deputati')); ?>	
	</div> 
       </div>  
    
    </div>
    
    <div class="W45_100 float-left">
      <div class="section-box">   
		
		<h3>le ultime dalla comunit&agrave;</h3>
		<ul>
		  <?php foreach ($latest_activities as $activity): ?>
		    <?php $news_text = community_news_text($activity); ?>
		      <?php if ($news_text != ''): ?>
		         <li class="float-container">
			     <div class="date"> <?php echo $activity->getCreatedAt("d/m/Y H:i"); ?></div>							
			     <?php echo $news_text ?>
			  </li>         
                       <?php endif ?>
                      <?php endforeach; ?>
		  </ul>
		  <div class="section-box-scroller tools-container has-next">
		       <?php echo link_to('<strong>vedi le ultime 100 attivit&agrave;</strong>','@news_comunita',array('class' => 'see-all')) ?>
		  </div> 
	      <div class="clear-both"></div>
	    </div>
    
    
    
    
    
    </div>
    
    
   </div>
	     
	       

 </div>
</div>

<?php slot('breadcrumbs') ?>
  <?php echo link_to("home", "@homepage") ?> /
  comunit&agrave;
<?php end_slot() ?>