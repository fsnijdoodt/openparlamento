<?php
class defaultComponents extends sfComponents
{

  public function executeClassifiche()
  {

    // box rotazione politici
    
    // random del ramo
    if ($this->ramo==0 ) {
	    if (rand(1,2)==1) {
	    	$tipo_carica=1;
	    	$this->nome_carica='deputati';
	    } else {
	    	$tipo_carica=4;
	    	$this->nome_carica='senatori';
	    }
    }
    if ($this->ramo==1) {
    	$tipo_carica=1;
    	$this->nome_carica='deputati';
    } 	
    if ($this->ramo==2) {
    	$tipo_carica=4;
    	$this->nome_carica='senatori';
    }	        	
    
    $c = new Criteria();
    $c->clearSelectColumns();
    $c->addSelectColumn(OppCaricaPeer::ID);
    $c->addSelectColumn(OppPoliticoPeer::ID);
    $c->addSelectColumn(OppPoliticoPeer::COGNOME);
    $c->addSelectColumn(OppPoliticoPeer::NOME);
    $c->addSelectColumn(OppCaricaPeer::CIRCOSCRIZIONE);
    $c->addSelectColumn(OppCaricaPeer::PRESENZE);
    $c->addSelectColumn(OppCaricaPeer::ASSENZE);
    $c->addSelectColumn(OppCaricaPeer::MISSIONI);
    $c->addSelectColumn(OppCaricaPeer::INDICE);
    $c->addSelectColumn(OppCaricaPeer::POSIZIONE);
    $c->addSelectColumn(OppCaricaPeer::MEDIA);
    $c->addSelectColumn(OppCaricaPeer::RIBELLE);
    $c->addSelectColumn(OppPoliticoPeer::N_MONITORING_USERS);
    $c->addJoin(OppCaricaPeer::POLITICO_ID, OppPoliticoPeer::ID, Criteria::INNER_JOIN);
    $c->add(OppCaricaPeer::DATA_FINE,NULL,Criteria::ISNULL);
    $c->add(OppCaricaPeer::TIPO_CARICA_ID,$tipo_carica);
    // escludere boldrini e grasso
    $c->add(OppPoliticoPeer::ID, array(686427, 687024), Criteria::NOT_IN);
    
    $this->quale_pagina=$this->classifica;
    
    // random sul cosa
    if ($this->classifica==0)
      $random=rand(1,4);
    else
       $random=$this->classifica;
       
    switch ($random) {
      case 1:
        $c->addDescendingOrderByColumn(OppCaricaPeer::PRESENZE);
        $c->addAscendingOrderByColumn(OppPoliticoPeer::COGNOME);
        $this->color='green';
        $this->string='pi&ugrave; presenti';
        $this->cosa=1;
      break;
      
      case 2:
        $c->addDescendingOrderByColumn(OppCaricaPeer::ASSENZE);
        $c->addAscendingOrderByColumn(OppPoliticoPeer::COGNOME);
        $this->color='red';
        $this->string='pi&ugrave; assenti';
        $this->cosa=2;
      break;

      
      case 5:
        $c->addDescendingOrderByColumn(OppCaricaPeer::INDICE);
        $c->addAscendingOrderByColumn(OppPoliticoPeer::COGNOME);
        $this->color='blue';
        $this->string='pi&ugrave; produttivi <span style="margin-left:5px;"><a href="http://indice.openpolis.it/info.html" style="font-size:11px;">come sono calcolati?</a></span>';
        $this->cosa=3;
      break;
      

       case 3:
        $c->addDescendingOrderByColumn(OppPoliticoPeer::N_MONITORING_USERS);
        $c->addAscendingOrderByColumn(OppPoliticoPeer::COGNOME);
        $this->color='orange';
        $this->string='pi&ugrave; monitorati dagli utenti';
        $this->cosa=4;
      break;
      
       case 4:
        $c->addDescendingOrderByColumn(OppCaricaPeer::RIBELLE);
        $c->addAscendingOrderByColumn(OppPoliticoPeer::COGNOME);
        $this->color='violet';
        $this->string='pi&ugrave; ribelli al proprio gruppo';
        $this->cosa=5;
      break;
      /*
       case 6:
        $c->addAscendingOrderByColumn(OppCaricaPeer::INDICE);
        $this->color='violet';
        $this->string='meno attivi';
        $this->cosa=6;
      break;
      */
    
    }
   
    $c->setLimit($this->limit); 
    $this->parlamentari = OppCaricaPeer::doSelectRS($c);
    
  }

}

?>
