<?php

/**
 * Subclass for performing query and update operations on the 'opp_atto' table.
 *
 * 
 *
 * @package lib.model
 */ 
class OppAttoPeer extends BaseOppAttoPeer
{

  const ATTI_DDL_TIPO_IDS = "1";
  const ATTI_DECRETI_TIPO_IDS = "12";
  const ATTI_DECRETI_LEGISLATIVI_TIPO_IDS = "15, 16, 17";
  const ATTI_NON_LEGISLATIVI_TIPO_IDS = "2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 13, 14";


  public static function getKeyActs($limit = 0, $namespace = 'home')
  {
    $c = new Criteria();
    $c->addJoin(OppAttoPeer::ID, sfLaunchingPeer::OBJECT_ID);
    $c->add(sfLaunchingPeer::OBJECT_MODEL, 'OppAtto'); 
    # validation
    if ($namespace == 'home') {
      $c->add(sfLaunchingPeer::LAUNCH_NAMESPACE, $namespace);
    } 
    $c->addDescendingOrderByColumn(sfLaunchingPeer::PRIORITY); 
    if ($limit != 0)
       $c->setLimit($limit);
    return OppAttoPeer::doSelect($c);    
  }

  public static function get_fattore_tipo_atto($tipo_atto_id)
  {
    switch ($tipo_atto_id)
    {
      case 1:
        $val = 10;
        $break;
      
       case 3:
       case 4:
       case 5:
       case 6:
       	$val = 3;
        break;
        
      case 10:
      case 11: 
      case 12:
      case 13:
      case 14:
      case 15:
      case 16:
      case 17:
        $val = 4;
        break;
        
      case 2:
      	$val = 6;
        break;
     
      case 7:
      case 8:
      case 9:
        $val = 5;
        break;
      
      default:
        $val = 0;
        break;
    }
    
    return $val;
    
  }


  public static function getDaAggiornare($leg, $tipo_aggiornamento='daily')
  {
		$con = Propel::getConnection(self::DATABASE_NAME);
		if ($tipo_aggiornamento == 'daily') {
  		$one_year_ago = strftime('%Y-%m-%d', strtotime('1 year ago'));
      $sql = sprintf("select a.*, ai.iter_id, ai.created_at from opp_atto a, opp_atto_has_iter ai, opp_iter i where ai.atto_id=a.id and ai.iter_id=i.id and a.tipo_atto_id = 1 and a.legislatura = %d and i.concluso != 1 and (ai.iter_id!=1 or ai.iter_id=1 and ai.created_at > '%s') and (ai.iter_id != 3 or ai.iter_id=3 and ai.created_at>'%s') group by a.id order by a.created_at desc;",
                     $leg, $one_year_ago, $one_year_ago);
		} elseif ($tipo_aggiornamento == 'weekly') {
		  // in realtà si potrebbe prendere tutto quello che non viene preso nel daily
      $sql = sprintf("select a.* from opp_atto a where a.legislatura = %d order by a.created_at desc;", $leg);		  
		} else {
		  throw new Exception('Wrong parameter: $tipo_aggiornamento may take the value: daily or weekly');
		}

    $stm = $con->createStatement(); 
    $rs = $stm->executeQuery($sql, ResultSet::FETCHMODE_ASSOC);
    return $rs;
  }
  
  public static function isUnifiedText($atto_id)
  {
    
		$con = Propel::getConnection(self::DATABASE_NAME);
    $sql = sprintf("select a.is_main_unified, ra.tipo_relazione_id, ra.atto_to_id from opp_atto a, opp_relazione_atto ra where (ra.atto_from_id=a.id) and ra.tipo_relazione_id=1 and a.id=%d group by a.id;",
                   $atto_id);

    $stm = $con->createStatement(); 
    $rs = $stm->executeQuery($sql, ResultSet::FETCHMODE_ASSOC);

    if (!$rs->next()) return null;
    else return $rs->getRow();
    
  }

