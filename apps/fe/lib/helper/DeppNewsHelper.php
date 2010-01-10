<?php 


/*
 * This file is part of the deppPropelMonitoringBehaviors package.
 * (c) 2008 Guglielmo Celata
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    deppPropelMonitoringBehaviors
 * @author     Guglielmo Celata <guglielmo.celata@symfony-project.com>
 * @version    SVN: $Id$
 */


// some costants, used in the code
define('CONTEXT_LIST', 1);
define('CONTEXT_ATTO', 0);
define('CONTEXT_POLITICO', 2);
define('CONTEXT_TAG', 3);

/**
 * print the date inside a strong tag
 *
 * @param string $newsdate 
 * @return string
 * @author Guglielmo Celata
 */
function news_date($newsdate)
{
  return content_tag('strong', $newsdate);
}

/**
 * convert a link into an absolute link, to be used inside the emails
 *
 * @param string $name 
 * @param string $internal_uri 
 * @param string $options 
 * @return string
 * @author Guglielmo Celata
 */
function link_to_in_mail($name = '', $internal_uri = '', $options = array())
{
  $html_options = _parse_attributes($options);
  $html_options = _convert_options_to_javascript($html_options);

  $site_url = sfConfig::get('sf_site_url', 'op_openparlamento.openpolis.it');
  if (isset($html_options['site_url']))
  {
    $site_url = $html_options['site_url'];
  }

  $url = url_for($internal_uri, true);
  $url_in_mail = preg_replace('/.*\/symfony\/(.*)/i',  'http://'.$site_url.'/$1', $url);
  return "<a href=\"$url_in_mail\">$name</a>";
}


/**
 * torna l'elenco ul/li delle news passate in argomento
 *
 * @param string $news array di oggetti News
 * @return string html
 * @author Guglielmo Celata
 */
function news_list($news, $for_mail_or_rss = false)
{
  $news_list = '';
  
  foreach ($news as $n)
  {
    // fetch del modello e dell'oggetto che ha generato la notizia
    $generator_model = $n->getGeneratorModel();
    if ($n->getGeneratorPrimaryKeys())
    {
      $pks = array_values(unserialize($n->getGeneratorPrimaryKeys()));
      $generator = call_user_func_array(array($generator_model.'Peer', 'retrieveByPK'), $pks);          
    } else {
      $pks = array();
      $generator = null;
    }
    
    $news_list .= content_tag('li', 
                              news_text($n, $generator_model, $pks, $generator, 
                                        array('in_mail' => $for_mail_or_rss)));    
  }
  return content_tag('ul', $news_list, array('class' => 'square-bullet'));  
}


/**
 * generate textual representation for a news
 *
 * @param News $news 
 * @param String $generator_model 
 * @param Array $pks 
 * @param BaseObject $generator 
 * @param Array $options 
 *   context  - 0: box, 1: news per politico, 2:pagina di un atto, 3: argomento
 *   in_mail  - if the news lives in a mail or not
 * @return string (html)
 * @author Guglielmo Celata
 */
