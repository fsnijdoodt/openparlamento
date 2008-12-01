<?php
/*
 * This file is part of the deppPropelActAsBookmarkableBehavior package.
 *
 * (c) 2008 Guglielmo Celata <guglielmo.celata@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
?>
<?php
/**
 * This Propel behavior aims at allowing the user to bookmark any Propel
 * object in a positive or negative weay
 * 
 *
 * @package    plugins
 * @subpackage bookmarking 
 * @author     Guglielmo Celata <guglielmo.celata@gmail.com>
 * @author     Nicolar Perriault
 * @author     Fabian Lange
 * @author     Vojtech Rysanek
 */
class deppPropelActAsBookmarkableBehavior
{
  
  public function countPositiveBookmarkings(BaseObject $object)
  {
    return self::_countBookmarkings($object, '+');
  }

  public function countNegativeBookmarkings(BaseObject $object)
  {
    return self::_countBookmarkings($object, '-');
  }
  
  /**
   * Counts how many bookmarkings have been made on the given bookmarkable object.
   * 
   * @param  BaseObject  $object
   * @param  char(1)     +|- type of bookmarking
   * @return int
   */
  protected static function _countBookmarkings(BaseObject $object, $type)
  {
    if ($type != '+' && $type != '-')
      throw new deppPropelActAsBookmarkableException('Type can only be + or -');

    $c = new Criteria();
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_ID, $object->getReferenceKey());
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_MODEL, get_class($object));
    $c->add(sfBookmarkingPeer::BOOKMARKING, ($type == '+'?1:-1));
    return sfBookmarkingPeer::doCount($c);
  }


  /**
   * Retrieves an existing bookmarking object, or return a new empty one
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_id  Unique user primary key
   * @return sfBookmarking
   * @throws deppPropelActAsBookmarkableException
   **/
  protected static function getOrCreate(BaseObject $object, $user_id)
  {
    if ($object->isNew())
    {
      throw new deppPropelActAsBookmarkableException('Unsaved objects are not bookmarkable');
    }
    
    if (is_null($user_id))
    {
      throw new deppPropelActAsBookmarkableException('Anonymous bookmarking not allowed');
    }
    
    $c = new Criteria();
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_ID, $object->getReferenceKey());
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_MODEL, get_class($object));
    $c->add(sfBookmarkingPeer::USER_ID, $user_id);
    $user_bookmarking = sfBookmarkingPeer::doSelectOne($c);
    return is_null($user_bookmarking) ? new sfBookmarking() : $user_bookmarking;
  }

  /**
   * Clear all bookmarkings for an object
   *
   * @param  BaseObject  $object
   **/
  public function clearBookmarkings(BaseObject $object)
  {
    $c = new Criteria();
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_ID, $object->getReferenceKey());
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_MODEL, get_class($object));
    $ret = sfBookmarkingPeer::doDelete($c);
    return $ret;
  }

  /**
   * Clear user bookmarking for an object
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_id  User primary key
   * @param  char(1)     +|- type of bookmarking
   **/
  private static function _clearUserBookmarking(BaseObject $object, $type, $user_id)
  {
    if ($type != '+' && $type != '-')
      throw new deppPropelActAsBookmarkableException('Type can only be + or -');

    if (is_null($user_id) or trim((string)$user_id) === '')
      throw new deppPropelActAsBookmarkableException('Impossible to clear a user bookmarking with no user primary key provided');
    
    $c = new Criteria();
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_ID, $object->getReferenceKey());
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_MODEL, get_class($object));
    $c->add(sfBookmarkingPeer::USER_ID, $user_id);
    $c->add(sfBookmarkingPeer::BOOKMARKING, ($type == '+'?1:-1));
    $ret = sfBookmarkingPeer::doDelete($c);
    return $ret;
  }

  /**
   * Checks if an Object has been bookmarked
   *
   * @param  BaseObject  $object
   **/
  public function hasBeenBokmarked(BaseObject $object)
  {
    $c = new Criteria();
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_ID, $object->getReferenceKey());
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_MODEL, get_class($object));
    return sfBookmarkingPeer::doCount($c) > 0;
  }

  /**
   * Checks if an Object has been bookmarked by a user
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_id  Unique reference to a user
   **/
  public function hasBeenBokmarkedByUser(BaseObject $object, $user_id)
  {
    if (is_null($user_id) or trim((string)$user_id) === '')
    {
      throw new deppPropelActAsBookmarkableException(
        'Impossible to check a user bookmarking with no user primary key provided');
    }
    $c = new Criteria();
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_ID, $object->getReferenceKey());
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_MODEL, get_class($object));
    $c->add(sfBookmarkingPeer::USER_ID, $user_id);
    return (sfBookmarkingPeer::doCount($c) > 0);
  }
  

  /**
   * Retrieves reference_field phpName from configuration
   * 
   * @param  BaseObject  $object
   * @return mixed
   */
  protected static function getObjectReferenceField(BaseObject $object)
  {
    return sfConfig::get(
      sprintf('propel_behavior_deppPropelActAsBookmarkableBehavior_%s_reference_field', 
              get_class($object)));
  }
  
  /**
   * Retrieves reference key for current bookmarkable object (default returns 
   * primary key)
   * 
   * @param  BaseObject $object
   * @return int
   */
  public function getReferenceKey(BaseObject $object)
  {
    $reference_field = self::getObjectReferenceField($object);
    if (is_null($reference_field))
    {
      return $object->getPrimaryKey();
    }
    
    $getter = 'get'.$reference_field;
    if (method_exists($object, $getter))
    {
      $ret = $object->$getter();
      if (!is_int($ret))
      {
        throw new deppPropelActAsBookmarkableException(
          'A reference field must be typed as integer');
      }
      return $ret;
    }
  }

  /**
   * Retrieves the object bookmarking
   *
   * @param  BaseObject  $object
   * @param  int         $precision   Result float precision
   * @return float
   **/
  public function getBookmarking(BaseObject $object, $precision=2, $docount=false)
  {
    if ($docount === false && !is_null(self::getObjectBookmarkingField($object)))
    {
      return round(self::getBookmarkingToObject($object), self::getPrecision());
    }
    
    $c = new Criteria();
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_ID, $object->getReferenceKey());
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_MODEL, get_class($object));
    $c->clearSelectColumns();
    $c->addAsColumn('nb_bookmarkings', 'COUNT('.sfBookmarkingPeer::ID.')');
    $c->addAsColumn('total', 'SUM('.sfBookmarkingPeer::BOOKMARKING.')');
    $c->addGroupByColumn(sfBookmarkingPeer::BOOKMARKABLE_MODEL);
    $rs = sfBookmarkingPeer::doSelectRS($c);
    $rs->setFetchmode(ResultSet::FETCHMODE_ASSOC);
    while ($rs->next())
    {
      $nb_bookmarkings = $rs->getInt('nb_bookmarkings');
      $total      = $rs->getInt('total');
      if (!$nb_bookmarkings or $nb_bookmarkings === 0)
      {
        return NULL; // Object has not been bookmarked yet
      }
      return round($total / $nb_bookmarkings, self::getPrecision($precision));
    }
  }
  
  /**
   * Gets the object bookmarking details
   *
   * @param  BaseObject  $object
   * @param  boolean     $include_all  Shall we include all available bookmarkings?
   * @return associative array containing (bookmarking => count)
   **/
  public function getBookmarkingDetails(BaseObject $object, $include_all = false)
  {
    $c = new Criteria();
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_ID, $object->getReferenceKey());
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_MODEL, get_class($object));
    $c->clearSelectColumns();
    $c->addAsColumn('nb_bookmarkings', 'COUNT('.sfBookmarkingPeer::ID.')');
    $c->addAsColumn('bookmarking', sfBookmarkingPeer::BOOKMARKING);
    $c->addGroupByColumn(sfBookmarkingPeer::BOOKMARKING);
    $rs = sfBookmarkingPeer::doSelectRS($c);
    $rs->setFetchmode(ResultSet::FETCHMODE_ASSOC);
    $details = array();
    while ($rs->next())
    {
      $details = $details + array ($rs->getInt('bookmarking') => (int)$rs->getString('nb_bookmarkings'));
    }
    if ($include_all === true)
    {
      for ($i=-1*$object->getBookmarkingRange(); $i<=$object->getBookmarkingRange(); $i++)
      {
        if (!array_key_exists($i, $details))
        {
          $details[$i] = 0;
        }
      }
    }
    ksort($details);
    return $details;
  }
  
  /**
   * Gets the object bookmarking for given user pk
   *
   * @param  BaseObject  $object
   * @param  mixed       $user_id  User primary key
   * @return int or false
   **/
  public function getUserBookmarking(BaseObject $object, $user_id)
  {
    if (is_null($user_id) or trim((string)$user_id) === '')
    {
      throw new deppPropelActAsBookmarkableException(
        'Impossible to get a user bookmarking with no user primary key provided');
    }
    
    $c = new Criteria();
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_ID, $object->getReferenceKey());
    $c->add(sfBookmarkingPeer::BOOKMARKABLE_MODEL, get_class($object));
    $c->add(sfBookmarkingPeer::USER_ID, $user_id);
    $bookmarking_object = sfBookmarkingPeer::doSelectOne($c);
    if (!is_null($bookmarking_object))
    {
      return $bookmarking_object->getBookmarking();
    }
  }

  
  /**
   * Retrieves bookmarkable object instance from class name and key
   * 
   * @param  string  $class_name
   * @param  int     $key
   * @return BaseObject
   */
  public static function retrieveByKey($object_name, $key)
  {
    if (!class_exists($object_name))
    {
      throw new deppPropelActAsBookmarkableException('Class %s does not exist', 
                                              $object_name);
    }
    $object = new $object_name;
    $peer = $object->getPeer();
    $field = self::getObjectReferenceField($object);
    if (is_null($field))
    {
      return call_user_func(array($peer, 'retrieveByPK'), $key);
    }
    else
    {
      $column = call_user_func(array($peer, 'translateFieldName'),
                               self::getObjectReferenceField($object), 
                               BasePeer::TYPE_PHPNAME, 
                               BasePeer::TYPE_COLNAME);
      $c = new Criteria();
      $c->add($column, $key);
      return call_user_func(array($peer, 'doSelectOne'), $c);
    }
  }

  /**
   * bookmarks the Object
   *
   * @param  BaseObject  $object
   * @param  int         $bookmarking
   * @param  mixed       $user_id  Optional unique reference to user
   * @throws deppPropelActAsBookmarkableException
   **/
  public function setBookmarking(BaseObject $object, $bookmarking, $user_id = null)
  {

    if (is_float($bookmarking) && floor($bookmarking) != $bookmarking)
    {
      throw new deppPropelActAsBookmarkableException(
        sprintf('You cannot bookmark an object with a float (you provided "%s")', 
                $bookmarking));
    }
    $bookmarking = (int)$bookmarking;
    
    if ($bookmarking > $object->getBookmarkingRange() || $bookmarking < -1*$object->getBookmarkingRange())
    {
      throw new deppPropelActAsBookmarkableException(
        sprintf('Bookmarking range is -%d to %d', $object->getBookmarkingRange(), $object->getBookmarkingRange()));
    }

    // test anonymous bookmarking, rejecting if not allowed
    if (!$object->allowsAnonymousBookmarking() && $user_id === null)
    {
      throw new deppPropelActAsBookmarkableException('Anonymous bookmarking not allowed');
    }
    
    // test neutral position, rejecting if not allowed
    if (!$object->allowsNeutralPosition() && $bookmarking == 0)
    {
      throw new deppPropelActAsBookmarkableException('Neutral position not allowed');
    }
    
    $bookmarking_object = self::getOrCreate($object, $user_id);
    $bookmarking_object->setBookmarkableModel(get_class($object));
    $bookmarking_object->setBookmarkableId($object->getReferenceKey());
    $bookmarking_object->setUserId($user_id);
    $bookmarking_object->setBookmarking($bookmarking);
    $ret = $bookmarking_object->save();
    self::setBookmarkingToObject($object, $this->getBookmarking($object, self::getPrecision(), true));

    return $ret;
  }
  
  /**
   * Deletes all bookmarking for a bookmarkable object (delete cascade emulation)
   * 
   * @param  BaseObject  $object
   */
  public function preDelete(BaseObject $object)
  {
    try
    {
      $c = new Criteria();
      $c->add(sfBookmarkingPeer::BOOKMARKABLE_ID, $object->getReferenceKey());
      sfBookmarkingPeer::doDelete($c);
    }
    catch (Exception $e)
    {
      throw new deppPropelActAsBookmarkableException(
        'Unable to delete bookmarkable object related bookmarkings records');
    }
  }
  

}