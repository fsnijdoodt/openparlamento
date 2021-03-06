<?php
/*
 * This file is part of the deppPropelActAsVotableBehavior package.
 *
 * (c) 2008 Guglielmo Celata <guglielmo.celata@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
?>
<?php
/**
 * Symfony Propel Voting behavior plugin toolkit 
 * 
 * @package plugins
 * @subpackage voting
 * @author Guglielmo Celata
 */
class deppPropelActAsVotableBehaviorToolkit 
{

  /**
   * Retrieves the id of currently connected user, with sfGuardPlugin detection
   * 
   * @return mixed (int or null if no user id retrieved)
   */
  public static function getUserId()
  {
    $session = @sfContext::getInstance()->getUser();

    // if a custom user getId method was defined, use it
    if (is_callable(get_class($session), 'getId'))
    {
      return call_user_func(array($session, 'getId'));
    }
    
    // sfGuardPlugin detection and guard user id retrieval
    if (class_exists('sfGuardSecurityUser')
        && $session instanceof sfGuardSecurityUser
        && is_callable(array($session, 'getGuardUser')))
    {
      $guard_user = $session->getGuardUser();
      if (!is_null($guard_user))
      {
        $guard_user_id = $guard_user->getId();
        if (!is_null($guard_user_id))
        {
          return $guard_user_id;
        }
      }       
    }
    
    $getter = sfConfig::get('app_voting_user_id_getter');
    if (is_array($getter) && class_exists($getter[0]))
    {
      return call_user_func($getter);
    }
    elseif (is_string($getter) && function_exists($getter))
    {
      return $getter();
    }
    else
    {
      return null;
    }
  }
  
  /**
   * Add a token to available ones in the user session and return generated 
   * token
   * 
   * @author Nicolas Perriault
   * @param  string  $object_model
   * @param  int     $object_id
   * @return string
   */
  public static function addTokenToSession($object_model, $object_id)
  {
    $session = sfContext::getInstance()->getUser();
    $token = self::generateToken($object_model, $object_id);
    $tokens = $session->getAttribute('tokens', array(), 'sf_votables');
    $tokens = array($token => array($object_model, $object_id)) + $tokens;
    $tokens = array_slice($tokens, 0, sfConfig::get('app_voting_max_tokens', 150));
    $session->setAttribute('tokens', $tokens, 'sf_votables');
    return $token;
  }
  
  /**
   * Generates token representing a ratable object from its model and its id
   * 
   * @author Nicolas Perriault
   * @param  string  $object_model
   * @param  int     $object_id
   * @return string
   */
  public static function generateToken($object_model, $object_id)
  {
    return md5(sprintf('%s-%s-%s', $object_model, $object_id, sfConfig::get('app_voting_salt', 'v0t4bl3')));
  }
  
  /**
   * Returns true if the passed model name is votable
   * 
   * @author     Xavier Lacot
   * @param      string  $object_name
   * @return     boolean
   */
  public static function isVotable($model)
  {
    if (is_object($model))
    {
      $model = get_class($model);
    }

    if (!is_string($model))
    {
      throw new Exception('The param passed to the metod isVotable must be a string.');
    }

    if (!class_exists($model))
    {
      throw new Exception(sprintf('Unknown class %s', $model));
    }

    $base_class = sprintf('Base%s', $model);
    return !is_null(sfMixer::getCallable($base_class.':setVoting'));
  }

  /**
   * Retrieve a votable object
   * 
   * @param  string  $object_model
   * @param  int     $object_id
   */
  public static function retrieveVotableObject($object_model, $object_id)
  {
    try
    {
      $peer = sprintf('%sPeer', $object_model);

      if (!class_exists($peer))
      {
        throw new Exception(sprintf('Unable to load class %s', $peer));
      }

      $object = call_user_func(array($peer, 'retrieveByPk'), $object_id);

      if (is_null($object))
      {
        throw new Exception(sprintf('Unable to retrieve %s with primary key %s', $object_model, $object_id));
      }

      if (!deppPropelActAsVotableBehaviorToolkit::isVotable($object))
      {
        throw new Exception(sprintf('Class %s does not have the votable behavior', $object_model));
      }

      return $object;
    }
    catch (Exception $e)
    {
      return sfContext::getInstance()->getLogger()->log($e->getMessage());
    }
  }
  
  /**
   * Retrieve votable object instance from token
   * 
   * @author Nicolas Perriault
   * @param  string  $token
   * @return BaseObject
   */
  public static function retrieveFromToken($token)
  {
    $session = sfContext::getInstance()->getUser();
    $tokens = $session->getAttribute('tokens', array(), 'sf_votables');
    if (array_key_exists($token, $tokens) && is_array($tokens[$token]) && class_exists($tokens[$token][0]))
    {
      $object_model = $tokens[$token][0];
      $object_id    = $tokens[$token][1];
      return self::retrieveVotableObject($object_model, $object_id);
    } else return null;
  }

}