  /**
   * check if an act is absorbed by another
   *
   * @param string $atto_id 
   * @return id of the absorbing act or null
   * @author Guglielmo Celata
   */
  public static function isAbsorbed($atto_id)
  {
    
		$con = Propel::getConnection(self::DATABASE_NAME);
    $sql = sprintf("select ra.atto_to_id from opp_atto a, opp_relazione_atto ra, opp_tipo_relazione r where ra.atto_from_id=a.id and ra.tipo_relazione_id=r.id and a.id=%d and r.id=4;",
                   $atto_id);

    $stm = $con->createStatement(); 
    $rs = $stm->executeQuery($sql, ResultSet::FETCHMODE_ASSOC);

    if (!$rs->next()) return null;
    else 
    {
      $row = $rs->getRow();
      return $row['atto_to_id'];      
    }
    
  }
  

  /**
   * torna array di OppAtto a partire da un array di ID 
   *
   * @param array $ids 
   * @return array of OppAtto
   * @author Guglielmo Celata
   */
  public function getRSFromIDArray($ids, $con = null)
  {
		if ($con === null) {
			$con = Propel::getConnection(self::DATABASE_NAME);
		}

    $sql = sprintf("select a.id, a.tipo_atto_id from opp_atto a where a.id in (%s)",
                   implode(",", $ids));
    $stm = $con->createStatement(); 
    return $stm->executeQuery($sql, ResultSet::FETCHMODE_ASSOC);
  }



  public static function isAttoVotatoDaOpposizione($atto_id, $data)
  {
		$con = Propel::getConnection(self::DATABASE_NAME);

    $sql = sprintf("select count(*) as nm from opp_votazione_has_atto va, opp_votazione v, opp_votazione_has_gruppo vg, opp_gruppo_is_maggioranza gm, opp_seduta s  where va.votazione_id=v.id and v.seduta_id=s.id and vg.votazione_id=v.id and vg.gruppo_id=gm.gruppo_id and va.atto_id=%d and v.finale=1 and gm.maggioranza = 0  and vg.voto = 'Favorevole' and vg.gruppo_id != 13 and s.data < '%s' and gm.data_inizio < '%s' and (gm.data_fine > '%s' or gm.data_fine is null)",
                   $atto_id, $data, $data, $data);
    $stm = $con->createStatement(); 
    $rs = $stm->executeQuery($sql, ResultSet::FETCHMODE_ASSOC);
    
    $rs->next();
    $row = $rs->getRow();
    $n_gruppi_maggioranza = $row['nm'];

    if ($n_gruppi_maggioranza > 0)
      return true;
    else 
      return false;
  }

  /**
   * estrae gli atti presentati entro una certa data
   * di iniziativa parlamentare, governativa o non legislativi
   * @param string   $data ['', 'y-m-d']
   * @param integer  $offset
   * @param integer  $limit
   * @return recordset
   * @author Guglielmo Celata
   */
  public static function getAttiDataRS($data, $offset = null, $limit = null)
  {
    // calcolo della legislatura
    $legislatura = OppLegislaturaPeer::getCurrent($data);

		$con = Propel::getConnection(self::DATABASE_NAME);
    $sql = sprintf("select a.id, a.tipo_atto_id from opp_atto a where legislatura = %d and data_pres < '%s' and (a.iniziativa = 1 or a.iniziativa = 2 or a.iniziativa is null) order by data_pres desc",
                   $legislatura, $data);
    if (!is_null($limit)) {
      if (!is_null($offset))
        $sql .= " limit $offset, $limit";
      else
        $sql .= " limit $limit";
    }
    $stm = $con->createStatement(); 
    return $stm->executeQuery($sql, ResultSet::FETCHMODE_ASSOC);
  }
  