function news_text(News $news, $generator_model, $pks, $generator, $options = array())
{
  sfLoader::loadHelpers('Asset');
  
  // default value for context
  if (!array_key_exists('context', $options))
    $context = CONTEXT_LIST;
  else
    $context = $options['context'];
    
  // default value for in_mail
  if (!array_key_exists('in_mail', $options))
    $in_mail = false;
  else
    $in_mail = $options['in_mail'];

  $news_string = "";
  
  // notizie di gruppo (votazioni o interventi)
  if (count($pks) == 0)
  {

    if ($generator_model == 'OppVotazioneHasAtto')
    {
      if ($news->getPriority() == 1)
      {
        $news_string .= content_tag('p', ($news->getRamoVotazione()=='C') ? 'Camera -  ' : 'Senato - ');      
        $news_string .= content_tag('p', 'si &egrave; svolta almeno una VOTAZIONE');
      } 
      else {
        $news_string .= "<p>";
        $news_string .= ($news->getRamoVotazione()=='C')?'Camera -  ' : 'Senato - ';
        $news_string .= 'si &egrave; svolta una VOTAZIONE</p>';
         
        if ($context == CONTEXT_LIST)
        {    
          $atto = call_user_func_array(array($news->getRelatedMonitorableModel().'Peer', 'retrieveByPK'), 
                                          $news->getRelatedMonitorableId());
     
          $atto_link = link_to_in_mail($atto->getRamo() . '.' .$atto->getNumfase(), 
                            'atto/index?id=' . $atto->getId(),
                            array('title' => $atto->getTitolo()));
          $news_string .= 'per ' . OppTipoAttoPeer::retrieveByPK($news->getTipoAttoId())->getDenominazione() .  ' ';
          $news_string .= '<p>'.$atto_link.'</p>';
        }   
      }
    }
    
    if ($generator_model == 'OppIntervento') 
    {
      $news_string .= "<p>";
      //$news_string .= ($news->getRamo()=='C')?'Camera -  ' : 'Senato - ';
      $news_string .= 'c\'&egrave; stato almeno un intervento ';
      $news_string .= 'in ' . OppSedePeer::retrieveByPK($news->getSedeInterventoId())->getTipologia().' '.OppSedePeer::retrieveByPK($news->getSedeInterventoId())->getDenominazione() .  '</p>';

      if ($context = CONTEXT_LIST)
      {    
          $news_string .= 'per ' . OppTipoAttoPeer::retrieveByPK($news->getTipoAttoId())->getDescrizione() .  ' ';

          // link all'atto
          $atto = call_user_func_array(array($news->getRelatedMonitorableModel().'Peer', 'retrieveByPK'), 
                                         $news->getRelatedMonitorableId());
      
          $atto_link = link_to_in_mail($atto->getRamo() . '.' .$atto->getNumfase(), 
                           'atto/index?id=' . $atto->getId(),
                           array('title' => $atto->getTitolo()));
          $news_string .= '<p>'.$atto_link.'</p>';
      }  else $news_string .= ''; 
      
    }
      
    return $news_string;
  }
  

  // eccezione per firma, quando in pagina argomenti
  // corregge bug #307
  if ($context == CONTEXT_TAG && $generator_model == 'OppCaricaHasAtto')
  {
    $atto = $generator->getOppAtto();
    $carica = $generator->getOppCarica();
    $tipo = $atto->getOppTipoAtto(); 
    $tipo_firma = $generator->getTipo();
    switch ($tipo_firma) 
    {
      case "P":
        $tipo_firma='presenta';
        break;
      case "C":
        $tipo_firma='firma';
        break;
      case "R":
        $tipo_firma='&egrave; relatore';
        break;
    }
    
    $atto_link = link_to_in_mail(troncaTesto(Text::denominazioneAtto($atto,'list'),200), 
                         'atto/index?id=' . $atto->getId(),
                         array('title' => $atto->getTitolo()));

    $politico = $carica->getOppPolitico();
    $politico_link = link_to_in_mail($politico, 
                        '@parlamentare?id=' . $politico->getId(),
                        array('title' => 'Vai alla scheda del politico'));
      

    $news_string .= '<p>';

    $news_string .= $politico_link;
    $news_string .= " <strong>".$tipo_firma."</strong> ";

    if ($tipo_firma=='&egrave; relatore')
      $news_string .= "dell'atto ";
    else
      $news_string .= "l'atto ";

          
    $news_string .= $tipo->getDescrizione() . "</p>";
    $news_string .= '<p>'.$atto_link.'</p>';

    return $news_string;
    
  }


  if ($generator) 
  {

    $related_monitorable_model = $news->getRelatedMonitorableModel();

    if ($related_monitorable_model == 'OppPolitico')
    {
      // fetch del politico
      $c = new Criteria(); $c->add(OppPoliticoPeer::ID, $news->getRelatedMonitorableId());
      $politici = OppPoliticoPeer::doSelect($c);

      if (count($politici) == 0) return 'empty OppPolitico:' . $news->getRelatedMonitorableId();

      $politico = $politici[0];

      // link al politico
      $politico_link = link_to_in_mail($politico->getNome() . ' ' .$politico->getCognome(), 
                           '@parlamentare?id=' . $politico->getId(),
                           array('title' => 'Vai alla scheda del politico'));


      // nuovo incarico
      if ($generator_model == 'OppCarica')
      {
        if ($context != CONTEXT_POLITICO) 
        {
         $news_string .= "<p><strong>assume l'incarico di " . $generator->getCarica()."</strong> il politico</p>";
         $news_string .= "<p>".$politico_link."</p>";
        }
        else {
          $news_string .= "<p><strong>Assume l'incarico di " . $generator->getCarica()."</strong></p>";  
        }  
      }
    
      // nuovo gruppo
      else if ($generator_model == 'OppCaricaHasGruppo') 
      {
        if ($context != CONTEXT_POLITICO) {
          $news_string .= "<p><strong>si unisce al gruppo " . $generator->getOppGruppo()->getNome()."</strong> il politico</p>";
          $news_string .= "<p>".$politico_link."</p>";
        }  
        else {
          $news_string .= "<p><strong>Si unisce al gruppo " . $generator->getOppGruppo()->getNome()."</strong></p>";  
        }  
      }

      // intervento
      else if ($generator_model == 'OppIntervento')
      {
        $atto = $generator->getOppAtto();
        $tipo = $atto->getOppTipoAtto();
        $atto_link = link_to_in_mail(troncaTesto(Text::denominazioneAtto($atto,'list'),200), 
                             'atto/index?id=' . $atto->getId(),
                             array('title' => $atto->getTitolo()));
        if ($context == CONTEXT_LIST) 
        {      
          $news_string .= "<p>".$politico_link. " <strong>interviene</strong>";
          if ($generator->getUrl()!=NULL) {
          	if (substr_count($generator->getUrl(),'@')>0) {
          		$int_urls=explode("@",$generator->getUrl()); 
          		$intervento_link= " [vai ai testi";
          		foreach ($int_urls as $cnt => $int_url) {
          			$intervento_link .= " ".link_to(($cnt+1),$int_url).",";
          		}
          		$intervento_link= rtrim($intervento_link,",");
          		$intervento_link .= "]";
          	}
          	else
          		$intervento_link=" [".link_to('vai al testo',$generator->getUrl())."]"; 
          }
          else
          	$intervento_link="";
        	
          $news_string .= $intervento_link." in ";
          if ($generator->getOppSede()->getId()!=35 && $generator->getOppSede()->getId()!=36)
           $news_string .= $generator->getOppSede()->getTipologia()." ";

          $news_string .= strtoupper($generator->getOppSede()->getDenominazione())." su "; 
          $news_string .= $tipo->getDescrizione() . "</p>";
          $news_string .= "<p>".$atto_link."</p>";
        }
        
        if ($context == CONTEXT_ATTO) 
        {                    
           $news_string .= "<p>";
          if ($generator->getOppSede()->getId()!=35 && $generator->getOppSede()->getId()!=36)
           $news_string .= $generator->getOppSede()->getTipologia()." - ";

          $news_string .= ucfirst(strtolower($generator->getOppSede()->getDenominazione()));       
          $news_string .= $politico_link . " <strong>&egrave; intervenuto</strong>";
          if ($generator->getUrl()!=NULL) {
          	if (substr_count($generator->getUrl(),'@')>0) {
          		$int_urls=explode("@",$generator->getUrl()); 
          		$intervento_link= " [vai ai testi";
          		foreach ($int_urls as $cnt => $int_url) {
          			$intervento_link .= " ".link_to(($cnt+1),$int_url).",";
          		}
          		$intervento_link= rtrim($intervento_link,",");
          		$intervento_link .= "]";
          	}
          	else
          		$intervento_link=" [".link_to('vai al testo',$generator->getUrl())."]"; 
          }
          else
          	$intervento_link="";
        	
          $news_string .= $intervento_link." sull'atto </p>";
        
        }  

        if ($context == CONTEXT_POLITICO) 
        {  
          $news_string .= "<p><strong>Interviene</strong>";
           if ($generator->getUrl()!=NULL) {
          	if (substr_count($generator->getUrl(),'@')>0) {
          		$int_urls=explode("@",$generator->getUrl()); 
           		$intervento_link= " [vai ai testi";
          		foreach ($int_urls as $cnt => $int_url) {
          			$intervento_link .= " ".link_to(($cnt+1),$int_url).",";
          		}
          		$intervento_link= rtrim($intervento_link,",");
          		$intervento_link .= "]";
          	}
          	else
          		$intervento_link=" [".link_to('vai al testo',$generator->getUrl())."]"; 
          }
          else
          	$intervento_link="";
        	
          $news_string .= $intervento_link." in ";
          $news_string .= $generator->getOppSede()->getTipologia()." ";
        
          $news_string .= strtoupper($generator->getOppSede()->getDenominazione())." su "; 
          $news_string .= $tipo->getDescrizione() . "</p>";
          $news_string .= "<p>".$atto_link."</p>";
        }
      
      }

      // firma
      else if ($generator_model == 'OppCaricaHasAtto')
      {
        
        $atto = $generator->getOppAtto();
        $tipo = $atto->getOppTipoAtto(); 
        
        $tipo_firma=$generator->getTipo();
        switch ($tipo_firma) {
          case "P":
          $tipo_firma='presenta';
          break;
          case "C":
          $tipo_firma='firma';
          break;
          case "R":
          $tipo_firma='&egrave; relatore';
          break;
        }
      
        $atto_link = link_to_in_mail(troncaTesto(Text::denominazioneAtto($atto,'list'),200), 
                             'atto/index?id=' . $atto->getId(),
                             array('title' => $atto->getTitolo()));
      
        if ($context == CONTEXT_POLITICO)                      
            $news_string .= '<p><strong>'.ucfirst($tipo_firma)."</strong> ";
        else
            $news_string .= '<p>'.$politico_link ." <strong>".$tipo_firma."</strong> ";
          
        if ($context != CONTEXT_ATTO)
        {    
           $news_string .= $tipo->getDescrizione() . "</p>";
           $news_string .= '<p>'.$atto_link.'</p>';
        }
        else {
          if ($tipo_firma=='&egrave; relatore')
            $news_string .= "dell'atto</p>";
          else
            $news_string .= "l'atto</p>";
        }    
      }

      else if ($generator_model == 'OppCaricaHasEmendamento')
      {
        $emendamento = $generator->getOppEmendamento();
        
        $tipo_firma=$generator->getTipo();
        switch ($tipo_firma) {
          case "P":
          $tipo_firma='presenta';
          break;
          case "C":
          $tipo_firma='firma';
          break;
          case "R":
          $tipo_firma='&egrave; relatore';
          break;
        }

        $news_string .= "<p>";
        
        if ($context == CONTEXT_POLITICO)                      
            $news_string .= '<strong>'.ucfirst($tipo_firma)."</strong> ";
        else
            $news_string .= $politico_link ." <strong>".$tipo_firma."</strong> ";
          
        if ($tipo_firma=='&egrave; relatore')
          $news_string .= "dell'emendamento";
        else
          $news_string .= "l'emendamento";

        
        $news_string .= ' "'. link_to_in_mail($emendamento->getTitoloCompleto(),
                                            '@singolo_emendamento?id=' . $emendamento->getId()) .'"';
                                            

        if ($context != CONTEXT_ATTO)
        {
          $atto = $emendamento->getAttoPortante();

          // tipo di atto e genere per gli articoli e la desinenza
          $tipo = $atto->getOppTipoAtto();
          if (in_array($tipo->getId(), array(1, 10, 11,12,13,15,16,17)))
            $gender = 'm';
          else
            $gender = 'f';

          $news_string .= " riferito ".($gender=='m'?'al ':'alla ');
          $news_string .= $atto->getOppTipoAtto()->getDescrizione()." ";
          $news_string .= link_to_in_mail(
            troncaTesto(
               Text::denominazioneAtto($atto, 'list'), 200
            ), 'atto/index?id='.$atto->getId());
        } 

      }
      
      else $news_string .= $generator_model;
    
    }
  
    else if ($related_monitorable_model == 'OppAtto')
    {
      // fetch dell'atto
      $c = new Criteria(); $c->add(OppAttoPeer::ID, $news->getRelatedMonitorableId());
      $atti = OppAttoPeer::doSelectJoinOppTipoAtto($c);

      // detect a void query
      if (count($atti) == 0) return 'empty OppAtto:' . $news->getRelatedMonitorableId();

      $atto = $atti[0];
    
      // tipo di atto e genere per gli articoli e la desinenza
      $tipo = $atto->getOppTipoAtto();
      if (in_array($tipo->getId(), array(1, 10, 11,12,13,15,16,17)))
        $gender = 'm';
      else
        $gender = 'f';

      // link all'atto
      $atto_link = link_to_in_mail(troncaTesto(Text::denominazioneAtto($atto,'list'),200), 
                           'atto/index?id=' . $atto->getId(),
                           array('title' => $atto->getTitolo()));
    
      // presentazione o passaggio di stato
      if ($generator_model == 'OppAtto')
      { 
        if ($tipo->getId() == 1 && $news->getSucc() !== null)
        {
          // passaggio di stato (cambio ramo?)

          // fetch dell'oggetto succ
          $succ_atto = OppAttoPeer::retrieveByPK($news->getSucc());
          $succ_atto_link = link_to_in_mail($succ_atto->getRamo() . "." . $succ_atto->getNumFase(), 
                               'atto/index?id=' . $succ_atto->getId(),
                               array('title' => $succ_atto->getTitolo()));
          $this_atto_link = link_to_in_mail($atto->getRamo() . "." . $atto->getNumFase(), 
                               'atto/index?id=' . $atto->getId(),
                               array('title' => $atto->getTitolo()));

          $news_string .= "<p>";
          $news_string .= "il ddl $this_atto_link, approvato ";
        
          if ($atto->getRamo()=='C') $news_string .= "alla Camera, ";
          else $news_string .= "al Senato, ";
        
          $news_string .= "<strong>&egrave; ora approdato ";

          if ($succ_atto->getRamo()=='C') $news_string .= "alla Camera</strong> ";
          else $news_string .= "al Senato</strong> ";
        
          $news_string .= "come $succ_atto_link.";
        
          $news_string .= "</p>";
        
        } else {

          // presentazione atto
          if ($tipo->getId()!=13 ) 
          {
            $news_string .= "<p>";
            $news_string .= ($news->getRamoVotazione()=='C')?'Camera -  ' : 'Senato - ';
            $news_string .= "<strong>Presentat" .($gender=='m'?'o':'a') . "</strong> ";
            if ($context!=0)
            {
              $news_string .= $tipo->getDescrizione() . "</p>";
              $news_string .= "<p>".$atto_link."</p>";
            }  
            else  $news_string .= "</p>"; 
          }    
          else {
            $news_string .= "<p>Comunicato del governo: "; 
            $news_string .= $atto_link."</p>";
         }      

        
        } 
      
      }
      
      // intervento 
      else if ($generator_model == 'OppIntervento')
      {
        $politico = $generator->getOppCarica()->getOppPolitico();
        $politico_link = link_to_in_mail($politico, 
                             '@parlamentare?id=' . $politico->getId(),
                             array('title' => 'Vai alla scheda del politico'));
      
        $news_string .= "<p>".$politico_link . " <strong>interviene</strong>";
        if ($generator->getUrl()!=NULL) {
        	if (substr_count($generator->getUrl(),'@')>0) {
        		$int_urls=explode("@",$generator->getUrl()); 
        		$intervento_link= " [vai ai testi";
        		foreach ($int_urls as $cnt => $int_url) {
        			$intervento_link .= " ".link_to(($cnt+1),$int_url).",";
        		}
        		$intervento_link= rtrim($intervento_link,",");
        		$intervento_link .= "]";
        	}
        	else
        		$intervento_link=" [".link_to('vai al testo',$generator->getUrl())."]"; 
        }
        else
        	$intervento_link="";
        	
        $news_string .= $intervento_link." in ";
        
        if ($generator->getOppSede()->getId()!=35 && $generator->getOppSede()->getId()!=36)
           $news_string .= $generator->getOppSede()->getTipologia()." ";
        $news_string .= strtoupper($generator->getOppSede()->getDenominazione()); 
      
        $news_string .= ($news->getRamoVotazione()=='C')?' alla Camera su' : ' al Senato su'; 
        $news_string .= " ".$tipo->getDescrizione() . "</p>";
        $news_string .= '<p>'.$atto_link.'</p>';

      
      }

      // firma
      else if ($generator_model == 'OppCaricaHasAtto')
      {
       $tipo_firma=$generator->getTipo();
        switch ($tipo_firma) {
          case "P":
          $tipo_firma='presentato';
          break;
          case "C":
          $tipo_firma='firmato';
          break;
          case "R":
          $tipo_firma='&egrave; relatore';
          break;
        }
        $politico = $generator->getOppCarica()->getOppPolitico();
        $politico_link = link_to_in_mail($politico, 
                             '@parlamentare?id=' . $politico->getId(),
                             array('title' => 'Vai alla scheda del politico'));
        if ($tipo_firma!='&egrave; relatore' )
        {
          $news_string .= "<p>";
          $news_string .= $politico_link;
          $news_string .= " <strong>ha ".$tipo_firma. "</strong> ";
          $news_string .= $tipo->getDescrizione() . "</p>";
          $news_string .= '<p>'.$atto_link.'</p>';
        
        }        
      }
    
      // spostamento in commissione
      else if ($generator_model == 'OppAttoHasSede')
      {
        $news_string .= "<p>";
        $news_string .= ($news->getRamoVotazione()=='C')?'Camera -  ' : 'Senato - '; 	
        $news_string .= "<strong>&egrave; all'esame</strong> in ";
        $news_string .= $generator->getOppSede()->getTipologia()." ";
        $news_string .= content_tag('b', strtoupper($generator->getOppSede()->getDenominazione()));
        if ($context!=0)
        {
        
           $news_string .= " ".$tipo->getDescrizione() . "</p>";
           $news_string .= "<p>".$atto_link . "</p>";
        }
        else
           $news_string .= "</p>";
      }
    
      // votazioni
      else if ($generator_model == 'OppVotazioneHasAtto')
      {
        $news_string .= "<p>";
        $news_string .= ($news->getRamoVotazione()=='C')?'Camera -  ' : 'Senato - '; 	
        if ($news->getPriority()==1) 
             $news_string .= link_to(' <strong>si &egrave; svolta la votazione finale</strong>','/votazione/'.$generator->getOppVotazione()->getId());
        else
             $news_string .= " si &egrave; svolta la votazione per ".link_to($generator->getOppVotazione()->getTitolo(),'/votazione/'.$generator->getOppVotazione()->getId());     
        if ($context!=0)
        {
           $news_string .= " relativa a ".$tipo->getDescrizione() . "</p>";
           $news_string .= "<p>".$atto_link."</p>"; 
        }
        else
           $news_string .= "</p>";     
      } 
    
      // status conclusivo
      else if ($generator_model == 'OppAttoHasIter') 
      {
        $news_string .= "<p>";
        $news_string .= ($news->getRamoVotazione()=='C')?'Camera -  ' : 'Senato - '; 	
        $news_string .= "Lo status &egrave; ora ";
        $news_string .= content_tag('b', ucfirst(strtolower($generator->getOppIter()->getFase())));
        $news_string .= " per " .($gender=='m'?" ":"la "). $tipo->getDescrizione() . "</p>";
        if ($context != CONTEXT_ATTO) 
          $news_string .= "<p>".$atto_link . "</p>";
        else  $news_string .= "";
     
      } 
    
      else if ($generator_model == 'Tagging')
      {
        $news_string .= "<p>".($gender=='m'?"il ":"la ");
        $news_string .= $tipo->getDescrizione() . " ";
        $news_string .= $atto_link . " ";
        $news_string .= "presentat" .($gender=='m'?'o':'a') . " ";
        if ($news->getRamoVotazione()=='C') $news_string .= ' alla Camera ';
        else
        {
          if ($news->getRamoVotazione()=='S') $news_string .= ' al Senato ';
        }
        $news_string .= "il " . $news->getDataPresentazioneAtto('d/m/Y') . " ";
        $news_string .= "&egrave; stat".($gender=='m'?'o':'a'). " <b>aggiunt".($gender=='m'?'o':'a'). " al monitoraggio dell'argomento ";
        $news_string .= $generator->getTag()->getTripleValue()."</b></p>";
      }
    
      else if ($generator_model == 'OppDocumento')
      {
        $news_string .= "<p>";
        $news_string .= ($news->getRamoVotazione()=='C')?'Camera -  ' : 'Senato - ';
        $news_string .= "E' disponibile il <strong>nuovo documento</strong> ";
        $news_string .= '"'.link_to($generator->getTitolo(),'atto/documento?id='.$generator->getId()).'"';
        if ($context != CONTEXT_ATTO)
        {
          $news_string .= " riferito ".($gender=='m'?'al ':'alla ');
          $news_string .= $generator->getOppAtto()->getOppTipoAtto()->getDescrizione()."</p>";
          $news_string .="<p>".link_to($generator->getOppAtto()->getRamo().".".$generator->getOppAtto()->getNumfase()." ".troncaTesto(Text::denominazioneAtto($generator->getOppAtto(),'list'),200),'atto/index?id='.$generator->getOppAtto()->getId())."</p>";
        } 
      }
    
      else if ($generator_model == 'OppAttoHasEmendamento')
      {
        $emendamento = $generator->getOppEmendamento();
        $news_string .= "<p>";
        $news_string .= ($news->getRamoVotazione()=='C')?'Camera -  ' : 'Senato - ';
        $news_string .= "E' stato presentato  ";
        $news_string .= " in " . $emendamento->getOppSede()->getDenominazione();
        $news_string .= " l'<b>emendamento</b> ";
        $news_string .= '"'. link_to_in_mail($emendamento->getTitoloCompleto(),
                                            '@singolo_emendamento?id=' . $emendamento->getId()) .'"';
        if ($context != CONTEXT_ATTO)
        {
          $news_string .= " riferito ".($gender=='m'?'al ':'alla ');
          $news_string .= $generator->getOppAtto()->getOppTipoAtto()->getDescrizione()." ";
          $news_string .= link_to_in_mail(
            troncaTesto(
               Text::denominazioneAtto($generator->getOppAtto(), 'list'), 200
            ), 'atto/index?id='.$generator->getOppAtto()->getId());
        } 
        
        $news_string .= "</p>";
        
      }
      
      else if ($generator_model == 'OppEmendamentoHasIter')
      {
        $emendamento = $generator->getOppEmendamento();
        $atti = $emendamento->getOppAttoHasEmendamentosJoinOppAtto();
        $news_string .= "<p>";
        $news_string .= ($news->getRamoVotazione()=='C')?'Camera -  ' : 'Senato - ';
        $news_string .= "L'<b>emendamento</b> ";
        $news_string .= '"'. link_to_in_mail($emendamento->getTitoloCompleto(),
                                            '@singolo_emendamento?id=' . $emendamento->getId()) .'"';
        
        if ($context != CONTEXT_ATTO)
        {
          $news_string .= " riferito " . ($gender=='m'?'al ':'alla ');
          $news_string .= $atto_link;
        }
        
        $news_string .= " &egrave; ora " . 
                        content_tag('b', ucfirst(strtolower($generator->getOppEmIter()->getFase())));
      }
      
      else if ($generator_model == 'OppEsitoSeduta')
      {
        $sede = $generator->getOppSede();
        $news_string .= "<p>";
        $news_string .= ($news->getRamoVotazione()=='C')?'Camera -  ' : 'Senato - ';
        $news_string .= "Esito seduta";
        $news_string .= " in " . $sede->getDenominazione();
        if ($context != CONTEXT_ATTO)
        {
          $news_string .= " riferita ".($gender=='m'?'al ':'alla ');
          $news_string .= $atto_link . ": ";
        } 
        $news_string .= "<a href=" .$generator->getUrl() . ">";
        $news_string .= $generator->getEsito();
        $news_string .= "</a>";
        $news_string .= "</p>";
        
      }
      
      else $news_string .= $generator_model;
    
    }

    else if ($related_monitorable_model == 'Tag')
    {
      
      // tag fetch
      $tag = TagPeer::retrieveByPK($news->getRelatedMonitorableId());
      
      if ($generator_model == 'Tagging')
      {
        $tagging_pks = array_values(unserialize($news->getGeneratorPrimaryKeys()));
        $tagging_id = $tagging_pks[0];
        $tagging = TaggingPeer::retrieveByPK($tagging_id);
        
        $taggable_model = $tagging->getTaggableModel();
        $taggable_id = $tagging->getTaggableId();
        $tagged_obj = call_user_func_array(array($taggable_model.'Peer', 'retrieveByPK'), $taggable_id);
        
        if ($taggable_model == 'OppAtto')
        {
          // the tagged object is an atto
          $atto = $tagged_obj;
          
          // tipo di atto e genere per gli articoli e la desinenza
          $tipo = $atto->getOppTipoAtto();
          if (in_array($tipo->getId(), array(1, 10, 11,12,13,15,16,17)))
            $gender = 'm';
          else
            $gender = 'f';

          $atto_link = link_to_in_mail(troncaTesto(Text::denominazioneAtto($atto,'list'),200), 
                               'atto/index?id=' . $atto->getId(),
                               array('title' => $atto->getTitolo()));

          $news_string .= "<p>".($gender=='m'?"il ":"la ");
          $news_string .= $tipo->getDescrizione() . " ";
          $news_string .= $atto_link . " ";
          $news_string .= "presentat" .($gender=='m'?'o':'a') . " ";
          if ($atto->getRamo()=='C') $news_string .= ' alla Camera ';
          else
          {
            if ($atto->getRamo()=='S') $news_string .= ' al Senato ';
          }
          $news_string .= "il " . $atto->getDataPres('d/m/Y') . " ";
          $news_string .= "&egrave; stat".($gender=='m'?'o':'a'). " <b>aggiunt".($gender=='m'?'o':'a'). " al monitoraggio dell'argomento ";
          if ($context != CONTEXT_TAG)
            $news_string .= $generator->getTag()->getTripleValue();
          $news_string .= "</b></p>";          
        } 
        
        if ($taggable_model == 'OppEmendamento')
        {
          $emendamento = $tagged_obj;
          $emendamento_link = link_to_in_mail($emendamento->getTitoloCompleto(),
                                              '@singolo_emendamento?id=' . $emendamento->getId());
          $news_string .= "<p>";
          $news_string .= "l'emendamento " . $emendamento_link . " &egrave; stato <b>aggiunto al monitoraggio dell'argomento ";
          if ($context != CONTEXT_TAG)
            $news_string .= $generator->getTag()->getTripleValue();
          $news_string .= "</b></p>";          
        }
      }
      
    }
  } 
  
  else {
    sfLogger::getInstance()->info('xxx: errore per: ' . $generator_model . ': chiavi: ' . $news->getGeneratorPrimaryKeys());
  }
  
  if ($in_mail)
  {
    $sf_site_url = sfConfig::get('sf_site_url', 'openparlamento');
    $news_string = str_replace('./symfony', $sf_site_url, $news_string); # per il test e per sicurezza
    $news_string = str_replace('a href=', 'a style="color: #339;" href=', $news_string);    
  }
  
  return $news_string;
}


