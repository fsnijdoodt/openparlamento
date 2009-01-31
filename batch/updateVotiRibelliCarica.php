<?php

define('SF_ROOT_DIR',    realpath(dirname(__FILE__).'/..'));
define('SF_APP',         'fe');
define('SF_ENVIRONMENT', 'prod');
define('SF_DEBUG',       false);

require_once(SF_ROOT_DIR.DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.SF_APP.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');
sfContext::getInstance();

require_once("batch/get_args_options.php");
$args = arguments($argv);
$argv = $args['input'];
$argc = count($argv);


# controllo sintassi
if ( $argc != 2 ) 
{
  print "sintassi: php batch/updateVotiRibelliCarica carica_id\n";  
  exit;
}

$carica_id = $argv[1];

$carica = OppCaricaPeer::retrieveByPK($carica_id);
$parlamentare =  $carica->getOppPolitico();
$votazioni = $carica->getOppVotazioneHasCaricas();
$nvotazioni = count($votazioni);
print "- $nvotazioni votazioni: \n";
foreach ($votazioni as $k => $votazione_carica) {
  $votazione = $votazione_carica->getOppVotazione();
  $data_votazione = $votazione->getOppSeduta()->getData();
  
  $c = new Criteria();
  $c->add(OppCaricaHasGruppoPeer::DATA_INIZIO, $data_votazione, Criteria::LESS_EQUAL);
  $cton = $c->getNewCriterion(OppCaricaHasGruppoPeer::DATA_FINE, $data_votazione, Criteria::GREATER_EQUAL);
  $cton->addOr($c->getNewCriterion(OppCaricaHasGruppoPeer::DATA_FINE, null, Criteria::ISNULL));
  $c->add($cton);
  $gruppi_carica_votazione = $carica->getOppCaricaHasGruppos($c);

  $gruppo_votazione = $gruppi_carica_votazione[0];
  $voto = $votazione_carica->getVoto();
  $voto_gruppo = $votazione->getVotoGruppo($gruppo_votazione->getGruppoId());
  if ( ($voto_gruppo == 'Favorevole' || $voto_gruppo == 'Astenuto' || $voto_gruppo == 'Contrario' ) && 
       ($voto == 'Favorevole' || $voto == 'Astenuto' || $voto == 'Contrario') && 
       $voto_gruppo != $voto )
  {  
    $votazione_carica->setRibelle(1);
    $votazione_carica->save();
  }
 
  if ($k % 10 == 0) print ".";
  if ($k >0 && $k % 1000 == 0) print "$k/$nvotazioni\n";
}

?>