  /**
   * estrae gli id degli atti di un certo tipo (o tutti, se non specificato)
   * @param string   $tipi un array di id 
   * @param integer  $offset
   * @param integer  $limit
   * @return recordset
   * @author Guglielmo Celata
   */
  public static function getAttiTipoRS($tipi = array(), $offset=null, $limit=null)
  {
    // calcolo della legislatura
    $legislatura = OppLegislaturaPeer::getCurrent();

    // tipi può essere un array o una stringa (id delim. da virgole)

		$con = Propel::getConnection(self::DATABASE_NAME);
		if (count($tipi)) {
      $sql = sprintf("select id, tipo_atto_id from opp_atto where legislatura = %d and tipo_atto_id in (%s) order by data_pres desc",
                     $legislatura, implode(",", $tipi));
		} else {
      $sql = sprintf("select id, tipo_atto_id from opp_atto where legislatura = %d order by data_pres desc",
                     $legislatura);		  
		}
    if (!is_null($limit)) {
      if (!is_null($offset))
        $sql .= " limit $offset, $limit";
      else
        $sql .= " limit $limit";
    }
    $stm = $con->createStatement(); 
    return $stm->executeQuery($sql, ResultSet::FETCHMODE_ASSOC);
  }
  
  
  /**
   * estrae gli atti presentati in un intervallo di date (al massimo 200)
   * estrae solo gli atti al primo step dell'iter (pred=null)
   * @param string   $data_inizio ['y-m-d']
   * @param string   $data_fine ['y-m-d']
   * @param integer  $ramo
   * @param integer  $tipo_atto
   * @param integer  $escludi_tipi - array di id opp_tipo_atto
   * @return array di OppAtto
   * @author Guglielmo Celata
   */
  public static function getAttiInDateInterval($data_inizio, $data_fine, $ramo = null, $tipo_atto = null, $escludi_tipi = array())
  {
    if ($data_fine < $data_inizio)
      throw new Exception('La data_inizio deve essere precedente o uguale alla data_fine');
      
    // calcolo della legislatura
    $legislatura = OppLegislaturaPeer::getCurrent($data_inizio);

    $c = new Criteria();
    $c->add(self::LEGISLATURA, $legislatura);
    
    // $c->add(self::PRED, null, Criteria::ISNULL);

    $c1 = $c->getNewCriterion(self::DATA_PRES, $data_inizio, Criteria::GREATER_EQUAL);
    $c2 = $c->getNewCriterion(self::DATA_PRES, $data_fine, Criteria::LESS_THAN);
    $c2->addAnd($c1);
    $c->add($c2);

    if ($ramo)
      $c->add(self::RAMO, $ramo);

    // non sono estratti odg e comunicati gov, a meno che non siano richiesti esplicitamente
    if ($tipo_atto)
    {
      $tipi_ids = self::getTipiIdsFromDenominazioneFB($tipo_atto);
      $c->add(self::TIPO_ATTO_ID, $tipi_ids, Criteria::IN);
    } else {
      // di default esclude atti di tipo ODG e Comunicati del Governo
      $escludi_tipi_ids = array(10, 11, 13);
      foreach ($escludi_tipi as $tipo_atto) {
        $escludi_tipi_ids = array_merge($escludi_tipi_ids, self::getTipiIdsFromDenominazioneFB(trim($tipo_atto)));
      }
      $c->add(self::TIPO_ATTO_ID, $escludi_tipi_ids, Criteria::NOT_IN);
    }
    
    $c->setLimit(500);
    $c->addDescendingOrderByColumn(self::DATA_PRES);
    
    return self::doSelect($c);
  }
  
  public static function getTipiIdsFromDenominazioneFB($denominazione)
  {
    switch (strtoupper($denominazione)) {
      case 'SDDL':
        $tipi_ids = array(1);
        break;
      case 'MOZIONE':
        $tipi_ids = array(2);
        break;
      case 'INTERPELLANZA':
        $tipi_ids = array(3);
        break;
      case 'INTERROGAZIONE':
        $tipi_ids = array(4, 5, 6);
        break;
      case 'RISOLUZIONE':
        $tipi_ids = array(7, 8, 9);
        break;
      case 'ODG':
        $tipi_ids = array(10, 11);
        break;
      case 'DECRETO':
        $tipi_ids = array(12);
        break;
      case 'AUDIZIONE':
        $tipi_ids = array(14);
        break;
      case 'DLGS':
        $tipi_ids = array(15, 16, 17);
        break;
      default:
        $tipi_ids = array();
        break;
    }
    return $tipi_ids;    
  }
  