/**
* return the correct icon for the given news
 *
 * @param String $generator_model 
 * @param BaseObject $generator 
 * @return string
 * @author Guglielmo Celata
 */
function news_icon_name($generator_model, $generator)
{
  $icon_types = array('OppIntervento'       => 'intervento',
                      'OppVotazioneHasAtto' => 'votazione',
                      'OppCaricaHasAtto'    => 'ordinanza',
                      'OppCarica'           => 'politico',
                      'OppCaricaHasGruppo'  => 'politico',
                      'OppCaricaHasAtto'    => 'ordinanza',
                      'OppAttoHasSede'      => 'next-iter',
                      'Tagging'             => 'etichetta',
                      'OppDocumento'        => 'document',
                      'OppAttoHasIter'      => 'next-iter',
                      'OppCaricaHasEmendamento' => 'ordinanza',
                      'OppAttoHasEmendamento' => 'attonoleg',
                      'OppEmendamentoHasIter' => 'next-iter',
                      );

  // attos are specials
  if ($generator_model == 'OppAtto')
  {
    $tipo_atto_id = $generator->getOppTipoAtto()->getId();
    
    // distinction between legislative and non-legislative acts
    if (in_array($tipo_atto_id, array(1, 12, 15, 16, 17)))
      $type = 'proposta';
    else
      $type = 'attonoleg';    
  } else {
    $type = $icon_types[$generator_model];
  }

  
  return "ico-type-$type.png";
}


