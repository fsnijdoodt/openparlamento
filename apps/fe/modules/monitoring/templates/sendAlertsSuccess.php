<?php use_helper('I18N') ?>

<table width="100%" cellpadding="0" cellspacing="0" style="color: #626262; font-family: Arial, Helvetica, sans-serif; font-size: 13px;">
    <tr>
        <td style="width: 190px;"><img src="http://<?php echo $sf_site_url?>/images/OP_mail.png" alt="Openparlamento" width="190" height="56" /></td>
        <td style="background-color: #fff; background-image: url(http://<?php echo $sf_site_url?>/images/header_bg.png); background-repeat: repeat-x; background-position: top left;"><img src="http://<?php echo $sf_site_url?>/images/header_bg.png" width="100%" height="56"/></td>
        <td style="width: 109px;"><img src="http://<?php echo $sf_site_url?>/images/Openpolisl.png" alt="e' uno strumento Openpolis" width="109" height="56" /></td>
    </tr>
</table>


<h2 style="color: #626262; font-family: Arial, Helvetica, sans-serif; padding-left: 12px; font-size: 20px;">
  Oggi hai 
  <?php echo format_number_choice('[1] 1 avviso |(1,+Inf] %1% avvisi', array('%1%' => $n_total_notifications), $n_total_notifications) ?>
  per <?php echo format_number_choice('[1] la parola |(1,+Inf] le %1% parole', array('%1%' => $n_alerts), $n_alerts) ?> che stai monitorando
</h2>

<table style="width: 100%; vertical-align: top; margin-bottom: 20px; color: #626262; font-family: Arial, Helvetica, sans-serif; font-size: 13px;">

  <?php foreach ($user_alerts as $user_alert): ?>
    <tr>
      <td>
        <?php echo sprintf("La parola %s è stata trovata %d volte", 
                           $user_alert['term'], count($user_alert['results'])) ?>
      </td>
    </tr>

    <?php foreach ($user_alert['results'] as $i => $res): ?>
      <tr>     
        <td>
          <?php echo get_partial($res->getInternalAlertPartial(), array('result' => $res, 'term' => $user_alert['term'])) ?>
        </td>
      </tr>  
    <?php endforeach ?>
      
  <?php endforeach; ?>
</table>

<div>
per rimuovere gli avvisi, vai a 
<a href="http://<?php echo $sf_site_url ?>/monitoring_alerts/<?php echo $user_token ?>">questa pagina</a>
</div>