  public static function getAttiInEvidenza($namespace = 'all')
  {
    $c = new Criteria();
    $c->addDescendingOrderByColumn(sfLaunchingPeer::PRIORITY);
    $c->addDescendingOrderByColumn(OppAttoPeer::STATO_LAST_DATE);
    $c->addJoin(sfLaunchingPeer::OBJECT_ID, OppAttoPeer::ID);
    $c->addJoin(OppTipoAttoPeer::ID, OppAttoPeer::TIPO_ATTO_ID);
    $c->add(sfLaunchingPeer::OBJECT_MODEL, 'OppAtto');
    if ($namespace != 'all') {
      $c->add(sfLaunchingPeer::LAUNCH_NAMESPACE, $namespace);
    }
    return OppAttoPeer::doSelect($c);
  }


  /**
   * extracts all acts of the given type, subjected to filters passed along
   *
   * @param string $atto_type_ids       constant to select the type of act
   * @param string $filtering_criteria  filter on acts' attributes
   * @return array of OppAtto objects
   * @author Guglielmo Celata
   */
  public static function doSelectFilteredActs( $atto_type_ids, $filtering_criteria = null)
  {
    if (is_null($filtering_criteria))
      $c = new Criteria();
    else
      $c = clone $filtering_criteria;

    $atto_type_ids_arr = explode(",", $atto_type_ids);
    $c->add(self::TIPO_ATTO_ID, $atto_type_ids_arr, Criteria::IN);      

    return OppAttoPeer::doSelect($c);
  }


  /**
   * returns the Atto objects that are indirectly monitored by a user, 
   * that monitors at least one tag with which the object has been tagged
   *
   * @param  OppUser
   * @param  OppTipoAtto - if give, acts as a filter
   * @return array of objects
   * @author Guglielmo Celata
   **/
  public static function doSelectIndirectlyMonitoredByUser($user, $type = null, $act_criteria = null, $my_monitored_tags_pks = null, $act_criteria = null)
  {
    if (!($user instanceof OppUser)) throw new Exception('A user must be specified');

    // build the array of monitored tags_ids if it is not passed as a param
    if (is_null($my_monitored_tags_pks))
      $my_monitored_tags_pks = $user->getMonitoredPks('Tag', $tag_criteria);
    
    // fetch all acts tagged with the monitored tags (indirect monitoring)
    if (is_null($act_criteria))
      $c = new Criteria();
    else
      $c = clone $act_criteria;
    if ($type instanceof OppTipoAtto)
    {
      $c->addJoin(OppTipoAttoPeer::ID, OppAttoPeer::TIPO_ATTO_ID);
      $c->add(OppTipoAttoPeer::ID, $type->getPrimaryKey());
    }
    $c->addJoin(OppAttoPeer::ID, TaggingPeer::TAGGABLE_ID);
    $c->add(TaggingPeer::TAGGABLE_MODEL, 'OppAtto');
    $c->add(TaggingPeer::TAG_ID, $my_monitored_tags_pks, Criteria::IN);

    // elimina atti conclusi
    $c->add(OppAttoPeer::STATO_COD, 'CO', Criteria::NOT_EQUAL);

    $indirectly_monitored_acts = OppAttoPeer::doSelect($c);
    unset($c);
    
    return $indirectly_monitored_acts;
    
  }
  