/**
 * generate the html representation for the given news
 *
 * @param string $news 
 * @return string (html)
 * @author Guglielmo Celata
 */
function community_news_text($news)
{
  $news_string = "";
  
  // fetch generator model
  $generator_model = $news->getGeneratorModel();

  // fetch related model and object (item)
  $related_model = $news->getRelatedModel();
  $related_id = $news->getRelatedId();
  $item = call_user_func_array($related_model.'Peer::retrieveByPK', array($related_id));

  if (is_null($item))
    return "notizia su oggetto inesistente: ($related_model:$related_id)";
  
  // build item link
  switch ($related_model)
  {
    case 'OppPolitico':
      // link al politico
      $item_type = 'il parlamentare';
      $link = link_to_in_mail($item, 
                             '@parlamentare?id=' . $related_id,
                             array('title' => 'Vai alla scheda del politico'));
      break;

    case 'OppDocumento':
      // link al documento
      $link = link_to_in_mail($item->getTitolo(), 
                              '@documento?id=' . $related_id,
                              array('title' => $item->getTitolo()));

      $related_atto = OppAttoPeer::retrieveByPK($item->getAttoId());

      // costruzione del link all'atto relativo al documento
      if (in_array($related_atto->getTipoAttoId(), array(1, 3, 4, 5, 6, 10, 11, 14))) 
        $atto_article = 'all\'';
      elseif (in_array($related_atto->getTipoAttoId(), array(12, 13, 15, 16, 17)))
        $atto_article = 'al ';
      else
        $atto_article = 'alla ';  
          
      $atto_link = $atto_article.$related_atto->getOppTipoAtto()->getDescrizione()." ";
      $atto_link .= link_to_in_mail(Text::denominazioneAtto($related_atto, 'list'), 
                                   'atto/index?id=' . $related_atto->getId(),
                                   array('title' => $related_atto->getTitolo()));
      
      break;

    case 'OppAtto': 
      // link all'atto 
      if (in_array($item->getTipoAttoId(), array(1, 10, 11,12,13,15,16,17)))  
        $gender = 'm'; 
      else 
        $gender = 'f';   

      $item_type = ($gender=='m'?'':'la')." ".$item->getOppTipoAtto()->getDescrizione()." "; 
      $link = link_to_in_mail(Text::denominazioneAtto($item, 'list'),  
                            'atto/index?id=' . $related_id, 
                            array('title' => $item->getTitolo())); 
      break; 

    case 'OppVotazione': 
      // link alla votazione 
      $item_type = 'la votazione'; 
      $link = link_to_in_mail($item->getTitolo(),  
                            '@votazione?id=' . $related_id, 
                            array('title' => 'Vai alla pagina della votazione')); 
      break; 

    case 'Tag': 
      // link all'argomento 
      $item_type = 'l\'argomento'; 
      $link = link_to_in_mail($item->getTripleValue(),  
                            '@argomento?triple_value=' . $item->getTripleValue(), 
                            array('title' => 'Vai alla pagina dell\'argomento')); 
      break; 
 	}       
 	 
 	// build html code   
 	switch ($generator_model)  
 	{ 

    case 'sfEmendComment':
      // link al documento
      $link = link_to_in_mail($item->getTitolo(), 
                              '@documento?id=' . $related_id,
                              array('title' => $item->getTitolo()));

      if ($news->getType() == 'C')
        return sprintf("<div class='ico-type float-left'>%s</div><p>%s ha commentato il documento</p><p><strong>%s</strong></p><p>relativo %s</p>", 
                       image_tag('/images/ico-type-commento.png', array('alt' => 'commento')),
                       strtolower($news->getUsername()), $link, $atto_link);      
      break;
    
    
    case 'sfComment':
      return sprintf("<div class='ico-type float-left'>%s</div><p>%s ha commentato %s</p><p> %s</p>", 
                     image_tag('/images/ico-type-commento.png', array('alt' => 'commento')),strtolower($news->getUsername()), $item_type, $link);
      break;

      
    case 'Monitoring':
      if ($news->getType() == 'C')
      {
          if ($news->getTotal()>0)
          {
            if ($news->getTotal()>1)
               return sprintf("<div class='ico-type float-left'>%s</div><p>un utente si è aggiunto agli altri %d che stanno monitorando %s</p><p> %s", 
                              image_tag('/images/ico-type-monitoring.png', array('alt' => 'monitor')),$news->getTotal(), $item_type, $link); 
            else
               return sprintf("<div class='ico-type float-left'>%s</div><p>un utente si è aggiunto a un altro che sta monitorando %s</p><p> %s", 
                              image_tag('/images/ico-type-monitoring.png', array('alt' => 'monitor')),$item_type, $link); 
          }                    
          else
               return sprintf("<div class='ico-type float-left'>%s</div><p>un primo utente ha avviato il monitoraggio per %s</p><p> %s", 
                              image_tag('/images/ico-type-monitoring.png', array('alt' => 'monitor')),$item_type, $link);      
      }                              
      else
         return sprintf("<div class='ico-type float-left'>%s</div><p>un utente ha smesso di monitorare %s</p><p> %s</p>", 
                              image_tag('/images/ico-type-monitoring.png', array('alt' => 'monitor')),$item_type, $link);
      break;
      
    case 'sfVoting':
      if ($news->getType() == 'C')
      {
        if ($news->getVote() == 1) $fav_contr = '<span style="color:green; font-weight:bold;">favorevoli</span>';
        else $fav_contr = '<span style="color:red; font-weight:bold;">contrari</span>';
        if ($news->getTotal()>0)
        {
           if ($news->getTotal()>1)
               return sprintf("<div class='ico-type float-left'>%s</div><p>un utente si è aggiunto agli altri %d %s al%s </p><p> %s</p>", 
                              image_tag('/images/ico-type-votazione-user.png', array('alt' => 'voto')),$news->getTotal(), $fav_contr, $item_type, $link);
           else
           {
               if (substr_count($fav_contr,'favorevoli') == 1) $fav_contr = '<span style="color:green; font-weight:bold;">favorevole</span>';
               else $fav_contr = '<span style="color:red; font-weight:bold;">contrario</span>';
               return sprintf("<div class='ico-type float-left'>%s</div><p>un utente si è aggiunto a un altro %s al%s</p><p>%s</p>", 
                              image_tag('/images/ico-type-votazione-user.png', array('alt' => 'voto')),$fav_contr, $item_type, $link);
           }                    
        }                      
        else
        {
               if (substr_count($fav_contr,'favorevoli') == 1) $fav_contr = '<span style="color:green; font-weight:bold;">favorevole</span>';
               else $fav_contr = '<span style="color:red; font-weight:bold;">contrario</span>';
               return sprintf("<div class='ico-type float-left'>%s</div><p>un utente &egrave; %s al%s</p><p> %s</p>", 
                               image_tag('/images/ico-type-votazione-user.png', array('alt' => 'voto')),$fav_contr, $item_type, $link);           
        }                           
      } else {
        return sprintf("<div class='ico-type float-left'>%s</div><p>utente ha ritirato il suo voto per %s</p><p> %s</p>", 
                      image_tag('/images/ico-type-votazione-user.png', array('alt' => 'voto')),$item_type, $link);          
      }
      break;

    case 'nahoWikiRevision':
      return sprintf("<div class='ico-type float-left'>%s</div><p>%s ha modificato la descrizione wiki per %s</p><p> %s</p>", 
                     image_tag('/images/ico-type-descrizione.png', array('alt' => 'wiki!')),strtolower($news->getUsername()), $item_type, $link);
      break;
  }
  
  
}


function troncaTesto($testo, $caratteri) { 

    if (strlen($testo) <= $caratteri) return $testo; 
    $nuovo = wordwrap($testo, $caratteri, "|"); 
    $nuovotesto=explode("|",$nuovo); 
    return $nuovotesto[0]."..."; 
} 


?>