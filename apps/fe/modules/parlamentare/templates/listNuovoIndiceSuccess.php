<?php use_helper('I18N', 'Date') ?>

<?php include_partial('tabs_nuovo_indice', array('ramo' => $ramo)) ?>

<div id="content" class="tabbed float-container">
  <div id="main">
    <div class="W73_100 float-left">	
      <?php include_partial('wiki') ?>       
    </div>

	  <div class="W100_100 float-left"> 
      <table class="disegni-decreti column-table lazyload">
        <thead>
          <tr>
            <th scope="col">parlamentare:</th>
            <th scope="col">indice di attivit&agrave;:</th> 	
          </tr>
        </thead>

        <tbody>

          <?php $tr_class = 'even' ?>				  
          <?php while ($parlamentari_rs->next()): $p = $parlamentari_rs->getRow() ?>
            <tr class="<?php echo $tr_class; ?>">
            <?php $tr_class = ($tr_class == 'even' ? 'odd' : 'even' )  ?>
              <th scope="row">
                <p class="politician-id">
                  <?php echo link_to($p['nome'].' '.$p['cognome'],
                                     '@parlamentare?id='.$p['p_id']) ?>
                  <?php if ($ramo != 'governo'): ?>
                    (<?php echo $p['acronimo'] ?>)                    
                  <?php endif ?>
                </p>
              </th>

              <td>
      		      <?php printf('<b>%01.2f</b> ', $p['indice']) ?>  
      	      </td>
            </tr>
          <?php endwhile; ?>
        </tbody>    
      </table>
    </div>
       
    <div class="clear-both"></div>
  </div>
</div>

<?php slot('breadcrumbs') ?>
  <?php echo link_to("home", "@homepage") ?> /
  
  <?php if($sf_params->get('ramo') && $sf_params->get('ramo')=='senato' ): ?>
    senatori
  <?php else: ?>
    deputati
  <?php endif; ?>
<?php end_slot() ?>