  /**
   * returns the Atto objects that are indirectly monitored by a user, 
   * joined with the Tags Objects themselves
   *
   * @param  OppUser
   * @param  OppTipoAtto - if give, acts as a filter
   * @return array of objects
   * @author Guglielmo Celata
   **/
  public static function doSelectIndirectlyMonitoredByUserJoinTags($user, $type = null, $tag_criteria = null, $my_monitored_tags_pks = null, $act_criteria = null)
  {
    if (!($user instanceof OppUser)) throw new Exception('A user must be specified');
    
    // build the array of monitored tags_ids if it is not passed as a param
    if (is_null($my_monitored_tags_pks))
      $my_monitored_tags_pks = $user->getMonitoredPks('Tag', $tag_criteria);
    
    // fetch all acts tagged with the monitored tags (indirect monitoring)
    if (is_null($act_criteria))
      $c = new Criteria();
    else
      $c = clone $act_criteria;
    if ($type instanceof OppTipoAtto)
    {
      $c->addJoin(OppTipoAttoPeer::ID, OppAttoPeer::TIPO_ATTO_ID);
      $c->add(OppTipoAttoPeer::ID, $type->getPrimaryKey());
    }
    $c->addJoin(OppAttoPeer::ID, TaggingPeer::TAGGABLE_ID);
    $c->add(TaggingPeer::TAGGABLE_MODEL, 'OppAtto');
    
    $c->add(TaggingPeer::TAG_ID, $my_monitored_tags_pks, Criteria::IN);

    // elimina atti conclusi
    $c->add(OppAttoPeer::STATO_COD, 'CO', Criteria::NOT_EQUAL);

    $indirectly_monitored_acts = OppAttoPeer::doSelect($c);
    unset($c);
    
    return $indirectly_monitored_acts;
    
  }
  
  
  /**
   * returns the Atto objects that are directly monitored by a user, 
   *
   * @param  OppUser
   * @param  OppTipoAtto - if given, acts as a filter
   * @return array of objects
   * @author Guglielmo Celata
   **/
  public static function doSelectDirectlyMonitoredByUser($user, $type = null, $act_criteria = null)
  {
    
    if (!($user instanceof OppUser)) throw new Exception('A user must be specified');

    // fetch all acts tagged with the monitored tags (indirect monitoring)
    if (is_null($act_criteria))
      $c = new Criteria();
    else
      $c = clone $act_criteria;

    if ($type instanceof OppTipoAtto)
    {
      $c->addJoin(OppTipoAttoPeer::ID, OppAttoPeer::TIPO_ATTO_ID);
      $c->add(OppTipoAttoPeer::ID, $type->getPrimaryKey());      
      
      // elimina atti conclusi
      $c->add(OppAttoPeer::STATO_COD, 'CO', Criteria::NOT_EQUAL);

    }

    // fetch directly monitored acts PKs
    $directly_monitored_acts_pks = $user->getMonitoredPks('OppAtto', $c);
    
    // return objects
    return self::retrieveByPKs($directly_monitored_acts_pks);
    
  }

  public static function merge($items1, $items2)
  {
    // merge directly and indirectly monitored acts types
    $items_pks = array_merge(Util::transformIntoPKs($items1), Util::transformIntoPKs($items2));
    return self::retrieveByPKs($items_pks);
  }
  
   
  public static function doSelectPrimiFirmatari($pred)
  {
    $primi_firmatari = array();
	
    $rs = OppAttoPeer::getRecordsetFirmatari($pred, 'P');
	
	  while ($rs->next())
    {
	    if($rs->getString(4) != '')
	      $primi_firmatari[$rs->getInt(1)]=$rs->getDate(5, 'Y-m-d').' * '.$rs->getString(2).' '.$rs->getString(3).' ('.$rs->getString(4).')'; 
	    else
	      $primi_firmatari[$rs->getInt(1)]=$rs->getDate(5, 'Y-m-d').' * '.$rs->getString(2).' '.$rs->getString(3); 
	  }
	  return $primi_firmatari;
	
  }
  
  
  public static function doSelectCoFirmatari($pred)
  {
    $co_firmatari = array();
	
	$rs = OppAttoPeer::getRecordsetFirmatari($pred, 'C');
	
	while ($rs->next())
    {
	  if($rs->getString(4) != '')
	    $co_firmatari[$rs->getInt(1)]=$rs->getDate(5, 'Y-m-d').' * '.$rs->getString(2).' '.$rs->getString(3).' ('.$rs->getString(4).')';  
	  else
	    $co_firmatari[$rs->getInt(1)]=$rs->getDate(5, 'Y-m-d').' * '.$rs->getString(2).' '.$rs->getString(3);    
	}
    
	return $co_firmatari;
  }
  
  
  
   public static function doSelectRelatori($pred)
  {
    $relatori = array();
	
	$rs = OppAttoPeer::getRecordsetFirmatari($pred, 'R');
	
	while ($rs->next())
    {
	  $relatori[$rs->getInt(1)]=$rs->getString(2).' '.$rs->getString(3).' ('.$rs->getString(4).')'; 
	}
    
	return $relatori;
  }
  
  public static function doSelectTeseo($pred)
  {
    $argomenti = array(); 
	
	$c = new Criteria();
	$c->clearSelectColumns();
	$c->addSelectColumn(OppTeseoPeer::ID);
	$c->addSelectColumn(OppTeseoPeer::DENOMINAZIONE);
	$c->add(OppAttoHasTeseoPeer::ATTO_ID, $pred, Criteria::EQUAL);
	$c->addJoin(OppAttoHasTeseoPeer::TESEO_ID, OppTeseoPeer::ID, Criteria::LEFT_JOIN);
	$c->addAscendingOrderByColumn(OppTeseoPeer::DENOMINAZIONE);
	$rs = OppAttoHasTeseoPeer::doSelectRS($c);
	
	while ($rs->next())
    {
	  $argomenti[$rs->getInt(1)]=$rs->getString(2);  
	}
    
	return $argomenti;
  }
  
    public static function getRelazioni($id)
  {
    $relazioni = array(); 
	 // testo unificato o assorbito
	$c = new Criteria();
	$c->add(OppRelazioneAttoPeer::ATTO_FROM_ID,$id);
	$c->add(OppRelazioneAttoPeer::TIPO_RELAZIONE_ID,array(1,2,4),Criteria::IN);
	$unificati = OppRelazioneAttoPeer::doSelect($c);     
	
	foreach ($unificati as $unificato)
    	{
	    array_push($relazioni,array($unificato->getAttoToId(),$unificato->getOppTipoRelazione()->getDenominazione(),$unificato->getDescrizione(),0));  
	}
	
	// stralcio FROM
        $c = new Criteria();
	$c->add(OppRelazioneAttoPeer::ATTO_FROM_ID,$id);
	$c->add(OppRelazioneAttoPeer::TIPO_RELAZIONE_ID,3);
	$from_stralci = OppRelazioneAttoPeer::doSelect($c);
	
	foreach ($from_stralci as $from_stralcio)
    	{
	    array_push($relazioni,array($from_stralcio->getAttoToId(),$from_stralcio->getOppTipoRelazione()->getDenominazione(),$from_stralcio->getDescrizione(),1));  
	}
	
	// stralcio TO
        $c = new Criteria();
	$c->add(OppRelazioneAttoPeer::ATTO_TO_ID,$id);
	$c->add(OppRelazioneAttoPeer::TIPO_RELAZIONE_ID,3);
	$to_stralci = OppRelazioneAttoPeer::doSelect($c);
	
	foreach ($to_stralci as $to_stralcio)
    	{
    	     $c = new Criteria();
             $c -> add(OppAttoPeer::ID,$to_stralcio->getAttoFromId());
             $rs = OppAttoPeer::doSelectOne($c);
             $sigla=$rs->getRamo().".".$rs->getNumfase();
	    array_push($relazioni,array($to_stralcio->getAttoFromId(),$to_stralcio->getOppTipoRelazione()->getDenominazione(),$sigla,2));  
	}
	
	// abbinato FROM
        $c = new Criteria();
	$c->add(OppRelazioneAttoPeer::ATTO_FROM_ID,$id);
	$c->add(OppRelazioneAttoPeer::TIPO_RELAZIONE_ID,5);
	$from_abbs = OppRelazioneAttoPeer::doSelect($c);
	
	foreach ($from_abbs as $from_abb)
    	{
	    array_push($relazioni,array($from_abb->getAttoToId(),$from_abb->getOppTipoRelazione()->getDenominazione(),$from_abb->getDescrizione(),0));  
	}
	
	
	return $relazioni;
  }
  
  public static function getRecordsetFirmatari($id, $tipo)
  {
    $c = new Criteria();
	  $c->clearSelectColumns();
	  $c->addSelectColumn(OppPoliticoPeer::ID);
	  $c->addSelectColumn(OppPoliticoPeer::NOME);
	  $c->addSelectColumn(OppPoliticoPeer::COGNOME);
	  $c->addSelectColumn(OppGruppoPeer::NOME);
	  $c->addSelectColumn(OppCaricaHasAttoPeer::DATA);
	  $c->addSelectColumn(OppGruppoPeer::ACRONIMO);
	  $c->addSelectColumn(OppCaricaPeer::TIPO_CARICA_ID);
	  $c->addSelectColumn(OppCaricaPeer::ID);
	  $c->add(OppCaricaHasAttoPeer::ATTO_ID, $id, Criteria::EQUAL);
	  $c->addJoin(OppCaricaHasAttoPeer::CARICA_ID, OppCaricaPeer::ID, Criteria::LEFT_JOIN);
	  $c->addJoin(OppCaricaPeer::POLITICO_ID, OppPoliticoPeer::ID, Criteria::LEFT_JOIN);
	  $c->addJoin(OppCaricaPeer::ID, OppCaricaHasGruppoPeer::CARICA_ID, Criteria::LEFT_JOIN);
	  $c->addJoin(OppCaricaHasGruppoPeer::GRUPPO_ID, OppGruppoPeer::ID, Criteria::LEFT_JOIN);
	  $c->add(OppCaricaHasGruppoPeer::DATA_FINE, NULL, Criteria::ISNULL);
	  $c->add(OppCaricaHasAttoPeer::TIPO, $tipo, Criteria::EQUAL);
	  $c->addAscendingOrderByColumn(OppPoliticoPeer::COGNOME);
	  $c->addAscendingOrderByColumn(OppCaricaHasGruppoPeer::DATA_FINE);
	  $rs = OppCaricaHasAttoPeer::doSelectRS($c);
	
	  return $rs;
  }
  
  public static function doSelectNews()
  {
    $news = array();
	
	// nuovi ddl
	$c = new Criteria();
	$c->clearSelectColumns();
	$c->addSelectColumn(OppAttoPeer::DATA_PRES);
	$c->addSelectColumn(OppAttoPeer::RAMO);
	$c->addSelectColumn(OppAttoPeer::NUMFASE);
	$c->addSelectColumn(OppAttoPeer::TITOLO);
	$c->addDescendingOrderByColumn(OppAttoPeer::DATA_PRES);
	$c->add(OppAttoPeer::TIPO_ATTO_ID, 1, Criteria::EQUAL);
	$c->setLimit(5);
	$rs = OppAttoPeer::doSelectRS($c);
	
	// cambi di status
	$c = new Criteria();
	$c->clearSelectColumns();
	$c->addSelectColumn(OppAttoHasIterPeer::DATA);
	$c->addSelectColumn(OppAttoPeer::RAMO);
	$c->addSelectColumn(OppAttoPeer::NUMFASE);
	$c->addSelectColumn(OppAttoPeer::TITOLO);
	$c->addSelectColumn(OppIterPeer::FASE);
	$c->addDescendingOrderByColumn(OppAttoPeer::DATA_PRES);
	$c->add(OppAttoPeer::TIPO_ATTO_ID, 1, Criteria::EQUAL);
	$c->addJoin(OppAttoHasIterPeer::ATTO_ID, OppAttoPeer::ID, Criteria::LEFT_JOIN);
	$c->addJoin(OppAttoHasIterPeer::ITER_ID, OppIterPeer::ID, Criteria::LEFT_JOIN);
	$c->setLimit(5);
	$rs = OppAttoPeer::doSelectRS($c);
	
	while ($rs->next())
    {
	  
	}  
  }
  
  public static function getAttiPerCommissioneLastIter($sede_id,$stato,$leg)
  {
  
    $c= new Criteria();
    $c->addJoin(OppAttoPeer::ID,OppAttoHasSedePeer::ATTO_ID);
    $c->add(OppAttoPeer::LEGISLATURA,$leg);
    if ($stato!='approvato definitivamente')
      $c->add(OppAttoPeer::STATO_FASE,$stato);
    else
      $c->add(OppAttoPeer::STATO_FASE,'%approvato definitivamente%',Criteria::LIKE);
    $c->add(OppAttoPeer::TIPO_ATTO_ID,1);
    $c->add(OppAttoHasSedePeer::SEDE_ID,$sede_id);
    $c->add(OppAttoHasSedePeer::TIPO,'Referente');
    $c->addDescendingOrderByColumn(OppAttoPeer::STATO_LAST_DATE);
    return OppAttoPeer::doSelect($c);
  }  
  
  
}
?>